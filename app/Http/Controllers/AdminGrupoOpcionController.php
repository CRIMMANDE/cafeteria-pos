<?php

namespace App\Http\Controllers;

use App\Models\GrupoOpcion;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminGrupoOpcionController extends Controller
{
    public function index(): View
    {
        return view('admin.grupos-opciones.index', [
            'grupos' => GrupoOpcion::query()->with(['producto', 'opcionPadre'])->orderBy('orden')->orderBy('nombre')->get(),
            'productos' => Producto::query()->orderBy('nombre')->get(),
            'opciones' => Opcion::query()->with('grupoOpcion.producto')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'modalidad' => ['required', 'in:todas,solo,desayuno,comida'],
            'obligatorio' => ['nullable', 'boolean'],
            'multiple' => ['nullable', 'boolean'],
            'orden' => ['required', 'integer', 'min:0'],
            'solo_si_opcion_id' => ['nullable', 'exists:opciones,id'],
            'activo' => ['nullable', 'boolean'],
        ]);

        GrupoOpcion::create([
            'producto_id' => (int) $data['producto_id'],
            'nombre' => trim($data['nombre']),
            'modalidad' => $data['modalidad'],
            'obligatorio' => (bool) ($data['obligatorio'] ?? false),
            'multiple' => (bool) ($data['multiple'] ?? false),
            'orden' => (int) $data['orden'],
            'solo_si_opcion_id' => $data['solo_si_opcion_id'] ?? null,
            'activo' => (bool) ($data['activo'] ?? true),
        ]);

        return redirect('/admin/grupos-opciones')->with('ok', 'Grupo creado correctamente.');
    }

    public function update(Request $request, GrupoOpcion $grupoOpcion): RedirectResponse
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'modalidad' => ['required', 'in:todas,solo,desayuno,comida'],
            'obligatorio' => ['nullable', 'boolean'],
            'multiple' => ['nullable', 'boolean'],
            'orden' => ['required', 'integer', 'min:0'],
            'solo_si_opcion_id' => ['nullable', 'exists:opciones,id'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $grupoOpcion->update([
            'producto_id' => (int) $data['producto_id'],
            'nombre' => trim($data['nombre']),
            'modalidad' => $data['modalidad'],
            'obligatorio' => (bool) ($data['obligatorio'] ?? false),
            'multiple' => (bool) ($data['multiple'] ?? false),
            'orden' => (int) $data['orden'],
            'solo_si_opcion_id' => $data['solo_si_opcion_id'] ?? null,
            'activo' => (bool) ($data['activo'] ?? false),
        ]);

        return redirect('/admin/grupos-opciones')->with('ok', 'Grupo actualizado.');
    }

    public function toggle(GrupoOpcion $grupoOpcion): RedirectResponse
    {
        $grupoOpcion->update(['activo' => !$grupoOpcion->activo]);

        return redirect('/admin/grupos-opciones')->with('ok', 'Estado actualizado.');
    }
}
