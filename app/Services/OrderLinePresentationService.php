<?php

namespace App\Services;

class OrderLinePresentationService
{
    public function commercialName(?string $productName, iterable $optionNames = [], ?string $modalidad = null, bool $esComidaDia = false): string
    {
        $productName = trim((string) $productName);
        $resolvedModalidad = $this->resolveModalidad($productName, $optionNames, $modalidad, $esComidaDia);

        if ($esComidaDia || $this->isComida($productName)) {
            $selections = $this->extractComidaSelections($optionNames);
            $tercerTiempo = $selections['tercer_tiempo'] ?? null;

            if ($esComidaDia) {
                return 'Comida del dia';
            }

            if ($tercerTiempo && !empty($selections['modalidad']) && str_contains($this->normalizeKey($selections['modalidad']), 'platillo')) {
                return 'Comida + ' . $tercerTiempo;
            }

            return 'Comida del dia';
        }

        return match ($resolvedModalidad) {
            'desayuno' => $productName . ' / Paquete desayuno',
            'comida' => $productName . ' / Comida',
            default => $productName,
        };
    }

    public function clientDetailLines(?string $productName, iterable $optionNames = [], ?string $modalidad = null, bool $esComidaDia = false): array
    {
        $productName = trim((string) $productName);
        $resolvedModalidad = $this->resolveModalidad($productName, $optionNames, $modalidad, $esComidaDia);
        $entries = $this->extractOptionEntries($optionNames);

        if ($esComidaDia || $this->isComida($productName)) {
            return [];
        }

        if ($resolvedModalidad === 'desayuno') {
            $bebida = $this->findSelectionValue($entries, ['bebida_del_paquete', 'bebida']);
            $fruta = $this->findSelectionValue($entries, ['fruta_del_paquete', 'fruta']);
            $granola = $this->findSelectionValue($entries, ['granola']) ?: $this->findSelectionValue($entries, ['agregar_granola']);
            $salsa = $this->findSelectionValue($entries, ['salsa']);

            $lines = [];
            if ($salsa) {
                $lines[] = 'Salsa: ' . $salsa;
            }

            if ($bebida) {
                $lines[] = $bebida;
            }

            if ($fruta) {
                $lines[] = $granola ? $fruta . ' con granola' : $fruta;
            }

            return $lines;
        }

        if ($resolvedModalidad === 'comida') {
            return collect($entries)
                ->reject(fn (array $entry) => $entry['group_key'] === 'modalidad')
                ->map(function (array $entry) {
                    if ($entry['group_key'] === 'salsa') {
                        return 'Salsa: ' . $entry['value'];
                    }

                    return $entry['group_label'] !== '' ? ($entry['group_label'] . ': ' . $entry['value']) : $entry['value'];
                })
                ->filter()
                ->values()
                ->all();
        }

        return collect($entries)
            ->reject(fn (array $entry) => $entry['group_key'] === 'modalidad')
            ->map(fn (array $entry) => $entry['group_key'] === 'salsa' ? ('Salsa: ' . $entry['value']) : $entry['value'])
            ->filter()
            ->values()
            ->all();
    }

    public function extractComidaSelections(iterable $optionNames): array
    {
        $result = [];

        foreach ($this->extractOptionEntries($optionNames) as $entry) {
            if ($entry['group_key'] === '' || $entry['value'] === '') {
                continue;
            }

            $result[$entry['group_key']] = $entry['value'];
        }

        return $result;
    }

    public function extractOptionEntries(iterable $optionNames): array
    {
        $entries = [];

        foreach ($optionNames as $optionName) {
            $optionName = trim((string) $optionName);
            if ($optionName === '') {
                continue;
            }

            $groupLabel = '';
            $groupKey = '';
            $value = $optionName;

            if (str_contains($optionName, ':')) {
                [$prefix, $suffix] = array_pad(explode(':', $optionName, 2), 2, '');
                $groupLabel = trim($prefix);
                $groupKey = $this->normalizeKey($groupLabel);
                $value = trim($suffix);
            }

            $entries[] = [
                'raw' => $optionName,
                'group_label' => $groupLabel,
                'group_key' => $groupKey,
                'value' => $value,
            ];
        }

        return $entries;
    }

    public function optionLabel(string $value): string
    {
        $value = trim($value);

        if (str_contains($value, ':')) {
            [, $label] = array_pad(explode(':', $value, 2), 2, '');
            return trim($label);
        }

        return $value;
    }

    public function resolveModalidad(?string $productName, iterable $optionNames = [], ?string $modalidad = null, bool $esComidaDia = false): string
    {
        if ($esComidaDia || $this->isComida($productName)) {
            return 'comida';
        }

        $normalized = $this->normalizeKey((string) $modalidad);
        if (in_array($normalized, ['solo', 'desayuno', 'comida'], true)) {
            return $normalized;
        }

        foreach ($this->extractOptionEntries($optionNames) as $entry) {
            if ($entry['group_key'] !== 'modalidad') {
                continue;
            }

            $value = $this->normalizeKey($entry['value']);
            if (str_contains($value, 'desayuno')) {
                return 'desayuno';
            }

            if (str_contains($value, 'comida')) {
                return 'comida';
            }
        }

        return 'solo';
    }

    public function isComida(?string $productName): bool
    {
        return $this->normalizeKey((string) $productName) === 'comida';
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

    private function normalizeKey(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtolower(trim($ascii));
        $ascii = preg_replace('/\s+/', '_', $ascii) ?? $ascii;

        return preg_replace('/[^a-z0-9_\+]/', '', $ascii) ?? $ascii;
    }
}
