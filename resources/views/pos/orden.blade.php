
<!DOCTYPE html>
<html>
<head>
    <title>POS</title>

    <style>
        body{font-family:Arial;padding:30px;background:#f7f1ea;color:#241813;}
        .controles-cantidad{display:flex;align-items:center;gap:8px;}
        .btn-cantidad{width:28px;height:28px;border:none;border-radius:6px;background:#ddd;cursor:pointer;font-weight:bold;font-size:16px;padding:0;line-height:28px;text-align:center;color:#333;}
        .cantidad-numero{min-width:20px;text-align:center;font-weight:bold;}
        .header-links{display:flex;gap:12px;align-items:center;}
        .btn-mesas,.btn-admin{display:inline-block;margin-bottom:20px;padding:10px 16px;color:white;text-decoration:none;border-radius:8px;font-size:16px;}
        .btn-mesas{background:#3498db;}
        .btn-admin{background:#5a3828;}
        .contenedor{display:grid;grid-template-columns:65% 35%;gap:30px;}
        .buscar{margin-bottom:20px;}
        input, textarea, select{padding:10px;font-size:16px;width:300px;}
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
        .modal-pago-contenido,.modal-config-contenido{width:min(100%, 760px);max-height:90vh;overflow:auto;padding:22px;border-radius:16px;background:#fff;box-shadow:0 20px 45px rgba(15,23,42,0.25);}
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
        .modalidad-bloque{margin-bottom:18px;padding:16px;border:1px solid #d8c0af;border-radius:14px;background:#fff5eb;}
        .modalidad-opciones{display:flex;flex-wrap:wrap;gap:10px;}
        .modal-total{margin:12px 0 18px;font-size:18px;font-weight:bold;color:#0f172a;}
        .toggle-row{display:flex;align-items:center;gap:10px;margin-bottom:12px;font-weight:600;color:#3b281f;}
        .toggle-row input[type="checkbox"]{width:18px;height:18px;}
        .extras-list{display:grid;grid-template-columns:1fr;gap:8px;}
        .extra-item{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid #e3d2c1;border-radius:10px;background:#fff;}
        .extra-item label{display:flex;align-items:center;gap:8px;margin:0;cursor:pointer;font-size:14px;}
        .extra-item input[type="checkbox"]{width:16px;height:16px;}
        .extra-price{font-size:13px;color:#6b4a39;}
        .extra-right{display:flex;align-items:center;gap:10px;}
        .extra-cantidad{display:flex;align-items:center;gap:6px;}
        .extra-cantidad button{margin:0;width:28px;height:28px;padding:0;border-radius:7px;font-size:14px;background:#d8c0af;color:#3b281f;}
        .extra-cantidad span{min-width:20px;text-align:center;font-weight:700;}
        .extra-subtotal{font-size:12px;color:#6b4a39;min-width:72px;text-align:right;}
        .otro-extra-fields{display:grid;grid-template-columns:1fr 140px;gap:10px;margin-top:10px;}
        .otro-extra-fields input{width:100%;}
        .nota-area textarea{width:100%;min-height:78px;resize:vertical;border:1px solid #d6c2b0;border-radius:10px;padding:10px;font-size:14px;}
        @media(max-width:1000px){
            .contenedor{grid-template-columns:1fr;}
            .productos{grid-template-columns:repeat(2,1fr);}
            .header-links{flex-wrap:wrap;justify-content:flex-end;}
            .otro-extra-fields{grid-template-columns:1fr;}
        }
    </style>
</head>

<body>
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; gap:16px;">
        <h1>{{ $mesaLabel }}</h1>
        <img src="{{ asset('images/logo.png') }}" alt="Cafeteria" style="height:80px;">
        <div class="header-links">
            <a href="/admin" class="btn-admin">Admin</a>
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
                        <small>Tap para configurar</small>
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
            <p id="modal-config-subtitulo">Selecciona las opciones necesarias para agregar este producto.</p>
            <div id="modal-config-total" class="modal-total"></div>
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
        const extrasCatalogoBase = @json($extrasPosJson);
        const productosMap = new Map(productosConfig.map(producto => [String(producto.id), producto]));

        let categoriaActual = 'all';
        let ticketBase = [];
        let ticketNuevo = [];
        let productoConfigActual = null;
        let modalidadActual = 'solo';
        let seleccionesConfigActual = {};

        let extrasActivos = false;
        let notasActivas = false;
        let extrasSeleccionados = {};
        let extraOtroNombre = '';
        let extraOtroPrecio = '';
        let notaActual = '';

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
        const modalConfigTotal = document.getElementById('modal-config-total');
        const modalConfigBody = document.getElementById('modal-config-body');
        const btnConfirmarConfig = document.getElementById('confirmar-config');
        const btnCancelarConfig = document.getElementById('cancelar-config');

        function normalizarTexto(valor){
            return (valor || '').toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        }

        function normalizarClave(valor){
            return normalizarTexto(valor).replace(/\s+/g, '_').replace(/[^a-z0-9_+]/g, '');
        }

        function etiquetaOpcion(opcion){
            const base = opcion.label || opcion.nombre || '';
            if(base.includes(':')){
                return base.split(':').slice(1).join(':').trim();
            }
            return base.trim();
        }

        function modalidadesDisponibles(producto){
            if(producto.es_comida_dia){ return []; }
            const modalidades = [];
            if(producto.permite_solo){ modalidades.push({ key: 'solo', label: 'Solo', incremento: 0 }); }
            if(producto.permite_desayuno){ modalidades.push({ key: 'desayuno', label: 'Paquete desayuno', incremento: Number(producto.incremento_desayuno || 0) }); }
            if(producto.permite_comida){ modalidades.push({ key: 'comida', label: 'Comida', incremento: Number(producto.incremento_comida || 0) }); }
            return modalidades;
        }

        function modalidadPorDefecto(producto){
            if(producto.es_comida_dia){ return 'comida'; }
            if(producto.permite_solo){ return 'solo'; }
            if(producto.permite_desayuno){ return 'desayuno'; }
            if(producto.permite_comida){ return 'comida'; }
            return 'solo';
        }

        function incrementoModalidad(producto, modalidad){
            if(producto.es_comida_dia){ return 0; }
            if(modalidad === 'desayuno'){ return Number(producto.incremento_desayuno || 0); }
            if(modalidad === 'comida'){ return Number(producto.incremento_comida || 0); }
            return 0;
        }

        function nombreComercial(producto, modalidad){
            if(producto.es_comida_dia){ return 'Comida del dia'; }
            if(modalidad === 'desayuno'){ return `${producto.nombre} / Paquete desayuno`; }
            if(modalidad === 'comida'){ return `${producto.nombre} / Comida`; }
            return producto.nombre;
        }

        function extraerEntradasOpcion(opciones){
            return (opciones || []).map(opcion => {
                const nombre = (opcion.nombre || '').trim();
                if(nombre.includes(':')){
                    const partes = nombre.split(':');
                    const grupo = partes.shift().trim();
                    return { groupLabel: grupo, groupKey: normalizarClave(grupo), value: partes.join(':').trim() };
                }
                return { groupLabel: '', groupKey: '', value: nombre };
            }).filter(entrada => entrada.value);
        }

        function buscarValorGrupo(entradas, claves){
            const encontrada = entradas.find(entrada => claves.includes(entrada.groupKey));
            return encontrada ? encontrada.value : '';
        }

        function detalleClienteBase(producto, opciones, modalidad){
            const entradas = extraerEntradasOpcion(opciones);

            if(producto.es_comida_dia){ return []; }

            if(modalidad === 'desayuno'){
                const bebida = buscarValorGrupo(entradas, ['bebida_del_paquete', 'bebida']);
                const fruta = buscarValorGrupo(entradas, ['fruta_del_paquete', 'fruta']);
                const granola = buscarValorGrupo(entradas, ['granola', 'agregar_granola']);
                const detalle = [];
                if(bebida){ detalle.push(bebida); }
                if(fruta){ detalle.push(granola ? `${fruta} con granola` : fruta); }
                return detalle;
            }

            if(modalidad === 'comida'){
                return entradas
                    .filter(entrada => entrada.groupKey !== 'modalidad')
                    .map(entrada => entrada.groupLabel ? `${entrada.groupLabel}: ${entrada.value}` : entrada.value);
            }

            return entradas
                .filter(entrada => entrada.groupKey !== 'modalidad')
                .map(entrada => entrada.value);
        }

        function resumenOpciones(item){
            const detalle = Array.isArray(item.detalle_cliente) ? item.detalle_cliente : [];
            if(detalle.length > 0){ return detalle.join(', '); }
            return detalleClienteBase({ nombre: item.producto_nombre || item.nombre, es_comida_dia: !!item.es_comida_dia }, item.opciones || [], item.modalidad || 'solo').join(', ');
        }

        function descripcionItem(item){
            const resumen = resumenOpciones(item);
            const nota = item.nota ? `Nota: ${item.nota}` : '';
            const detalle = [resumen, nota].filter(Boolean).join(' | ');
            if(!detalle){ return `<div class="item-titulo">${item.nombre}</div>`; }
            return `<div class="item-titulo">${item.nombre}</div><div class="item-resumen">${detalle}</div>`;
        }

        function serializarItem(item){
            const payload = {
                id: parseInt(item.id),
                nombre: item.nombre,
                producto_nombre: item.producto_nombre || item.nombre,
                modalidad: item.modalidad || 'solo',
                precio_base: parseFloat(item.precio_base || 0),
                incremento_modalidad: parseFloat(item.incremento_modalidad || 0),
                precio: parseFloat(item.precio),
                cantidad: parseInt(item.cantidad)
            };

            if(item.nota){ payload.nota = item.nota; }

            if(Array.isArray(item.extras) && item.extras.length > 0){
                payload.extras = item.extras.map(extra => {
                    const cantidad = Math.max(1, parseInt(extra.cantidad || 1));
                    const precioUnitario = parseFloat(extra.precio_unitario ?? extra.precio ?? 0);
                    const subtotal = parseFloat(extra.subtotal ?? (precioUnitario * cantidad));

                    return {
                        extra_id: extra.extra_id ?? extra.id ?? null,
                        nombre_personalizado: extra.nombre_personalizado ?? extra.nombre ?? null,
                        cantidad,
                        precio_unitario: precioUnitario,
                        subtotal,
                        precio: subtotal,
                        nota: extra.nota || null
                    };
                });
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

            const extras = [...(item.extras || [])].map(extra => {
                const cantidad = Math.max(1, Number(extra.cantidad || 1));
                const precioUnitario = Number(extra.precio_unitario ?? extra.precio ?? 0);
                const subtotal = Number(extra.subtotal ?? (precioUnitario * cantidad));

                return {
                    extra_id: extra.extra_id ?? null,
                    nombre_personalizado: extra.nombre_personalizado ?? extra.nombre ?? '',
                    cantidad,
                    precio_unitario: precioUnitario.toFixed(2),
                    subtotal: subtotal.toFixed(2),
                    nota: extra.nota || null
                };
            }).sort((a, b) => JSON.stringify(a).localeCompare(JSON.stringify(b)));

            return JSON.stringify({ id: Number(item.id), modalidad: item.modalidad || 'solo', nota: item.nota || null, opciones, extras });
        }

        function agregarATicket(item){
            const firma = firmaItem(item);
            const existente = ticketNuevo.find(actual => firmaItem(actual) === firma);
            if(existente){ existente.cantidad += item.cantidad || 1; }
            else { ticketNuevo.push(item); }
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
                body:JSON.stringify({ mesa:{{ $mesa }}, productos:ticketFinal, productosNuevos:ticketNuevoActual })
            })
            .then(async res => {
                const texto = await res.text();
                if (!res.ok) { throw new Error(texto); }
                return JSON.parse(texto);
            });
        }

        function obtenerSeleccionadas(){
            return Object.values(seleccionesConfigActual).flat();
        }

        function esGrupoSalsa(grupo){
            if(!grupo){ return false; }
            if(grupo.is_salsa){ return true; }
            return normalizarClave(grupo.nombre || '') === 'salsa';
        }

        function grupoEsObligatorio(grupo){
            return Boolean(grupo?.obligatorio) || esGrupoSalsa(grupo);
        }

        function esGrupoVisible(grupo){
            const modalidadGrupo = grupo.modalidad || 'todas';
            if(modalidadGrupo !== 'todas' && modalidadGrupo !== modalidadActual){ return false; }
            if(!grupo.visible_if_option_id){ return true; }
            return obtenerSeleccionadas().some(opcion => Number(opcion.opcion_id) === Number(grupo.visible_if_option_id));
        }

        function limpiarSeleccionesOcultas(){
            if(!productoConfigActual){ return; }
            (productoConfigActual.grupos || []).forEach(grupo => {
                if(!esGrupoVisible(grupo)){ delete seleccionesConfigActual[grupo.key]; }
            });
        }

        function nombreGuardadoOpcion(grupo, opcion){
            return `${grupo.nombre}: ${etiquetaOpcion(opcion)}`;
        }

        function opcionesSeleccionadasVisibles(){
            if(!productoConfigActual){ return []; }
            return (productoConfigActual.grupos || [])
                .filter(grupo => esGrupoVisible(grupo))
                .flatMap(grupo => (seleccionesConfigActual[grupo.key] || []).map(opcion => ({
                    opcion_id: opcion.opcion_id ?? null,
                    nombre: nombreGuardadoOpcion(grupo, opcion),
                    incremento_precio: Number(opcion.incremento_precio || 0),
                    incremento_costo: Number(opcion.incremento_costo || 0)
                })));
        }

        function extrasCatalogo(){
            const extras = (extrasCatalogoBase || []).map(extra => ({
                key: `extra_${extra.id}`,
                extra_id: Number(extra.id),
                nombre: (extra.nombre || '').trim(),
                precio: Number(extra.precio || 0),
                is_otro: normalizarClave(extra.nombre || '') === 'otro'
            }));

            if(!extras.some(extra => extra.is_otro)){
                extras.push({ key: 'extra_otro_virtual', extra_id: null, nombre: 'Otro', precio: 0, is_otro: true });
            }

            return extras;
        }

        function extrasSeleccionadosLista(){
            const catalogo = extrasCatalogo();
            const seleccionados = [];

            catalogo.forEach(extra => {
                const cantidad = Number(extrasSeleccionados[extra.key] || 0);
                if(cantidad < 1){ return; }

                if(extra.is_otro){
                    const nombre = (extraOtroNombre || '').trim();
                    const precio = Number(extraOtroPrecio || 0);
                    const precioUnitario = Number.isFinite(precio) && precio >= 0 ? precio : 0;
                    if(nombre !== ''){
                        seleccionados.push({
                            extra_id: null,
                            nombre: nombre,
                            nombre_personalizado: nombre,
                            cantidad: cantidad,
                            precio_unitario: precioUnitario,
                            subtotal: precioUnitario * cantidad,
                            precio: precioUnitario * cantidad
                        });
                    }
                    return;
                }

                seleccionados.push({
                    extra_id: extra.extra_id,
                    nombre: extra.nombre,
                    nombre_personalizado: null,
                    cantidad: cantidad,
                    precio_unitario: extra.precio,
                    subtotal: Number(extra.precio || 0) * cantidad,
                    precio: Number(extra.precio || 0) * cantidad
                });
            });

            return seleccionados;
        }

        function precioConfiguradoActual(){
            if(!productoConfigActual){ return 0; }

            const opciones = opcionesSeleccionadasVisibles();
            const incrementoOpciones = opciones.reduce((total, opcion) => total + Number(esEmpleado ? opcion.incremento_costo : opcion.incremento_precio), 0);
            const incrementoModo = incrementoModalidad(productoConfigActual, modalidadActual);
            const totalExtras = extrasSeleccionadosLista().reduce((total, extra) => total + Number(extra.subtotal || 0), 0);

            return Number(productoConfigActual.precio_venta || 0) + incrementoModo + incrementoOpciones + totalExtras;
        }

        function actualizarResumenModal(){
            if(!productoConfigActual){
                modalConfigTotal.textContent = '';
                return;
            }

            const precio = precioConfiguradoActual();
            const nombreModalidad = modalidadActual === 'desayuno'
                ? 'Paquete desayuno'
                : (modalidadActual === 'comida' ? 'Comida' : 'Solo');

            modalConfigTotal.textContent = `Modalidad: ${productoConfigActual.es_comida_dia ? 'Comida del dia' : nombreModalidad} | Precio final: $${precio.toFixed(2)}`;
        }

        function renderExtrasSection(){
            const wrapper = document.createElement('div');
            wrapper.className = 'grupo-config';
            wrapper.innerHTML = '<h4>Extras</h4>';

            const toggleRow = document.createElement('label');
            toggleRow.className = 'toggle-row';
            const toggle = document.createElement('input');
            toggle.type = 'checkbox';
            toggle.checked = extrasActivos;
            toggle.addEventListener('change', function(){
                extrasActivos = this.checked;
                if(!extrasActivos){
                    extrasSeleccionados = {};
                    extraOtroNombre = '';
                    extraOtroPrecio = '';
                }
                renderModalConfiguracion();
            });
            const toggleText = document.createElement('span');
            toggleText.textContent = 'Agregar extras';
            toggleRow.appendChild(toggle);
            toggleRow.appendChild(toggleText);
            wrapper.appendChild(toggleRow);

            if(!extrasActivos){
                const hint = document.createElement('div');
                hint.className = 'grupo-ayuda';
                hint.textContent = 'Activa para seleccionar uno o varios extras.';
                wrapper.appendChild(hint);
                return wrapper;
            }

            const list = document.createElement('div');
            list.className = 'extras-list';
            const catalogo = extrasCatalogo();

            catalogo.forEach(extra => {
                const item = document.createElement('div');
                item.className = 'extra-item';

                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox';
                input.checked = Number(extrasSeleccionados[extra.key] || 0) > 0;
                input.addEventListener('change', function(){
                    extrasSeleccionados[extra.key] = this.checked ? 1 : 0;
                    renderModalConfiguracion();
                });

                const text = document.createElement('span');
                text.textContent = extra.nombre;

                label.appendChild(input);
                label.appendChild(text);

                const right = document.createElement('div');
                right.className = 'extra-right';

                const price = document.createElement('span');
                price.className = 'extra-price';
                price.textContent = extra.is_otro ? 'Manual' : `+$${Number(extra.precio || 0).toFixed(2)}`;
                right.appendChild(price);

                const cantidadActual = Number(extrasSeleccionados[extra.key] || 0);
                if(cantidadActual > 0){
                    const qtyWrap = document.createElement('div');
                    qtyWrap.className = 'extra-cantidad';

                    const minus = document.createElement('button');
                    minus.type = 'button';
                    minus.textContent = '-';
                    minus.addEventListener('click', function(){
                        const nueva = Math.max(0, Number(extrasSeleccionados[extra.key] || 0) - 1);
                        extrasSeleccionados[extra.key] = nueva;
                        renderModalConfiguracion();
                    });

                    const qty = document.createElement('span');
                    qty.textContent = String(cantidadActual);

                    const plus = document.createElement('button');
                    plus.type = 'button';
                    plus.textContent = '+';
                    plus.addEventListener('click', function(){
                        extrasSeleccionados[extra.key] = Number(extrasSeleccionados[extra.key] || 0) + 1;
                        renderModalConfiguracion();
                    });

                    qtyWrap.appendChild(minus);
                    qtyWrap.appendChild(qty);
                    qtyWrap.appendChild(plus);
                    right.appendChild(qtyWrap);

                    const subtotal = document.createElement('span');
                    subtotal.className = 'extra-subtotal';
                    const unit = extra.is_otro ? Number(extraOtroPrecio || 0) : Number(extra.precio || 0);
                    subtotal.textContent = `$${(Math.max(0, unit) * cantidadActual).toFixed(2)}`;
                    right.appendChild(subtotal);
                }

                item.appendChild(label);
                item.appendChild(right);
                list.appendChild(item);
            });

            wrapper.appendChild(list);

            const otro = catalogo.find(extra => extra.is_otro);
            if(otro && extrasSeleccionados[otro.key]){
                const fields = document.createElement('div');
                fields.className = 'otro-extra-fields';

                const nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.placeholder = 'Nombre del extra (ej. aguacate)';
                nameInput.value = extraOtroNombre;
                nameInput.addEventListener('input', function(){
                    extraOtroNombre = this.value;
                    actualizarResumenModal();
                });

                const priceInput = document.createElement('input');
                priceInput.type = 'number';
                priceInput.min = '0';
                priceInput.step = '0.01';
                priceInput.placeholder = 'Precio';
                priceInput.value = extraOtroPrecio;
                priceInput.addEventListener('input', function(){
                    extraOtroPrecio = this.value;
                    actualizarResumenModal();
                });

                fields.appendChild(nameInput);
                fields.appendChild(priceInput);
                wrapper.appendChild(fields);
            }

            return wrapper;
        }

        function renderNotasSection(){
            const wrapper = document.createElement('div');
            wrapper.className = 'grupo-config nota-area';
            wrapper.innerHTML = '<h4>Notas</h4>';

            const toggleRow = document.createElement('label');
            toggleRow.className = 'toggle-row';
            const toggle = document.createElement('input');
            toggle.type = 'checkbox';
            toggle.checked = notasActivas;
            toggle.addEventListener('change', function(){
                notasActivas = this.checked;
                if(!notasActivas){ notaActual = ''; }
                renderModalConfiguracion();
            });
            const toggleText = document.createElement('span');
            toggleText.textContent = 'Agregar nota para este producto';
            toggleRow.appendChild(toggle);
            toggleRow.appendChild(toggleText);
            wrapper.appendChild(toggleRow);

            if(!notasActivas){
                const hint = document.createElement('div');
                hint.className = 'grupo-ayuda';
                hint.textContent = 'Activa para escribir instrucciones como "sin crema" o "poco doradas".';
                wrapper.appendChild(hint);
                return wrapper;
            }

            const textarea = document.createElement('textarea');
            textarea.placeholder = 'Escribe la observacion...';
            textarea.value = notaActual;
            textarea.addEventListener('input', function(){ notaActual = this.value; });
            wrapper.appendChild(textarea);

            return wrapper;
        }

        function renderModalConfiguracion(){
            if(!productoConfigActual){ return; }

            limpiarSeleccionesOcultas();
            modalConfigTitulo.textContent = productoConfigActual.nombre;
            modalConfigSubtitulo.textContent = productoConfigActual.es_comida_dia
                ? 'Selecciona los tiempos de la comida del dia.'
                : 'Selecciona modalidad y opciones. Extras y notas son opcionales.';
            modalConfigBody.innerHTML = '';

            const modalidades = modalidadesDisponibles(productoConfigActual);
            if(modalidades.length > 0){
                const modalidadWrap = document.createElement('div');
                modalidadWrap.className = 'modalidad-bloque';
                modalidadWrap.innerHTML = '<h4>Modalidad</h4><div class="grupo-ayuda">Seleccion exclusiva. El precio se ajusta automaticamente.</div>';

                const opcionesWrap = document.createElement('div');
                opcionesWrap.className = 'modalidad-opciones';

                modalidades.forEach(modalidad => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'opcion-btn';
                    button.textContent = modalidad.incremento > 0 ? `${modalidad.label} (+$${modalidad.incremento.toFixed(2)})` : modalidad.label;
                    if(modalidadActual === modalidad.key){ button.classList.add('activa'); }
                    button.addEventListener('click', function(){ modalidadActual = modalidad.key; renderModalConfiguracion(); });
                    opcionesWrap.appendChild(button);
                });

                modalidadWrap.appendChild(opcionesWrap);
                modalConfigBody.appendChild(modalidadWrap);
            }

            const gruposVisibles = (productoConfigActual.grupos || []).filter(grupo => esGrupoVisible(grupo));
            const gruposOrdenados = [
                ...gruposVisibles.filter(grupo => esGrupoSalsa(grupo)),
                ...gruposVisibles.filter(grupo => !esGrupoSalsa(grupo))
            ];

            gruposOrdenados.forEach(grupo => {

                const wrapper = document.createElement('div');
                wrapper.className = 'grupo-config';
                const obligatoria = grupoEsObligatorio(grupo) ? 'Obligatorio' : 'Opcional';
                const multiple = (grupo.multiple && !esGrupoSalsa(grupo)) ? 'multiple' : 'una sola opcion';
                wrapper.innerHTML = `<h4>${grupo.nombre}</h4><div class="grupo-ayuda">${obligatoria} | Selecciona ${multiple}</div>`;

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
                    if(seleccionadas.some(actual => (actual.key || '') === (opcion.key || ''))){ button.classList.add('activa'); }

                    button.addEventListener('click', function(){
                        const actuales = [...(seleccionesConfigActual[grupo.key] || [])];
                        const index = actuales.findIndex(actual => (actual.key || '') === (opcion.key || ''));
                        const permiteMultiple = Boolean(grupo.multiple) && !esGrupoSalsa(grupo);

                        if(permiteMultiple){
                            if(index >= 0){ actuales.splice(index, 1); } else { actuales.push(opcion); }
                        }else if(index >= 0){
                            actuales.splice(index, 1);
                        }else{
                            actuales.splice(0, actuales.length, opcion);
                        }

                        seleccionesConfigActual[grupo.key] = actuales;
                        renderModalConfiguracion();
                    });

                    opcionesWrap.appendChild(button);
                });

                wrapper.appendChild(opcionesWrap);
                modalConfigBody.appendChild(wrapper);
            });

            modalConfigBody.appendChild(renderExtrasSection());
            modalConfigBody.appendChild(renderNotasSection());
            actualizarResumenModal();
        }

        function abrirModalConfiguracion(producto){
            productoConfigActual = producto;
            modalidadActual = modalidadPorDefecto(producto);
            seleccionesConfigActual = {};
            extrasActivos = false;
            notasActivas = false;
            extrasSeleccionados = {};
            extraOtroNombre = '';
            extraOtroPrecio = '';
            notaActual = '';
            renderModalConfiguracion();
            modalConfig.classList.remove('oculto');
        }

        function cerrarModalConfiguracion(){
            modalConfig.classList.add('oculto');
            productoConfigActual = null;
            modalidadActual = 'solo';
            seleccionesConfigActual = {};
            extrasActivos = false;
            notasActivas = false;
            extrasSeleccionados = {};
            extraOtroNombre = '';
            extraOtroPrecio = '';
            notaActual = '';
        }

        function validarConfiguracion(){
            if(!productoConfigActual){ return false; }

            for(const grupo of (productoConfigActual.grupos || [])){
                if(!esGrupoVisible(grupo)){ continue; }

                const seleccionadas = seleccionesConfigActual[grupo.key] || [];
                if(grupoEsObligatorio(grupo) && seleccionadas.length === 0){
                    alert(`Selecciona una opcion para ${grupo.nombre}`);
                    return false;
                }

                if(grupoEsObligatorio(grupo) && (!grupo.options || grupo.options.length === 0)){
                    alert(`No hay opciones disponibles para ${grupo.nombre}`);
                    return false;
                }
            }

            const otroSeleccionado = extrasCatalogo().find(extra => extra.is_otro && Number(extrasSeleccionados[extra.key] || 0) > 0);
            if(otroSeleccionado){
                if((extraOtroNombre || '').trim() === ''){
                    alert('Captura el nombre del extra en "Otro".');
                    return false;
                }
                const precio = Number(extraOtroPrecio || 0);
                if(!Number.isFinite(precio) || precio < 0){
                    alert('El precio del extra "Otro" debe ser un numero valido mayor o igual a 0.');
                    return false;
                }
            }

            return true;
        }

        function construirItemConfigurado(){
            const opciones = opcionesSeleccionadasVisibles();
            const extras = extrasSeleccionadosLista();
            const incrementoModo = incrementoModalidad(productoConfigActual, modalidadActual);
            const precioBase = Number(productoConfigActual.precio_venta || 0);
            const precio = precioBase
                + incrementoModo
                + opciones.reduce((total, opcion) => total + Number(esEmpleado ? opcion.incremento_costo : opcion.incremento_precio), 0)
                + extras.reduce((total, extra) => total + Number(extra.subtotal || 0), 0);

            const detalleCliente = [
                ...detalleClienteBase(productoConfigActual, opciones, modalidadActual),
                ...extras.map(extra => `${extra.nombre_personalizado || extra.nombre || ''} x${extra.cantidad || 1}`)
            ].filter(Boolean);

            const nota = notasActivas ? (notaActual || '').trim() : '';

            return {
                id: productoConfigActual.id,
                producto_nombre: productoConfigActual.nombre,
                es_comida_dia: !!productoConfigActual.es_comida_dia,
                modalidad: modalidadActual,
                nombre: nombreComercial(productoConfigActual, modalidadActual),
                detalle_cliente: detalleCliente,
                precio_base: precioBase,
                incremento_modalidad: incrementoModo,
                precio,
                cantidad: 1,
                opciones,
                extras,
                nota: nota || null
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
                const producto = productosMap.get(String(id));
                if(!producto){
                    alert('No se pudo cargar la configuracion del producto.');
                    return;
                }
                abrirModalConfiguracion(producto);
            });
        });

        btnCancelarConfig.addEventListener('click', cerrarModalConfiguracion);
        btnConfirmarConfig.addEventListener('click', function(){
            if(!validarConfiguracion()){ return; }
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
                body:JSON.stringify({ mesa:{{ $mesa }}, productos:ticketFinal, metodo_pago:metodoPagoSeleccionado.value })
            })
            .then(async res => {
                const texto = await res.text();
                if (!res.ok) { throw new Error(texto); }
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
                body:JSON.stringify({ mesa:{{ $mesa }}, productos:ticketFinal })
            })
            .then(async res => {
                const texto = await res.text();
                if (!res.ok) { throw new Error(texto); }
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
