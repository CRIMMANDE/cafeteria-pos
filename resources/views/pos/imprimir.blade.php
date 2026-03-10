<!DOCTYPE html>
<html>
<head>
    <title>Cuenta Mesa {{ $mesa }}</title>
    <style>
        body{
            font-family:Arial, sans-serif;
            padding:30px;
            max-width:700px;
            margin:0 auto;
        }

        h1,h2{
            text-align:center;
            margin:0;
        }

        .info{
            margin-top:20px;
            margin-bottom:20px;
            font-size:16px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        th, td{
            border-bottom:1px solid #ddd;
            padding:10px;
            text-align:left;
        }

        .text-right{
            text-align:right;
        }

        .total{
            margin-top:20px;
            font-size:22px;
            font-weight:bold;
            text-align:right;
        }

        .acciones{
            margin-top:30px;
            text-align:center;
        }

        button, a{
            display:inline-block;
            margin:0 10px;
            padding:12px 18px;
            border:none;
            border-radius:8px;
            text-decoration:none;
            font-size:16px;
            cursor:pointer;
        }

        .btn-print{
            background:#2c3e50;
            color:white;
        }

        .btn-back{
            background:#3498db;
            color:white;
        }

        @media print{
            .acciones{
                display:none;
            }

            body{
                padding:0;
            }
        }
    </style>
</head>
<body>

    <h1>CAFETERÍA</h1>
    <h2>Cuenta de mesa {{ $mesa }}</h2>

    <div class="info">
        <div><strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}</div>
        <div><strong>Orden:</strong> #{{ $orden->id }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $producto)
                <tr>
                    <td>{{ $producto['nombre'] }}</td>
                    <td class="text-right">{{ $producto['cantidad'] }}</td>
                    <td class="text-right">$ {{ number_format($producto['precio'], 2) }}</td>
                    <td class="text-right">$ {{ number_format($producto['subtotal'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        TOTAL: $ {{ number_format($orden->total, 2) }}
    </div>

    <div class="acciones">
        <button class="btn-print" onclick="window.print()">Imprimir</button>
        <a href="/pos/mesa/{{ $mesa }}" class="btn-back">Volver a la mesa</a>
    </div>

</body>
</html>