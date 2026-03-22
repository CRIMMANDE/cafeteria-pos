<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket {{ $mesaLabel }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #ffffff; color: #000000; font-family: "Courier New", Courier, monospace; font-size: 12px; line-height: 1.35; }
        body { width: 80mm; margin: 0 auto; }
        .ticket { width: 80mm; display: inline-block; padding: 4mm 3mm 2mm 3mm; margin: 0 auto; }
        .center { text-align: center; }
        .ticket-logo { display: block; max-width: 44mm; width: 100%; height: auto; margin: 0 auto 4px auto; }
        .separator { margin: 6px 0; white-space: pre; overflow: hidden; }
        .meta { margin: 6px 0; }
        .meta div { margin: 1px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { padding: 1px 0; vertical-align: top; }
        .qty { width: 16%; text-align: left; }
        .name { width: 56%; word-wrap: break-word; overflow-wrap: break-word; }
        .price { width: 28%; text-align: right; white-space: nowrap; }
        .detail-line td { padding-top: 0; font-size: 11px; }
        .detail-name { padding-left: 8px; }
        .total-row td { padding-top: 2px; font-weight: bold; }
        .footer { margin-top: 8px; text-align: center; }
        @media screen { body { text-align: center; } }
    </style>
</head>
<body>
    <div class="ticket">
        @php
            $storeAddress = (string) config('impresoras.ventas.store_address', '');
            $storePhone = (string) config('impresoras.ventas.store_phone', '');
        @endphp

        <div class="center">
            <img src="{{ asset('images/bruma.png') }}" alt="Bruma" class="ticket-logo">
            @if($storeAddress !== '')
                <div>{{ $storeAddress }}</div>
            @endif
            @if($storePhone !== '')
                <div>Tel: {{ $storePhone }}</div>
            @endif
        </div>

        <div class="separator">--------------------------------</div>

        <div class="meta">
            <div>Fecha: {{ $orden->created_at ? $orden->created_at->format('Y-m-d') : now()->format('Y-m-d') }}</div>
            <div>Hora: {{ $orden->created_at ? $orden->created_at->format('H:i') : now()->format('H:i') }}</div>
            <div>{{ $esParaLlevar ? 'Tipo' : 'Mesa' }}: {{ $esParaLlevar ? 'P/LLEVAR' : $mesa }}</div>
            <div>Ticket: #{{ $orden->id }}</div>
        </div>

        <div class="separator">--------------------------------</div>

        <table>
            <tbody>
                @foreach($productos as $producto)
                    <tr>
                        <td class="qty">{{ (int) $producto['cantidad'] }}</td>
                        <td class="name">{{ $producto['nombre'] }}</td>
                        <td class="price">${{ number_format($producto['subtotal'], 0) }}</td>
                    </tr>
                    @foreach($producto['detalle_cliente'] ?? [] as $detalle)
                        <tr class="detail-line">
                            <td class="qty"></td>
                            <td class="name detail-name">- {{ $detalle }}</td>
                            <td class="price"></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="separator">--------------------------------</div>

        <table>
            <tbody>
                <tr class="total-row">
                    <td class="qty"></td>
                    <td class="name">TOTAL</td>
                    <td class="price">${{ number_format($orden->total, 0) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="separator">--------------------------------</div>

        <div class="footer">
            <div>!GRACIAS POR SU VISITA!</div>
        </div>

        <div class="separator center">--------------------------------</div>
    </div>

    <script>
        window.onafterprint = () => { window.close(); };
        window.addEventListener('load', () => {
            setTimeout(() => { window.print(); }, 150);
        });
    </script>
</body>
</html>

