@extends('admin.layout')

@section('title', 'Categorias')
@section('subtitle', 'Administra nombre, area y disponibilidad de categorias.')

@section('content')
<div class="grid">
    <div class="card">
        <h2>Nueva categoria</h2>
        <form method="POST" action="/admin/categorias">
            @csrf
            <label>Nombre<input type="text" name="nombre" required></label>
            <label>Tipo
                <select name="tipo" required>
                    <option value="cocina">Cocina</option>
                    <option value="barra">Barra</option>
                </select>
            </label>
            <label class="checkbox"><input type="checkbox" name="activo" value="1" checked> Activa</label>
            <button class="btn-primary" type="submit">Crear categoria</button>
        </form>
    </div>

    <div class="card">
        <h2>Listado</h2>
        <table>
            <thead>
                <tr><th>Categoria</th><th>Tipo</th><th>Estado</th><th>Editar</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($categorias as $categoria)
                    <tr>
                        <td>{{ $categoria->nombre }}</td>
                        <td>{{ strtoupper($categoria->tipo) }}</td>
                        <td><span class="pill {{ $categoria->activo ? 'on' : 'off' }}">{{ $categoria->activo ? 'Activa' : 'Inactiva' }}</span></td>
                        <td style="min-width:420px;">
                            <form method="POST" action="/admin/categorias/{{ $categoria->id }}" class="row-form compact-4">
                                @csrf
                                @method('PUT')
                                <label>Nombre<input type="text" name="nombre" value="{{ $categoria->nombre }}" required></label>
                                <label>Tipo
                                    <select name="tipo" required>
                                        <option value="cocina" @selected($categoria->tipo === 'cocina')>Cocina</option>
                                        <option value="barra" @selected($categoria->tipo === 'barra')>Barra</option>
                                    </select>
                                </label>
                                <label class="checkbox"><input type="checkbox" name="activo" value="1" @checked($categoria->activo)> Activa</label>
                                <button class="btn-secondary" type="submit">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="/admin/categorias/{{ $categoria->id }}/toggle">
                                @csrf
                                <button class="btn-secondary" type="submit">{{ $categoria->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
