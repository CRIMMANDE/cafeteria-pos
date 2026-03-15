<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Orden;

class MesaController extends Controller
{
    public function index()
    {
        Mesa::ensureTakeawayMesa();
        Mesa::ensureEmployeeMesa();

        $mesas = range(1,10);

        $ocupadas = Orden::where('estado','abierta')
            ->pluck('mesa_id')
            ->map(fn ($mesaId) => (int) $mesaId)
            ->toArray();

        return view('pos.mesas', compact('mesas', 'ocupadas'));
    }
}
