<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminGastoController extends Controller
{
    public function index(Request $request): View
    {
        $editarId = $request->integer('editar');

        $gastos = Gasto::query()
            ->orderByDesc('fecha_gasto')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $gastoEditar = null;
        if ($editarId > 0) {
            $gastoEditar = $gastos->firstWhere('id', $editarId)
                ?? Gasto::query()->find($editarId);
        }

        return view('admin.gastos.index', [
            'gastos' => $gastos,
            'gastoEditar' => $gastoEditar,
            'fechaDefault' => now()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fecha_gasto' => ['required', 'date_format:Y-m-d'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'monto' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ], [
            'fecha_gasto.required' => 'La fecha de gasto es obligatoria.',
            'fecha_gasto.date_format' => 'La fecha de gasto no tiene formato valido.',
            'descripcion.required' => 'La descripcion es obligatoria.',
            'descripcion.max' => 'La descripcion no puede exceder 1000 caracteres.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numerico.',
            'monto.min' => 'El monto no puede ser negativo.',
            'monto.max' => 'El monto excede el maximo permitido.',
        ]);

        Gasto::query()->create([
            'fecha_gasto' => $this->parseInputDate((string) $data['fecha_gasto']),
            'descripcion' => trim((string) $data['descripcion']),
            'monto' => round((float) $data['monto'], 2),
            'status' => Gasto::STATUS_ACTIVO,
            'cancelado_at' => null,
        ]);

        return redirect('/admin/gastos')->with('ok', 'Gasto agregado correctamente.');
    }

    public function update(Request $request, Gasto $gasto): RedirectResponse
    {
        if ($gasto->status !== Gasto::STATUS_ACTIVO) {
            return redirect('/admin/gastos?editar=' . $gasto->id)
                ->with('error', 'Solo se pueden actualizar gastos activos.');
        }

        $data = $request->validate([
            'fecha_gasto' => ['required', 'date_format:Y-m-d'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'monto' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ], [
            'fecha_gasto.required' => 'La fecha de gasto es obligatoria.',
            'fecha_gasto.date_format' => 'La fecha de gasto no tiene formato valido.',
            'descripcion.required' => 'La descripcion es obligatoria.',
            'descripcion.max' => 'La descripcion no puede exceder 1000 caracteres.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numerico.',
            'monto.min' => 'El monto no puede ser negativo.',
            'monto.max' => 'El monto excede el maximo permitido.',
        ]);

        $gasto->update([
            'fecha_gasto' => $this->parseInputDate((string) $data['fecha_gasto']),
            'descripcion' => trim((string) $data['descripcion']),
            'monto' => round((float) $data['monto'], 2),
        ]);

        return redirect('/admin/gastos?editar=' . $gasto->id)->with('ok', 'Gasto actualizado correctamente.');
    }

    public function cancel(Gasto $gasto): RedirectResponse
    {
        if ($gasto->status !== Gasto::STATUS_ACTIVO) {
            return redirect('/admin/gastos?editar=' . $gasto->id)
                ->with('error', 'El gasto ya estaba cancelado.');
        }

        $gasto->update([
            'status' => Gasto::STATUS_CANCELADO,
            'cancelado_at' => now(),
        ]);

        return redirect('/admin/gastos')->with('ok', 'Gasto cancelado correctamente.');
    }

    private function parseInputDate(string $value): Carbon
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');
        return Carbon::createFromFormat('Y-m-d', $value, $timezone)->startOfDay();
    }
}
