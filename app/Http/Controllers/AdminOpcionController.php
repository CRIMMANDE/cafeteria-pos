<?php

namespace App\Http\Controllers;

use App\Models\GrupoOpcion;
use App\Models\Opcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOpcionController extends Controller
{
    public function index(): View
    {
        return view('admin.opciones.index', [
            'opciones' => Opcion::query()->with('grupoOpcion.producto')->orderBy('nombre')->get(),
            'grupos' => GrupoOpcion::query()->with('producto')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'grupo_opcion_id' => ['required', 'exists:grupos_opciones,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'incremento_precio' => ['required', 'numeric', 'min:0'],
            'incremento_costo' => ['required', 'numeric', 'min:0'],
            'codigo_corto' => ['nullable', 'string', 'max:50'],
            'activo' => ['nullable', 'boolean'],
        ]);

        Opcion::create([
            'grupo_opcion_id' => (int) $data['grupo_opcion_id'],
            'nombre' => trim($data['nombre']),
            'incremento_precio' => (float) $data['incremento_precio'],
            'incremento_costo' => (float) $data['incremento_costo'],
            'codigo_corto' => $data['codigo_corto'] ? trim($data['codigo_corto']) : null,
            'activo' => (bool) ($data['activo'] ?? true),
        ]);

        return redirect('/admin/opciones')->with('ok', 'Opcion creada correctamente.');
    }

    public function update(Request $request, Opcion $opcion): RedirectResponse
    {
        $data = $request->validate([
            'grupo_opcion_id' => ['required', 'exists:grupos_opciones,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'incremento_precio' => ['required', 'numeric', 'min:0'],
            'incremento_costo' => ['required', 'numeric', 'min:0'],
            'codigo_corto' => ['nullable', 'string', 'max:50'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $opcion->update([
            'grupo_opcion_id' => (int) $data['grupo_opcion_id'],
            'nombre' => trim($data['nombre']),
            'incremento_precio' => (float) $data['incremento_precio'],
            'incremento_costo' => (float) $data['incremento_costo'],
            'codigo_corto' => $data['codigo_corto'] ? trim($data['codigo_corto']) : null,
            'activo' => (bool) ($data['activo'] ?? false),
        ]);

        return redirect('/admin/opciones')->with('ok', 'Opcion actualizada.');
    }

    public function toggle(Opcion $opcion): RedirectResponse
    {
        $opcion->update(['activo' => !$opcion->activo]);

        return redirect('/admin/opciones')->with('ok', 'Estado actualizado.');
    }
}
