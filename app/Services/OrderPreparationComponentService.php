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


        if ((bool) $detalle->es_otro_manual) {
            $rows = $this->buildManualOtherRows($detalle);
            return $this->appendExtrasAndNoteRows($rows, $detalle, $rows[0]['area'] ?? 'cocina');
        }

        $configuredRows = $this->buildCatalogConfiguredRows($detalle, $modalidad);
        if ($configuredRows !== []) {
            $rows = $configuredRows;

            if ($modalidad === 'desayuno') {
                $rows = $this->appendBreakfastSelectionRows($rows, $detalle);
            }

            if ($isComidaDia) {
                $rows = $this->applyPrimaryRowDescription($rows, $this->resolveComidaHeader($detalle));
            }

            if ($isComidaDia || $modalidad === 'comida') {
                $rows = $this->appendComidaSelectionRows($rows, $detalle);
            }

            if ($this->matchesAny($productName, config('preparation_components.cappuccino_aliases', []))) {
                $rows = $this->appendSimplifiedOptionRows($rows, $detalle, 'barra');
            }

            return $this->appendExtrasAndNoteRows($rows, $detalle, $this->defaultAreaFromRows($rows));
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
        $rows = [[
            'area' => 'cocina',
            'descripcion' => $this->resolveComidaHeader($detalle),
            'cantidad' => (int) $detalle->cantidad,
        ]];

        return $this->appendComidaSelectionRows($rows, $detalle);
    }

    private function buildManualOtherRows(OrdenDetalle $detalle): array
    {
        $area = in_array($detalle->area_preparacion, ['cocina', 'barra'], true)
            ? $detalle->area_preparacion
            : 'cocina';
        $descripcion = strtoupper(trim((string) ($detalle->nombre_personalizado ?: $detalle->producto?->nombre ?: 'OTRO')));

        return [[
            'area' => $area,
            'descripcion' => $descripcion,
            'cantidad' => (int) $detalle->cantidad,
        ]];
    }

    private function buildConfiguredBreakfastRows(OrdenDetalle $detalle): array
    {
        $rows = [[
            'area' => 'cocina',
            'descripcion' => strtoupper($this->normalized($detalle->producto?->nombre)),
            'cantidad' => (int) $detalle->cantidad,
        ]];

        return $this->appendBreakfastSelectionRows($rows, $detalle);
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
        $rows = [[
            'area' => 'barra',
            'descripcion' => 'CAPUCCINO',
            'cantidad' => (int) $detalle->cantidad,
        ]];

        $detailLines = $this->linePresentationService->clientDetailLines(
            $detalle->producto?->nombre,
            $detalle->opciones->pluck('nombre')->all(),
            $detalle->modalidad,
            (bool) ($detalle->producto?->es_comida_dia)
        );

        foreach ($detailLines as $detailLine) {
            $label = strtoupper($this->normalized((string) $detailLine));
            if ($label === '' || str_starts_with($label, 'SALSA:')) {
                continue;
            }

            $rows[] = [
                'area' => 'barra',
                'descripcion' => '- ' . $label,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        return $rows;
    }

    private function appendSimplifiedOptionRows(array $rows, OrdenDetalle $detalle, string $area): array
    {
        $detailLines = $this->linePresentationService->clientDetailLines(
            $detalle->producto?->nombre,
            $detalle->opciones->pluck('nombre')->all(),
            $detalle->modalidad,
            (bool) ($detalle->producto?->es_comida_dia)
        );

        $labels = collect($detailLines)
            ->map(fn ($line) => strtoupper($this->normalized((string) $line)))
            ->reject(fn (string $label) => $label === '' || str_starts_with($label, 'SALSA:'))
            ->unique()
            ->values();

        foreach ($labels as $label) {
            $rows[] = [
                'area' => $area,
                'descripcion' => '- ' . $label,
                'cantidad' => (int) $detalle->cantidad,
            ];
        }

        return $rows;
    }

    private function resolveComidaHeader(OrdenDetalle $detalle): string
    {
        $entries = $this->linePresentationService->extractOptionEntries($detalle->opciones->pluck('nombre')->all());
        $modalidad = (string) ($this->findSelectionValue($entries, ['modalidad']) ?? '');
        $productKey = $this->normalized((string) ($detalle->producto?->sku ?: $detalle->producto?->nombre));

        return str_contains($this->normalized($modalidad), 'platillo') || str_contains($productKey, 'platillo')
            ? 'PLATILLO DEL DIA'
            : 'COMIDA DEL DIA';
    }

    private function applyPrimaryRowDescription(array $rows, string $description): array
    {
        if ($rows === []) {
            return $rows;
        }

        $rows[0]['descripcion'] = $description;

        return $rows;
    }

    private function appendComidaSelectionRows(array $rows, OrdenDetalle $detalle): array
    {
        return $this->appendUniqueRows($rows, $this->comidaSelectionRows($detalle));
    }

    private function comidaSelectionRows(OrdenDetalle $detalle): array
    {
        $entries = $this->linePresentationService->extractOptionEntries($detalle->opciones->pluck('nombre')->all());
        $qty = (int) $detalle->cantidad;
        $rows = [];

        foreach ($entries as $entry) {
            if (($entry['group_key'] ?? '') === 'modalidad' || ($entry['value'] ?? '') === '') {
                continue;
            }

            $rows[] = [
                'area' => 'cocina',
                'descripcion' => $this->formatMealSelectionForKitchen(
                    (string) ($entry['group_key'] ?? ''),
                    (string) ($entry['group_label'] ?? ''),
                    (string) ($entry['value'] ?? '')
                ),
                'cantidad' => $qty,
            ];
        }

        return $rows;
    }

    private function appendBreakfastSelectionRows(array $rows, OrdenDetalle $detalle): array
    {
        return $this->appendUniqueRows($rows, $this->breakfastSelectionRows($detalle));
    }

    private function breakfastSelectionRows(OrdenDetalle $detalle): array
    {
        $entries = $this->linePresentationService->extractOptionEntries($detalle->opciones->pluck('nombre')->all());
        $qty = (int) $detalle->cantidad;
        $rows = [];

        $proteina = $this->findSelectionValue($entries, ['proteina', 'proteina_huevo'])
            ?? $this->findSelectionValueByContainsKey($entries, 'proteina');

        if ($proteina) {
            $rows[] = [
                'area' => 'cocina',
                'descripcion' => strtoupper($proteina),
                'cantidad' => $qty,
            ];
        }

        $bebida = $this->findSelectionValue($entries, ['bebida_del_paquete', 'bebida']);
        $saborTe = $this->findSelectionValue($entries, ['sabor_te'])
            ?? $this->findSelectionValueByContainsKey($entries, 'sabor_te');

        if ($bebida) {
            $descripcionBebida = strtoupper($bebida);
            if ($this->normalized($bebida) === 'te' && $saborTe) {
                $descripcionBebida = 'TE ' . strtoupper($saborTe);
            }

            if (!$this->isBreakfastPackageCoffeeSelection($bebida)) {
                $rows[] = [
                    'area' => 'barra',
                    'descripcion' => $descripcionBebida,
                    'cantidad' => $qty,
                ];
            }
        }

        $fruta = $this->findSelectionValue($entries, ['fruta_del_paquete', 'fruta']);
        $granola = $this->findSelectionValue($entries, ['granola', 'agregar_granola']);

        if ($fruta) {
            $descripcionFruta = strtoupper($granola ? ($fruta . ' con granola') : $fruta);
            $rows[] = [
                'area' => 'barra',
                'descripcion' => $descripcionFruta,
                'cantidad' => $qty,
            ];
        }

        return $rows;
    }

    private function findSelectionValueByContainsKey(array $entries, string $needle): ?string
    {
        $needle = $this->normalized($needle);

        foreach ($entries as $entry) {
            $groupKey = $this->normalized((string) ($entry['group_key'] ?? ''));
            $value = trim((string) ($entry['value'] ?? ''));

            if ($groupKey !== '' && str_contains($groupKey, $needle) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function appendUniqueRows(array $rows, array $candidates): array
    {
        foreach ($candidates as $candidate) {
            $area = (string) ($candidate['area'] ?? '');
            $description = trim((string) ($candidate['descripcion'] ?? ''));

            if ($area === '' || $description === '') {
                continue;
            }

            if ($this->hasRowDescription($rows, $area, $description)) {
                continue;
            }

            $rows[] = [
                'area' => $area,
                'descripcion' => $description,
                'cantidad' => (int) ($candidate['cantidad'] ?? 1),
            ];
        }

        return $rows;
    }

    private function hasRowDescription(array $rows, string $area, string $description): bool
    {
        $normalizedArea = $this->normalized($area);
        $normalizedDescription = $this->normalized($description);

        foreach ($rows as $row) {
            if ($this->normalized((string) ($row['area'] ?? '')) !== $normalizedArea) {
                continue;
            }

            if ($this->normalized((string) ($row['descripcion'] ?? '')) === $normalizedDescription) {
                return true;
            }
        }

        return false;
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
            if ($this->isBreakfastPackageCoffeeSelection($description)) {
                continue;
            }

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

    private function isBreakfastPackageCoffeeSelection(?string $value): bool
    {
        $normalized = $this->normalized((string) $value);

        return $normalized === 'cafe'
            || $normalized === 'cafe americano'
            || str_contains($normalized, 'cafe americano')
            || $normalized === 'americano';
    }
}







