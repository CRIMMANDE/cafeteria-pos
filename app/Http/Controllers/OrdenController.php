<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\Producto;
use App\Services\ThermalPrinter\AreaCommandPrintService;
use App\Services\ThermalPrinter\ThermalPrinterService;
use Illuminate\Http\Request;

class OrdenController extends Controller
{
    public function guardar(Request $request, AreaCommandPrintService $areaCommandPrintService)
    {
        $mesaId = (int) $request->mesa;
        $this->ensureSpecialMesaExists($mesaId);

        [$orden, $newDetailIds] = $this->syncOpenOrder(
            $mesaId,
            $request->input('productos', []),
            $request->input('productosNuevos', [])
        );

        $commandResults = $this->printNewAreaCommands($orden, $newDetailIds, $areaCommandPrintService);

        return response()->json([
            'ok' => true,
            'orden_id' => $orden->id,
            'command_results' => $commandResults,
        ]);
    }

    public function imprimirTicket(Request $request, ThermalPrinterService $thermalPrinterService)
    {
        $mesaId = (int) $request->mesa;
        $this->ensureSpecialMesaExists($mesaId);

        [$orden] = $this->syncOpenOrder(
            $mesaId,
            $request->input('productos', []),
            []
        );

        $result = $thermalPrinterService->printOrder($orden->load(['detalles.producto', 'pagos']));

        return response()->json(array_merge([
            'orden_id' => $orden->id,
        ], $result->toArray()));
    }

    public function mesa($mesa)
    {
        $mesaId = (int) $mesa;
        $this->ensureSpecialMesaExists($mesaId);

        $orden = Orden::where('mesa_id', $mesa)
            ->where('estado', 'abierta')
            ->with('detalles.producto')
            ->first();

        if (!$orden) {
            return response()->json([]);
        }

        $agrupados = [];

        foreach ($orden->detalles as $det) {
            if (!$det->producto) {
                continue;
            }

            $productoId = (int) $det->producto_id;

            if (!isset($agrupados[$productoId])) {
                $agrupados[$productoId] = [
                    'id' => $productoId,
                    'nombre' => strtolower($det->producto->nombre),
                    'precio' => (float) $det->precio,
                    'cantidad' => 0,
                ];
            }

            $agrupados[$productoId]['cantidad'] += (int) $det->cantidad;
        }

        return response()->json(array_values($agrupados));
    }

    public function cerrar(Request $request)
    {
        $mesaId = (int) $request->mesa;
        $this->ensureSpecialMesaExists($mesaId);

        $orden = Orden::where('mesa_id', $mesaId)
            ->where('estado', 'abierta')
            ->first();

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay una orden abierta para esta mesa',
            ], 404);
        }

        [$orden] = $this->syncExistingOrder(
            $orden,
            $request->input('productos', []),
            [],
            'pagada'
        );

        return response()->json([
            'ok' => true,
            'message' => 'Cuenta cerrada correctamente',
        ]);
    }

    public function recuperar(Request $request)
    {
        $mesaId = (int) $request->mesa;
        $this->ensureSpecialMesaExists($mesaId);

        $abierta = Orden::where('mesa_id', $request->mesa)
            ->where('estado', 'abierta')
            ->first();

        if ($abierta) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe una orden abierta en esta mesa',
            ], 400);
        }

        $orden = Orden::where('mesa_id', $request->mesa)
            ->where('estado', 'pagada')
            ->latest('id')
            ->first();

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay una cuenta anterior para recuperar',
            ], 404);
        }

        $orden->update([
            'estado' => 'abierta',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Cuenta recuperada correctamente',
        ]);
    }

    public function imprimir($mesa)
    {
        $mesaId = (int) $mesa;
        $this->ensureSpecialMesaExists($mesaId);

        $orden = Orden::where('mesa_id', $mesa)
            ->where('estado', 'abierta')
            ->with('detalles.producto')
            ->first();

        if (!$orden) {
            return redirect('/mesas')->with('error', 'No hay orden abierta para esta mesa');
        }

        $productos = [];

        foreach ($orden->detalles as $det) {
            if (!$det->producto) {
                continue;
            }

            $productoId = (int) $det->producto_id;

            if (!isset($productos[$productoId])) {
                $productos[$productoId] = [
                    'nombre' => $det->producto->nombre,
                    'cantidad' => 0,
                    'precio' => (float) $det->precio,
                    'subtotal' => 0,
                ];
            }

            $productos[$productoId]['cantidad'] += (int) $det->cantidad;
            $productos[$productoId]['subtotal'] += ((float) $det->precio * (int) $det->cantidad);
        }

        return view('pos.imprimir', [
            'mesa' => $mesa,
            'mesaLabel' => Mesa::isEmployee($mesaId) ? 'EMPLEADOS' : (Mesa::isTakeaway($mesaId) ? 'P/LLEVAR' : 'Mesa ' . $mesa),
            'esParaLlevar' => Mesa::isTakeaway($mesaId),
            'orden' => $orden,
            'productos' => array_values($productos),
        ]);
    }

    private function printNewAreaCommands(Orden $orden, array $newDetailIds, AreaCommandPrintService $areaCommandPrintService): array
    {
        $results = [];

        foreach (['cocina', 'barra'] as $area) {
            $result = $areaCommandPrintService->printNewItems($orden, $area, $newDetailIds);
            $results[$area] = $result->toArray();
        }

        return $results;
    }

    private function syncOpenOrder(int $mesaId, array $productos, array $productosNuevos): array
    {
        $isEmployeeOrder = Mesa::isEmployee($mesaId);
        $orden = Orden::where('mesa_id', $mesaId)
            ->where('estado', 'abierta')
            ->first();

        if (!$orden) {
            $orden = Orden::create([
                'mesa_id' => $mesaId,
                'estado' => 'abierta',
                'total' => 0,
                'desc_empleado' => $isEmployeeOrder,
            ]);
        } elseif ((bool) $orden->desc_empleado !== $isEmployeeOrder) {
            $orden->update([
                'desc_empleado' => $isEmployeeOrder,
            ]);
        }

        return $this->syncExistingOrder($orden, $productos, $productosNuevos, 'abierta');
    }

    private function syncExistingOrder(Orden $orden, array $productos, array $productosNuevos, string $estado): array
    {
        $currentQuantities = $orden->detalles()
            ->selectRaw('producto_id, SUM(cantidad) as total')
            ->groupBy('producto_id')
            ->pluck('total', 'producto_id')
            ->map(fn ($total) => (int) $total)
            ->toArray();

        $targetProducts = $this->normalizeProducts($productos);
        $targetQuantities = [];
        $priceMap = $this->resolveProductPrices(array_column($targetProducts, 'id'), (bool) $orden->desc_empleado);

        foreach ($targetProducts as $producto) {
            $targetQuantities[$producto['id']] = $producto['cantidad'];
        }

        $newProductsMap = [];
        foreach ($this->normalizeProducts($productosNuevos) as $producto) {
            $newProductsMap[$producto['id']] = $producto['cantidad'];
        }
        $hasExplicitNewProducts = $newProductsMap !== [];

        $newDetailIds = [];
        $productIds = array_unique(array_merge(array_keys($currentQuantities), array_keys($targetQuantities)));

        foreach ($productIds as $productId) {
            $currentQty = $currentQuantities[$productId] ?? 0;
            $targetQty = $targetQuantities[$productId] ?? 0;
            $delta = $targetQty - $currentQty;

            if ($delta > 0) {
                $price = $priceMap[$productId] ?? 0;
                $pendingQty = $hasExplicitNewProducts
                    ? min($delta, $newProductsMap[$productId] ?? 0)
                    : $delta;
                $printedQty = $delta - $pendingQty;

                if ($pendingQty > 0) {
                    $detail = OrdenDetalle::create([
                        'orden_id' => $orden->id,
                        'producto_id' => $productId,
                        'cantidad' => $pendingQty,
                        'precio' => $price,
                        'impreso' => false,
                    ]);

                    $newDetailIds[] = $detail->id;
                }

                if ($printedQty > 0) {
                    OrdenDetalle::create([
                        'orden_id' => $orden->id,
                        'producto_id' => $productId,
                        'cantidad' => $printedQty,
                        'precio' => $price,
                        'impreso' => true,
                    ]);
                }
            }

            if ($delta < 0) {
                $this->reduceProductQuantity($orden, $productId, abs($delta));
            }
        }

        $total = (float) $orden->detalles()
            ->selectRaw('COALESCE(SUM(cantidad * precio), 0) as total')
            ->value('total');

        $orden->update([
            'total' => $total,
            'estado' => $estado,
        ]);

        return [
            $orden->fresh(['detalles.producto.categoria', 'pagos']),
            $newDetailIds,
        ];
    }

    private function reduceProductQuantity(Orden $orden, int $productId, int $quantityToReduce): void
    {
        $details = $orden->detalles()
            ->where('producto_id', $productId)
            ->orderByDesc('id')
            ->get();

        foreach ($details as $detail) {
            if ($quantityToReduce <= 0) {
                break;
            }

            if ((int) $detail->cantidad <= $quantityToReduce) {
                $quantityToReduce -= (int) $detail->cantidad;
                $detail->delete();
                continue;
            }

            $detail->update([
                'cantidad' => (int) $detail->cantidad - $quantityToReduce,
            ]);

            $quantityToReduce = 0;
        }
    }

    private function normalizeProducts(array $productos): array
    {
        $grouped = [];

        foreach ($productos as $producto) {
            $productId = (int) ($producto['id'] ?? 0);
            $cantidad = (int) ($producto['cantidad'] ?? 0);

            if ($productId <= 0 || $cantidad <= 0) {
                continue;
            }

            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [
                    'id' => $productId,
                    'cantidad' => 0,
                ];
            }

            $grouped[$productId]['cantidad'] += $cantidad;
        }

        return array_values($grouped);
    }

    private function resolveProductPrices(array $productIds, bool $useEmployeePrice): array
    {
        if ($productIds === []) {
            return [];
        }

        return Producto::query()
            ->whereIn('id', $productIds)
            ->get()
            ->mapWithKeys(fn (Producto $producto) => [
                $producto->id => $useEmployeePrice
                    ? (float) ($producto->costo ?? 0)
                    : (float) $producto->precio,
            ])
            ->toArray();
    }

    private function ensureSpecialMesaExists(int $mesaId): void
    {
        if (Mesa::isEmployee($mesaId)) {
            Mesa::ensureEmployeeMesa();
        }

        if (Mesa::isTakeaway($mesaId)) {
            Mesa::ensureTakeawayMesa();
        }
    }
}
