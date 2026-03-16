<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCategoriaController extends Controller
{
    public function index(): View
    {
        return view('admin.categorias.index', [
            'categorias' => Categoria::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:cocina,barra'],
            'activo' => ['nullable', 'boolean'],
        ]);

        Categoria::create([
            'nombre' => trim($data['nombre']),
            'tipo' => $data['tipo'],
            'activo' => (bool) ($data['activo'] ?? true),
        ]);

        return redirect('/admin/categorias')->with('ok', 'Categoria creada correctamente.');
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'in:cocina,barra'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $categoria->update([
            'nombre' => trim($data['nombre']),
            'tipo' => $data['tipo'],
            'activo' => (bool) ($data['activo'] ?? false),
        ]);

        return redirect('/admin/categorias')->with('ok', 'Categoria actualizada.');
    }

    public function toggle(Categoria $categoria): RedirectResponse
    {
        $categoria->update(['activo' => !$categoria->activo]);

        return redirect('/admin/categorias')->with('ok', 'Estado actualizado.');
    }
}
