<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administracion')</title>
    <style>
        body { margin:0; font-family:Arial, sans-serif; background:#f6efe7; color:#2d211c; }
        .page { max-width:1280px; margin:0 auto; padding:28px; }
        .topbar { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
        .topbar h1 { margin:0; font-size:32px; }
        .topbar p { margin:6px 0 0; color:#6e5a4f; }
        .top-actions a { display:inline-block; padding:10px 14px; border-radius:10px; background:#2d211c; color:#fff; text-decoration:none; }
        .nav { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
        .nav a { padding:10px 14px; border-radius:999px; background:#ead8c8; color:#3c2a23; text-decoration:none; font-weight:bold; }
        .nav a.active { background:#5f3b2b; color:#fff; }
        .flash { margin-bottom:16px; padding:12px 14px; border-radius:10px; background:#dff5e6; color:#16553a; }
        .errors { margin-bottom:16px; padding:12px 14px; border-radius:10px; background:#fde7e2; color:#8b2f1b; }
        .grid { display:grid; grid-template-columns:360px 1fr; gap:20px; }
        .card { background:#fffdf9; border:1px solid #e7d8ca; border-radius:18px; padding:20px; box-shadow:0 14px 30px rgba(66,43,24,0.08); }
        .card h2 { margin:0 0 16px; }
        label { display:block; margin-bottom:12px; color:#6e5a4f; font-size:14px; }
        input, select { width:100%; box-sizing:border-box; margin-top:6px; padding:10px 12px; border:1px solid #d6c1b4; border-radius:10px; background:#fff; }
        .checkbox { display:flex; align-items:center; gap:8px; color:#3c2a23; font-size:14px; }
        .checkbox input { width:auto; margin:0; }
        button { border:0; border-radius:10px; cursor:pointer; }
        .btn-primary { width:100%; padding:12px 14px; background:#b2502e; color:#fff; font-weight:bold; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 10px; border-bottom:1px solid #f0e4d8; text-align:left; vertical-align:top; }
        th { color:#8a6f61; font-size:13px; text-transform:uppercase; }
        .pill { display:inline-block; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:bold; }
        .pill.on { background:#d9f3df; color:#17633a; }
        .pill.off { background:#f9ddd7; color:#8d3522; }
        .btn-secondary { padding:9px 12px; background:#2d211c; color:#fff; }
        .row-form { display:grid; grid-template-columns:repeat(6, minmax(120px, 1fr)); gap:10px; align-items:end; }
        .row-form.compact-5 { grid-template-columns:repeat(5, minmax(120px, 1fr)); }
        .row-form.compact-4 { grid-template-columns:repeat(4, minmax(120px, 1fr)); }
        .row-form .checkbox { margin-bottom:12px; }
        .actions-inline { display:flex; gap:8px; align-items:center; }
        @media (max-width: 1080px) {
            .grid { grid-template-columns:1fr; }
            .row-form, .row-form.compact-5, .row-form.compact-4 { grid-template-columns:1fr; }
            .topbar { flex-direction:column; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div>
                <h1>@yield('title', 'Administracion')</h1>
                <p>@yield('subtitle', 'Gestion del catalogo del POS')</p>
            </div>
            <div class="top-actions">
                <a href="/mesas">Volver al POS</a>
            </div>
        </div>

        <div class="nav">
            <a href="/admin" class="{{ request()->is('admin') ? 'active' : '' }}">Panel</a>
            <a href="/admin/productos" class="{{ request()->is('admin/productos*') ? 'active' : '' }}">Productos</a>
            <a href="/admin/categorias" class="{{ request()->is('admin/categorias*') ? 'active' : '' }}">Categorias</a>
            <a href="/admin/grupos-opciones" class="{{ request()->is('admin/grupos-opciones*') ? 'active' : '' }}">Grupos</a>
            <a href="/admin/opciones" class="{{ request()->is('admin/opciones*') ? 'active' : '' }}">Opciones</a>
            <a href="/admin/extras" class="{{ request()->is('admin/extras*') ? 'active' : '' }}">Extras</a>
            <a href="/admin/menu-dia" class="{{ request()->is('admin/menu-dia*') ? 'active' : '' }}">Menu del dia</a>
        </div>

        @if(session('ok'))
            <div class="flash">{{ session('ok') }}</div>
        @endif

        @if($errors->any())
            <div class="errors">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>
