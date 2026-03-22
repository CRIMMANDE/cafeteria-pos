<?php

namespace App\Services\ThermalPrinter;

use App\Models\Mesa;
use App\Models\Orden;
use App\Services\OrderLinePresentationService;

class TicketFormatter
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function buildOrderTicket(Orden $orden): string
    {
        $builder = (new EscPosBuilder())->initialize();
        $lineWidth = max(32, (int) ($this->config['characters_per_line'] ?? 48));
        $separator = str_repeat('-', $lineWidth);
        $orderType = $orden->tipo ?: (Mesa::isEmployee((int) $orden->mesa_id) ? 'empleados' : (Mesa::isTakeaway((int) $orden->mesa_id) ? 'llevar' : 'mesa'));
        $typeLabel = match ($orderType) {
            'empleados' => 'Empleados',
            'llevar' => 'Para llevar',
            default => 'Mesa',
        };
        $mesaLabel = $orderType === 'mesa' ? (string) $orden->mesa_id : '-';
        $presentation = new OrderLinePresentationService();
        $logoPath = $this->resolveLogoPath();

        $builder->alignCenter();

        if ($logoPath !== null) {
            $builder->rasterImageFromPng($logoPath, (int) ($this->config['store_logo_max_width_dots'] ?? 380));
        }

        if (!empty($this->config['store_address'])) {
            $builder->line($this->sanitize($this->config['store_address']));
        }

        if (!empty($this->config['store_phone'])) {
            $builder->line('Tel. ' . $this->sanitize($this->config['store_phone']));
        }

        $builder
            ->alignLeft()
            ->line($separator)
            ->line($this->keyValue('Folio:', (string) $orden->id, $lineWidth))
            ->line($this->keyValue('Fecha:', $orden->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'), $lineWidth))
            ->line($this->keyValue('Tipo:', $typeLabel, $lineWidth))
            ->line($this->keyValue('Mesa:', $mesaLabel, $lineWidth))
            ->line($separator);

        foreach ($orden->detalles as $detalle) {
            if (!$detalle->producto) {
                continue;
            }

            $subtotal = (float) $detalle->precio * (int) $detalle->cantidad;
            $prefix = (int) $detalle->cantidad . ' ';
            $price = number_format($subtotal, 2);
            $name = $presentation->commercialName(
                $detalle->producto->nombre,
                $detalle->opciones->pluck('nombre')->all(),
                $detalle->modalidad,
                (bool) $detalle->producto->es_comida_dia || $presentation->isComida($detalle->producto->nombre)
            );

            foreach ($this->wrapItemLine($prefix, $this->sanitize($name), $price, $lineWidth) as $line) {
                $builder->line($line);
            }

            $detailLines = $presentation->clientDetailLines(
                $detalle->producto->nombre,
                $detalle->opciones->pluck('nombre')->all(),
                $detalle->modalidad,
                (bool) $detalle->producto->es_comida_dia || $presentation->isComida($detalle->producto->nombre)
            );

            foreach ($detalle->extras as $extra) {
                $extraName = trim((string) ($extra->nombre_personalizado ?: $extra->extra?->nombre ?: ''));
                if ($extraName !== '') {
                    $extraQty = max(1, (int) ($extra->cantidad ?? 1));
                    $detailLines[] = $extraName . ' x' . $extraQty;
                }
            }

            if ($detalle->nota) {
                $detailLines[] = 'Nota: ' . $detalle->nota;
            }

            foreach ($detailLines as $detailLine) {
                foreach ($this->wrapText('- ' . $this->sanitize($detailLine), $lineWidth - 2) as $line) {
                    $builder->line('  ' . $line);
                }
            }
        }

        $total = (float) $orden->total;

        $builder
            ->line($separator)
            ->bold()
            ->doubleSize()
            ->line($this->moneyLine('TOTAL:', $total, $lineWidth, true))
            ->doubleSize(false)
            ->bold(false)
            ->line($separator)
            ->alignCenter()
            ->line('Gracias por su compra')
            ->line($separator)
            ->alignLeft();

        if (!empty($this->config['open_drawer'])) {
            $builder->openDrawer();
        }

        if (!empty($this->config['cut_at_end'])) {
            $builder->cut();
        }

        return $builder->bytes();
    }

    private function keyValue(string $label, string $value, int $lineWidth): string
    {
        $value = trim($value);
        $available = max(1, $lineWidth - mb_strlen($label));

        if (mb_strlen($value) > $available) {
            $value = mb_substr($value, 0, $available);
        }

        return $label . str_pad($value, $available, ' ', STR_PAD_LEFT);
    }

    private function moneyLine(string $label, float $amount, int $lineWidth, bool $tight = false, ?string $prefixValue = null): string
    {
        $value = number_format($amount, 2);

        if ($prefixValue !== null && $prefixValue !== '-') {
            $value = $prefixValue . ' ' . $value;
        } elseif ($prefixValue === '-') {
            $value = '-';
        }

        $available = max(1, $lineWidth - mb_strlen($label));
        if (mb_strlen($value) > $available) {
            $value = mb_substr($value, 0, $available);
        }

        $paddingType = $tight ? STR_PAD_LEFT : STR_PAD_LEFT;

        return $label . str_pad($value, $available, ' ', $paddingType);
    }

    private function wrapItemLine(string $prefix, string $name, string $price, int $lineWidth): array
    {
        $priceWidth = max(8, mb_strlen($price) + 2);
        $nameWidth = max(8, $lineWidth - $priceWidth);
        $firstLineWidth = max(1, $nameWidth - mb_strlen($prefix));
        $wrapped = $this->wrapText($name, $firstLineWidth);

        if ($wrapped === []) {
            $wrapped = [''];
        }

        $lines = [];
        $first = array_shift($wrapped);
        $lines[] = $prefix . str_pad($first, $firstLineWidth) . str_pad($price, $priceWidth, ' ', STR_PAD_LEFT);

        foreach ($wrapped as $line) {
            $lines[] = str_repeat(' ', mb_strlen($prefix)) . $line;
        }

        return $lines;
    }

    private function wrapText(string $text, int $width): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $words = preg_split('/\s+/', $text) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;

            if (mb_strlen($candidate) <= $width) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                $current = '';
            }

            while (mb_strlen($word) > $width) {
                $lines[] = mb_substr($word, 0, $width);
                $word = mb_substr($word, $width);
            }

            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function sanitize(string $value): string
    {
        return trim(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value);
    }

    private function resolveLogoPath(): ?string
    {
        $configured = trim((string) ($this->config['store_logo_path'] ?? 'public/images/bruma.png'));

        if ($configured === '') {
            $configured = 'public/images/bruma.png';
        }

        if (!$this->isAbsolutePath($configured)) {
            $configured = base_path($configured);
        }

        return is_file($configured) ? $configured : null;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
