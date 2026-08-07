<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesas - {{ $sucursal->nombre }}</title>
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
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
            gap:16px;
        }

        .header img{
            height:110px;
        }

        .acciones-superiores{
            display:flex;
            justify-content:flex-end;
            align-items:center;
            gap:12px;
        }

        .btn-recuperar,
        .btn-sucursales{
            border:none;
            border-radius:12px;
            background:#3498db;
            color:white;
            padding:14px 18px;
            font-size:16px;
            font-weight:bold;
            cursor:pointer;
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
            text-decoration:none;
        }

        .btn-sucursales{
            background:#2c3e50;
        }

        .modal-recuperar{
            position:fixed;
            inset:0;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            background:rgba(15,23,42,0.45);
            z-index:1000;
        }

        .modal-recuperar.oculto{
            display:none;
        }

        .modal-recuperar-contenido{
            width:min(100%, 420px);
            padding:24px;
            background:#fff;
            border-radius:16px;
            box-shadow:0 20px 45px rgba(15,23,42,0.25);
            text-align:left;
        }

        .modal-recuperar-contenido h3{
            margin:0 0 8px 0;
            font-size:24px;
            color:#1e293b;
        }

        .modal-recuperar-contenido p{
            margin:0 0 16px 0;
            color:#64748b;
        }

        .modal-recuperar-contenido input{
            width:100%;
            box-sizing:border-box;
            padding:12px 14px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            font-size:16px;
            margin-bottom:18px;
        }

        .modal-botones{
            display:flex;
            gap:12px;
        }

        .modal-botones button{
            flex:1;
            border:none;
            border-radius:10px;
            padding:14px 16px;
            color:white;
            font-size:16px;
            font-weight:bold;
            cursor:pointer;
        }

        .btn-confirmar-recuperar{
            background:#3498db;
        }

        .btn-cancelar-recuperar{
            background:#e74c3c;
        }

        .titulo{
            margin:0 0 20px 0;
            font-size:28px;
            color:#2c3e50;
        }

        .encabezado-panel{
            margin-bottom:20px;
        }

        .encabezado-panel .titulo{
            margin-bottom:4px;
        }

        .subtitulo{
            margin:0;
            color:#64748b;
            font-size:17px;
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

        .mesa.libre.empleados{
            background:#f1c40f;
            color:#2c3e50;
        }

        .mesa.libre.llevar{
            background:#34495e;
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

        .mesa-enlace{
            display:block;
            width:100%;
            padding:0;
            border:none;
            background:transparent;
            cursor:pointer;
            text-align:inherit;
        }

        .modal-referencia-ayuda{
            display:block;
            margin:-8px 0 18px;
            color:#64748b;
            font-size:13px;
        }

        @media (max-width: 900px){
            .mesas{
                grid-template-columns:repeat(2,1fr);
            }
        }

        @media (max-width: 520px){
            .header{
                align-items:flex-start;
                flex-direction:column;
            }

            .acciones-superiores{
                width:100%;
            }

            .btn-recuperar,
            .btn-sucursales{
                flex:1;
                text-align:center;
            }

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
        <div class="acciones-superiores">
            <a class="btn-sucursales" href="{{ route('pos.sucursales.index') }}">Sucursales</a>
            <button class="btn-recuperar" id="abrir-recuperar" type="button">Recuperar cuenta</button>
        </div>
    </div>

    <div class="encabezado-panel">
        <h1 class="titulo">{{ $sucursal->nombre }}</h1>
        <p class="subtitulo">Selecciona una mesa o modalidad</p>
    </div>

    <div class="mesas">

        @foreach($mesas as $mesa)
            <a href="{{ route('pos.mesas.show', ['sucursal' => $sucursal, 'mesa' => $mesa]) }}">
                <div class="mesa {{ in_array($mesa->id, $ocupadas, true) ? 'ocupada' : 'libre' }}">
                    <div>Mesa {{ $mesa->numero }}</div>
                    <div class="estado">
                        {{ in_array($mesa->id, $ocupadas, true) ? 'Ocupada' : 'Libre' }}
                    </div>
                </div>
            </a>
        @endforeach

        @if($llevarTieneOrdenAbierta)
            <a href="{{ route('pos.llevar.show', ['sucursal' => $sucursal]) }}">
                <div class="mesa llevar ocupada">
                    <div>P/LLEVAR</div>
                    <div class="estado">Ocupada</div>
                </div>
            </a>
        @else
            <button class="mesa-enlace" id="abrir-referencia-llevar" type="button">
                <div class="mesa llevar libre">
                    <div>P/LLEVAR</div>
                    <div class="estado">Libre</div>
                </div>
            </button>
        @endif

        <a href="{{ route('pos.empleados.show', ['sucursal' => $sucursal]) }}">
            <div class="mesa empleados {{ in_array($empleados->id, $ocupadas, true) ? 'ocupada' : 'libre' }}">
                <div>EMPLEADOS</div>
                <div class="estado">
                    {{ in_array($empleados->id, $ocupadas, true) ? 'Ocupada' : 'Libre' }}
                </div>
            </div>
        </a>

    </div>

    <div class="modal-recuperar oculto" id="modal-recuperar">
        <div class="modal-recuperar-contenido">
            <h3>Recuperar cuenta</h3>
            <p>Captura el folio para reabrir la cuenta en su contexto original.</p>
            <input type="text" id="folio-recuperar" placeholder="Folio de la orden">
            <div class="modal-botones">
                <button class="btn-confirmar-recuperar" id="confirmar-recuperar" type="button">Recuperar</button>
                <button class="btn-cancelar-recuperar" id="cancelar-recuperar" type="button">Cancelar</button>
            </div>
        </div>
    </div>

    @unless($llevarTieneOrdenAbierta)
        <div class="modal-recuperar oculto" id="modal-referencia-llevar">
            <form class="modal-recuperar-contenido" method="POST" action="{{ route('pos.llevar.start', ['sucursal' => $sucursal]) }}">
                @csrf
                <h3>Pedido para llevar</h3>
                <p>Agrega una referencia para identificar el pedido, si la necesitas.</p>
                <label for="referencia-llevar">Referencia (opcional)</label>
                <input
                    type="text"
                    id="referencia-llevar"
                    name="referencia"
                    maxlength="150"
                    placeholder="Nombre, calle, número, etc."
                    autocomplete="off"
                >
                <small class="modal-referencia-ayuda">Máximo 150 caracteres</small>
                <div class="modal-botones">
                    <button class="btn-cancelar-recuperar" id="cancelar-referencia-llevar" type="button">Cancelar</button>
                    <button class="btn-confirmar-recuperar" type="submit">Continuar</button>
                </div>
            </form>
        </div>
    @endunless

    <script>
        const modalRecuperar = document.getElementById('modal-recuperar');
        const btnAbrirRecuperar = document.getElementById('abrir-recuperar');
        const btnConfirmarRecuperar = document.getElementById('confirmar-recuperar');
        const btnCancelarRecuperar = document.getElementById('cancelar-recuperar');
        const inputFolioRecuperar = document.getElementById('folio-recuperar');

        const modalReferenciaLlevar = document.getElementById('modal-referencia-llevar');
        const btnAbrirReferenciaLlevar = document.getElementById('abrir-referencia-llevar');
        const btnCancelarReferenciaLlevar = document.getElementById('cancelar-referencia-llevar');
        const inputReferenciaLlevar = document.getElementById('referencia-llevar');

        if (btnAbrirReferenciaLlevar) {
            btnAbrirReferenciaLlevar.addEventListener('click', function(){
                inputReferenciaLlevar.value = '';
                modalReferenciaLlevar.classList.remove('oculto');
                inputReferenciaLlevar.focus();
            });

            btnCancelarReferenciaLlevar.addEventListener('click', function(){
                modalReferenciaLlevar.classList.add('oculto');
            });
        }

        btnAbrirRecuperar.addEventListener('click', function(){
            inputFolioRecuperar.value = '';
            modalRecuperar.classList.remove('oculto');
            inputFolioRecuperar.focus();
        });

        btnCancelarRecuperar.addEventListener('click', function(){
            modalRecuperar.classList.add('oculto');
        });

        btnConfirmarRecuperar.addEventListener('click', function(){
            const folio = parseInt(inputFolioRecuperar.value, 10);

            if (!folio || folio <= 0) {
                alert('Captura un folio valido');
                return;
            }

            fetch(@json(route('pos.orden.recover', ['sucursal' => $sucursal])), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    folio: folio
                })
            })
            .then(async res => {
                const texto = await res.text();
                console.log('Respuesta recuperar:', texto);

                if (!res.ok) {
                    throw new Error(texto);
                }

                return JSON.parse(texto);
            })
            .then(data => {
                modalRecuperar.classList.add('oculto');
                window.location.href = data.redirect_url;
            })
            .catch(error => {
                console.error('Error real al recuperar:', error);

                try {
                    const respuesta = JSON.parse(error.message);
                    alert(respuesta.message || 'No se pudo recuperar la cuenta');
                } catch (_) {
                    alert('No se pudo recuperar la cuenta');
                }
            });
        });
    </script>

</body>
</html>
