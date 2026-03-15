<?php

namespace App\Http\Controllers;

use App\Models\Orden;
use App\Services\OrderPreparationComponentService;
use App\Services\ThermalPrinter\AreaCommandPrintService;
use Illuminate\Support\Facades\DB;

class AreaController extends Controller
{
    public function __construct(
        private readonly OrderPreparationComponentService $componentService,
    ) {
    }

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
            'mesaLabel' => $service->formatMesaLabelForOrder($orden),
            'orden' => $orden,
            'items' => $service->getAreaItemsForView($orden, $area),
        ]);
    }

    private function index(string $area)
    {
        $ordenes = Orden::query()
            ->where('estado', 'abierta')
            ->with(['detalles.componentes'])
            ->orderBy('mesa_id')
            ->get()
            ->map(function (Orden $orden) use ($area) {
                $this->componentService->ensureComponentsForOrder($orden);

                $items = $orden->detalles
                    ->flatMap->componentes
                    ->where('area', $area);

                if ($items->isEmpty()) {
                    return null;
                }

                return [
                    'mesa_id' => $orden->mesa_id,
                    'mesa_label' => match ($orden->tipo) {
                        'empleados' => 'EMPLEADOS',
                        'llevar' => 'P/LLEVAR',
                        default => 'Mesa ' . $orden->mesa_id,
                    },
                    'orden_id' => $orden->id,
                    'items' => (int) $items->sum('cantidad'),
                    'pendientes' => (int) $items->where('impreso', false)->sum('cantidad'),
                    'updated_at' => $orden->updated_at,
                ];
            })
            ->filter()
            ->values();

        return view('areas.index', [
            'area' => $area,
            'areaTitulo' => strtoupper($area),
            'ordenes' => $ordenes,
        ]);
    }

    private function findOpenOrder(int $mesa): ?Orden
    {
        $orden = Orden::query()
            ->where('mesa_id', $mesa)
            ->where('estado', 'abierta')
            ->with([
                'detalles.producto.categoria',
                'detalles.opciones.opcion.grupoOpcion',
                'detalles.extras.extra',
                'detalles.componentes',
            ])
            ->first();

        if ($orden) {
            DB::transaction(fn () => $this->componentService->ensureComponentsForOrder($orden));
        }

        return $orden;
    }
}
