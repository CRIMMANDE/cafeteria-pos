<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Orden;

class MesaController extends Controller
{
    public function index()
    {

    $mesas = range(1,10);

    $ocupadas = Orden::where('estado','abierta')
    ->pluck('mesa_id')
    ->toArray();

    return view('pos.mesas',compact('mesas','ocupadas'));

    }
}
