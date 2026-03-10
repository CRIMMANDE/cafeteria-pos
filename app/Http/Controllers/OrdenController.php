<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Orden;
use App\Models\OrdenDetalle;

class OrdenController extends Controller
{
    public function guardar(Request $request)
    {
        $orden = Orden::where('mesa_id', $request->mesa)
            ->where('estado', 'abierta')
            ->first();

        if (!$orden) {
            $orden = Orden::create([
                'mesa_id' => $request->mesa,
                'estado' => 'abierta',
                'total' => 0
            ]);
        } else {
            OrdenDetalle::where('orden_id', $orden->id)->delete();
        }

        $total = 0;

        foreach ($request->productos as $prod) {
            $productoId = (int) $prod['id'];
            $cantidad = (int) $prod['cantidad'];
            $precio = (float) $prod['precio'];

            $subtotal = $precio * $cantidad;

            OrdenDetalle::create([
                'orden_id' => $orden->id,
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'impreso' => false
            ]);

            $total += $subtotal;
        }

        $orden->update([
            'total' => $total
        ]);

        return response()->json([
            'ok' => true,
            'orden_id' => $orden->id
        ]);
    }

    public function mesa($mesa)
    {
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
                    'cantidad' => 0
                ];
            }

            $agrupados[$productoId]['cantidad'] += (int) $det->cantidad;
        }

        return response()->json(array_values($agrupados));
    }

    public function cerrar(Request $request)
    {
        $orden = Orden::where('mesa_id', $request->mesa)
            ->where('estado', 'abierta')
            ->first();

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay una orden abierta para esta mesa'
            ], 404);
        }

        OrdenDetalle::where('orden_id', $orden->id)->delete();

        $total = 0;

        foreach ($request->productos as $prod) {
            $productoId = (int) $prod['id'];
            $cantidad = (int) $prod['cantidad'];
            $precio = (float) $prod['precio'];

            $subtotal = $precio * $cantidad;

            OrdenDetalle::create([
                'orden_id' => $orden->id,
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'impreso' => false
            ]);

            $total += $subtotal;
        }

        $orden->update([
            'total' => $total,
            'estado' => 'pagada'
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Cuenta cerrada correctamente'
        ]);
    }

    public function recuperar(Request $request)
    {
        $abierta = Orden::where('mesa_id', $request->mesa)
            ->where('estado', 'abierta')
            ->first();

        if ($abierta) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe una orden abierta en esta mesa'
            ], 400);
        }

        $orden = Orden::where('mesa_id', $request->mesa)
            ->where('estado', 'pagada')
            ->latest('id')
            ->first();

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay una cuenta anterior para recuperar'
            ], 404);
        }

        $orden->update([
            'estado' => 'abierta'
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Cuenta recuperada correctamente'
        ]);
    }

    public function imprimir($mesa)
    {
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
                    'subtotal' => 0
                ];
            }

            $productos[$productoId]['cantidad'] += (int) $det->cantidad;
            $productos[$productoId]['subtotal'] += ((float) $det->precio * (int) $det->cantidad);
        }

        return view('pos.imprimir', [
            'mesa' => $mesa,
            'orden' => $orden,
            'productos' => array_values($productos)
        ]);
    }
}