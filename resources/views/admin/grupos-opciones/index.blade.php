@extends('admin.layout')

@section('title', 'Grupos de Opciones')
@section('subtitle', 'Asigna grupos a productos y define orden, modalidad, obligatoriedad, dependencia y estado.')

@section('content')
<div class="grid">
    <div class="card">
        <h2>Nuevo grupo</h2>
        <form method="POST" action="/admin/grupos-opciones">
            @csrf
            <label>Producto
                <select name="producto_id" required>
                    @foreach($productos as $producto)
                        <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
                    @endforeach
                </select>
            </label>
            <label>Nombre<input type="text" name="nombre" required></label>
            <label>Modalidad
                <select name="modalidad" required>
                    <option value="todas">Todas</option>
                    <option value="solo">Solo</option>
                    <option value="desayuno">Desayuno</option>
                    <option value="comida">Comida</option>
                </select>
            </label>
            <label>Orden<input type="number" name="orden" min="0" value="0" required></label>
            <label>Solo si opcion
                <select name="solo_si_opcion_id">
                    <option value="">Siempre visible</option>
                    @foreach($opciones as $opcion)
                        <option value="{{ $opcion->id }}">{{ $opcion->grupoOpcion?->producto?->nombre }} / {{ $opcion->nombre }}</option>
                    @endforeach
                </select>
            </label>
            <label class="checkbox"><input type="checkbox" name="obligatorio" value="1"> Obligatorio</label>
            <label class="checkbox"><input type="checkbox" name="multiple" value="1"> Multiple</label>
            <label class="checkbox"><input type="checkbox" name="activo" value="1" checked> Activo</label>
            <button class="btn-primary" type="submit">Crear grupo</button>
        </form>
    </div>

    <div class="card">
        <h2>Listado</h2>
        <table>
            <thead>
                <tr><th>Grupo</th><th>Producto</th><th>Estado</th><th>Editar</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($grupos as $grupo)
                    <tr>
                        <td>
                            <strong>{{ $grupo->nombre }}</strong><br>
                            <small>Orden {{ $grupo->orden }} � {{ ucfirst($grupo->modalidad ?? 'todas') }} � {{ $grupo->obligatorio ? 'Obligatorio' : 'Opcional' }} � {{ $grupo->multiple ? 'Multiple' : 'Unica' }}</small>
                        </td>
                        <td>{{ $grupo->producto?->nombre ?? 'Sin producto' }}</td>
                        <td><span class="pill {{ $grupo->activo ? 'on' : 'off' }}">{{ $grupo->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td style="min-width:840px;">
                            <form method="POST" action="/admin/grupos-opciones/{{ $grupo->id }}" class="row-form">
                                @csrf
                                @method('PUT')
                                <label>Producto
                                    <select name="producto_id" required>
                                        @foreach($productos as $producto)
                                            <option value="{{ $producto->id }}" @selected($grupo->producto_id === $producto->id)>{{ $producto->nombre }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Nombre<input type="text" name="nombre" value="{{ $grupo->nombre }}" required></label>
                                <label>Modalidad
                                    <select name="modalidad" required>
                                        <option value="todas" @selected(($grupo->modalidad ?? 'todas') === 'todas')>Todas</option>
                                        <option value="solo" @selected($grupo->modalidad === 'solo')>Solo</option>
                                        <option value="desayuno" @selected($grupo->modalidad === 'desayuno')>Desayuno</option>
                                        <option value="comida" @selected($grupo->modalidad === 'comida')>Comida</option>
                                    </select>
                                </label>
                                <label>Orden<input type="number" name="orden" min="0" value="{{ $grupo->orden }}" required></label>
                                <label>Solo si opcion
                                    <select name="solo_si_opcion_id">
                                        <option value="">Siempre visible</option>
                                        @foreach($opciones as $opcion)
                                            <option value="{{ $opcion->id }}" @selected((int) $grupo->solo_si_opcion_id === (int) $opcion->id)>{{ $opcion->grupoOpcion?->producto?->nombre }} / {{ $opcion->nombre }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="checkbox"><input type="checkbox" name="obligatorio" value="1" @checked($grupo->obligatorio)> Obligatorio</label>
                                <label class="checkbox"><input type="checkbox" name="multiple" value="1" @checked($grupo->multiple)> Multiple</label>
                                <label class="checkbox"><input type="checkbox" name="activo" value="1" @checked($grupo->activo)> Activo</label>
                                <button class="btn-secondary" type="submit">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="/admin/grupos-opciones/{{ $grupo->id }}/toggle">
                                @csrf
                                <button class="btn-secondary" type="submit">{{ $grupo->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
