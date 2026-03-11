<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $areaTitulo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 24px;
            background: #f4f6f8;
            color: #1f2937;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .meta {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 14px;
        }

        button,
        a {
            display: inline-block;
            width: 100%;
            margin-top: 8px;
            padding: 10px 12px;
            border: 0;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            font-size: 15px;
            cursor: pointer;
        }

        .btn-print {
            background: #111827;
            color: #fff;
        }

        .btn-view {
            background: #e5e7eb;
            color: #111827;
        }

        .empty {
            background: #fff;
            border-radius: 14px;
            padding: 30px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $areaTitulo }}</h1>
        <a href="/mesas" class="btn-view" style="width:auto;">Volver al POS</a>
    </div>

    @if($ordenes->isEmpty())
        <div class="empty">No hay pedidos activos para {{ $area }}.</div>
    @else
        <div class="grid">
            @foreach($ordenes as $orden)
                <div class="card">
                    <div class="title">{{ $orden['mesa_label'] }}</div>
                    <div class="meta">Orden #{{ $orden['orden_id'] }}</div>
                    <div class="meta">Items en {{ $area }}: {{ $orden['items'] }}</div>
                    <div class="meta">Pendientes sin imprimir: {{ $orden['pendientes'] }}</div>
                    <div class="meta">Actualizado: {{ optional($orden['updated_at'])->format('d/m/Y H:i') }}</div>

                    <button
                        class="btn-print"
                        data-area="{{ $area }}"
                        data-mesa="{{ $orden['mesa_id'] }}"
                    >
                        Reimprimir comanda
                    </button>
                    <a class="btn-view" href="/{{ $area }}/mesa/{{ $orden['mesa_id'] }}/imprimir" target="_blank">
                        Ver vista imprimible
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        document.querySelectorAll('.btn-print').forEach(button => {
            button.addEventListener('click', function(){
                const area = this.dataset.area;
                const mesa = this.dataset.mesa;

                fetch(`/${area}/mesa/${mesa}/reimprimir`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(async res => {
                    const texto = await res.text();

                    if (!res.ok) {
                        throw new Error(texto);
                    }

                    return JSON.parse(texto);
                })
                .then(data => {
                    if (data.printed) {
                        alert('Comanda reenviada a impresión');
                        return;
                    }

                    if (data.fallback_url) {
                        const abrir = confirm((data.message || 'No se pudo imprimir directamente') + "\n\n¿Deseas abrir la vista imprimible?");

                        if (abrir) {
                            window.open(data.fallback_url, '_blank');
                        }
                        return;
                    }

                    alert(data.message || 'No se pudo imprimir la comanda');
                })
                .catch(error => {
                    console.error(error);
                    alert('Error al reimprimir la comanda');
                });
            });
        });
    </script>
</body>
</html>
