<?php

namespace App\Services\ThermalPrinter;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalleComponente;
use App\Services\OrderPreparationComponentService;
use Illuminate\Support\Collection;

class AreaCommandPrintService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
        private readonly OrderPreparationComponentService $componentService,
    ) {
    }

    public function printNewItems(Orden $orden, string $area, array $detailIds): PrintResult
    {
        $componentes = $this->queryComponents($orden, $area)
            ->whereIn('orden_detalles.id', $detailIds)
            ->where('orden_detalle_componentes.impreso', false)
            ->get();

        if ($componentes->isEmpty()) {
            return new PrintResult(true, false, "No hay productos nuevos para {$area}.");
        }

        $agrupados = $this->groupComponents($componentes);

        $result = $this->printer->send(
            (new AreaCommandFormatter($this->config($area)))->build($orden, $agrupados, $area, $this->formatMesaLabelForOrder($orden)),
            $this->config($area),
            url("/{$area}/mesa/{$orden->mesa_id}/imprimir")
        );

        if ($result->printed) {
            OrdenDetalleComponente::whereIn('id', $componentes->pluck('id'))->update(['impreso' => true]);
        }

        return $result;
    }

    public function reprintFullOrder(Orden $orden, string $area): PrintResult
    {
        $componentes = $this->queryComponents($orden, $area)->get();

        if ($componentes->isEmpty()) {
            return new PrintResult(true, false, "No hay productos de {$area} en esta orden.");
        }

        return $this->printer->send(
            (new AreaCommandFormatter($this->config($area)))->build($orden, $this->groupComponents($componentes), $area, $this->formatMesaLabelForOrder($orden)),
            $this->config($area),
            url("/{$area}/mesa/{$orden->mesa_id}/imprimir")
        );
    }

    public function getAreaItemsForView(Orden $orden, string $area): Collection
    {
        $componentes = $this->queryComponents($orden, $area)->get();

        return $this->groupComponents($componentes);
    }

    public function formatMesaLabel(int $mesaId): string
    {
        if (Mesa::isEmployee($mesaId)) {
            return 'EMPLEADOS';
        }

        return Mesa::isTakeaway($mesaId) ? 'P/LLEVAR' : 'Mesa ' . $mesaId;
    }

    public function formatMesaLabelForOrder(Orden $orden): string
    {
        if ($orden->tipo === 'empleados' || Mesa::isEmployee((int) $orden->mesa_id)) {
            return 'EMPLEADOS';
        }

        return Mesa::isTakeaway((int) $orden->mesa_id) ? 'P/LLEVAR' : 'MESA ' . $orden->mesa_id;
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

                if (!isset($blocksBySignature[$signature])) {
                    $blocksBySignature[$signature] = $block;
                    continue;
                }

                $blocksBySignature[$signature]['cantidad'] += (int) $block['cantidad'];
            }
        }

        return collect(array_values($blocksBySignature));
    }

    /**
     * @param Collection<int, OrdenDetalleComponente> $detailItems
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
     * @param array<int, string> $descriptions
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
     * @param array{descripcion:string,cantidad:int,detalle:array<int,string>} $block
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

    private function config(string $area): array
    {
        return config("impresoras.{$area}", []);
    }
}
