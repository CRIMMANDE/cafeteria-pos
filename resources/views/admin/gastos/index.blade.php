<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gastos</title>
    <style>
        body { margin:0; font-family:Arial, sans-serif; background:#f5efe6; color:#2f241f; }
        .page { max-width: 1200px; margin:0 auto; padding:28px; }
        .hero { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; }
        .hero h1 { margin:0; font-size:32px; }
        .hero p { margin:6px 0 0; color:#6a5449; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; }
        .actions a { display:inline-block; padding:10px 14px; border-radius:10px; background:#2f241f; color:#fff; text-decoration:none; }
        .grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .card { background:#fffdf9; border:1px solid #e7d8ca; border-radius:18px; padding:20px; box-shadow:0 14px 30px rgba(66,43,24,.08); }
        .card h2 { margin-top:0; margin-bottom:16px; }
        .hint { color:#7a665b; margin-top:0; margin-bottom:14px; }
        label { display:block; margin-bottom:12px; color:#6a5449; font-size:14px; }
        input, textarea { width:100%; box-sizing:border-box; margin-top:6px; padding:10px 12px; border:1px solid #d8c0af; border-radius:10px; background:#fff; }
        textarea { min-height: 108px; resize: vertical; }
        button { border:0; border-radius:10px; cursor:pointer; font-weight:bold; }
        .btn-primary { width:100%; padding:12px 14px; background:#b2502e; color:#fff; }
        .btn-secondary { width:100%; padding:12px 14px; background:#2f241f; color:#fff; margin-top:10px; }
        .btn-danger { width:100%; padding:12px 14px; background:#8f3121; color:#fff; margin-top:10px; }
        .flash { margin-bottom:16px; padding:12px 14px; border-radius:10px; }
        .flash.ok { background:#e3f6e8; color:#1a5b35; }
        .flash.error { background:#fde7e2; color:#8b2f1b; }
        .errors { margin-bottom:16px; padding:12px 14px; border-radius:10px; background:#fde7e2; color:#8b2f1b; }
        .table-card { margin-top:20px; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding:12px 10px; border-bottom:1px solid #f0e4d8; text-align:left; vertical-align:top; }
        th { font-size:13px; color:#8a6f61; text-transform:uppercase; letter-spacing:.04em; }
        .pill { display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:bold; }
        .pill.on { background:#d9f3df; color:#17633a; }
        .pill.off { background:#f9ddd7; color:#8d3522; }
        .row-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .row-actions a, .row-actions button { font-size:12px; border-radius:8px; padding:7px 10px; text-decoration:none; border:0; cursor:pointer; }
        .btn-link { background:#2f241f; color:#fff; }
        .btn-cancel-row { background:#8f3121; color:#fff; }
        .empty { padding:24px; border:1px dashed #d8c0af; border-radius:14px; text-align:center; color:#7a665b; }
        @media (max-width: 960px) {
            .grid { grid-template-columns:1fr; }
            .hero { flex-direction:column; align-items:stretch; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <div>
                <h1>Gastos</h1>
                <p>Modulo aislado para alta, actualizacion y cancelacion por ID.</p>
            </div>
            <div class="actions">
                <a href="/admin/gastos">Gastos</a>
                <a href="/admin/corte-gastos">Corte Gastos</a>
                <a href="/admin/menu-dia">Menu del dia</a>
                <a href="/admin/corte-ventas">Corte de ventas</a>
                <a href="/admin">Panel admin</a>
                <a href="/mesas">Volver al POS</a>
            </div>
        </div>

        @if(session('ok'))
            <div class="flash ok">{{ session('ok') }}</div>
        @endif

        @if(session('error'))
            <div class="flash error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="errors">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid">
            <div class="card">
                <h2>Agregar gasto</h2>
                <p class="hint">Al crear, el gasto queda en estado activo y se identifica por ID.</p>
                <form method="POST" action="/admin/gastos">
                    @csrf
                    <label>
                        Fecha de gasto
                        <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', $fechaDefault) }}" required>
                    </label>
                    <label>
                        Descripcion
                        <textarea name="descripcion" placeholder="Ej. Compra de insumos" required>{{ old('descripcion') }}</textarea>
                    </label>
                    <label>
                        Monto
                        <input type="number" step="0.01" min="0" name="monto" value="{{ old('monto') }}" required>
                    </label>
                    <button class="btn-primary" type="submit">Agregar gasto</button>
                </form>
            </div>

            <div class="card">
                <h2>Actualizar / Cancelar gasto</h2>
                @if($gastoEditar)
                    @php
                        $fechaEditar = $gastoEditar->fecha_gasto ? $gastoEditar->fecha_gasto->format('Y-m-d') : $fechaDefault;
                    @endphp

                    <p class="hint">ID seleccionado: <strong>{{ $gastoEditar->id }}</strong></p>

                    @if($gastoEditar->status === 'activo')
                        <form method="POST" action="/admin/gastos/{{ $gastoEditar->id }}/actualizar">
                            @csrf
                            <label>
                                ID (no editable)
                                <input type="text" value="{{ $gastoEditar->id }}" readonly>
                            </label>
                            <label>
                                Fecha de gasto
                                <input type="date" name="fecha_gasto" value="{{ old('fecha_gasto', $fechaEditar) }}" required>
                            </label>
                            <label>
                                Descripcion
                                <textarea name="descripcion" required>{{ old('descripcion', $gastoEditar->descripcion) }}</textarea>
                            </label>
                            <label>
                                Monto
                                <input type="number" step="0.01" min="0" name="monto" value="{{ old('monto', number_format((float) $gastoEditar->monto, 2, '.', '')) }}" required>
                            </label>
                            <button class="btn-secondary" type="submit">Actualizar gasto</button>
                        </form>

                        <form method="POST" action="/admin/gastos/{{ $gastoEditar->id }}/cancelar" onsubmit="return confirm('Se cancelara el gasto ID {{ $gastoEditar->id }}. Continuar?');">
                            @csrf
                            <button class="btn-danger" type="submit">Cancelar gasto</button>
                        </form>
                    @else
                        <div class="flash error" style="margin-bottom:0;">Este gasto ya esta cancelado y no puede editarse.</div>
                    @endif
                @else
                    <div class="empty">Selecciona un gasto desde la tabla para editar/cancelar por ID.</div>
                @endif
            </div>
        </div>

        <div class="card table-card">
            <h2>Registros de gastos (ultimos 200)</h2>

            @if($gastos->isEmpty())
                <div class="empty">No hay gastos registrados.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha gasto</th>
                            <th>Descripcion</th>
                            <th>Monto</th>
                            <th>Status</th>
                            <th>Cancelado at</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gastos as $gasto)
                            <tr>
                                <td>{{ $gasto->id }}</td>
                                <td>{{ optional($gasto->fecha_gasto)->format('d-m-Y') }}</td>
                                <td>{{ $gasto->descripcion }}</td>
                                <td>$ {{ number_format((float) $gasto->monto, 2) }}</td>
                                <td>
                                    <span class="pill {{ $gasto->status === 'activo' ? 'on' : 'off' }}">
                                        {{ $gasto->status }}
                                    </span>
                                </td>
                                <td>{{ optional($gasto->cancelado_at)->format('d-m-Y H:i:s') ?: '-' }}</td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn-link" href="/admin/gastos?editar={{ $gasto->id }}">Editar ID {{ $gasto->id }}</a>
                                        @if($gasto->status === 'activo')
                                            <form method="POST" action="/admin/gastos/{{ $gasto->id }}/cancelar" onsubmit="return confirm('Se cancelara el gasto ID {{ $gasto->id }}. Continuar?');">
                                                @csrf
                                                <button class="btn-cancel-row" type="submit">Cancelar gasto</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</body>
</html>
