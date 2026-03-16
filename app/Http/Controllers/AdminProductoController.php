<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProductoController extends Controller
{
    public function index(): View
    {
        return view('admin.productos.index', [
            'productos' => Producto::query()->with('categoria')->orderBy('nombre')->get(),
            'categorias' => Categoria::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'permite_solo' => ['nullable', 'boolean'],
            'permite_desayuno' => ['nullable', 'boolean'],
            'permite_comida' => ['nullable', 'boolean'],
            'incremento_desayuno' => ['nullable', 'numeric', 'min:0'],
            'incremento_comida' => ['nullable', 'numeric', 'min:0'],
            'es_comida_dia' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
        ]);

        Producto::create($this->payload($data, true));

        return redirect('/admin/productos')->with('ok', 'Producto creado correctamente.');
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'precio' => ['required', 'numeric', 'min:0'],
            'costo' => ['required', 'numeric', 'min:0'],
            'permite_solo' => ['nullable', 'boolean'],
            'permite_desayuno' => ['nullable', 'boolean'],
            'permite_comida' => ['nullable', 'boolean'],
            'incremento_desayuno' => ['nullable', 'numeric', 'min:0'],
            'incremento_comida' => ['nullable', 'numeric', 'min:0'],
            'es_comida_dia' => ['nullable', 'boolean'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $producto->update($this->payload($data, false));

        return redirect('/admin/productos')->with('ok', 'Producto actualizado.');
    }

    public function toggle(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => !$producto->activo]);

        return redirect('/admin/productos')->with('ok', 'Estado actualizado.');
    }

    private function payload(array $data, bool $defaultActive): array
    {
        return [
            'nombre' => trim($data['nombre']),
            'categoria_id' => (int) $data['categoria_id'],
            'precio' => (float) $data['precio'],
            'costo' => (float) $data['costo'],
            'permite_solo' => (bool) ($data['permite_solo'] ?? false),
            'permite_desayuno' => (bool) ($data['permite_desayuno'] ?? false),
            'permite_comida' => (bool) ($data['permite_comida'] ?? false),
            'incremento_desayuno' => (float) ($data['incremento_desayuno'] ?? 0),
            'incremento_comida' => (float) ($data['incremento_comida'] ?? 0),
            'es_comida_dia' => (bool) ($data['es_comida_dia'] ?? false),
            'activo' => (bool) ($data['activo'] ?? $defaultActive),
        ];
    }
}
