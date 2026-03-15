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
    public function ensureComponentsForOrder(Orden $orden): void
    {
        $orden->loadMissing([
            'detalles.producto.categoria',
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
            ->selectRaw('descripcion, SUM(cantidad) as cantidad')
            ->join('orden_detalles', 'orden_detalles.id', '=', 'orden_detalle_componentes.orden_detalle_id')
            ->where('orden_detalles.orden_id', $orden->id)
            ->where('orden_detalle_componentes.area', $area)
            ->groupBy('descripcion')
            ->orderBy('descripcion')
            ->get()
            ->map(fn (OrdenDetalleComponente $component) => [
                'descripcion' => $component->descripcion,
                'cantidad' => (int) $component->cantidad,
            ]);
    }

    private function buildRows(OrdenDetalle $detalle): array
    {
        $productName = $this->normalized($detalle->producto?->nombre);

        if ($this->matchesAny($productName, config('preparation_components.cappuccino_aliases', []))) {
            return $this->buildCappuccinoRows($detalle);
        }

        if ($this->matchesAny($productName, config('preparation_components.breakfast_package_aliases', []))) {
            return $this->buildBreakfastPackageRows($detalle);
        }

        $defaultArea = $detalle->producto?->categoria?->tipo;
        if (!in_array($defaultArea, ['cocina', 'barra'], true)) {
            return [];
        }

        return [[
            'area' => $defaultArea,
            'descripcion' => $this->buildGenericDescription($detalle),
            'cantidad' => (int) $detalle->cantidad,
        ]];
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
                $flavors[] = strtoupper($name);
                continue;
            }

            $modifiers[] = strtoupper($name);
        }

        foreach ($detalle->extras as $extra) {
            $name = $this->normalized($this->extraName($extra));
            if ($name === '') {
                continue;
            }

            $milkCode = $this->resolveMappedCode($name, config('preparation_components.milk_codes', []));
            if ($milkCode) {
                $description[] = $milkCode;
                continue;
            }

            $modifiers[] = strtoupper($name);
        }

        if ($flavors !== []) {
            $description[] = implode(' ', array_values(array_unique($flavors)));
        }

        $note = $this->normalized($detalle->nota);
        if ($note !== '') {
            $modifiers[] = strtoupper($note);
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
            'descripcion' => $this->cleanupPackageKitchenDescription($detalle->producto?->nombre, $detalle->nota),
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
        $parts = [strtoupper($this->normalized($detalle->producto?->nombre))];

        $optionParts = $detalle->opciones
            ->map(fn (OrdenDetalleOpcion $opcion) => strtoupper($this->normalized($opcion->nombre)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($optionParts !== []) {
            $parts[] = implode(' ', $optionParts);
        }

        $extraParts = $detalle->extras
            ->map(fn (OrdenDetalleExtra $extra) => strtoupper($this->normalized($this->extraName($extra))))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($extraParts !== []) {
            $parts[] = implode(' ', $extraParts);
        }

        $note = strtoupper($this->normalized($detalle->nota));
        if ($note !== '') {
            $parts[] = $note;
        }

        return trim(implode(' ', array_filter($parts)));
    }

    private function cleanupPackageKitchenDescription(?string $productName, ?string $note): string
    {
        $description = strtoupper($this->normalized($productName));
        $description = preg_replace('/^PAQUETE\s+/', '', $description ?? '') ?? '';

        $note = strtoupper($this->normalized($note));
        if ($note !== '') {
            $description = trim($description . ' ' . $note);
        }

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

