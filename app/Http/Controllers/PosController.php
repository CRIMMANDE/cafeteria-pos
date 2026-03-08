<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Categoria;

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

    return view('pos.orden', compact('mesa','productos','categorias'));
}
}