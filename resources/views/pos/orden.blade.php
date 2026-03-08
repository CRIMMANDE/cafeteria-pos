<!DOCTYPE html>
<html>

<head>

<title>POS</title>

<style>

body{
font-family:Arial;
padding:30px;
}

.buscar{
margin-bottom:20px;
}

input{
padding:10px;
font-size:16px;
width:300px;
}

.categorias{
margin-bottom:20px;
}

.categoria{
display:inline-block;
padding:10px 20px;
background:#eee;
margin-right:10px;
border-radius:8px;
cursor:pointer;
}

.productos{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
}

.producto{
padding:20px;
background:#f4f4f4;
border-radius:10px;
text-align:center;
cursor:pointer;
}

</style>

</head>

<body>

<h1>Mesa {{ $mesa }}</h1>

<div class="buscar">

<input type="text" id="buscar" placeholder="Buscar producto...">

</div>

<div class="categorias">

@foreach($categorias as $categoria)

<div class="categoria" data-id="{{ $categoria->id }}">

{{ $categoria->nombre }}

</div>

@endforeach

</div>

<div class="productos">

@foreach($productos as $producto)

<div class="producto"
data-nombre="{{ strtolower($producto->nombre) }}"
data-categoria="{{ $producto->categoria_id }}">

{{ $producto->nombre }}

<br>

$ {{ $producto->precio }}

</div>

@endforeach

</div>

</body>

</html>

<script>

let categoriaActual = "all";

const buscar = document.getElementById('buscar');
const categorias = document.querySelectorAll('.categoria');
const productos = document.querySelectorAll('.producto');

function filtrarProductos(){

let texto = buscar.value.toLowerCase();

productos.forEach(prod => {

let nombre = prod.getAttribute('data-nombre');
let categoria = prod.getAttribute('data-categoria');

let coincideBusqueda = nombre.includes(texto);
let coincideCategoria = (categoriaActual === "all" || categoria == categoriaActual);

if(coincideBusqueda && coincideCategoria){
prod.style.display = "block";
}else{
prod.style.display = "none";
}

});

}

// búsqueda
buscar.addEventListener('keyup', filtrarProductos);

// categorías
categorias.forEach(cat => {

cat.addEventListener('click', function(){

categoriaActual = this.getAttribute('data-id');

filtrarProductos();

});

});

</script>