<?php

namespace App\Http\Controllers;

use App\Models\Extra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminExtraController extends Controller
{
    public function index(): View
    {
        return view('admin.extras.index', [
            'extras' => Extra::query()->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        Extra::create([
            'nombre' => trim($data['nombre']),
            'precio' => (float) $data['precio'],
            'activo' => (bool) ($data['activo'] ?? true),
        ]);

        return redirect('/admin/extras')->with('ok', 'Extra creado correctamente.');
    }

    public function update(Request $request, Extra $extra): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $extra->update([
            'nombre' => trim($data['nombre']),
            'precio' => (float) $data['precio'],
            'activo' => (bool) ($data['activo'] ?? false),
        ]);

        return redirect('/admin/extras')->with('ok', 'Extra actualizado.');
    }

    public function toggle(Extra $extra): RedirectResponse
    {
        $extra->update(['activo' => !$extra->activo]);

        return redirect('/admin/extras')->with('ok', 'Estado actualizado.');
    }
}
