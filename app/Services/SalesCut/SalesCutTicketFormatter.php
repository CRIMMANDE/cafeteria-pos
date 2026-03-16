<?php

namespace App\Services\SalesCut;

use App\Services\ThermalPrinter\EscPosBuilder;
use Carbon\CarbonInterface;

class SalesCutTicketFormatter
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function build(array $summary): string
    {
        $builder = (new EscPosBuilder())->initialize();
        $lineWidth = max(32, (int) ($this->config['characters_per_line'] ?? 48));
        $separator = str_repeat('-', $lineWidth);

        /** @var CarbonInterface $inicio */
        $inicio = $summary['inicio'];
        /** @var CarbonInterface $fin */
        $fin = $summary['fin'];

        $builder
            ->alignCenter()
            ->bold()
            ->doubleSize()
            ->line('CORTE DE VENTAS')
            ->doubleSize(false)
            ->bold(false)
            ->alignLeft()
            ->line($separator)
            ->line($this->keyValue('Inicio:', $inicio->format('d-m-Y H:i'), $lineWidth))
            ->line($this->keyValue('Fin:', $fin->format('d-m-Y H:i'), $lineWidth))
            ->line($separator)
            ->line($this->moneyLine('Subtotal:', (float) $summary['subtotal'], $lineWidth))
            ->line($this->moneyLine('Parcial efectivo:', (float) $summary['parcial_efectivo'], $lineWidth))
            ->line($this->moneyLine('Parcial tarjeta:', (float) $summary['parcial_tarjeta_neto'], $lineWidth))
            ->bold()
            ->line($this->moneyLine('TOTAL:', (float) $summary['total_final'], $lineWidth))
            ->bold(false)
            ->line($separator);

        if (!empty($this->config['cut_at_end'])) {
            $builder->cut();
        }

        return $builder->bytes();
    }

    private function keyValue(string $label, string $value, int $lineWidth): string
    {
        $available = max(1, $lineWidth - mb_strlen($label));
        if (mb_strlen($value) > $available) {
            $value = mb_substr($value, 0, $available);
        }

        return $label . str_pad($value, $available, ' ', STR_PAD_LEFT);
    }

    private function moneyLine(string $label, float $amount, int $lineWidth): string
    {
        $value = number_format($amount, 2, '.', '');
        $available = max(1, $lineWidth - mb_strlen($label));

        if (mb_strlen($value) > $available) {
            $value = mb_substr($value, 0, $available);
        }

        return $label . str_pad($value, $available, ' ', STR_PAD_LEFT);
    }
}
