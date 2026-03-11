<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Services\ThermalPrinter\AreaCommandPrintService;

class AreaController extends Controller
{
    public function cocina()
    {
        return $this->index('cocina');
    }

    public function barra()
    {
        return $this->index('barra');
    }

    public function reimprimir(string $area, int $mesa, AreaCommandPrintService $service)
    {
        $orden = $this->findOpenOrder($mesa);

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay una orden abierta para ese pedido.',
            ], 404);
        }

        $result = $service->reprintFullOrder($orden, $area);

        return response()->json(array_merge([
            'orden_id' => $orden->id,
        ], $result->toArray()));
    }

    public function imprimir(string $area, int $mesa, AreaCommandPrintService $service)
    {
        $orden = $this->findOpenOrder($mesa);

        if (!$orden) {
            return redirect('/' . $area)->with('error', 'No hay una orden abierta para ese pedido.');
        }

        return view('areas.imprimir', [
            'area' => $area,
            'areaTitulo' => strtoupper($area),
            'mesaLabel' => $service->formatMesaLabel($mesa),
            'orden' => $orden,
            'items' => $service->getAreaItemsForView($orden, $area),
        ]);
    }

    private function index(string $area)
    {
        $ordenes = Orden::query()
            ->where('estado', 'abierta')
            ->whereHas('detalles.producto.categoria', fn ($query) => $query->where('tipo', $area))
            ->with(['detalles.producto.categoria'])
            ->orderBy('mesa_id')
            ->get()
            ->map(function (Orden $orden) use ($area) {
                $items = $orden->detalles
                    ->filter(fn ($detalle) => $detalle->producto && optional($detalle->producto->categoria)->tipo === $area);

                return [
                    'mesa_id' => $orden->mesa_id,
                    'mesa_label' => Mesa::isTakeaway((int) $orden->mesa_id) ? 'P/LLEVAR' : 'Mesa ' . $orden->mesa_id,
                    'orden_id' => $orden->id,
                    'items' => $items->sum('cantidad'),
                    'pendientes' => $items->where('impreso', false)->sum('cantidad'),
                    'updated_at' => $orden->updated_at,
                ];
            });

        return view('areas.index', [
            'area' => $area,
            'areaTitulo' => strtoupper($area),
            'ordenes' => $ordenes,
        ]);
    }

    private function findOpenOrder(int $mesa): ?Orden
    {
        if (Mesa::isTakeaway($mesa)) {
            Mesa::ensureTakeawayMesa();
        }

        return Orden::query()
            ->where('mesa_id', $mesa)
            ->where('estado', 'abierta')
            ->with(['detalles.producto.categoria'])
            ->first();
    }
}
