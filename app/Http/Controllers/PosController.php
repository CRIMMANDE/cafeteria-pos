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
        $productos = Producto::all();
        $categorias = Categoria::all();

        $ordenAbierta = Orden::where('mesa_id', $mesa)
            ->where('estado','abierta')
            ->first();

        $ordenPagada = Orden::where('mesa_id',$mesa)
            ->where('estado','pagada')
            ->latest('id')
            ->first();

        return view('pos.orden',[
            'mesa'=>$mesa,
            'productos'=>$productos,
            'categorias'=>$categorias,
            'puedeRecuperar'=> !$ordenAbierta && $ordenPagada
        ]);
    }
}