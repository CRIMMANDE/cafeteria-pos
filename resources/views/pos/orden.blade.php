<!DOCTYPE html>
<html>

<head>
    <title>POS</title>

    <style>
        body{
            font-family:Arial;
            padding:30px;
        }

        .controles-cantidad{
            display:flex;
            align-items:center;
            gap:8px;
        }

        .btn-cantidad{
            width:28px;
            height:28px;
            border:none;
            border-radius:6px;
            background:#ddd;
            cursor:pointer;
            font-weight:bold;
            font-size:16px;
            padding:0;
            line-height:28px;
            text-align:center;
            color:#333;
        }

        .cantidad-numero{
            min-width:20px;
            text-align:center;
            font-weight:bold;
        }

        .btn-mesas{
            display:inline-block;
            margin-bottom:20px;
            padding:10px 16px;
            background:#3498db;
            color:white;
            text-decoration:none;
            border-radius:8px;
            font-size:16px;
        }

        .contenedor{
            display:grid;
            grid-template-columns:65% 35%;
            gap:30px;
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

        .ticket{
            background:#fafafa;
            border:1px solid #ddd;
            border-radius:10px;
            padding:20px;
        }

        .bloque-ticket{
            margin-bottom:25px;
            padding-bottom:15px;
            border-bottom:1px solid #ddd;
        }

        .bloque-ticket h3{
            margin-bottom:15px;
        }

        .item-ticket{
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:10px;
            margin-bottom:10px;
            padding:8px 0;
        }

        .acciones{
            display:flex;
            align-items:center;
            gap:8px;
        }

        .total{
            margin-top:20px;
            font-size:22px;
            font-weight:bold;
        }

        button{
            width:100%;
            padding:15px;
            margin-top:15px;
            color:white;
            border:none;
            border-radius:8px;
            font-size:18px;
            cursor:pointer;
        }

        #guardar-orden{
            background:#27ae60;
        }

        #cerrar-cuenta{
            background:#e67e22;
        }

        #imprimir-cuenta{
            background:#2c3e50;
        }

        .eliminar{
            color:red;
            cursor:pointer;
            font-weight:bold;
        }

        .vacio{
            color:#777;
            font-style:italic;
        }

        .modal-pago{
            position:fixed;
            inset:0;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:24px;
            background:rgba(15,23,42,0.45);
            z-index:1000;
        }

        .modal-pago.oculto{
            display:none;
        }

        .modal-pago-contenido{
            width:min(100%, 420px);
            padding:22px;
            border-radius:16px;
            background:#fff;
            box-shadow:0 20px 45px rgba(15,23,42,0.25);
        }

        .modal-pago h3{
            margin:0 0 8px 0;
            font-size:22px;
            color:#0f172a;
        }

        .modal-pago p{
            margin:0 0 18px 0;
            color:#475569;
            font-size:14px;
        }

        .metodo-pago-opciones{
            display:flex;
            justify-content:center;
            align-items:center;
            gap:120px;
            margin-top:40px;
            margin-bottom:40px;
        }

        .metodo-pago-opcion{
            display:flex;
            align-items:center;
            gap:8px;
            font-weight:bold;
            color:#1f2937;
            cursor:pointer;
        }

        .metodo-pago-opcion input{
            width:auto;
            margin:0;
            cursor:pointer;
        }

        .botones-cierre{
            display:flex;
            justify-content:space-between;
            gap:16px;
            margin-top:20px;
        }

        .botones-cierre button{
            flex:1;
            width:auto;
            margin-top:0;
            padding:16px 12px;
            border:none;
            border-radius:4px;
            color:white;
            font-weight:600;
            cursor:pointer;
            font-size:16px;
            transition:all 0.2s ease;
        }

        #confirmar-cierre{
            background:#16a34a;
        }

        #cancelar-cierre{
            background:#e67e22;
        }

        #confirmar-cierre:hover{
            background:#15803d;
        }

        #cancelar-cierre:hover{
            background:#475569;
        }
    </style>
</head>

<body>
    <div style="
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:5px;
    ">

        <h1>{{ $mesaLabel }}</h1>
        <img src="{{ asset('images/logo.png') }}" 
            alt="Cafetería" 
            style="height:80px;">

        <a href="/mesas" class="btn-mesas">← Volver a mesas</a>

    </div>

    <div class="contenedor">

        <div>
            <div class="buscar">
                <input type="text" id="buscar" placeholder="Buscar producto...">
            </div>

            @if($esEmpleado)
                <div style="margin-bottom:16px; padding:12px 14px; background:#fff3cd; color:#7a5d00; border:1px solid #f4d98b; border-radius:10px;">
                    Vista de empleados: los productos se cobran con costo de produccion.
                </div>
            @endif

            <div class="categorias">
                <div class="categoria" data-id="all">Todos</div>

                @foreach($categorias as $categoria)
                    <div class="categoria" data-id="{{ $categoria->id }}">
                        {{ $categoria->nombre }}
                    </div>
                @endforeach
            </div>

            <div class="productos">
                @foreach($productos as $producto)
                    <div class="producto"
                        data-id="{{ $producto->id }}"
                        data-nombre="{{ strtolower($producto->nombre) }}"
                        data-precio="{{ $producto->precio_venta }}"
                        data-categoria="{{ $producto->categoria_id }}">

                        {{ $producto->nombre }}
                        <br>
                        $ {{ number_format($producto->precio_venta, 2) }}
                    </div>
                @endforeach
            </div>
        </div>

        <div class="ticket">
            <div class="bloque-ticket">
                <h3>Resumen actual</h3>
                <div id="lista-base"></div>
            </div>

            <div class="bloque-ticket">
                <h3>Agregar</h3>
                <div id="lista-nuevo"></div>
            </div>

            <div class="total">
                Total general: $ <span id="total">0</span>
            </div>

            <button id="guardar-orden">Ordenar / Guardar</button>
            <button id="imprimir-cuenta">Imprimir ticket</button>
            <button id="cerrar-cuenta">Cerrar cuenta</button>
        </div>

    </div>

    <div class="modal-pago oculto" id="modal-pago">
        <div class="modal-pago-contenido">
            <h3>Cerrar cuenta</h3>
            <p>Selecciona el metodo de pago para finalizar la venta.</p>
            <div class="metodo-pago-opciones">
                <label class="metodo-pago-opcion">
                    <input type="radio" name="metodo_pago" value="efectivo">
                    <span>Efectivo</span>
                </label>
                <label class="metodo-pago-opcion">
                    <input type="radio" name="metodo_pago" value="tarjeta">
                    <span>Tarjeta</span>
                </label>
            </div>
            <div class="botones-cierre">
                <button id="confirmar-cierre" type="button">Confirmar cierre</button>
                <button id="cancelar-cierre" type="button">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        let categoriaActual = "all";

        let ticketBase = [];
        let ticketNuevo = [];

        const buscar = document.getElementById('buscar');
        const categorias = document.querySelectorAll('.categoria');
        const productos = document.querySelectorAll('.producto');

        const listaBase = document.getElementById('lista-base');
        const listaNuevo = document.getElementById('lista-nuevo');
        const totalHTML = document.getElementById('total');
        const modalPago = document.getElementById('modal-pago');
        const modalRecuperar = document.getElementById('modal-recuperar');
        const metodoPagoInputs = document.querySelectorAll('input[name="metodo_pago"]');
        const btnCerrarCuenta = document.getElementById('cerrar-cuenta');
        const btnConfirmarCierre = document.getElementById('confirmar-cierre');
        const btnCancelarCierre = document.getElementById('cancelar-cierre');

        function serializarItem(item){
            const payload = {
                id: parseInt(item.id),
                nombre: item.nombre,
                precio: parseFloat(item.precio),
                cantidad: parseInt(item.cantidad)
            };

            if(item.nota){
                payload.nota = item.nota;
            }

            if(Array.isArray(item.extras) && item.extras.length > 0){
                payload.extras = item.extras;
            }

            if(Array.isArray(item.opciones) && item.opciones.length > 0){
                payload.opciones = item.opciones;
            }

            return payload;
        }

        function descripcionItem(item){
            return item.nota ? `${item.nombre}<br><small style="color:#777;">Nota: ${item.nota}</small>` : item.nombre;
        }

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
        function guardarOrden(ticketFinal, ticketNuevoActual){
            return fetch('/orden/guardar', {
                method:'POST',
                headers:{
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN':'{{ csrf_token() }}',
                    'Accept':'application/json'
                },
                body:JSON.stringify({
                    mesa:{{ $mesa }},
                    productos:ticketFinal,
                    productosNuevos:ticketNuevoActual
                })
            })
            .then(async res => {
                const texto = await res.text();
                console.log('Respuesta guardar:', texto);

                if (!res.ok) {
                    throw new Error(texto);
                }

                return JSON.parse(texto);
            });

        }

        buscar.addEventListener('keyup', filtrarProductos);

        categorias.forEach(cat => {
            cat.addEventListener('click', function(){
                categoriaActual = this.getAttribute('data-id');
                filtrarProductos();
            });
        });

        productos.forEach(prod => {
            prod.addEventListener('click', function(){

                let id = this.getAttribute('data-id');
                let nombre = this.getAttribute('data-nombre');
                let precio = parseFloat(this.getAttribute('data-precio'));

                let existente = ticketNuevo.find(p => p.id == id);

                if(existente){
                    existente.cantidad++;
                }else{
                    ticketNuevo.push({
                        id:id,
                        nombre:nombre,
                        precio:precio,
                        cantidad:1
                    });
                }

                dibujarTicket();
            });
        });

        function dibujarTicket(){

            listaBase.innerHTML = "";
            listaNuevo.innerHTML = "";

            let total = 0;

            if(ticketBase.length === 0){
                listaBase.innerHTML = '<div class="vacio">Sin productos guardados</div>';
            } else {
                ticketBase.forEach((item,index)=>{
                    let subtotal = item.precio * item.cantidad;
                    total += subtotal;

                    let div = document.createElement('div');
                    div.classList.add('item-ticket');

                    div.innerHTML = `
                        <span>${descripcionItem(item)}</span>
                        <div class="acciones">
                            <div class="controles-cantidad">
                                <button class="btn-cantidad" onclick="restarBase(${index})">−</button>
                                <span class="cantidad-numero">${item.cantidad}</span>
                                <button class="btn-cantidad" onclick="sumarBase(${index})">+</button>
                            </div>
                            <span>$${subtotal}</span>
                            <span class="eliminar" onclick="eliminarBase(${index})">❌</span>
                        </div>
                    `;

                    listaBase.appendChild(div);
                });
            }

            if(ticketNuevo.length === 0){
                listaNuevo.innerHTML = '<div class="vacio">Sin productos nuevos</div>';
            } else {
                ticketNuevo.forEach((item,index)=>{
                    let subtotal = item.precio * item.cantidad;
                    total += subtotal;

                    let div = document.createElement('div');
                    div.classList.add('item-ticket');

                    div.innerHTML = `
                        <span>${descripcionItem(item)}</span>
                        <div class="acciones">
                            <div class="controles-cantidad">
                                <button class="btn-cantidad" onclick="restarNuevo(${index})">−</button>
                                <span class="cantidad-numero">${item.cantidad}</span>
                                <button class="btn-cantidad" onclick="sumarNuevo(${index})">+</button>
                            </div>
                            <span>$${subtotal}</span>
                            <span class="eliminar" onclick="eliminarNuevo(${index})">❌</span>
                        </div>
                    `;

                    listaNuevo.appendChild(div);
                });
            }

            totalHTML.innerText = total;
        }

        function eliminarBase(index){
            ticketBase.splice(index,1);
            dibujarTicket();
        }

        function eliminarNuevo(index){
            ticketNuevo.splice(index,1);
            dibujarTicket();
        }

        function sumarBase(index){
            ticketBase[index].cantidad++;
            dibujarTicket();
        }

        function restarBase(index){
            ticketBase[index].cantidad--;

            if(ticketBase[index].cantidad <= 0){
                ticketBase.splice(index,1);
            }

            dibujarTicket();
        }

        function sumarNuevo(index){
            ticketNuevo[index].cantidad++;
            dibujarTicket();
        }

        function restarNuevo(index){
            ticketNuevo[index].cantidad--;

            if(ticketNuevo[index].cantidad <= 0){
                ticketNuevo.splice(index,1);
            }

            dibujarTicket();
        }

        function cargarMesa(){
            fetch('/orden/mesa/{{ $mesa }}')
            .then(res => res.json())
            .then(data => {
                ticketBase = data;
                ticketNuevo = [];
                dibujarTicket();
            })
            .catch(error => {
                console.error('Error cargando mesa:', error);
            });
        }

        cargarMesa();

    document.getElementById('guardar-orden').addEventListener('click', function(){
        const ticketFinal = [...ticketBase, ...ticketNuevo].map(serializarItem);
        const ticketNuevoActual = ticketNuevo.map(serializarItem);

        if(ticketFinal.length === 0){
            alert("No hay productos en la orden");
            return;
        }

        guardarOrden(ticketFinal, ticketNuevoActual)
        .then(data => {
            const resultados = data.command_results || {};
            const errores = Object.entries(resultados)
                .filter(([, resultado]) => resultado && resultado.printed === false && resultado.message && !resultado.message.includes('No hay productos nuevos'))
                .map(([area, resultado]) => `${area}: ${resultado.message}`);

            if (errores.length > 0) {
                alert("Orden guardada, pero hubo problemas al imprimir comandas:\n\n" + errores.join("\n"));
            }
            window.location.href = '/mesas';
        })
        .catch(error => {
            console.error('Error real al guardar:', error);
            alert("Error al guardar");
        });

    });

    btnCerrarCuenta.addEventListener('click', function(){
        const ticketFinal = [...ticketBase, ...ticketNuevo].map(serializarItem);

        if(ticketFinal.length === 0){
            alert("No hay productos en la orden");
            return;
        }

        modalPago.classList.remove('oculto');
    });

    btnCancelarCierre.addEventListener('click', function(){
        modalPago.classList.add('oculto');
    });

    btnConfirmarCierre.addEventListener('click', function(){
        const ticketFinal = [...ticketBase, ...ticketNuevo].map(serializarItem);

        if(ticketFinal.length === 0){
            alert("No hay productos en la orden");
            return;
        }

        const metodoPagoSeleccionado = Array.from(metodoPagoInputs).find(input => input.checked);

        if(!metodoPagoSeleccionado){
            alert("Selecciona un metodo de pago");
            return;
        }

        fetch('/orden/cerrar', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}',
                'Accept':'application/json'
            },
            body:JSON.stringify({
                mesa:{{ $mesa }},
                productos:ticketFinal,
                metodo_pago:metodoPagoSeleccionado.value
            })
        })
        .then(async res => {
            const texto = await res.text();
            console.log('Respuesta cerrar:', texto);

            if (!res.ok) {
                throw new Error(texto);
            }

            return JSON.parse(texto);
        })
        .then(data => {
            window.location.href = '/mesas';
        })
        .catch(error => {
            console.error('Error real al cerrar:', error);
            alert("Error al cerrar cuenta");
        });
    });

    document.getElementById('imprimir-cuenta').addEventListener('click', function(){
        const ticketFinal = [...ticketBase, ...ticketNuevo].map(serializarItem);

        if(ticketFinal.length === 0){
            alert("No hay productos en la orden");
            return;
        }

        fetch('/orden/imprimir-ticket', {
            method:'POST',
            headers:{
                'Content-Type':'application/json',
                'X-CSRF-TOKEN':'{{ csrf_token() }}',
                'Accept':'application/json'
            },
            body:JSON.stringify({
                mesa:{{ $mesa }},
                productos:ticketFinal
            })
        })
        .then(async res => {
            const texto = await res.text();
            console.log('Respuesta imprimir:', texto);

            if (!res.ok) {
                throw new Error(texto);
            }

            return JSON.parse(texto);
        })
        .then(data => {
            if (data.printed) {
                window.location.href = '/mesas';
                return;
            }

            let mensaje = data.message || "La venta se guardó, pero la impresión falló";

            if (data.error) {
                console.error('Detalle impresión:', data.error);
            }

            if (data.fallback_url) {
                const abrirRespaldo = confirm(mensaje + "\n\n¿Deseas abrir la vista de respaldo para reintentar?");

                if (abrirRespaldo) {
                    window.open(data.fallback_url, '_blank');
                }
            } else {
                alert(mensaje);
            }

            window.location.href = '/mesas';
        })
        .catch(error => {
            console.error('Error real al imprimir:', error);
            alert("Error al imprimir ticket");
        });

    });
    </script>

</body>
</html>
