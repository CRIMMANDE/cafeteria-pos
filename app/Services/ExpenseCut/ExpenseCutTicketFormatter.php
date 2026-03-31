<?php

namespace App\Services\ExpenseCut;

use App\Services\ThermalPrinter\EscPosBuilder;
use Carbon\CarbonInterface;

class ExpenseCutTicketFormatter
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
            ->line('CORTE DE GASTOS')
            ->doubleSize(false)
            ->bold(false)
            ->alignLeft()
            ->line($separator)
            ->line($this->keyValue('Inicio:', $inicio->format('d-m-Y'), $lineWidth))
            ->line($this->keyValue('Fin:', $fin->format('d-m-Y'), $lineWidth))
            ->line($separator)
            ->line($this->keyValue('Registros:', (string) $summary['gastos_count'], $lineWidth))
            ->bold()
            ->line($this->moneyLine('TOTAL GASTOS:', (float) $summary['total_gastos'], $lineWidth))
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
