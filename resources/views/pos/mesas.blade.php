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

        .btn-recuperar{
            border:none;
            border-radius:12px;
            background:#3498db;
            color:white;
            padding:14px 18px;
            font-size:16px;
            font-weight:bold;
            cursor:pointer;
            box-shadow:0 6px 14px rgba(0,0,0,0.10);
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
        <div class="acciones-superiores">
            <button class="btn-recuperar" id="abrir-recuperar" type="button">Recuperar cuenta</button>
        </div>
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
            <div class="mesa llevar {{ in_array(\App\Models\Mesa::TAKEAWAY_ID, $ocupadas) ? 'ocupada' : 'libre' }}">
                <div>P/LLEVAR</div>
                <div class="estado">
                    {{ in_array(\App\Models\Mesa::TAKEAWAY_ID, $ocupadas) ? 'Ocupada' : 'Libre' }}
                </div>
            </div>
        </a>

        <a href="/pos/empleados">
            <div class="mesa empleados {{ in_array(\App\Models\Mesa::EMPLOYEE_ID, $ocupadas) ? 'ocupada' : 'libre' }}">
                <div>EMPLEADOS</div>
                <div class="estado">
                    {{ in_array(\App\Models\Mesa::EMPLOYEE_ID, $ocupadas) ? 'Ocupada' : 'Libre' }}
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

    <script>
        const modalRecuperar = document.getElementById('modal-recuperar');
        const btnAbrirRecuperar = document.getElementById('abrir-recuperar');
        const btnConfirmarRecuperar = document.getElementById('confirmar-recuperar');
        const btnCancelarRecuperar = document.getElementById('cancelar-recuperar');
        const inputFolioRecuperar = document.getElementById('folio-recuperar');

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

            fetch('/orden/recuperar', {
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
