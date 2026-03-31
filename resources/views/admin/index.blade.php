<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administracion</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f5efe6; color: #2f241f; }
        .page { max-width: 980px; margin: 0 auto; padding: 28px; }
        .hero { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; }
        .hero h1 { margin: 0; font-size: 32px; }
        .hero p { margin: 6px 0 0; color:#6a5449; }
        .actions a { display:inline-block; padding:10px 14px; border-radius:10px; background:#2f241f; color:#fff; text-decoration:none; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px; }
        .card { display:block; text-decoration:none; color:#2f241f; background:#fffdf9; border:1px solid #e7d8ca; border-radius:18px; padding:20px; box-shadow:0 14px 30px rgba(66,43,24,.08); }
        .card h2 { margin: 0 0 8px; font-size:22px; }
        .card p { margin:0; color:#705a4f; line-height:1.35; }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <div>
                <h1>Administracion</h1>
                <p>Selecciona la herramienta que necesitas.</p>
            </div>
            <div class="actions">
                <a href="/mesas">Volver al POS</a>
            </div>
        </div>

        <div class="grid">
            <a class="card" href="/admin/menu-dia">
                <h2>Menu del dia</h2>
                <p>Gestiona las opciones activas del tercer tiempo de Comida.</p>
            </a>

            <a class="card" href="/admin/corte-ventas">
                <h2>Corte de ventas</h2>
                <p>Filtra por fecha-hora, imprime corte y exporta Excel detallado.</p>
            </a>

            <a class="card" href="/admin/gastos">
                <h2>Gastos</h2>
                <p>Captura, actualiza o cancela gastos por ID en modulo independiente.</p>
            </a>

            <a class="card" href="/admin/corte-gastos">
                <h2>Corte Gastos</h2>
                <p>Filtra por fecha de gasto, imprime resumen y exporta Excel detallado.</p>
            </a>
        </div>
    </div>
</body>
</html>
