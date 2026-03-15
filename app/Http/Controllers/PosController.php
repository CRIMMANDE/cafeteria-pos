<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Orden;

class PosController extends Controller
{
    public function mesas()
    {
        $mesas = Mesa::all();

        return view('pos.mesas', compact('mesas'));
    }

    public function orden($mesa)
    {
        return $this->renderOrdenView((int) $mesa);
    }

    public function llevar()
    {
        Mesa::ensureTakeawayMesa();

        return $this->renderOrdenView(Mesa::TAKEAWAY_ID);
    }

    public function empleados()
    {
        Mesa::ensureEmployeeMesa();

        return $this->renderOrdenView(Mesa::EMPLOYEE_ID);
    }

    private function renderOrdenView(int $mesa)
    {
        $esParaLlevar = Mesa::isTakeaway($mesa);
        $esEmpleado = Mesa::isEmployee($mesa);
        $productos = Producto::all()->map(function (Producto $producto) use ($esEmpleado) {
            $producto->precio_venta = $esEmpleado
                ? (float) ($producto->costo ?? 0)
                : (float) $producto->precio;

            return $producto;
        });
        $categorias = Categoria::all();

        $ordenAbierta = Orden::where('mesa_id', $mesa)
            ->where('estado','abierta')
            ->first();

        return view('pos.orden',[
            'mesa'=>$mesa,
            'mesaLabel' => $esEmpleado ? 'EMPLEADOS' : ($esParaLlevar ? 'P/LLEVAR' : 'Mesa ' . $mesa),
            'esParaLlevar' => $esParaLlevar,
            'esEmpleado' => $esEmpleado,
            'productos'=>$productos,
            'categorias'=>$categorias,
            'puedeRecuperar'=> false
        ]);
    }
}
