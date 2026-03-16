@extends('admin.layout')

@section('title', 'Productos')
@section('subtitle', 'Gestiona nombre, categoria, precio, costo, modalidades y disponibilidad del catalogo.')

@section('content')
<div class="grid">
    <div class="card">
        <h2>Nuevo producto</h2>
        <form method="POST" action="/admin/productos">
            @csrf
            <label>Nombre<input type="text" name="nombre" required></label>
            <label>Categoria
                <select name="categoria_id" required>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }} ({{ $categoria->tipo }})</option>
                    @endforeach
                </select>
            </label>
            <label>Precio<input type="number" name="precio" min="0" step="0.01" required></label>
            <label>Costo<input type="number" name="costo" min="0" step="0.01" required></label>
            <label>Incremento desayuno<input type="number" name="incremento_desayuno" min="0" step="0.01" value="0"></label>
            <label>Incremento comida<input type="number" name="incremento_comida" min="0" step="0.01" value="0"></label>
            <label class="checkbox"><input type="checkbox" name="permite_solo" value="1" checked> Permite solo</label>
            <label class="checkbox"><input type="checkbox" name="permite_desayuno" value="1"> Permite desayuno</label>
            <label class="checkbox"><input type="checkbox" name="permite_comida" value="1"> Permite comida</label>
            <label class="checkbox"><input type="checkbox" name="es_comida_dia" value="1"> Es comida del dia</label>
            <label class="checkbox"><input type="checkbox" name="activo" value="1" checked> Activo</label>
            <button class="btn-primary" type="submit">Crear producto</button>
        </form>
    </div>

    <div class="card">
        <h2>Catalogo</h2>
        <table>
            <thead>
                <tr><th>Producto</th><th>Categoria</th><th>Estado</th><th>Editar</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($productos as $producto)
                    <tr>
                        <td>
                            <strong>{{ $producto->nombre }}</strong><br>
                            <small>
                                Precio: ${{ number_format($producto->precio, 2) }} � Costo: ${{ number_format($producto->costo, 2) }}<br>
                                Modalidades:
                                {{ $producto->permite_solo ? 'Solo ' : '' }}
                                {{ $producto->permite_desayuno ? 'Desayuno ' : '' }}
                                {{ $producto->permite_comida ? 'Comida ' : '' }}
                                @if($producto->es_comida_dia)
                                    � Comida del dia
                                @endif
                            </small>
                        </td>
                        <td>{{ $producto->categoria?->nombre ?? 'Sin categoria' }}</td>
                        <td><span class="pill {{ $producto->activo ? 'on' : 'off' }}">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td style="min-width:920px;">
                            <form method="POST" action="/admin/productos/{{ $producto->id }}" class="row-form">
                                @csrf
                                @method('PUT')
                                <label>Nombre<input type="text" name="nombre" value="{{ $producto->nombre }}" required></label>
                                <label>Categoria
                                    <select name="categoria_id" required>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" @selected($producto->categoria_id === $categoria->id)>{{ $categoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Precio<input type="number" name="precio" min="0" step="0.01" value="{{ number_format($producto->precio, 2, '.', '') }}" required></label>
                                <label>Costo<input type="number" name="costo" min="0" step="0.01" value="{{ number_format($producto->costo, 2, '.', '') }}" required></label>
                                <label>Inc. desayuno<input type="number" name="incremento_desayuno" min="0" step="0.01" value="{{ number_format($producto->incremento_desayuno ?? 0, 2, '.', '') }}"></label>
                                <label>Inc. comida<input type="number" name="incremento_comida" min="0" step="0.01" value="{{ number_format($producto->incremento_comida ?? 0, 2, '.', '') }}"></label>
                                <label class="checkbox"><input type="checkbox" name="permite_solo" value="1" @checked($producto->permite_solo)> Solo</label>
                                <label class="checkbox"><input type="checkbox" name="permite_desayuno" value="1" @checked($producto->permite_desayuno)> Desayuno</label>
                                <label class="checkbox"><input type="checkbox" name="permite_comida" value="1" @checked($producto->permite_comida)> Comida</label>
                                <label class="checkbox"><input type="checkbox" name="es_comida_dia" value="1" @checked($producto->es_comida_dia)> Comida del dia</label>
                                <label class="checkbox"><input type="checkbox" name="activo" value="1" @checked($producto->activo)> Activo</label>
                                <button class="btn-secondary" type="submit">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="/admin/productos/{{ $producto->id }}/toggle">
                                @csrf
                                <button class="btn-secondary" type="submit">{{ $producto->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
