<?php

namespace App\Http\Controllers;

use App\Models\MenuDiaOpcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMenuDiaController extends Controller
{
    public function index(Request $request): View
    {
        $fecha = $request->input('fecha', now()->toDateString());

        $opciones = MenuDiaOpcion::query()
            ->forDate($fecha)
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        return view('admin.menu-dia.index', [
            'fecha' => $fecha,
            'tipos' => [
                'comida_tercer_tiempo' => 'Comida - tercer tiempo',
            ],
            'opciones' => $opciones,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'string', 'max:100'],
            'nombre' => ['required', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
        ]);

        MenuDiaOpcion::create([
            'tipo' => $data['tipo'],
            'nombre' => trim($data['nombre']),
            'fecha' => $data['fecha'],
            'activo' => true,
        ]);

        return redirect('/admin/menu-dia?fecha=' . $data['fecha'])->with('ok', 'Opcion agregada correctamente.');
    }

    public function toggle(MenuDiaOpcion $menuDiaOpcion): RedirectResponse
    {
        $menuDiaOpcion->update([
            'activo' => !$menuDiaOpcion->activo,
        ]);

        $fecha = $menuDiaOpcion->fecha?->toDateString() ?? now()->toDateString();

        return redirect('/admin/menu-dia?fecha=' . $fecha)->with('ok', 'Estado actualizado.');
    }
}
