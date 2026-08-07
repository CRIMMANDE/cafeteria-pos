<?php

namespace App\Services\ThermalPrinter;

use App\Models\Orden;
use App\Models\OrdenDetalleComponente;
use App\Services\OrderPreparationComponentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AreaCommandPrintService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
        private readonly OrderPreparationComponentService $componentService,
        private readonly PrinterDestinationResolver $destinationResolver,
    ) {}

    public function printNewItems(Orden $orden, string $area, array $detailIds): PrintResult
    {
        $orden->loadMissing(['sucursal', 'mesa']);
        $componentes = $this->queryComponents($orden, $area)
            ->where('orden_detalle_componentes.impreso', false)
            ->where(function ($query) use ($detailIds) {
                $query->whereIn('orden_detalles.id', $detailIds)
                    ->orWhere('orden_detalles.es_otro_manual', true);
            })
            ->get();

        if ($componentes->isEmpty()) {
            return new PrintResult(true, false, "No hay productos nuevos para {$area}.");
        }

        $agrupados = $this->groupComponents($componentes);
        $destination = $this->destinationResolver->area($area);
        $config = config("impresoras.{$destination}", []);

        $result = $this->printer->send(
            (new AreaCommandFormatter($config))->build($orden, $agrupados, $area, $this->formatMesaLabelForOrder($orden)),
            $config,
            route('area.order.printable', ['area' => $area, 'orden' => $orden])
        );

        if ($result->printed) {
            OrdenDetalleComponente::whereIn('id', $componentes->pluck('id'))->update(['impreso' => true]);
        } else {
            $this->logFailure($orden, $destination, $result);
        }

        return $result;
    }

    public function reprintFullOrder(Orden $orden, string $area): PrintResult
    {
        $orden->loadMissing(['sucursal', 'mesa']);
        $componentes = $this->queryComponents($orden, $area)->get();

        if ($componentes->isEmpty()) {
            return new PrintResult(true, false, "No hay productos de {$area} en esta orden.");
        }

        $destination = $this->destinationResolver->area($area);
        $config = config("impresoras.{$destination}", []);
        $result = $this->printer->send(
            (new AreaCommandFormatter($config))->build($orden, $this->groupComponents($componentes), $area, $this->formatMesaLabelForOrder($orden)),
            $config,
            route('area.order.printable', ['area' => $area, 'orden' => $orden])
        );

        if (! $result->printed) {
            $this->logFailure($orden, $destination, $result);
        }

        return $result;
    }

    public function getAreaItemsForView(Orden $orden, string $area): Collection
    {
        $componentes = $this->queryComponents($orden, $area)->get();

        return $this->groupComponents($componentes);
    }

    public function formatMesaLabelForOrder(Orden $orden): string
    {
        $orden->loadMissing('mesa');
        $tipo = $orden->tipo ?: $orden->mesa?->tipo;

        return match ($tipo) {
            'empleados' => 'EMPLEADOS',
            'llevar' => 'P/LLEVAR',
            'mesa' => $orden->mesa?->numero === null
                ? throw new RuntimeException("La mesa física de la orden {$orden->getKey()} no tiene número visible.")
                : 'MESA '.$orden->mesa->numero,
            default => throw new RuntimeException("La orden {$orden->getKey()} tiene un tipo no imprimible."),
        };
    }

    private function queryComponents(Orden $orden, string $area)
    {
        $this->componentService->ensureComponentsForOrder($orden);

        return OrdenDetalleComponente::query()
            ->select('orden_detalle_componentes.*')
            ->join('orden_detalles', 'orden_detalles.id', '=', 'orden_detalle_componentes.orden_detalle_id')
            ->where('orden_detalles.orden_id', $orden->id)
            ->where('orden_detalle_componentes.area', $area)
            ->orderBy('orden_detalle_componentes.id');
    }

    private function groupComponents(Collection $componentes): Collection
    {
        if ($componentes->isEmpty()) {
            return collect();
        }

        $blocksBySignature = [];

        $groupedByDetail = $componentes
            ->sortBy('id')
            ->groupBy(fn (OrdenDetalleComponente $component) => (int) $component->orden_detalle_id);

        foreach ($groupedByDetail as $detailItems) {
            $blocks = $this->buildBlocksFromDetailComponents($detailItems);

            foreach ($blocks as $block) {
                $signature = $this->buildBlockSignature($block);

                if (! isset($blocksBySignature[$signature])) {
                    $blocksBySignature[$signature] = $block;

                    continue;
                }

                $blocksBySignature[$signature]['cantidad'] += (int) $block['cantidad'];
            }
        }

        return collect(array_values($blocksBySignature));
    }

    /**
     * @param  Collection<int, OrdenDetalleComponente>  $detailItems
     * @return array<int, array{descripcion:string,cantidad:int,detalle:array<int,string>}>
     */
    private function buildBlocksFromDetailComponents(Collection $detailItems): array
    {
        $ordered = $detailItems->sortBy('id')->values();
        $qty = max(1, (int) $ordered->max('cantidad'));

        $descriptions = $ordered
            ->pluck('descripcion')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '')
            ->values();

        if ($descriptions->isEmpty()) {
            return [[
                'descripcion' => '',
                'cantidad' => $qty,
                'detalle' => [],
            ]];
        }

        $defaultBlock = [[
            'descripcion' => (string) $descriptions->first(),
            'cantidad' => $qty,
            'detalle' => $descriptions->slice(1)->values()->all(),
        ]];

        $area = strtolower(trim((string) ($ordered->first()->area ?? '')));
        if ($area !== 'barra') {
            return $defaultBlock;
        }

        $splitBlocks = $this->splitTeaAndFruitBlocks($descriptions->all(), $qty);

        return $splitBlocks ?? $defaultBlock;
    }

    /**
     * @param  array<int, string>  $descriptions
     * @return array<int, array{descripcion:string,cantidad:int,detalle:array<int,string>}>|null
     */
    private function splitTeaAndFruitBlocks(array $descriptions, int $qty): ?array
    {
        if (count($descriptions) !== 2) {
            return null;
        }

        $teaLabel = null;
        $fruitLabel = null;

        foreach ($descriptions as $description) {
            if ($teaLabel === null && $this->isTeaWithFlavorLabel($description)) {
                $teaLabel = $description;

                continue;
            }

            if ($fruitLabel === null && $this->isFruitLabel($description)) {
                $fruitLabel = $description;
            }
        }

        if ($teaLabel === null || $fruitLabel === null) {
            return null;
        }

        $teaFlavor = trim(substr($this->normalizeForSignature($teaLabel), 3));
        if ($teaFlavor === '') {
            return null;
        }

        return [
            [
                'descripcion' => 'TE',
                'cantidad' => $qty,
                'detalle' => [$teaFlavor],
            ],
            [
                'descripcion' => trim($fruitLabel),
                'cantidad' => $qty,
                'detalle' => [],
            ],
        ];
    }

    private function isTeaWithFlavorLabel(string $description): bool
    {
        $normalized = $this->normalizeForSignature($description);

        return str_starts_with($normalized, 'TE ')
            && trim(substr($normalized, 3)) !== '';
    }

    private function isFruitLabel(string $description): bool
    {
        $normalized = $this->normalizeForSignature($description);
        $needles = [
            'PAPAYA',
            'MELON',
            'SANDIA',
            'FRESA',
            'PLATANO',
            'MANZANA',
            'FRUTA',
        ];

        foreach ($needles as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{descripcion:string,cantidad:int,detalle:array<int,string>}  $block
     */
    private function buildBlockSignature(array $block): string
    {
        $normalized = [
            'descripcion' => $this->normalizeForSignature($block['descripcion']),
            'detalle' => array_map(
                fn (string $line) => $this->normalizeForSignature($line),
                $block['detalle'] ?? []
            ),
        ];

        return sha1(json_encode($normalized, JSON_UNESCAPED_UNICODE) ?: '');
    }

    private function normalizeForSignature(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtoupper(trim($value));

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function logFailure(Orden $orden, string $destination, PrintResult $result): void
    {
        Log::warning('Area command printing was not completed', [
            'destination' => $destination,
            'order_id' => $orden->getKey(),
            'branch' => $orden->sucursal?->codigo,
        ]);
    }
}
