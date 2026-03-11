<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda {{ $areaTitulo }} {{ $mesaLabel }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
        }

        body {
            width: 80mm;
            margin: 0 auto;
            text-align: center;
        }

        .ticket {
            width: 80mm;
            display: inline-block;
            padding: 4mm 3mm 2mm 3mm;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .separator {
            margin: 6px 0;
            white-space: pre;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <div><strong>COMANDA {{ $areaTitulo }}</strong></div>
            <div>{{ $mesaLabel }}</div>
            <div>Orden #{{ $orden->id }}</div>
            <div>{{ $orden->updated_at?->format('Y-m-d H:i') }}</div>
        </div>

        <div class="separator">--------------------------------</div>

        @foreach($items as $item)
            @if($item->producto)
                <div>{{ (int) $item->cantidad }} {{ $item->producto->nombre }}</div>
            @endif
        @endforeach

        <div class="separator">--------------------------------</div>
        <div class="center">FIN DE COMANDA</div>
        <div class="separator">--------------------------------</div>
    </div>

    <script>
        window.onafterprint = () => window.close();
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 150);
        });
    </script>
</body>
</html>
