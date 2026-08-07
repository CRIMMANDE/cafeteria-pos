<?php

namespace App\Services\SalesCut;

use App\Services\ThermalPrinter\EscPosBuilder;
use Carbon\CarbonInterface;

class SalesCutTicketFormatter
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function build(array $summary): string
    {
        $builder = (new EscPosBuilder)->initialize();
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
            ->line('Sucursal: '.$summary['sucursal_nombre'])
            ->alignLeft()
            ->line($separator)
            ->line($this->keyValue('Inicio:', $inicio->format('d-m-Y H:i'), $lineWidth))
            ->line($this->keyValue('Fin:', $fin->format('d-m-Y H:i'), $lineWidth));

        if ($summary['filtro'] === 'todas') {
            foreach ($summary['sucursales'] as $branch) {
                $this->appendSummary($builder, $branch, $lineWidth, $separator, $branch['sucursal_nombre']);
            }

            $this->appendSummary($builder, $summary, $lineWidth, $separator, 'TOTAL GENERAL', true);
        } else {
            $this->appendSummary($builder, $summary, $lineWidth, $separator, null, true);
        }

        $builder->line($separator);

        if (! empty($this->config['cut_at_end'])) {
            $builder->cut();
        }

        return $builder->bytes();
    }

    private function appendSummary(
        EscPosBuilder $builder,
        array $summary,
        int $lineWidth,
        string $separator,
        ?string $title,
        bool $boldTotal = false
    ): void {
        $builder->line($separator);

        if ($title !== null) {
            $builder->bold()->line($title)->bold(false);
        }

        $builder
            ->line($this->keyValue('Ordenes:', (string) $summary['ordenes_count'], $lineWidth))
            ->line($this->moneyLine('Subtotal:', (float) $summary['subtotal'], $lineWidth))
            ->line($this->moneyLine('Efectivo:', (float) $summary['parcial_efectivo'], $lineWidth))
            ->line($this->moneyLine('Tarjeta bruto:', (float) $summary['parcial_tarjeta_bruto'], $lineWidth))
            ->line($this->moneyLine('Tarjeta neto:', (float) $summary['parcial_tarjeta_neto'], $lineWidth));

        if ($boldTotal) {
            $builder->bold();
        }

        $builder->line($this->moneyLine('TOTAL:', (float) $summary['total_final'], $lineWidth));

        if ($boldTotal) {
            $builder->bold(false);
        }
    }

    private function keyValue(string $label, string $value, int $lineWidth): string
    {
        $available = max(1, $lineWidth - mb_strlen($label));
        if (mb_strlen($value) > $available) {
            $value = mb_substr($value, 0, $available);
        }

        return $label.str_pad($value, $available, ' ', STR_PAD_LEFT);
    }

    private function moneyLine(string $label, float $amount, int $lineWidth): string
    {
        $value = number_format($amount, 2, '.', '');
        $available = max(1, $lineWidth - mb_strlen($label));

        if (mb_strlen($value) > $available) {
            $value = mb_substr($value, 0, $available);
        }

        return $label.str_pad($value, $available, ' ', STR_PAD_LEFT);
    }
}
