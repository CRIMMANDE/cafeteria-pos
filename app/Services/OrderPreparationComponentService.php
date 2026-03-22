<?php

namespace App\Services;

use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\OrdenDetalleComponente;
use App\Models\OrdenDetalleExtra;
use App\Models\OrdenDetalleOpcion;
use Illuminate\Support\Collection;

class OrderPreparationComponentService
{
    public function __construct(
        private readonly OrderLinePresentationService $linePresentationService,
    ) {
    }

    public function ensureComponentsForOrder(Orden $orden): void
    {
        $orden->loadMissing([
            'detalles.producto.categoria',
            'detalles.producto.componentesPreparacion',
            'detalles.opciones.opcion.grupoOpcion',
            'detalles.extras.extra',
            'detalles.componentes',
        ]);

        foreach ($orden->detalles as $detalle) {
            if ($detalle->componentes->isNotEmpty()) {
                continue;
            }

            $this->regenerateForDetail($detalle, (bool) $detalle->impreso);
        }
    }

    public function regenerateForDetail(OrdenDetalle $detalle, ?bool $printed = null): Collection
    {
        $detalle->loadMissing([
            'producto.categoria',
            'producto.componentesPreparacion',
            'opciones.opcion.grupoOpcion',
            'extras.extra',
            'componentes',
        ]);

        $detalle->componentes()->delete();

        $rows = collect($this->buildRows($detalle))->filter(
            fn (array $row) => in_array($row['area'], ['cocina', 'barra'], true)
                && trim((string) $row['descripcion']) !== ''
                && (int) $row['cantidad'] > 0
        );

        $created = $rows->map(function (array $row) use ($detalle, $printed) {
            return $detalle->componentes()->create([
                'area' => $row['area'],
                'descripcion' => $row['descripcion'],
                'cantidad' => (int) $row['cantidad'],
                'impreso' => $printed ?? false,
            ]);
        });

        $detalle->setRelation('componentes', $created);

        return $created;
    }

    public function groupedComponentsForOrder(Orden $orden, string $area): Collection
    {
        $this->ensureComponentsForOrder($orden);

        return OrdenDetalleComponente::query()
            ->selectRaw('orden_detalle_componentes.descripcion as descripcion, SUM(orden_detalle_componentes.cantidad) as cantidad')
            ->join('orden_detalles', 'orden_detalles.id', '=', 'orden_detalle_componentes.orden_detalle_id')
            ->where('orden_detalles.orden_id', $orden->id)
            ->where('orden_detalle_componentes.area', $area)
            ->groupBy('orden_detalle_componentes.descripcion')
            ->orderBy('orden_detalle_componentes.descripcion')
            ->get()
            ->map(fn (OrdenDetalleComponente $component) => [
                'descripcion' => $component->descripcion,
                'cantidad' => (int) $component->cantidad,
            ]);
    }

    private function buildRows(OrdenDetalle $detalle): array
    {
        $productName = $this->normalized($detalle->producto?->nombre);
        $isComidaDia = (bool) ($detalle->producto?->es_comida_dia) || $this->linePresentationService->isComida($detalle->producto?->nombre);
        $modalidad = $this->linePresentationService->resolveModalidad(
            $detalle->producto?->nombre,
            $detalle->opciones->pluck('nombre')->all(),
            $detalle->modalidad,
            $isComidaDia
        );

        $configuredRows = $this->buildCatalogConfiguredRows($detalle, $modalidad);
        if ($configuredRows !== []) {
            return $this->appendExtrasAndNoteRows($configuredRows, $detalle, $this->defaultAreaFromRows($configuredRows));
        }

        if ($isComidaDia) {
            $rows = $this->buildComidaDiaRows($detalle);
            return $this->appendExtrasAndNoteRows($rows, $detalle, 'cocina');
        }

        if ($modalidad === 'desayuno') {
            $rows = $this->buildConfiguredBreakfastRows($detalle);
            return $this->appendExtrasAndNoteRows($rows, $detalle, 'cocina');
        }

        if ($modalidad === 'comida') {
            $rows = $this->buildConfiguredMealRows($detalle);
            return $this->appendExtrasAndNoteRows($rows, $detalle, 'cocina');
        }

        if ($this->matchesAny($productName, config('preparation_components.cappuccino_aliases', []))) {
            $rows = $this->buildCappuccinoRows($detalle);
            return $this->appendExtrasAndNoteRows($rows, $detalle, 'barra');
        }

        if ($this->matchesAny($productName, config('preparation_components.breakfast_package_aliases', []))) {
            $rows = $this->buildBreakfastPackageRows($detalle);
            return $this->appendExtrasAndNoteRows($rows, $detalle, 'cocina');
        }

        $defaultArea = $detalle->producto?->categoria?->tipo;
        if (!in_array($defaultArea, ['cocina', 'barra'], true)) {
            return [];
        }

        $rows = [[
            'area' => $defaultArea,
            'descripcion' => $this->buildGenericDescription($detalle),
            'cantidad' => (int) $detalle->cantidad,
        ]];

        return $this->appendExtrasAndNoteRows($rows, $detalle, $defaultArea);
    }

    private function buildCatalogConfiguredRows(OrdenDetalle $detalle, string $modalidad): array
    {
        $componentes = $detalle->producto?->componentesPreparacion
            ?->where('activo', true)
            ->filter(function ($component) use ($modalidad) {
                $scope = $this->normalized((string) ($component->modalidad ?? 'todas'));

                return $scope === '' || $scope === 'todas' || $scope === $modalidad;
            })
            ->sortBy([
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        if (!$componentes || $componentes->isEmpty()) {
            return [];
        }

        return $componentes->map(function ($component) use ($detalle) {
            return [
                'area' => $component->area,
                'descripcion' => strtoupper((string) $component->nombre_componente),
                'cantidad' => max(1, (int) round(((float) $component->cantidad) * max(1, (int) $detalle->cantidad))),
            ];
        })->all();
    }

    private function defaultAreaFromRows(array $rows): string
    {
        foreach ($rows as $row) {
            $area = $row['area'] ?? null;
            if (in_array($area, ['cocina', 'barra'], true)) {
                return $area;
            }
        }

        return 'cocina';
    }

    private function buildComidaDiaRows(OrdenDetalle $detalle): array
    {
        $entries = $this->linePresentationService->extractOptionEntries($detalle->opciones->pluck('nombre')->all());
        $rows = [[
            'area' => 'cocina',
            'descripcion' => 'COMIDA',
            'cantidad' => (int) $detalle->cantidad,
        ]];

        foreach ($entries as $entry) {
            if ($entry['group_key'] === 'modalidad' || $entry['value'] === '') {
                continue;
            }

            $rows[] = [
                'area' => 'cocina',
                'descripcion' => $this->formatMealSelectionForKitchen($entry['group_key'], $entry['group_label'], $entry['value']),
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        return $rows;
    }

    private function buildConfiguredBreakfastRows(OrdenDetalle $detalle): array
    {
        $entries = $this->linePresentationService->extractOptionEntries($detalle->opciones->pluck('nombre')->all());
        $rows = [[
            'area' => 'cocina',
            'descripcion' => strtoupper($this->normalized($detalle->producto?->nombre)),
            'cantidad' => (int) $detalle->cantidad,
        ]];

        $bebida = $this->findSelectionValue($entries, ['bebida_del_paquete', 'bebida']);
        $fruta = $this->findSelectionValue($entries, ['fruta_del_paquete', 'fruta']);
        $granola = $this->findSelectionValue($entries, ['granola', 'agregar_granola']);

        if ($bebida) {
            $rows[] = [
                'area' => 'barra',
                'descripcion' => strtoupper($bebida),
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        if ($fruta) {
            $descripcionFruta = strtoupper($granola ? ($fruta . ' con granola') : $fruta);
            $rows[] = [
                'area' => 'barra',
                'descripcion' => $descripcionFruta,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        return $rows;
    }

    private function buildConfiguredMealRows(OrdenDetalle $detalle): array
    {
        $entries = $this->linePresentationService->extractOptionEntries($detalle->opciones->pluck('nombre')->all());
        $rows = [[
            'area' => 'cocina',
            'descripcion' => strtoupper($this->normalized($detalle->producto?->nombre)),
            'cantidad' => (int) $detalle->cantidad,
        ]];

        foreach ($entries as $entry) {
            if ($entry['group_key'] === 'modalidad' || $entry['value'] === '') {
                continue;
            }

            $rows[] = [
                'area' => 'cocina',
                'descripcion' => $this->formatMealSelectionForKitchen($entry['group_key'], $entry['group_label'], $entry['value']),
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        return $rows;
    }

    private function buildCappuccinoRows(OrdenDetalle $detalle): array
    {
        $description = ['CAPUCCINO'];
        $flavors = [];
        $modifiers = [];

        foreach ($detalle->opciones as $opcion) {
            $name = $this->normalized($opcion->nombre);
            $group = $this->normalized($opcion->opcion?->grupoOpcion?->nombre);
            $code = trim(strtoupper((string) ($opcion->opcion?->codigo_corto ?? '')));

            if ($code !== '') {
                $description[] = $code;
                continue;
            }

            $milkCode = $this->resolveMappedCode($name, config('preparation_components.milk_codes', []))
                ?? $this->resolveMappedCode($group . ' ' . $name, config('preparation_components.milk_codes', []));

            if ($milkCode) {
                $description[] = $milkCode;
                continue;
            }

            if ($this->containsAny($group . ' ' . $name, config('preparation_components.flavor_keywords', []))) {
                $flavors[] = strtoupper($this->linePresentationService->optionLabel($opcion->nombre));
                continue;
            }

            $modifiers[] = strtoupper($this->linePresentationService->optionLabel($opcion->nombre));
        }

        if ($flavors !== []) {
            $description[] = implode(' ', array_values(array_unique($flavors)));
        }

        if ($modifiers !== []) {
            $description[] = implode(' ', array_values(array_unique($modifiers)));
        }

        return [[
            'area' => 'barra',
            'descripcion' => trim(implode(' ', array_values(array_unique(array_filter($description))))),
            'cantidad' => (int) $detalle->cantidad,
        ]];
    }

    private function buildBreakfastPackageRows(OrdenDetalle $detalle): array
    {
        $rows = [[
            'area' => 'cocina',
            'descripcion' => $this->cleanupPackageKitchenDescription($detalle->producto?->nombre),
            'cantidad' => (int) $detalle->cantidad,
        ]];

        $drinkComponents = $this->extractNamedComponents(
            $detalle,
            config('preparation_components.drink_keywords', []),
            'CAFE'
        );

        foreach ($drinkComponents as $description) {
            $rows[] = [
                'area' => 'barra',
                'descripcion' => $description,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        $fruitComponents = $this->extractNamedComponents(
            $detalle,
            config('preparation_components.fruit_keywords', []),
            'FRUTA MIXTA CON GRANOLA'
        );

        foreach ($fruitComponents as $description) {
            $rows[] = [
                'area' => 'barra',
                'descripcion' => $description,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        return $rows;
    }

    private function buildGenericDescription(OrdenDetalle $detalle): string
    {
        $parts = [strtoupper($this->linePresentationService->commercialName(
            $detalle->producto?->nombre,
            $detalle->opciones->pluck('nombre')->all(),
            $detalle->modalidad,
            (bool) ($detalle->producto?->es_comida_dia)
        ))];

        $optionParts = $detalle->opciones
            ->map(fn (OrdenDetalleOpcion $opcion) => strtoupper($this->linePresentationService->optionLabel($opcion->nombre)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($optionParts !== []) {
            $parts[] = implode(' ', $optionParts);
        }

        return trim(implode(' ', array_filter($parts)));
    }

    private function appendExtrasAndNoteRows(array $rows, OrdenDetalle $detalle, string $defaultArea): array
    {
        $area = in_array($defaultArea, ['cocina', 'barra'], true) ? $defaultArea : 'cocina';
        $salsa = strtoupper($this->findSelectionValue(
            $this->linePresentationService->extractOptionEntries($detalle->opciones->pluck('nombre')->all()),
            ['salsa']
        ) ?? '');

        if ($salsa !== '' && !$this->hasSalsaRow($rows)) {
            $rows[] = [
                'area' => 'cocina',
                'descripcion' => 'SALSA: ' . $salsa,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        foreach ($detalle->extras as $extra) {
            $name = strtoupper($this->normalized($this->extraName($extra)));
            if ($name === '') {
                continue;
            }

            $extraQty = max(1, (int) ($extra->cantidad ?? 1));

            $rows[] = [
                'area' => $area,
                'descripcion' => '+ ' . $name . ' x' . $extraQty,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        $note = strtoupper($this->normalized($detalle->nota));
        if ($note !== '') {
            $rows[] = [
                'area' => $area,
                'descripcion' => 'OBS: ' . $note,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        return $rows;
    }

    private function hasSalsaRow(array $rows): bool
    {
        foreach ($rows as $row) {
            $description = strtoupper(trim((string) ($row['descripcion'] ?? '')));
            if (str_starts_with($description, 'SALSA:')) {
                return true;
            }
        }

        return false;
    }

    private function formatMealSelectionForKitchen(string $groupKey, string $groupLabel, string $value): string
    {
        $value = strtoupper($value);

        return match ($groupKey) {
            'primer_tiempo' => '1ER TIEMPO: ' . $value,
            'segundo_tiempo' => '2DO TIEMPO: ' . $value,
            'tercer_tiempo', 'plato_principal_del_dia', 'platillo_principal' => '3ER TIEMPO: ' . $value,
            default => strtoupper(($groupLabel !== '' ? $groupLabel . ': ' : '') . $value),
        };
    }

    private function findSelectionValue(array $entries, array $keys): ?string
    {
        foreach ($entries as $entry) {
            if (in_array($entry['group_key'], $keys, true) && $entry['value'] !== '') {
                return $entry['value'];
            }
        }

        return null;
    }

    private function cleanupPackageKitchenDescription(?string $productName): string
    {
        $description = strtoupper($this->normalized($productName));
        $description = preg_replace('/^PAQUETE\s+/', '', $description ?? '') ?? '';

        return trim($description);
    }

    private function extractNamedComponents(OrdenDetalle $detalle, array $map, string $default): array
    {
        $found = [];

        foreach ($detalle->opciones as $opcion) {
            $match = $this->resolveMappedCode(
                $this->normalized($opcion->opcion?->grupoOpcion?->nombre) . ' ' . $this->normalized($opcion->nombre),
                $map
            ) ?? $this->resolveMappedCode($this->normalized($opcion->nombre), $map);

            if ($match) {
                $found[] = $match;
            }
        }

        foreach ($detalle->extras as $extra) {
            $match = $this->resolveMappedCode($this->normalized($this->extraName($extra)), $map);
            if ($match) {
                $found[] = $match;
            }
        }

        $found = array_values(array_unique(array_filter($found)));

        return $found === [] ? [$default] : $found;
    }

    private function extraName(OrdenDetalleExtra $extra): string
    {
        return (string) ($extra->nombre_personalizado ?: $extra->extra?->nombre ?: '');
    }

    private function normalized(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtolower($ascii);

        return preg_replace('/\s+/', ' ', $ascii) ?? $ascii;
    }

    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $this->normalized($needle))) {
                return true;
            }
        }

        return false;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        return $this->matchesAny($this->normalized($haystack), $needles);
    }

    private function resolveMappedCode(string $value, array $map): ?string
    {
        $normalized = $this->normalized($value);

        foreach ($map as $key => $code) {
            if ($key !== '' && str_contains($normalized, $this->normalized((string) $key))) {
                return strtoupper((string) $code);
            }
        }

        return null;
    }
}






