<!DOCTYPE html>
<html>
<head>
    <title>Mesas</title>
    <style>
        body{
            font-family:Arial, sans-serif;
            padding:12px 24px 24px 24px;
            text-align:center;
            background:#f4f6f8;
            margin:0;
        }

        .header{
            display:flex;
            justify-content:center;
            align-items:center;
            margin-bottom:20px;
        }

        .header img{
            height:110px;
        }

        .titulo{
            margin:0 0 20px 0;
            font-size:28px;
            color:#2c3e50;
        }

        .mesas{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:20px;
            margin-top:10px;
        }

        .mesa{
            min-height:80px;
            border-radius:18px;
            padding:15px;
            font-size:24px;
            font-weight:bold;
            color:white;
            display:flex;
            flex-direction:column;
            justify-content:center;
            align-items:center;
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
            transition:transform 0.15s ease, box-shadow 0.15s ease;
        }

        .mesa:hover{
            transform:translateY(-3px);
            box-shadow:0 10px 18px rgba(0,0,0,0.14);
        }

        .libre{
            background:#27ae60;
        }

        .ocupada{
            background:#e74c3c;
        }

        .llevar{
            background:#34495e;
        }

        .empleados{
            background:#f1c40f;
            color:#2c3e50;
        }

        .estado{
            margin-top:10px;
            font-size:14px;
            font-weight:normal;
            opacity:0.95;
            background:rgba(255,255,255,0.18);
            padding:6px 10px;
            border-radius:999px;
        }

        a{
            text-decoration:none;
        }

        @media (max-width: 900px){
            .mesas{
                grid-template-columns:repeat(2,1fr);
            }
        }

        @media (max-width: 520px){
            .mesas{
                grid-template-columns:1fr;
            }

            .mesa{
                min-height:100px;
                font-size:20px;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="{{ asset('images/bruma.png') }}" alt="nombre_cafeteria">
    </div>

    <div class="mesas">

        @foreach($mesas as $mesa)
            <a href="/pos/mesa/{{ $mesa }}">
                <div class="mesa {{ in_array($mesa, $ocupadas) ? 'ocupada' : 'libre' }}">
                    <div>Mesa {{ $mesa }}</div>
                    <div class="estado">
                        {{ in_array($mesa, $ocupadas) ? 'Ocupada' : 'Libre' }}
                    </div>
                </div>
            </a>
        @endforeach

        <a href="/pos/llevar">
            <div class="mesa llevar">
                <div>P/LLEVAR</div>
                <div class="estado">Pedido rápido</div>
            </div>
        </a>

        <a href="/pos/empleados">
            <div class="mesa empleados">
                <div>EMPLEADOS</div>
                <div class="estado">Precio por costo</div>
            </div>
        </a>

    </div>

</body>
</html>
