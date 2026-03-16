@extends('admin.layout')

@section('title', 'Extras')
@section('subtitle', 'Administra extras del POS sin afectar el historial de ordenes.')

@section('content')
<div class="grid">
    <div class="card">
        <h2>Nuevo extra</h2>
        <form method="POST" action="/admin/extras">
            @csrf
            <label>Nombre<input type="text" name="nombre" required></label>
            <label>Precio<input type="number" name="precio" min="0" step="0.01" required></label>
            <label class="checkbox"><input type="checkbox" name="activo" value="1" checked> Activo</label>
            <button class="btn-primary" type="submit">Crear extra</button>
        </form>
    </div>

    <div class="card">
        <h2>Listado</h2>
        <table>
            <thead>
                <tr><th>Extra</th><th>Estado</th><th>Editar</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($extras as $extra)
                    <tr>
                        <td><strong>{{ $extra->nombre }}</strong><br><small>${{ number_format($extra->precio, 2) }}</small></td>
                        <td><span class="pill {{ $extra->activo ? 'on' : 'off' }}">{{ $extra->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td style="min-width:520px;">
                            <form method="POST" action="/admin/extras/{{ $extra->id }}" class="row-form compact-4">
                                @csrf
                                @method('PUT')
                                <label>Nombre<input type="text" name="nombre" value="{{ $extra->nombre }}" required></label>
                                <label>Precio<input type="number" name="precio" min="0" step="0.01" value="{{ number_format($extra->precio, 2, '.', '') }}" required></label>
                                <label class="checkbox"><input type="checkbox" name="activo" value="1" @checked($extra->activo)> Activo</label>
                                <button class="btn-secondary" type="submit">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="/admin/extras/{{ $extra->id }}/toggle">
                                @csrf
                                <button class="btn-secondary" type="submit">{{ $extra->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
