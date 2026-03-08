<!DOCTYPE html>
<html>
<head>

<title>POS Cafetería</title>

<style>

body{
font-family:Arial;
background:#f4f4f4;
padding:40px;
}

.grid{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
}

.mesa{
padding:40px;
text-align:center;
background:white;
border-radius:12px;
font-size:22px;
box-shadow:0 4px 10px rgba(0,0,0,0.1);
cursor:pointer;
transition:0.2s;
}

.mesa:hover{
transform:scale(1.05);
}

</style>

</head>

<body>

<h1>Mesas</h1>

<div class="grid">

@foreach($mesas as $mesa)

<a href="/orden/{{ $mesa->id }}" style="text-decoration:none;color:black;">

<div class="mesa">

Mesa {{ $mesa->numero }}

</div>

</a>

@endforeach

</div>

</body>
</html>