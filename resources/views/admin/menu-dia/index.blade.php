<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu del dia</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5efe6;
            color: #2f241f;
        }

        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 28px;
        }

        .hero {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .hero h1 {
            margin: 0;
            font-size: 32px;
        }

        .hero p {
            margin: 6px 0 0;
            color: #6a5449;
        }

        .actions a,
        .actions button,
        .actions input,
        .actions select {
            font: inherit;
        }

        .actions a {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            background: #2f241f;
            color: #fff;
            text-decoration: none;
        }

        .grid {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 20px;
        }

        .card {
            background: #fffdf9;
            border: 1px solid #e7d8ca;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 14px 30px rgba(66, 43, 24, 0.08);
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 12px;
            font-size: 14px;
            color: #6a5449;
        }

        input,
        select {
            width: 100%;
            margin-top: 6px;
            padding: 10px 12px;
            border: 1px solid #d8c0af;
            border-radius: 10px;
            background: #fff;
            box-sizing: border-box;
        }

        button {
            border: 0;
            border-radius: 10px;
            cursor: pointer;
        }

        .btn-primary {
            width: 100%;
            padding: 12px 14px;
            background: #b2502e;
            color: #fff;
            font-weight: bold;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 12px;
            margin-bottom: 18px;
        }

        .toolbar form {
            display: flex;
            align-items: end;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #f0e4d8;
            text-align: left;
            vertical-align: middle;
        }

        th {
            font-size: 13px;
            color: #8a6f61;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .pill {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .pill.on {
            background: #d9f3df;
            color: #17633a;
        }

        .pill.off {
            background: #f9ddd7;
            color: #8d3522;
        }

        .btn-toggle {
            padding: 8px 12px;
            background: #2f241f;
            color: #fff;
        }

        .flash {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 10px;
            background: #e3f6e8;
            color: #1a5b35;
        }

        .empty {
            padding: 24px;
            border: 1px dashed #d8c0af;
            border-radius: 14px;
            text-align: center;
            color: #7a665b;
        }

        @media (max-width: 860px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .hero,
            .toolbar,
            .toolbar form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <div>
                <h1>Menu del dia</h1>
                <p>Administra el tercer tiempo de Comida sin tocar codigo.</p>
            </div>
            <div class="actions">
                <a href="/admin">Volver al POS</a>
            </div>
        </div>

        @if(session('ok'))
            <div class="flash">{{ session('ok') }}</div>
        @endif

        <div class="grid">
            <div class="card">
                <h2>Nueva opcion</h2>
                <form method="POST" action="/admin/menu-dia">
                    @csrf
                    <label>
                        Fecha
                        <input type="date" name="fecha" value="{{ $fecha }}" required>
                    </label>
                    <label>
                        Tipo
                        <select name="tipo" required>
                            @foreach($tipos as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        Nombre de opcion
                        <input type="text" name="nombre" placeholder="Ej. Milanesa de pollo" required>
                    </label>
                    <button class="btn-primary" type="submit">Agregar opcion</button>
                </form>
            </div>

            <div class="card">
                <div class="toolbar">
                    <div>
                        <h2 style="margin:0;">Opciones configuradas</h2>
                        <div style="color:#7a665b; margin-top:6px;">Fecha consultada: {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</div>
                    </div>
                    <form method="GET" action="/admin/menu-dia">
                        <label style="margin:0; min-width:220px;">
                            Cambiar fecha
                            <input type="date" name="fecha" value="{{ $fecha }}">
                        </label>
                        <button class="btn-toggle" type="submit">Ver</button>
                    </form>
                </div>

                @if($opciones->isEmpty())
                    <div class="empty">No hay opciones configuradas para esta fecha.</div>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($opciones as $opcion)
                                <tr>
                                    <td>{{ $tipos[$opcion->tipo] ?? $opcion->tipo }}</td>
                                    <td>{{ $opcion->nombre }}</td>
                                    <td>
                                        <span class="pill {{ $opcion->activo ? 'on' : 'off' }}">
                                            {{ $opcion->activo ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="/admin/menu-dia/{{ $opcion->id }}/toggle">
                                            @csrf
                                            <button class="btn-toggle" type="submit">
                                                {{ $opcion->activo ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
