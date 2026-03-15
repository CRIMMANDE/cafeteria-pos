<?php

namespace App\Services\ThermalPrinter;

use App\Models\Orden;
use Illuminate\Support\Collection;

class AreaCommandFormatter
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function build(Orden $orden, Collection $items, string $area, string $mesaLabel): string
    {
        $builder = (new EscPosBuilder())->initialize();
        $lineWidth = max(32, (int) ($this->config['characters_per_line'] ?? 48));
        $separator = str_repeat('-', $lineWidth);

        $builder
            ->alignCenter()
            ->bold()
            ->doubleSize()
            ->line($this->sanitize($this->config['header'] ?? strtoupper($area)))
            ->doubleSize(false)
            ->bold(false)
            ->alignLeft()
            ->line($separator)
            ->line('Folio: ' . $orden->id)
            ->line('Fecha: ' . ($orden->updated_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i')))
            ->line('Area: ' . strtoupper($area))
            ->line('Pedido: ' . $this->sanitize($mesaLabel))
            ->line($separator);

        foreach ($items as $item) {
            $prefix = (int) ($item['cantidad'] ?? 0) . ' ';
            $description = $this->sanitize((string) ($item['descripcion'] ?? ''));

            foreach ($this->wrapText($prefix . $description, $lineWidth) as $line) {
                $builder->line($line);
            }
        }

        $builder
            ->line($separator)
            ->alignCenter()
            ->line('FIN DE COMANDA')
            ->line($separator)
            ->alignLeft();

        if (!empty($this->config['cut_at_end'])) {
            $builder->cut();
        }

        return $builder->bytes();
    }

    private function wrapText(string $text, int $width): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private function sanitize(string $value): string
    {
        return trim(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value);
    }
}
