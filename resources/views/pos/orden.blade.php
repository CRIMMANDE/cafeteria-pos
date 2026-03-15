
<!DOCTYPE html>
<html>
<head>
    <title>POS</title>

    <style>
        body{
            font-family:Arial;
            padding:30px;
            background:#f7f1ea;
            color:#241813;
        }

        .controles-cantidad{display:flex;align-items:center;gap:8px;}
        .btn-cantidad{width:28px;height:28px;border:none;border-radius:6px;background:#ddd;cursor:pointer;font-weight:bold;font-size:16px;padding:0;line-height:28px;text-align:center;color:#333;}
        .cantidad-numero{min-width:20px;text-align:center;font-weight:bold;}
        .header-links{display:flex;gap:12px;align-items:center;}
        .btn-mesas,.btn-admin{display:inline-block;margin-bottom:20px;padding:10px 16px;color:white;text-decoration:none;border-radius:8px;font-size:16px;}
        .btn-mesas{background:#3498db;}
        .btn-admin{background:#5a3828;}
        .contenedor{display:grid;grid-template-columns:65% 35%;gap:30px;}
        .buscar{margin-bottom:20px;}
        input, textarea{padding:10px;font-size:16px;width:300px;}
        .categorias{margin-bottom:20px;}
        .categoria{display:inline-block;padding:10px 20px;background:#eee;margin-right:10px;border-radius:8px;cursor:pointer;}
        .categoria.activa{background:#5a3828;color:#fff;}
        .productos{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
        .producto{padding:20px;background:#fffdf9;border:1px solid #eadccf;border-radius:10px;text-align:center;cursor:pointer;box-shadow:0 10px 20px rgba(62,39,20,0.08);}
        .producto small{display:block;margin-top:8px;color:#7b6659;}
        .ticket{background:#fffdf9;border:1px solid #ddd;border-radius:10px;padding:20px;}
        .bloque-ticket{margin-bottom:25px;padding-bottom:15px;border-bottom:1px solid #ddd;}
        .bloque-ticket h3{margin-bottom:15px;}
        .item-ticket{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px;padding:8px 0;}
        .item-info{flex:1;}
        .item-titulo{font-weight:bold;}
        .item-resumen{margin-top:4px;font-size:12px;color:#7a665b;line-height:1.35;}
        .acciones{display:flex;align-items:center;gap:8px;}
        .total{margin-top:20px;font-size:22px;font-weight:bold;}
        button{width:100%;padding:15px;margin-top:15px;color:white;border:none;border-radius:8px;font-size:18px;cursor:pointer;}
        #guardar-orden{background:#27ae60;}
        #cerrar-cuenta{background:#e67e22;}
        #imprimir-cuenta{background:#2c3e50;}
        .eliminar{color:red;cursor:pointer;font-weight:bold;}
        .vacio{color:#777;font-style:italic;}
        .modal-pago,.modal-config{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:24px;background:rgba(15,23,42,0.45);z-index:1000;}
        .modal-pago.oculto,.modal-config.oculto{display:none;}
        .modal-pago-contenido,.modal-config-contenido{width:min(100%, 720px);max-height:90vh;overflow:auto;padding:22px;border-radius:16px;background:#fff;box-shadow:0 20px 45px rgba(15,23,42,0.25);}
        .modal-pago h3,.modal-config h3{margin:0 0 8px 0;font-size:22px;color:#0f172a;}
        .modal-pago p,.modal-config p{margin:0 0 18px 0;color:#475569;font-size:14px;}
        .metodo-pago-opciones{display:flex;justify-content:center;align-items:center;gap:120px;margin-top:40px;margin-bottom:40px;}
        .metodo-pago-opcion{display:flex;align-items:center;gap:8px;font-weight:bold;color:#1f2937;cursor:pointer;}
        .metodo-pago-opcion input{width:auto;margin:0;cursor:pointer;}
        .botones-cierre,.botones-config{display:flex;justify-content:space-between;gap:16px;margin-top:20px;}
        .botones-cierre button,.botones-config button{flex:1;width:auto;margin-top:0;padding:16px 12px;border:none;border-radius:4px;color:white;font-weight:600;cursor:pointer;font-size:16px;transition:all 0.2s ease;}
        #confirmar-cierre,#confirmar-config{background:#16a34a;}
        #cancelar-cierre,#cancelar-config{background:#e67e22;}
        .grupo-config{margin-bottom:18px;padding:16px;border:1px solid #eadccf;border-radius:14px;background:#fffaf5;}
        .grupo-config h4{margin:0 0 6px 0;font-size:18px;}
        .grupo-ayuda{margin-bottom:12px;color:#6b7280;font-size:13px;}
        .grupo-opciones{display:flex;flex-wrap:wrap;gap:10px;}
        .opcion-btn{width:auto;margin-top:0;padding:10px 14px;background:#f1e2d3;color:#3b281f;border:1px solid #d8c0af;border-radius:999px;font-size:14px;}
        .opcion-btn.activa{background:#5a3828;color:#fff;border-color:#5a3828;}
        .grupo-vacio{color:#8b6f61;font-size:14px;padding:10px 0;}
        @media(max-width:1000px){.contenedor{grid-template-columns:1fr;}.productos{grid-template-columns:repeat(2,1fr);}.header-links{flex-wrap:wrap;justify-content:flex-end;}}
    </style>
</head>

<body>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; gap:16px;">
        <h1>{{ $mesaLabel }}</h1>
        <img src="{{ asset('images/logo.png') }}" alt="Cafeteria" style="height:80px;">
        <div class="header-links">
            <a href="/admin/menu-dia" class="btn-admin">Menu del dia</a>
            <a href="/mesas" class="btn-mesas">Volver a mesas</a>
        </div>
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
                <div class="categoria activa" data-id="all">Todos</div>
                @foreach($categorias as $categoria)
                    <div class="categoria" data-id="{{ $categoria->id }}">{{ $categoria->nombre }}</div>
                @endforeach
            </div>

            <div class="productos">
                @foreach($productos as $producto)
                    <div class="producto" data-id="{{ $producto->id }}" data-nombre="{{ strtolower($producto->nombre) }}" data-precio="{{ $producto->precio_venta }}" data-categoria="{{ $producto->categoria_id }}">
                        {{ $producto->nombre }}
                        <br>
                        $ {{ number_format($producto->precio_venta, 2) }}
                        @php
                            $hasGroups = $producto->gruposOpciones->isNotEmpty();
                        @endphp
                        @if($hasGroups || strtolower($producto->nombre) === 'comida')
                            <small>Configurable</small>
                        @endif
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

    <div class="modal-config oculto" id="modal-config">
        <div class="modal-config-contenido">
            <h3 id="modal-config-titulo">Configurar producto</h3>
            <p id="modal-config-subtitulo">Selecciona las opciones necesarias para agregar el producto.</p>
            <div id="modal-config-body"></div>
            <div class="botones-config">
                <button id="confirmar-config" type="button">Agregar al ticket</button>
                <button id="cancelar-config" type="button">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        const esEmpleado = @json($esEmpleado);
        const productosConfig = @json($productosPosJson);
        const productosMap = new Map(productosConfig.map(producto => [String(producto.id), producto]));

        let categoriaActual = 'all';
        let ticketBase = [];
        let ticketNuevo = [];
        let productoConfigActual = null;
        let seleccionesConfigActual = {};

        const buscar = document.getElementById('buscar');
        const categorias = document.querySelectorAll('.categoria');
        const productos = document.querySelectorAll('.producto');
        const listaBase = document.getElementById('lista-base');
        const listaNuevo = document.getElementById('lista-nuevo');
        const totalHTML = document.getElementById('total');
        const modalPago = document.getElementById('modal-pago');
        const metodoPagoInputs = document.querySelectorAll('input[name="metodo_pago"]');
        const btnCerrarCuenta = document.getElementById('cerrar-cuenta');
        const btnConfirmarCierre = document.getElementById('confirmar-cierre');
        const btnCancelarCierre = document.getElementById('cancelar-cierre');
        const modalConfig = document.getElementById('modal-config');
        const modalConfigTitulo = document.getElementById('modal-config-titulo');
        const modalConfigSubtitulo = document.getElementById('modal-config-subtitulo');
        const modalConfigBody = document.getElementById('modal-config-body');
        const btnConfirmarConfig = document.getElementById('confirmar-config');
        const btnCancelarConfig = document.getElementById('cancelar-config');
        function normalizarTexto(valor){
            return (valor || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        }

        function etiquetaOpcion(opcion){
            const base = opcion.label || opcion.nombre || '';
            if(base.includes(':')){
                return base.split(':').slice(1).join(':').trim();
            }
            return base.trim();
        }

        function nombreComercial(producto, opciones){
            const nombreProducto = producto?.nombre || '';
            if(normalizarTexto(nombreProducto) !== 'comida'){
                return nombreProducto;
            }

            let modalidad = '';
            let tercerTiempo = '';

            opciones.forEach(opcion => {
                const nombre = opcion.nombre || '';
                const partes = nombre.split(':');
                if(partes.length < 2){
                    return;
                }

                const prefijo = normalizarTexto(partes[0]);
                const valor = partes.slice(1).join(':').trim();

                if(prefijo === 'modalidad'){
                    modalidad = valor;
                }

                if(prefijo === 'tercer_tiempo' || prefijo === 'tercer tiempo'){
                    tercerTiempo = valor;
                }
            });

            if(normalizarTexto(modalidad).includes('platillo') && tercerTiempo){
                return `Comida + ${tercerTiempo}`;
            }

            return 'Comida del dia';
        }

        function resumenOpciones(item){
            return (item.opciones || []).map(etiquetaOpcion).filter(Boolean).join(', ');
        }

        function descripcionItem(item){
            const resumen = resumenOpciones(item);
            const nota = item.nota ? `Nota: ${item.nota}` : '';
            const detalle = [resumen, nota].filter(Boolean).join(' | ');

            if(!detalle){
                return `<div class="item-titulo">${item.nombre}</div>`;
            }

            return `<div class="item-titulo">${item.nombre}</div><div class="item-resumen">${detalle}</div>`;
        }

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
                payload.extras = item.extras.map(extra => ({
                    extra_id: extra.extra_id ?? extra.id ?? null,
                    nombre_personalizado: extra.nombre_personalizado ?? extra.nombre ?? null,
                    precio: parseFloat(extra.precio || 0),
                    nota: extra.nota || null
                }));
            }

            if(Array.isArray(item.opciones) && item.opciones.length > 0){
                payload.opciones = item.opciones.map(opcion => ({
                    opcion_id: opcion.opcion_id ?? opcion.id ?? null,
                    nombre: opcion.nombre,
                    incremento_precio: parseFloat(opcion.incremento_precio || 0),
                    incremento_costo: parseFloat(opcion.incremento_costo || 0)
                }));
            }

            return payload;
        }

        function firmaItem(item){
            const opciones = [...(item.opciones || [])].map(opcion => ({
                opcion_id: opcion.opcion_id ?? null,
                nombre: opcion.nombre ?? '',
                incremento_precio: Number(opcion.incremento_precio || 0).toFixed(2),
                incremento_costo: Number(opcion.incremento_costo || 0).toFixed(2)
            })).sort((a, b) => JSON.stringify(a).localeCompare(JSON.stringify(b)));

            const extras = [...(item.extras || [])].map(extra => ({
                extra_id: extra.extra_id ?? null,
                nombre_personalizado: extra.nombre_personalizado ?? extra.nombre ?? '',
                precio: Number(extra.precio || 0).toFixed(2),
                nota: extra.nota || null
            })).sort((a, b) => JSON.stringify(a).localeCompare(JSON.stringify(b)));

            return JSON.stringify({id: Number(item.id), nota: item.nota || null, opciones, extras});
        }

        function agregarATicket(item){
            const firma = firmaItem(item);
            const existente = ticketNuevo.find(actual => firmaItem(actual) === firma);

            if(existente){
                existente.cantidad += item.cantidad || 1;
            }else{
                ticketNuevo.push(item);
            }

            dibujarTicket();
        }

        function filtrarProductos(){
            let texto = buscar.value.toLowerCase();

            productos.forEach(prod => {
                let nombre = prod.getAttribute('data-nombre');
                let categoria = prod.getAttribute('data-categoria');
                let coincideBusqueda = nombre.includes(texto);
                let coincideCategoria = (categoriaActual === 'all' || categoria == categoriaActual);
                prod.style.display = coincideBusqueda && coincideCategoria ? 'block' : 'none';
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
                if (!res.ok) {
                    throw new Error(texto);
                }
                return JSON.parse(texto);
            });
        }

        function tieneConfiguracion(producto){
            return Array.isArray(producto?.grupos) && producto.grupos.some(grupo => Array.isArray(grupo.options) && grupo.options.length > 0);
        }

        function obtenerSeleccionadas(){
            return Object.values(seleccionesConfigActual).flat();
        }

        function esGrupoVisible(grupo){
            if(!grupo.visible_if_option_id){
                return true;
            }

            return obtenerSeleccionadas().some(opcion => Number(opcion.opcion_id) === Number(grupo.visible_if_option_id));
        }

        function limpiarSeleccionesOcultas(){
            if(!productoConfigActual){
                return;
            }

            productoConfigActual.grupos.forEach(grupo => {
                if(!esGrupoVisible(grupo)){
                    delete seleccionesConfigActual[grupo.key];
                }
            });
        }

        function renderModalConfiguracion(){
            if(!productoConfigActual){
                return;
            }

            limpiarSeleccionesOcultas();
            modalConfigTitulo.textContent = productoConfigActual.nombre;
            modalConfigSubtitulo.textContent = 'Selecciona las opciones requeridas para agregar este producto.';
            modalConfigBody.innerHTML = '';

            productoConfigActual.grupos.forEach(grupo => {
                if(!esGrupoVisible(grupo)){
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'grupo-config';
                const obligatoria = grupo.obligatorio ? 'Obligatorio' : 'Opcional';
                const multiple = grupo.multiple ? 'multiple' : 'una sola opcion';
                wrapper.innerHTML = `<h4>${grupo.nombre}</h4><div class="grupo-ayuda">${obligatoria} · Selecciona ${multiple}</div>`;

                const opcionesWrap = document.createElement('div');
                opcionesWrap.className = 'grupo-opciones';

                if(!grupo.options || grupo.options.length === 0){
                    const vacio = document.createElement('div');
                    vacio.className = 'grupo-vacio';
                    vacio.textContent = 'No hay opciones disponibles para este grupo.';
                    wrapper.appendChild(vacio);
                    modalConfigBody.appendChild(wrapper);
                    return;
                }
                grupo.options.forEach(opcion => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'opcion-btn';
                    button.textContent = etiquetaOpcion(opcion);

                    const seleccionadas = seleccionesConfigActual[grupo.key] || [];
                    if(seleccionadas.some(actual => (actual.key || '') === (opcion.key || ''))){
                        button.classList.add('activa');
                    }

                    button.addEventListener('click', function(){
                        const actuales = [...(seleccionesConfigActual[grupo.key] || [])];
                        const index = actuales.findIndex(actual => (actual.key || '') === (opcion.key || ''));

                        if(grupo.multiple){
                            if(index >= 0){
                                actuales.splice(index, 1);
                            }else{
                                actuales.push(opcion);
                            }
                        }else{
                            if(index >= 0){
                                actuales.splice(index, 1);
                            }else{
                                actuales.splice(0, actuales.length, opcion);
                            }
                        }

                        seleccionesConfigActual[grupo.key] = actuales;
                        renderModalConfiguracion();
                    });

                    opcionesWrap.appendChild(button);
                });

                wrapper.appendChild(opcionesWrap);
                modalConfigBody.appendChild(wrapper);
            });
        }

        function abrirModalConfiguracion(producto){
            productoConfigActual = producto;
            seleccionesConfigActual = {};
            renderModalConfiguracion();
            modalConfig.classList.remove('oculto');
        }

        function cerrarModalConfiguracion(){
            modalConfig.classList.add('oculto');
            productoConfigActual = null;
            seleccionesConfigActual = {};
        }

        function validarConfiguracion(){
            if(!productoConfigActual){
                return false;
            }

            for(const grupo of productoConfigActual.grupos){
                if(!esGrupoVisible(grupo)){
                    continue;
                }

                const seleccionadas = seleccionesConfigActual[grupo.key] || [];
                if(grupo.obligatorio && seleccionadas.length === 0){
                    alert(`Selecciona una opcion para ${grupo.nombre}`);
                    return false;
                }

                if(grupo.obligatorio && (!grupo.options || grupo.options.length === 0)){
                    alert(`No hay opciones disponibles para ${grupo.nombre}`);
                    return false;
                }
            }

            return true;
        }

        function construirItemConfigurado(){
            const opciones = productoConfigActual.grupos
                .filter(grupo => esGrupoVisible(grupo))
                .flatMap(grupo => seleccionesConfigActual[grupo.key] || [])
                .map(opcion => ({
                    opcion_id: opcion.opcion_id ?? null,
                    nombre: opcion.nombre,
                    incremento_precio: Number(opcion.incremento_precio || 0),
                    incremento_costo: Number(opcion.incremento_costo || 0)
                }));

            const incremento = opciones.reduce((total, opcion) => total + Number(esEmpleado ? opcion.incremento_costo : opcion.incremento_precio), 0);

            return {
                id: productoConfigActual.id,
                nombre: nombreComercial(productoConfigActual, opciones),
                precio: Number(productoConfigActual.precio_venta || 0) + incremento,
                cantidad: 1,
                opciones
            };
        }

        buscar.addEventListener('keyup', filtrarProductos);

        categorias.forEach(cat => {
            cat.addEventListener('click', function(){
                categorias.forEach(item => item.classList.remove('activa'));
                this.classList.add('activa');
                categoriaActual = this.getAttribute('data-id');
                filtrarProductos();
            });
        });

        productos.forEach(prod => {
            prod.addEventListener('click', function(){
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                const precio = parseFloat(this.getAttribute('data-precio'));
                const producto = productosMap.get(String(id));

                if(producto && tieneConfiguracion(producto)){
                    abrirModalConfiguracion(producto);
                    return;
                }

                agregarATicket({
                    id:id,
                    nombre:nombre.replace(/\b\w/g, letra => letra.toUpperCase()),
                    precio:precio,
                    cantidad:1
                });
            });
        });

        btnCancelarConfig.addEventListener('click', cerrarModalConfiguracion);
        btnConfirmarConfig.addEventListener('click', function(){
            if(!validarConfiguracion()){
                return;
            }

            agregarATicket(construirItemConfigurado());
            cerrarModalConfiguracion();
        });
        function dibujarTicket(){
            listaBase.innerHTML = '';
            listaNuevo.innerHTML = '';
            let total = 0;

            if(ticketBase.length === 0){
                listaBase.innerHTML = '<div class="vacio">Sin productos guardados</div>';
            } else {
                ticketBase.forEach((item,index)=>{
                    let subtotal = item.precio * item.cantidad;
                    total += subtotal;
                    let div = document.createElement('div');
                    div.classList.add('item-ticket');
                    div.innerHTML = `<div class="item-info">${descripcionItem(item)}</div><div class="acciones"><div class="controles-cantidad"><button class="btn-cantidad" onclick="restarBase(${index})">-</button><span class="cantidad-numero">${item.cantidad}</span><button class="btn-cantidad" onclick="sumarBase(${index})">+</button></div><span>$${subtotal.toFixed(2)}</span><span class="eliminar" onclick="eliminarBase(${index})">X</span></div>`;
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
                    div.innerHTML = `<div class="item-info">${descripcionItem(item)}</div><div class="acciones"><div class="controles-cantidad"><button class="btn-cantidad" onclick="restarNuevo(${index})">-</button><span class="cantidad-numero">${item.cantidad}</span><button class="btn-cantidad" onclick="sumarNuevo(${index})">+</button></div><span>$${subtotal.toFixed(2)}</span><span class="eliminar" onclick="eliminarNuevo(${index})">X</span></div>`;
                    listaNuevo.appendChild(div);
                });
            }

            totalHTML.innerText = total.toFixed(2);
        }

        function eliminarBase(index){ ticketBase.splice(index,1); dibujarTicket(); }
        function eliminarNuevo(index){ ticketNuevo.splice(index,1); dibujarTicket(); }
        function sumarBase(index){ ticketBase[index].cantidad++; dibujarTicket(); }
        function restarBase(index){ ticketBase[index].cantidad--; if(ticketBase[index].cantidad <= 0){ ticketBase.splice(index,1); } dibujarTicket(); }
        function sumarNuevo(index){ ticketNuevo[index].cantidad++; dibujarTicket(); }
        function restarNuevo(index){ ticketNuevo[index].cantidad--; if(ticketNuevo[index].cantidad <= 0){ ticketNuevo.splice(index,1); } dibujarTicket(); }

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
                alert('No hay productos en la orden');
                return;
            }

            guardarOrden(ticketFinal, ticketNuevoActual)
            .then(data => {
                const resultados = data.command_results || {};
                const errores = Object.entries(resultados)
                    .filter(([, resultado]) => resultado && resultado.printed === false && resultado.message && !resultado.message.includes('No hay productos nuevos'))
                    .map(([area, resultado]) => `${area}: ${resultado.message}`);

                if (errores.length > 0) {
                    alert('Orden guardada, pero hubo problemas al imprimir comandas:\n\n' + errores.join('\n'));
                }
                window.location.href = '/mesas';
            })
            .catch(error => {
                console.error('Error real al guardar:', error);
                alert('Error al guardar');
            });
        });

        btnCerrarCuenta.addEventListener('click', function(){
            const ticketFinal = [...ticketBase, ...ticketNuevo].map(serializarItem);
            if(ticketFinal.length === 0){
                alert('No hay productos en la orden');
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
                alert('No hay productos en la orden');
                return;
            }
            const metodoPagoSeleccionado = Array.from(metodoPagoInputs).find(input => input.checked);
            if(!metodoPagoSeleccionado){
                alert('Selecciona un metodo de pago');
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
                if (!res.ok) {
                    throw new Error(texto);
                }
                return JSON.parse(texto);
            })
            .then(() => {
                window.location.href = '/mesas';
            })
            .catch(error => {
                console.error('Error real al cerrar:', error);
                alert('Error al cerrar cuenta');
            });
        });

        document.getElementById('imprimir-cuenta').addEventListener('click', function(){
            const ticketFinal = [...ticketBase, ...ticketNuevo].map(serializarItem);
            if(ticketFinal.length === 0){
                alert('No hay productos en la orden');
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

                let mensaje = data.message || 'La venta se guardo, pero la impresion fallo';
                if (data.fallback_url) {
                    const abrirRespaldo = confirm(mensaje + '\n\nDeseas abrir la vista de respaldo para reintentar?');
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
                alert('Error al imprimir ticket');
            });
        });
    </script>
</body>
</html>

