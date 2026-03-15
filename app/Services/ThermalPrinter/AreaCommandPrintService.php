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
        return $this->componentService->groupedComponentsForOrder($orden, $area);
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
        return $componentes
            ->groupBy(fn (OrdenDetalleComponente $component) => $component->descripcion)
            ->map(fn (Collection $items, string $descripcion) => [
                'descripcion' => $descripcion,
                'cantidad' => (int) $items->sum('cantidad'),
            ])
            ->sortBy('descripcion')
            ->values();
    }

    private function config(string $area): array
    {
        return config("area_printers.{$area}", []);
    }
}
