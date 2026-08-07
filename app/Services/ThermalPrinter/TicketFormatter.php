<?php

namespace App\Services\ThermalPrinter;

use App\Models\Orden;
use App\Services\OrderLinePresentationService;
use App\Support\Money;
use Illuminate\Support\Str;
use RuntimeException;

class TicketFormatter
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function buildOrderTicket(Orden $orden): string
    {
        $orden->loadMissing(['sucursal', 'mesa', 'pagos']);
        $sucursal = $orden->sucursal;
        $mesa = $orden->mesa;

        if ($sucursal === null || $mesa === null || (int) $mesa->sucursal_id !== (int) $sucursal->getKey()) {
            throw new RuntimeException("La orden {$orden->getKey()} tiene un contexto de sucursal o mesa inválido.");
        }

        $builder = (new EscPosBuilder)->initialize();
        $lineWidth = max(32, (int) ($this->config['characters_per_line'] ?? 48));
        $separator = str_repeat('-', $lineWidth);
        $orderType = $orden->tipo ?: $mesa->tipo;
        $typeLabel = match ($orderType) {
            'empleados' => 'Empleados',
            'llevar' => 'Para llevar',
            default => 'Mesa',
        };
        $mesaLabel = match ($orderType) {
            'empleados' => 'EMPLEADOS',
            'llevar' => 'P/LLEVAR',
            default => $mesa->numero === null
                ? throw new RuntimeException("La mesa física de la orden {$orden->getKey()} no tiene número visible.")
                : (string) $mesa->numero,
        };
        $branchName = $this->sanitize((string) $sucursal->nombre);
        $reference = trim((string) $orden->referencia);
        $payment = $orden->pagos->sortByDesc('id')->first();
        $receivedCents = $payment?->metodo === 'efectivo' ? Money::toCents($payment->monto_recibido) : null;
        $changeCents = $payment?->metodo === 'efectivo' ? Money::toCents($payment->cambio) : null;
        $presentation = new OrderLinePresentationService;
        $logoPath = $this->resolveLogoPath();

        $builder->alignCenter();

        if ($logoPath !== null) {
            $builder->rasterImageFromPng($logoPath, (int) ($this->config['store_logo_max_width_dots'] ?? 380));
        }

        if (! empty($this->config['store_address'])) {
            $builder->line($this->sanitize($this->config['store_address']));
        }

        if (! empty($this->config['store_phone'])) {
            $builder->line('Tel. '.$this->sanitize($this->config['store_phone']));
        }

        $builder
            ->alignLeft()
            ->line($separator)
            ->line('Sucursal: '.$branchName);

        if ($reference !== '') {
            foreach ($this->wrapText('Referencia: '.$this->sanitize($reference), $lineWidth) as $line) {
                $builder->line($line);
            }
        }

        $builder
            ->line($this->keyValue('Folio:', (string) $orden->id, $lineWidth))
            ->line($this->keyValue('Fecha:', $orden->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'), $lineWidth))
            ->line($this->keyValue('Tipo:', $typeLabel, $lineWidth))
            ->line($this->keyValue('Mesa:', $mesaLabel, $lineWidth))
            ->line($separator);

        foreach ($orden->detalles as $detalle) {
            if (! $detalle->producto) {
                continue;
            }

            $subtotal = (float) $detalle->precio * (int) $detalle->cantidad;
            $prefix = (int) $detalle->cantidad.' ';
            $price = number_format($subtotal, 2);
            $isOtroManual = (bool) $detalle->es_otro_manual;
            $name = $isOtroManual
                ? trim((string) ($detalle->nombre_personalizado ?: $detalle->producto->nombre))
                : $presentation->commercialName(
                    $detalle->producto->nombre,
                    $detalle->opciones->pluck('nombre')->all(),
                    $detalle->modalidad,
                    (bool) $detalle->producto->es_comida_dia || $presentation->isComida($detalle->producto->nombre)
                );

            foreach ($this->wrapItemLine($prefix, $this->sanitize($name), $price, $lineWidth) as $line) {
                $builder->line($line);
            }

            $detailLines = $isOtroManual
                ? []
                : $presentation->clientDetailLines(
                    $detalle->producto->nombre,
                    $detalle->opciones->pluck('nombre')->all(),
                    $detalle->modalidad,
                    (bool) $detalle->producto->es_comida_dia || $presentation->isComida($detalle->producto->nombre)
                );

            foreach ($detalle->extras as $extra) {
                $extraName = trim((string) ($extra->nombre_personalizado ?: $extra->extra?->nombre ?: ''));
                if ($extraName !== '') {
                    $extraQty = max(1, (int) ($extra->cantidad ?? 1));
                    $detailLines[] = $extraName.' x'.$extraQty;
                }
            }

            if ($detalle->nota) {
                $detailLines[] = 'Nota: '.$detalle->nota;
            }

            foreach ($detailLines as $detailLine) {
                foreach ($this->wrapText('- '.$this->sanitize($detailLine), $lineWidth - 2) as $line) {
                    $builder->line('  '.$line);
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
            ->bold(false);

        if ($receivedCents !== null && $changeCents !== null) {
            $builder
                ->line($this->moneyLineFromCents('Recibido: $', $receivedCents, $lineWidth))
                ->line($this->moneyLineFromCents('Cambio: $', $changeCents, $lineWidth));
        }

        $builder
            ->line($separator)
            ->alignCenter()
            ->line('Gracias por su compra')
            ->line($separator)
            ->alignLeft();

        if (! empty($this->config['open_drawer'])) {
            $builder->openDrawer();
        }

        if (! empty($this->config['cut_at_end'])) {
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

        return $label.str_pad($value, $available, ' ', STR_PAD_LEFT);
    }

    private function moneyLine(string $label, float $amount, int $lineWidth, bool $tight = false, ?string $prefixValue = null): string
    {
        $value = number_format($amount, 2);

        if ($prefixValue !== null && $prefixValue !== '-') {
            $value = $prefixValue.' '.$value;
        } elseif ($prefixValue === '-') {
            $value = '-';
        }

        $available = max(1, $lineWidth - mb_strlen($label));
        if (mb_strlen($value) > $available) {
            $value = mb_substr($value, 0, $available);
        }

        $paddingType = $tight ? STR_PAD_LEFT : STR_PAD_LEFT;

        return $label.str_pad($value, $available, ' ', $paddingType);
    }

    private function moneyLineFromCents(string $label, int $cents, int $lineWidth): string
    {
        $value = Money::fromCents($cents);
        $available = max(1, $lineWidth - mb_strlen($label));

        return $label.str_pad($value, $available, ' ', STR_PAD_LEFT);
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
        $lines[] = $prefix.str_pad($first, $firstLineWidth).str_pad($price, $priceWidth, ' ', STR_PAD_LEFT);

        foreach ($wrapped as $line) {
            $lines[] = str_repeat(' ', mb_strlen($prefix)).$line;
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
            $candidate = $current === '' ? $word : $current.' '.$word;

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
        return trim(Str::ascii($value));
    }

    private function resolveLogoPath(): ?string
    {
        $configured = trim((string) ($this->config['store_logo_path'] ?? 'public/images/bruma.png'));

        if ($configured === '') {
            $configured = 'public/images/bruma.png';
        }

        if (! $this->isAbsolutePath($configured)) {
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
