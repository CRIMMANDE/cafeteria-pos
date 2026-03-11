<?php

namespace App\Services\ThermalPrinter;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use Illuminate\Support\Collection;

class AreaCommandPrintService
{
    public function __construct(
        private readonly RawEscPosPrinter $printer,
    ) {
    }

    public function printNewItems(Orden $orden, string $area, array $detailIds): PrintResult
    {
        $detalles = $this->queryDetalles($orden, $area)
            ->whereIn('orden_detalles.id', $detailIds)
            ->get();

        if ($detalles->isEmpty()) {
            return new PrintResult(true, false, "No hay productos nuevos para {$area}.");
        }

        $result = $this->printer->send(
            (new AreaCommandFormatter($this->config($area)))->build($orden, $detalles, $area),
            $this->config($area),
            url("/{$area}/mesa/{$orden->mesa_id}/imprimir")
        );

        if ($result->printed) {
            OrdenDetalle::whereIn('id', $detalles->pluck('id'))->update(['impreso' => true]);
        }

        return $result;
    }

    public function reprintFullOrder(Orden $orden, string $area): PrintResult
    {
        $detalles = $this->queryDetalles($orden, $area)->get();

        if ($detalles->isEmpty()) {
            return new PrintResult(true, false, "No hay productos de {$area} en esta orden.");
        }

        return $this->printer->send(
            (new AreaCommandFormatter($this->config($area)))->build($orden, $detalles, $area),
            $this->config($area),
            url("/{$area}/mesa/{$orden->mesa_id}/imprimir")
        );
    }

    public function getAreaItemsForView(Orden $orden, string $area): Collection
    {
        return $this->queryDetalles($orden, $area)->get();
    }

    public function formatMesaLabel(int $mesaId): string
    {
        return Mesa::isTakeaway($mesaId) ? 'P/LLEVAR' : 'Mesa ' . $mesaId;
    }

    private function queryDetalles(Orden $orden, string $area)
    {
        return $orden->detalles()
            ->select('orden_detalles.*')
            ->with('producto.categoria')
            ->join('productos', 'productos.id', '=', 'orden_detalles.producto_id')
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->where('categorias.tipo', $area)
            ->orderBy('orden_detalles.id');
    }

    private function config(string $area): array
    {
        return config("area_printers.{$area}", []);
    }
}
