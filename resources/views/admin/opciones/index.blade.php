@extends('admin.layout')

@section('title', 'Opciones')
@section('subtitle', 'Gestiona incrementos, codigo corto, grupo y disponibilidad de cada opcion.')

@section('content')
<div class="grid">
    <div class="card">
        <h2>Nueva opcion</h2>
        <form method="POST" action="/admin/opciones">
            @csrf
            <label>Grupo
                <select name="grupo_opcion_id" required>
                    @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}">{{ $grupo->producto?->nombre }} / {{ $grupo->nombre }}</option>
                    @endforeach
                </select>
            </label>
            <label>Nombre<input type="text" name="nombre" required></label>
            <label>Incremento precio<input type="number" name="incremento_precio" min="0" step="0.01" value="0" required></label>
            <label>Incremento costo<input type="number" name="incremento_costo" min="0" step="0.01" value="0" required></label>
            <label>Codigo corto<input type="text" name="codigo_corto"></label>
            <label class="checkbox"><input type="checkbox" name="activo" value="1" checked> Activa</label>
            <button class="btn-primary" type="submit">Crear opcion</button>
        </form>
    </div>

    <div class="card">
        <h2>Listado</h2>
        <table>
            <thead>
                <tr><th>Opcion</th><th>Grupo</th><th>Estado</th><th>Editar</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($opciones as $opcion)
                    <tr>
                        <td>
                            <strong>{{ $opcion->nombre }}</strong><br>
                            <small>Precio +${{ number_format($opcion->incremento_precio, 2) }} · Costo +${{ number_format($opcion->incremento_costo, 2) }} · Codigo {{ $opcion->codigo_corto ?: '-' }}</small>
                        </td>
                        <td>{{ $opcion->grupoOpcion?->producto?->nombre }} / {{ $opcion->grupoOpcion?->nombre }}</td>
                        <td><span class="pill {{ $opcion->activo ? 'on' : 'off' }}">{{ $opcion->activo ? 'Activa' : 'Inactiva' }}</span></td>
                        <td style="min-width:760px;">
                            <form method="POST" action="/admin/opciones/{{ $opcion->id }}" class="row-form">
                                @csrf
                                @method('PUT')
                                <label>Grupo
                                    <select name="grupo_opcion_id" required>
                                        @foreach($grupos as $grupo)
                                            <option value="{{ $grupo->id }}" @selected($opcion->grupo_opcion_id === $grupo->id)>{{ $grupo->producto?->nombre }} / {{ $grupo->nombre }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Nombre<input type="text" name="nombre" value="{{ $opcion->nombre }}" required></label>
                                <label>Precio<input type="number" name="incremento_precio" min="0" step="0.01" value="{{ number_format($opcion->incremento_precio, 2, '.', '') }}" required></label>
                                <label>Costo<input type="number" name="incremento_costo" min="0" step="0.01" value="{{ number_format($opcion->incremento_costo, 2, '.', '') }}" required></label>
                                <label>Codigo<input type="text" name="codigo_corto" value="{{ $opcion->codigo_corto }}"></label>
                                <label class="checkbox"><input type="checkbox" name="activo" value="1" @checked($opcion->activo)> Activa</label>
                                <button class="btn-secondary" type="submit">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="/admin/opciones/{{ $opcion->id }}/toggle">
                                @csrf
                                <button class="btn-secondary" type="submit">{{ $opcion->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
