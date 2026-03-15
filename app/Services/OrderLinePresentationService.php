<?php

namespace App\Services;

class OrderLinePresentationService
{
    public function commercialName(?string $productName, iterable $optionNames = []): string
    {
        $productName = trim((string) $productName);

        if ($this->isComida($productName)) {
            $selections = $this->extractComidaSelections($optionNames);
            $modalidad = strtolower($selections['modalidad'] ?? '');
            $tercerTiempo = $selections['tercer_tiempo'] ?? null;

            if (str_contains($modalidad, 'platillo') && $tercerTiempo) {
                return 'Comida + ' . $tercerTiempo;
            }

            return 'Comida del dia';
        }

        return $productName;
    }

    public function extractComidaSelections(iterable $optionNames): array
    {
        $result = [];

        foreach ($optionNames as $optionName) {
            $optionName = trim((string) $optionName);
            if ($optionName === '' || !str_contains($optionName, ':')) {
                continue;
            }

            [$prefix, $value] = array_pad(explode(':', $optionName, 2), 2, '');
            $key = $this->normalizeKey($prefix);
            $value = trim($value);

            if ($key !== '' && $value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
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

    public function isComida(?string $productName): bool
    {
        return $this->normalizeKey((string) $productName) === 'comida';
    }

    private function normalizeKey(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtolower(trim($ascii));
        $ascii = preg_replace('/\s+/', '_', $ascii) ?? $ascii;

        return preg_replace('/[^a-z0-9_\+]/', '', $ascii) ?? $ascii;
    }
}
