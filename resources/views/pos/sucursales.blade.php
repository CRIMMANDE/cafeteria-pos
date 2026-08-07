<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sucursales</title>
    <style>
        *{box-sizing:border-box;}
        body{
            min-height:100vh;
            margin:0;
            padding:24px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-family:Arial, sans-serif;
            background:#f4f6f8;
            color:#2c3e50;
        }
        .selector{
            width:min(100%, 820px);
            text-align:center;
        }
        .selector img{
            width:auto;
            height:120px;
            max-width:100%;
            object-fit:contain;
        }
        h1{margin:12px 0 8px;font-size:32px;}
        p{margin:0 0 28px;color:#64748b;font-size:18px;}
        .sucursales{
            display:grid;
            grid-template-columns:repeat(2, minmax(0, 1fr));
            gap:22px;
        }
        .sucursal{
            min-height:150px;
            padding:28px 20px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:20px;
            background:#2c3e50;
            color:#fff;
            font-size:30px;
            font-weight:bold;
            text-decoration:none;
            box-shadow:0 8px 20px rgba(15,23,42,0.16);
            transition:transform .15s ease, box-shadow .15s ease;
        }
        .sucursal:hover{
            transform:translateY(-3px);
            box-shadow:0 12px 26px rgba(15,23,42,0.22);
        }
        @media (max-width:600px){
            body{padding:18px;}
            .sucursales{grid-template-columns:1fr;}
            .sucursal{min-height:110px;font-size:25px;}
        }
    </style>
</head>
<body>
    <main class="selector">
        <img src="{{ asset('images/bruma.png') }}" alt="Cafetería Bruma">
        <h1>Selecciona una sucursal</h1>
        <p>Elige dónde se realizará la operación.</p>

        <div class="sucursales">
            @forelse($sucursales as $sucursal)
                <a class="sucursal" href="{{ route('pos.mesas.index', ['sucursal' => $sucursal]) }}">
                    {{ $sucursal->nombre }}
                </a>
            @empty
                <p>No hay sucursales activas disponibles.</p>
            @endforelse
        </div>
    </main>
</body>
</html>
