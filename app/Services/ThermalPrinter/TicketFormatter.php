<?php

namespace App\Services\ThermalPrinter;

use App\Models\Mesa;
use App\Models\Orden;

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
        $cashier = auth()->user()?->name ?? ($this->config['default_cashier'] ?? 'Sistema');
        $orderType = $orden->tipo ?: (Mesa::isEmployee((int) $orden->mesa_id) ? 'empleados' : (Mesa::isTakeaway((int) $orden->mesa_id) ? 'llevar' : 'mesa'));
        $typeLabel = match ($orderType) {
            'empleados' => 'Empleados',
            'llevar' => 'Para llevar',
            default => 'Mesa',
        };
        $mesaLabel = $orderType === 'mesa' ? (string) $orden->mesa_id : '-';
        $payment = $orden->pagos->sortByDesc('id')->first();

        $builder
            ->alignCenter()
            ->bold()
            ->doubleSize()
            ->line($this->sanitize($this->config['store_name'] ?? 'BRUMA CAFE'))
            ->doubleSize(false)
            ->bold(false);

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
            ->line($this->keyValue('Cajero:', $this->sanitize($cashier), $lineWidth))
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

            foreach ($this->wrapItemLine($prefix, $this->sanitize($detalle->producto->nombre), $price, $lineWidth) as $line) {
                $builder->line($line);
            }
        }

        $subtotal = (float) $orden->total;
        $discount = 0.0;
        $paymentLabel = $payment ? ucfirst($payment->metodo) : ($orden->metodo_pago ? ucfirst($orden->metodo_pago) : '-');
        $paymentAmount = $payment ? (float) $payment->monto : ($orden->metodo_pago ? (float) $orden->total : 0.0);
        $change = max(0, $paymentAmount - $subtotal);

        $builder
            ->line($separator)
            ->line($this->moneyLine('Subtotal:', $subtotal, $lineWidth))
            ->line($this->moneyLine('Descuento:', $discount, $lineWidth))
            ->bold()
            ->doubleSize()
            ->line($this->moneyLine('TOTAL:', $subtotal, $lineWidth, true))
            ->doubleSize(false)
            ->bold(false)
            ->line($this->moneyLine('Pago:', $paymentAmount, $lineWidth, false, $paymentLabel))
            ->line($this->moneyLine('Cambio:', $change, $lineWidth))
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
}
