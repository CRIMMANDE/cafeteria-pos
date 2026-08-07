<?php

namespace App\Services\ThermalPrinter;

use App\Models\Orden;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class AreaCommandFormatter
{
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function build(Orden $orden, Collection $items, string $area, string $mesaLabel): string
    {
        $orden->loadMissing(['sucursal', 'mesa']);

        if ($orden->sucursal === null || $orden->mesa === null || (int) $orden->mesa->sucursal_id !== (int) $orden->sucursal_id) {
            throw new RuntimeException("La orden {$orden->getKey()} tiene un contexto de sucursal o mesa inválido.");
        }

        $builder = (new EscPosBuilder())->initialize();
        $lineWidth = max(32, (int) ($this->config['characters_per_line'] ?? 48));
        $separator = str_repeat('-', $lineWidth);
        $headerMain = $this->sanitize($mesaLabel . ' #' . $orden->id);
        $headerDate = $orden->updated_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');
        $branchName = $this->sanitize((string) $orden->sucursal->nombre);
        $reference = trim((string) $orden->referencia);

        $builder
            ->alignCenter()
            ->bold()
            ->doubleSize()
            ->line($this->fitText($headerMain, $lineWidth))
            ->doubleSize(false)
            ->bold(false)
            ->line($headerDate)
            ->line('Sucursal: ' . $branchName);

        if ($reference !== '') {
            foreach ($this->wrapText('Referencia: ' . $this->sanitize($reference), $lineWidth) as $line) {
                $builder->line($line);
            }
        }

        $builder
            ->alignLeft()
            ->line($separator);

        $values = $items->values();

        foreach ($values as $index => $item) {
            $qty = max(1, (int) ($item['cantidad'] ?? 1));
            $main = $this->sanitize((string) ($item['descripcion'] ?? ''));

            if ($main === '') {
                continue;
            }

            foreach ($this->wrapText($qty . ' ' . $main, $lineWidth) as $line) {
                $builder->line($line);
            }

            foreach (($item['detalle'] ?? []) as $detailLine) {
                $detail = $this->sanitize((string) $detailLine);
                if ($detail === '') {
                    continue;
                }

                foreach ($this->wrapText('- ' . $detail, max(1, $lineWidth - 2)) as $line) {
                    $builder->line('  ' . $line);
                }
            }

            if ($index < $values->count() - 1) {
                $builder->line('');
            }
        }

        $builder
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
        return trim(Str::ascii($value));
    }

    private function fitText(string $text, int $width): string
    {
        if ($width <= 0) {
            return '';
        }

        if (strlen($text) <= $width) {
            return $text;
        }

        return substr($text, 0, $width);
    }
}


