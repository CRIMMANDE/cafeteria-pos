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

    private function renderOrdenView(int $mesa)
    {
        $productos = Producto::all();
        $categorias = Categoria::all();
        $esParaLlevar = Mesa::isTakeaway($mesa);

        $ordenAbierta = Orden::where('mesa_id', $mesa)
            ->where('estado','abierta')
            ->first();

        $ordenPagada = Orden::where('mesa_id',$mesa)
            ->where('estado','pagada')
            ->latest('id')
            ->first();

        return view('pos.orden',[
            'mesa'=>$mesa,
            'mesaLabel' => $esParaLlevar ? 'P/LLEVAR' : 'Mesa ' . $mesa,
            'esParaLlevar' => $esParaLlevar,
            'productos'=>$productos,
            'categorias'=>$categorias,
            'puedeRecuperar'=> !$ordenAbierta && $ordenPagada
        ]);
    }
}
