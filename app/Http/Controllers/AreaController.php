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

    public function reimprimir(string $area, Orden $orden, AreaCommandPrintService $service)
    {
        $orden = $this->printableOrder($orden);

        $result = $service->reprintFullOrder($orden, $area);

        return response()->json(array_merge([
            'orden_id' => $orden->id,
        ], $result->toPublicArray()));
    }

    public function imprimir(string $area, Orden $orden, AreaCommandPrintService $service)
    {
        $orden = $this->printableOrder($orden);

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
            ->with(['sucursal', 'mesa.sucursal', 'detalles.componentes'])
            ->orderBy('sucursal_id')
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
                    'mesa_label' => match ($orden->tipo) {
                        'empleados' => 'EMPLEADOS',
                        'llevar' => 'P/LLEVAR',
                        default => $orden->mesa?->numero === null
                            ? 'MESA SIN NÚMERO'
                            : 'Mesa '.$orden->mesa->numero,
                    },
                    'sucursal' => $orden->mesa?->sucursal?->nombre ?? $orden->sucursal?->nombre,
                    'orden_id' => $orden->id,
                    'reprint_url' => route('area.order.reprint', ['area' => $area, 'orden' => $orden]),
                    'printable_url' => route('area.order.printable', ['area' => $area, 'orden' => $orden]),
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

    private function printableOrder(Orden $orden): Orden
    {
        abort_unless($orden->estado === 'abierta', 404, 'No hay una orden abierta para ese pedido.');

        $orden->load([
            'sucursal',
            'mesa.sucursal',
            'detalles.producto.categoria',
            'detalles.opciones.opcion.grupoOpcion',
            'detalles.extras.extra',
            'detalles.componentes',
        ]);

        abort_if(
            $orden->sucursal === null
            || $orden->mesa === null
            || (int) $orden->mesa->sucursal_id !== (int) $orden->sucursal_id,
            409,
            'La orden tiene un contexto de sucursal o mesa inconsistente.'
        );

        DB::transaction(fn () => $this->componentService->ensureComponentsForOrder($orden));

        return $orden;
    }
}
