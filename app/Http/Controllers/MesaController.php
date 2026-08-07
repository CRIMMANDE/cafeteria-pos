<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Sucursal;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class MesaController extends Controller
{
    public function sucursales()
    {
        $sucursales = Sucursal::query()
            ->where('activa', true)
            ->orderBy('nombre')
            ->orderBy('codigo')
            ->get();

        return view('pos.sucursales', compact('sucursales'));
    }

    public function index(Sucursal $sucursal)
    {
        abort_unless($sucursal->activa, 404);

        $mesas = Mesa::query()
            ->forSucursal($sucursal)
            ->where('tipo', 'mesa')
            ->orderBy('numero')
            ->orderBy('id')
            ->get();

        $llevar = Mesa::specialForSucursal($sucursal, 'llevar');
        $empleados = Mesa::specialForSucursal($sucursal, 'empleados');

        if ($llevar === null || $empleados === null) {
            throw new ConflictHttpException('Falta una pseudomesa requerida para esta sucursal.');
        }

        $mesaIds = $mesas->pluck('id')->push($llevar->id)->push($empleados->id);
        $ordenesAbiertas = Orden::query()
            ->forSucursal($sucursal)
            ->where('estado', 'abierta')
            ->whereIn('mesa_id', $mesaIds)
            ->orderBy('id')
            ->get(['id', 'mesa_id']);

        if ($ordenesAbiertas->groupBy('mesa_id')->contains(fn ($ordenes) => $ordenes->count() > 1)) {
            throw new ConflictHttpException('Existen varias órdenes abiertas para una mesa de esta sucursal.');
        }

        $ocupadas = $ordenesAbiertas
            ->pluck('mesa_id')
            ->map(fn ($mesaId) => (int) $mesaId)
            ->toArray();

        $llevarTieneOrdenAbierta = in_array($llevar->id, $ocupadas, true);

        return view('pos.mesas', compact(
            'sucursal',
            'mesas',
            'llevar',
            'empleados',
            'ocupadas',
            'llevarTieneOrdenAbierta'
        ));
    }
}
