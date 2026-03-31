<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte Gastos</title>
    <style>
        body { margin:0; font-family:Arial, sans-serif; background:#f5efe6; color:#2f241f; }
        .page { max-width: 1100px; margin:0 auto; padding:28px; }
        .hero { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; }
        .hero h1 { margin:0; font-size:32px; }
        .hero p { margin:6px 0 0; color:#6a5449; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; }
        .actions a { display:inline-block; padding:10px 14px; border-radius:10px; background:#2f241f; color:#fff; text-decoration:none; }
        .grid { display:grid; grid-template-columns:360px 1fr; gap:20px; }
        .card { background:#fffdf9; border:1px solid #e7d8ca; border-radius:18px; padding:20px; box-shadow:0 14px 30px rgba(66,43,24,.08); }
        .card h2 { margin-top:0; margin-bottom:16px; }
        label { display:block; margin-bottom:12px; color:#6a5449; font-size:14px; }
        input { width:100%; box-sizing:border-box; margin-top:6px; padding:10px 12px; border:1px solid #d8c0af; border-radius:10px; background:#fff; }
        button { border:0; border-radius:10px; cursor:pointer; font-weight:bold; }
        .btn-primary { width:100%; padding:12px 14px; background:#b2502e; color:#fff; }
        .btn-secondary { width:100%; padding:12px 14px; margin-top:10px; background:#2f241f; color:#fff; }
        .flash { margin-bottom:16px; padding:12px 14px; border-radius:10px; }
        .flash.ok { background:#e3f6e8; color:#1a5b35; }
        .flash.error { background:#fde7e2; color:#8b2f1b; }
        .errors { margin-bottom:16px; padding:12px 14px; border-radius:10px; background:#fde7e2; color:#8b2f1b; }
        .stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
        .stat { padding:12px; border:1px solid #ecdccc; border-radius:12px; background:#fff; }
        .stat .label { color:#7f685d; font-size:12px; text-transform:uppercase; letter-spacing:.03em; }
        .stat .value { margin-top:6px; font-size:22px; font-weight:bold; }
        .summary-head { margin-bottom:14px; color:#6a5449; }
        @media (max-width: 860px) {
            .grid { grid-template-columns:1fr; }
            .hero { flex-direction:column; align-items:stretch; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <div>
                <h1>Corte Gastos</h1>
                <p>Filtra por <strong>fecha_gasto</strong> (solo fecha) y considera solo gastos activos.</p>
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
                <h2>Rango de consulta</h2>
                <form method="POST" action="/admin/corte-gastos/imprimir">
                    @csrf
                    <label>
                        Fecha inicio
                        <input type="date" name="inicio" value="{{ old('inicio', $inicio) }}" required>
                    </label>
                    <label>
                        Fecha fin
                        <input type="date" name="fin" value="{{ old('fin', $fin) }}" required>
                    </label>
                    <button class="btn-primary" type="submit">Imprimir</button>
                    <button class="btn-secondary" type="submit" formaction="/admin/corte-gastos/excel">Excel detallado</button>
                </form>
            </div>

            <div class="card">
                <h2>Resumen</h2>

                @if($resumen)
                    <div class="summary-head">
                        Inicio: <strong>{{ $resumen['inicio']->format('d-m-Y') }}</strong><br>
                        Fin: <strong>{{ $resumen['fin']->format('d-m-Y') }}</strong><br>
                        Gastos activos en rango: <strong>{{ $resumen['gastos_count'] }}</strong>
                    </div>

                    <div class="stats">
                        <div class="stat">
                            <div class="label">Registros</div>
                            <div class="value">{{ $resumen['gastos_count'] }}</div>
                        </div>
                        <div class="stat">
                            <div class="label">Total gastos</div>
                            <div class="value">$ {{ number_format($resumen['total_gastos'], 2) }}</div>
                        </div>
                    </div>
                @else
                    <div style="color:#7a665b;">Captura inicio y fin para calcular el corte de gastos.</div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
