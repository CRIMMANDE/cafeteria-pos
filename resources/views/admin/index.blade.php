@extends('admin.layout')

@section('title', 'Panel administrativo')
@section('subtitle', 'Selecciona un modulo para gestionar tu catalogo y configuraciones del POS.')

@section('content')
<style>
    .admin-hub {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
    }
    .hub-card {
        display: block;
        text-decoration: none;
        color: #2d211c;
        background: #fffdf9;
        border: 1px solid #e7d8ca;
        border-radius: 16px;
        padding: 18px;
        box-shadow: 0 10px 24px rgba(66, 43, 24, 0.08);
        transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease;
    }
    .hub-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 30px rgba(66, 43, 24, 0.12);
        border-color: #c9a78f;
    }
    .hub-card h3 {
        margin: 0 0 8px;
        font-size: 19px;
    }
    .hub-card p {
        margin: 0;
        color: #6e5a4f;
        line-height: 1.35;
        font-size: 14px;
    }
</style>

<div class="admin-hub">
    <a class="hub-card" href="/admin/productos">
        <h3>Productos</h3>
        <p>Crear, editar, activar o desactivar productos y modalidades.</p>
    </a>

    <a class="hub-card" href="/admin/menu-dia">
        <h3>Menu del dia</h3>
        <p>Configurar opciones dinamicas del dia para comida.</p>
    </a>

    <a class="hub-card" href="/admin/categorias">
        <h3>Categorias</h3>
        <p>Administrar categorias de cocina/barra y estado activo.</p>
    </a>

    <a class="hub-card" href="/admin/grupos-opciones">
        <h3>Grupos de opciones</h3>
        <p>Asignar grupos por producto: salsa, bebida, fruta, tiempos, etc.</p>
    </a>

    <a class="hub-card" href="/admin/opciones">
        <h3>Opciones</h3>
        <p>Configurar opciones por grupo con incrementos y codigo corto.</p>
    </a>

    <a class="hub-card" href="/admin/extras">
        <h3>Extras</h3>
        <p>Gestionar extras disponibles en POS, incluyendo "Otro".</p>
    </a>
</div>
@endsection
