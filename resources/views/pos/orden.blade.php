
<!DOCTYPE html>
<html>
<head>
    <title>POS</title>

    <style>
        *,*::before,*::after{box-sizing:border-box;}
        html,body{height:100%;margin:0;}
        body{font-family:Arial;padding:16px;background:#f7f1ea;color:#241813;display:flex;flex-direction:column;gap:14px;height:100dvh;min-height:100dvh;overflow:hidden;}
        .controles-cantidad{display:flex;align-items:center;gap:8px;}
        .btn-cantidad{width:28px;height:28px;border:none;border-radius:6px;background:#ddd;cursor:pointer;font-weight:bold;font-size:16px;padding:0;line-height:28px;text-align:center;color:#333;}
        .cantidad-numero{min-width:20px;text-align:center;font-weight:bold;}
        .header-links{display:flex;gap:12px;align-items:center;}
        .encabezado-pos{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-shrink:0;}
        .encabezado-pos h1{margin:0;}
        .btn-mesas{display:inline-block;padding:10px 16px;color:white;text-decoration:none;border-radius:8px;font-size:16px;}
        .btn-mesas{background:#3498db;}
        .contenedor{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(360px,1fr);gap:18px;flex:1;min-height:0;height:100%;overflow:hidden;}
        .panel-productos{min-width:0;min-height:0;overflow-y:auto;padding-right:6px;}
        .buscar{margin-bottom:14px;}
        input, textarea, select{padding:10px;font-size:16px;width:300px;}
        .categorias{margin-bottom:14px;display:flex;flex-wrap:wrap;gap:10px 12px;align-items:flex-start;}
        .categoria{display:inline-flex;align-items:center;padding:10px 20px;background:#eee;margin-right:0;border-radius:8px;cursor:pointer;}
        .categoria.activa{background:#5a3828;color:#fff;}
        .productos{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;padding-bottom:16px;}
        .producto{padding:24px 16px;background:#fffdf9;border:1px solid #eadccf;border-radius:12px;text-align:center;cursor:pointer;box-shadow:0 10px 20px rgba(62,39,20,0.08);min-height:110px;display:flex;flex-direction:column;justify-content:center;font-size:18px;}
        .producto small{display:block;margin-top:8px;color:#7b6659;}
        .ticket{background:#fffdf9;border:1px solid #ddd;border-radius:12px;display:flex;flex-direction:column;min-width:0;min-height:0;height:100%;overflow:hidden;}
        .ticket-scroll{padding:18px 18px 8px;overflow-y:auto;min-height:0;flex:1;scrollbar-gutter:stable;}
        .ticket-acciones{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:12px;padding:14px 14px 16px;background:#fffdf9;border-top:1px solid #e5d7cb;flex-shrink:0;}
        .bloque-ticket{margin-bottom:25px;padding-bottom:15px;border-bottom:1px solid #ddd;}
        .bloque-ticket h3{margin-bottom:15px;}
        .item-ticket{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:10px;padding:8px 0;}
        .item-info{flex:1;}
        .item-titulo{font-weight:bold;font-size:14px;line-height:1.3;}
        .item-detalle-lista{margin-top:6px;display:flex;flex-direction:column;gap:2px;}
        .item-detalle-linea{font-size:12px;color:#7a665b;line-height:1.3;}
        .item-detalle-linea.nota{font-style:italic;color:#6b4a39;}
        .acciones{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:flex-end;}
        .acciones-top{display:flex;align-items:center;gap:8px;}
        .acciones-total{font-size:13px;font-weight:700;color:#3b281f;min-width:72px;text-align:right;}
        .btn-accion{margin:0;padding:0;width:28px;height:28px;border-radius:7px;border:none;cursor:pointer;font-size:14px;line-height:28px;text-align:center;}
        .btn-editar{background:#d8c0af;color:#3b281f;}
        .btn-eliminar{background:#ffdede;color:#9b1c1c;}
        .total{margin-top:14px;margin-bottom:8px;font-size:22px;font-weight:bold;}
        button{width:100%;padding:15px;margin-top:15px;color:white;border:none;border-radius:8px;font-size:18px;cursor:pointer;}
        .ticket-acciones button{margin:0;width:100%;height:64px;min-height:64px;padding:10px 8px;font-size:14px;font-weight:700;border-radius:10px;display:flex;align-items:center;justify-content:center;text-align:center;line-height:1.2;}
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
        .extras-list{display:grid;grid-template-columns:1fr;gap:8px;max-height:min(38vh, 320px);overflow-y:auto;padding-right:4px;}
        .extras-buscar{margin-bottom:10px;}
        .extras-buscar input{width:100%;padding:10px 12px;font-size:14px;border:1px solid #d6c2b0;border-radius:10px;background:#fff;}
        .extras-sin-resultados{display:none;padding:6px 2px;color:#8b6f61;font-size:13px;}
        .extra-item{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid #e3d2c1;border-radius:10px;background:#fff;}
        .extra-item label{display:flex;align-items:center;gap:8px;margin:0;cursor:pointer;font-size:14px;}
        .extra-item input[type="checkbox"]{width:16px;height:16px;}
        .extra-price{font-size:13px;color:#6b4a39;}
        .extra-right{display:flex;align-items:center;gap:10px;}
        .extra-cantidad{display:flex;align-items:center;gap:6px;}
        .extra-cantidad button{margin:0;width:28px;height:28px;padding:0;border-radius:7px;font-size:14px;background:#d8c0af;color:#3b281f;}
        .extra-cantidad span{min-width:20px;text-align:center;font-weight:700;}
        .extra-subtotal{font-size:12px;color:#6b4a39;min-width:72px;text-align:right;}
        .otro-extra-fields{display:grid;grid-template-columns:1fr 140px;gap:10px;margin-top:10px;margin-bottom:10px;}
        .otro-extra-fields input{width:100%;}
        .nota-area textarea{width:100%;min-height:78px;resize:vertical;border:1px solid #d6c2b0;border-radius:10px;padding:10px;font-size:14px;}
        .otro-manual-grid{display:grid;grid-template-columns:1fr 160px;gap:12px;}
        .otro-manual-grid label{display:flex;flex-direction:column;gap:6px;font-size:13px;color:#5b4638;font-weight:600;}
        .otro-manual-grid input,.otro-manual-grid select{width:100%;padding:10px 12px;font-size:15px;border:1px solid #d6c2b0;border-radius:10px;background:#fff;}
        .otro-manual-grid .full{grid-column:1/-1;}
        @media(max-width:1360px){
            .ticket-acciones{gap:10px;padding:12px 12px 14px;}
            .ticket-acciones button{height:60px;min-height:60px;font-size:13px;}
        }
        @media(max-width:1000px){
            body{padding:10px;gap:10px;height:100dvh;min-height:100dvh;overflow:hidden;}
            .encabezado-pos{flex-wrap:wrap;gap:10px;}
            .encabezado-pos img{height:56px !important;}
            .header-links{flex-wrap:wrap;justify-content:flex-end;}
            .contenedor{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px;}
            .panel-productos{overflow-y:auto;padding-right:4px;}
            .ticket{min-height:0;max-height:none;height:100%;}
            .ticket-scroll{max-height:none;overflow-y:auto;}
            .ticket-acciones{grid-template-columns:1fr;gap:8px;padding:8px;}
            .ticket-acciones button{height:52px;min-height:52px;padding:8px 6px;font-size:12px;border-radius:9px;}
            input, textarea, select{width:100%;}
            .buscar{margin-bottom:10px;}
            .categorias{display:flex;gap:6px;overflow-x:auto;padding-bottom:4px;white-space:nowrap;}
            .categoria{flex:0 0 auto;padding:8px 12px;margin-right:0;}
            .productos{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}
            .producto{padding:14px 10px;min-height:92px;font-size:15px;}
            .item-ticket{flex-direction:column;align-items:stretch;gap:6px;}
            .acciones{justify-content:space-between;}
            .acciones-top{flex-wrap:wrap;}
            .acciones-total{text-align:left;min-width:0;}
            .otro-extra-fields{grid-template-columns:1fr;}
        }
    </style>
</head>

<body>
    <div class="encabezado-pos" style="margin-bottom:5px;">
        <h1>{{ $mesaLabel }}</h1>
        <img src="{{ asset('images/logo.png') }}" alt="Cafeteria" style="height:80px;">
        <div class="header-links">
            <a href="/mesas" class="btn-mesas">Volver a mesas</a>
        </div>
    </div>

    <div class="contenedor">
        <div class="panel-productos">
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
            <div class="ticket-scroll">
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
            </div>

            <div class="ticket-acciones">
                <button id="guardar-orden">Ordenar / Guardar</button>
                <button id="imprimir-cuenta">Imprimir ticket</button>
                <button id="cerrar-cuenta">Cerrar cuenta</button>
            </div>
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
        const desayunoGruposCatalogo = @json($desayunoGruposJson ?? []);
        const productosMap = new Map(productosConfig.map(producto => [String(producto.id), producto]));

        let categoriaActual = 'all';
        let ticketBase = [];
        let ticketNuevo = [];
        let productoConfigActual = null;
        let modalidadActual = 'solo';
        let seleccionesConfigActual = {};
        let edicionActual = null;

        let extrasActivos = false;
        let notasActivas = false;
        let extrasSeleccionados = {};
        let extraOtroNombre = '';
        let extraOtroPrecio = '';
        let extrasBusqueda = '';
        let notaActual = '';
        let otroManualDescripcion = '';
        let otroManualPrecio = '';
        let otroManualArea = '';

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

        function normalizarComparacion(valor){
            return normalizarTexto(valor)
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
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

        function esEtiquetaPlatilloDia(nombreProducto, opciones){
            const nombre = normalizarComparacion(nombreProducto || '');
            const porNombre = nombre === 'platillo' || nombre.includes('platillo');
            if(porNombre){ return true; }

            const entradas = extraerEntradasOpcion(opciones || []);
            const modalidadSeleccion = normalizarClave(buscarValorGrupo(entradas, ['modalidad']) || '');
            return modalidadSeleccion.includes('platillo');
        }

        function etiquetaComidaDelDia(nombreProducto, opciones){
            return esEtiquetaPlatilloDia(nombreProducto, opciones) ? 'Platillo del dia' : 'Comida del dia';
        }

        function nombreComercial(producto, modalidad, opciones = []){
            if(producto.es_comida_dia){ return etiquetaComidaDelDia(producto.nombre, opciones); }
            if(modalidad === 'desayuno'){ return `${producto.nombre} / Paquete desayuno`; }
            if(modalidad === 'comida'){ return `${producto.nombre} / Comida`; }
            return producto.nombre;
        }

        function esProductoManualOtro(producto){
            if(!producto){ return false; }
            if(producto.is_otro_manual === true){ return true; }

            const sku = normalizarComparacion(producto.sku || '');
            const nombre = normalizarComparacion(producto.nombre || '');

            return sku === 'otro' || nombre === 'otro';
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

        function escapeHtml(value){
            return (value || '')
                .toString()
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#39;');
        }

        function modalidadDetalle(item){
            if(item.es_comida_dia){
                const etiqueta = etiquetaComidaDelDia(item.producto_nombre || item.nombre, item.opciones || []);
                return `Modalidad: ${etiqueta}`;
            }
            if(item.modalidad === 'desayuno'){ return 'Modalidad: Paquete desayuno'; }
            if(item.modalidad === 'comida'){ return 'Modalidad: Comida'; }
            return '';
        }

        function detalleLineasOpciones(item){
            const entradas = extraerEntradasOpcion(item.opciones || []);
            const grouped = new Map();

            entradas.forEach(entrada => {
                if(!entrada.value){ return; }
                if(entrada.groupKey === 'modalidad'){ return; }

                const label = entrada.groupLabel || 'Opcion';
                const actual = grouped.get(label) || [];
                actual.push(entrada.value);
                grouped.set(label, actual);
            });

            const lines = [];
            grouped.forEach((values, label) => {
                const unico = [...new Set(values)];
                lines.push(`${label}: ${unico.join(', ')}`);
            });

            return lines;
        }

        function detalleLineasExtras(item){
            return (item.extras || [])
                .map(extra => {
                    const name = (extra.nombre_personalizado || extra.nombre || '').toString().trim();
                    if(!name){ return null; }
                    const cantidad = Math.max(1, Number(extra.cantidad || 1));
                    return `Extra: ${name} x${cantidad}`;
                })
                .filter(Boolean);
        }

        function detalleVisualLineas(item){
            const lines = [];
            const modalidad = modalidadDetalle(item);
            if(modalidad){ lines.push({ text: modalidad, type: 'normal' }); }

            detalleLineasOpciones(item).forEach(line => lines.push({ text: line, type: 'normal' }));
            detalleLineasExtras(item).forEach(line => lines.push({ text: line, type: 'normal' }));

            if(item.nota){
                lines.push({ text: `Nota: ${item.nota}`, type: 'nota' });
            }

            return lines;
        }

        function descripcionItem(item){
            const titulo = `<div class="item-titulo">${escapeHtml(item.nombre || '')}</div>`;
            const lines = detalleVisualLineas(item);
            if(lines.length === 0){ return titulo; }

            const detailHtml = lines
                .map(line => `<div class="item-detalle-linea ${line.type === 'nota' ? 'nota' : ''}">- ${escapeHtml(line.text)}</div>`)
                .join('');

            return `${titulo}<div class="item-detalle-lista">${detailHtml}</div>`;
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

            if(item.es_otro_manual){
                payload.es_otro_manual = true;
                payload.nombre_personalizado = (item.otro_descripcion || item.nombre || '').toString().trim();
                payload.area_preparacion = item.otro_area || null;
                payload.precio_manual = parseFloat(item.precio);
            }

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

            const otroManual = item.es_otro_manual ? {
                es_otro_manual: true,
                descripcion: (item.otro_descripcion || item.nombre || '').toString().trim(),
                area: item.otro_area || null,
                precio: Number(item.precio || 0).toFixed(2)
            } : { es_otro_manual: false };

            return JSON.stringify({ id: Number(item.id), modalidad: item.modalidad || 'solo', nota: item.nota || null, otro_manual: otroManual, opciones, extras });
        }

        function combinarLineaEnLista(lista, item){
            const firma = firmaItem(item);
            const existente = lista.find(actual => firmaItem(actual) === firma);
            if(existente){
                existente.cantidad += Math.max(1, Number(item.cantidad || 1));
                return;
            }

            lista.push(item);
        }

        function agregarATicket(item){
            combinarLineaEnLista(ticketNuevo, item);
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
            if(esGrupoSalsa(grupo)){ return true; }

            const key = claveGrupo(grupo);
            if(modalidadActual === 'desayuno' && (key === 'bebida_del_paquete' || key === 'fruta_del_paquete' || key === 'fruta')){
                return false;
            }

            return Boolean(grupo?.obligatorio);
        }

        function claveGrupo(grupo){
            if(!grupo){ return ''; }
            return normalizarComparacion(grupo.slug || grupo.nombre || '');
        }

        function clonarGrupoParaModal(grupo, overrides = {}){
            return {
                ...grupo,
                ...overrides,
                options: (grupo?.options || []).map(opcion => ({ ...opcion })),
            };
        }

        function buscarGrupoCatalogoPorClaves(claves){
            const wanted = new Set((claves || []).map(clave => normalizarComparacion(clave)).filter(Boolean));
            if(wanted.size === 0){ return null; }

            for(const grupo of (desayunoGruposCatalogo || [])){
                if(!Array.isArray(grupo.options) || grupo.options.length === 0){ continue; }
                if(wanted.has(claveGrupo(grupo))){ return grupo; }
            }

            for(const producto of (productosConfig || [])){
                for(const grupo of (producto.grupos || [])){
                    if(!Array.isArray(grupo.options) || grupo.options.length === 0){ continue; }
                    if(!wanted.has(claveGrupo(grupo))){ continue; }
                    return grupo;
                }
            }

            return null;
        }

        function opcionesSeleccionadasPorGroupSlugs(slugs){
            if(!Array.isArray(slugs) || slugs.length === 0){ return []; }

            const keys = new Set(slugs.map(slug => normalizarClave(slug || '')).filter(Boolean));
            const resultado = [];

            gruposConfigurablesActuales().forEach(grupo => {
                if(!keys.has(claveGrupo(grupo))){ return; }
                (seleccionesConfigActual[grupo.key] || []).forEach(opcion => resultado.push(opcion));
            });

            return resultado;
        }

        function coincideSeleccionConSlugs(opcion, slugs){
            if(!Array.isArray(slugs) || slugs.length === 0){ return true; }
            const targets = slugs.map(slug => normalizarComparacion(slug || '')).filter(Boolean);
            if(targets.length === 0){ return true; }

            const optionValues = [
                opcion?.slug || '',
                etiquetaOpcion(opcion),
                opcion?.nombre || ''
            ].map(value => normalizarComparacion(value)).filter(Boolean);

            const coincideTarget = (value, target) => {
                if(value === target){ return true; }
                if(value.endsWith(`_${target}`)){ return true; }
                const tokens = value.split('_').filter(Boolean);
                return tokens.includes(target);
            };

            return targets.some(target => optionValues.some(value => coincideTarget(value, target)));
        }

        function grupoVirtualBebidaDesayuno(){
            return {
                key: 'grupo_desayuno_bebida',
                slug: 'bebida_del_paquete',
                nombre: 'Bebida del paquete',
                modalidad: 'desayuno',
                obligatorio: false,
                multiple: false,
                visible_if_option_id: null,
                options: [
                    {
                        key: 'opcion_virtual_desayuno_bebida_cafe',
                        opcion_id: null,
                        slug: 'cafe_americano',
                        nombre: 'Bebida del paquete: Cafe Americano',
                        label: 'Cafe',
                        incremento_precio: 0,
                        incremento_costo: 0,
                        virtual_code: 'desayuno_bebida_cafe',
                    },
                    {
                        key: 'opcion_virtual_desayuno_bebida_te',
                        opcion_id: null,
                        slug: 'te',
                        nombre: 'Bebida del paquete: Te',
                        label: 'Te',
                        incremento_precio: 0,
                        incremento_costo: 0,
                        virtual_code: 'desayuno_bebida_te',
                    },
                ],
            };
        }

        function gruposDinamicosDesayuno(){
            if(!productoConfigActual || productoConfigActual.es_comida_dia){ return []; }
            if(modalidadActual !== 'desayuno'){ return []; }

            const baseGroups = productoConfigActual.grupos || [];
            const baseKeys = new Set(baseGroups.map(grupo => claveGrupo(grupo)));
            const dynamic = [];

            if(!baseKeys.has('bebida_del_paquete') && !baseKeys.has('bebida')){
                dynamic.push(grupoVirtualBebidaDesayuno());
            }

            const saborTeSource = buscarGrupoCatalogoPorClaves(['sabor_te']);
            if(saborTeSource && !baseKeys.has('sabor_te')){
                dynamic.push(clonarGrupoParaModal(saborTeSource, {
                    key: 'grupo_desayuno_sabor_te',
                    modalidad: 'desayuno',
                    obligatorio: true,
                    multiple: false,
                    visible_if_option_id: null,
                    visible_if_group_slugs: ['bebida_del_paquete', 'bebida'],
                    visible_if_option_slugs: ['te'],
                }));
            }

            const frutaSource = buscarGrupoCatalogoPorClaves(['fruta_del_paquete', 'fruta']);
            if(frutaSource && !baseKeys.has('fruta_del_paquete') && !baseKeys.has('fruta')){
                dynamic.push(clonarGrupoParaModal(frutaSource, {
                    key: 'grupo_desayuno_fruta',
                    modalidad: 'desayuno',
                    obligatorio: false,
                    multiple: false,
                    visible_if_option_id: null,
                }));
            }

            const granolaSource = buscarGrupoCatalogoPorClaves(['agregar_granola', 'granola']);
            if(granolaSource && !baseKeys.has('agregar_granola') && !baseKeys.has('granola')){
                dynamic.push(clonarGrupoParaModal(granolaSource, {
                    key: 'grupo_desayuno_granola',
                    modalidad: 'desayuno',
                    obligatorio: false,
                    multiple: false,
                    visible_if_option_id: null,
                    visible_if_group_slugs: ['fruta_del_paquete', 'fruta'],
                }));
            }

            return dynamic;
        }

        function grupoVirtualTiempoComida(keyBase, nombre){
            const opciones = [
                { suffix: 'nada', label: 'Nada' },
                { suffix: 'sopa', label: 'Sopa' },
                { suffix: 'arroz', label: 'Arroz' },
                { suffix: 'pasta', label: 'Pasta' },
            ];

            return {
                key: `grupo_${keyBase}`,
                slug: keyBase.replace(/_/g, '-'),
                nombre,
                modalidad: 'comida',
                obligatorio: true,
                multiple: false,
                visible_if_option_id: null,
                options: opciones.map(opcion => ({
                    key: `opcion_${keyBase}_${opcion.suffix}`,
                    opcion_id: null,
                    slug: opcion.suffix,
                    nombre: `${nombre}: ${opcion.label}`,
                    label: opcion.label,
                    incremento_precio: 0,
                    incremento_costo: 0,
                })),
            };
        }

        function asegurarOpcionNadaEnGrupoComida(grupo){
            const opciones = Array.isArray(grupo?.options) ? [...grupo.options] : [];
            const existeNada = opciones.some(opcion => {
                const slug = normalizarComparacion(opcion?.slug || '');
                const label = normalizarComparacion(etiquetaOpcion(opcion));
                const nombre = normalizarComparacion(opcion?.nombre || '');
                return slug === 'nada' || label === 'nada' || nombre.endsWith('_nada');
            });

            if(existeNada){
                return {
                    ...grupo,
                    obligatorio: true,
                    multiple: false,
                    options: opciones
                };
            }

            const nombreGrupo = (grupo?.nombre || '').trim();
            const keyGrupo = normalizarComparacion(grupo?.key || grupo?.slug || nombreGrupo || 'grupo_tiempo');
            const opcionNada = {
                key: `${keyGrupo}_nada`,
                opcion_id: null,
                slug: 'nada',
                nombre: `${nombreGrupo}: Nada`,
                label: 'Nada',
                incremento_precio: 0,
                incremento_costo: 0,
            };

            return {
                ...grupo,
                obligatorio: true,
                multiple: false,
                options: [opcionNada, ...opciones]
            };
        }

        function clonarGrupoComidaSinCosto(grupo, overrides = {}){
            const clone = clonarGrupoParaModal(grupo, overrides);
            clone.options = (clone.options || []).map(opcion => ({
                ...opcion,
                opcion_id: null,
                incremento_precio: 0,
                incremento_costo: 0,
            }));
            return asegurarOpcionNadaEnGrupoComida(clone);
        }

        function gruposDinamicosComida(){
            if(!productoConfigActual || productoConfigActual.es_comida_dia){ return []; }
            if(modalidadActual !== 'comida'){ return []; }
            if(!productoConfigActual.permite_comida){ return []; }

            const skuKey = normalizarComparacion(productoConfigActual.sku || '');
            const nombreKey = normalizarComparacion(productoConfigActual.nombre || '');
            if(skuKey === 'platillo' || nombreKey === 'platillo'){ return []; }

            const baseGroups = productoConfigActual.grupos || [];
            const baseKeys = new Set(baseGroups.map(grupo => claveGrupo(grupo)));
            const dynamic = [];

            const primerSource = buscarGrupoCatalogoPorClaves(['primer_tiempo']);
            if(!baseKeys.has('primer_tiempo')){
                if(primerSource){
                    dynamic.push(clonarGrupoComidaSinCosto(primerSource, {
                        key: 'grupo_comida_primer_tiempo',
                        modalidad: 'comida',
                        obligatorio: true,
                        multiple: false,
                        visible_if_option_id: null,
                    }));
                } else {
                    dynamic.push(grupoVirtualTiempoComida('primer_tiempo', 'Primer tiempo'));
                }
            }

            const segundoSource = buscarGrupoCatalogoPorClaves(['segundo_tiempo']);
            if(!baseKeys.has('segundo_tiempo')){
                if(segundoSource){
                    dynamic.push(clonarGrupoComidaSinCosto(segundoSource, {
                        key: 'grupo_comida_segundo_tiempo',
                        modalidad: 'comida',
                        obligatorio: true,
                        multiple: false,
                        visible_if_option_id: null,
                    }));
                } else {
                    dynamic.push(grupoVirtualTiempoComida('segundo_tiempo', 'Segundo tiempo'));
                }
            }

            return dynamic;
        }

        function gruposConfigurablesActuales(){
            const base = productoConfigActual?.grupos || [];
            return [...base, ...gruposDinamicosDesayuno(), ...gruposDinamicosComida()];
        }

        function esGrupoVisible(grupo){
            const modalidadGrupo = grupo.modalidad || 'todas';
            if(modalidadGrupo !== 'todas' && modalidadGrupo !== modalidadActual){ return false; }

            if(grupo.visible_if_virtual_code){
                const hasVirtual = obtenerSeleccionadas().some(opcion => (opcion.virtual_code || '') === grupo.visible_if_virtual_code);
                if(!hasVirtual){ return false; }
            }

            if(grupo.visible_if_group_key){
                const selectedParent = seleccionesConfigActual[grupo.visible_if_group_key] || [];
                if(selectedParent.length === 0){ return false; }
            }

            if(Array.isArray(grupo.visible_if_group_slugs) && grupo.visible_if_group_slugs.length > 0){
                const selected = opcionesSeleccionadasPorGroupSlugs(grupo.visible_if_group_slugs);
                if(selected.length === 0){ return false; }

                if(Array.isArray(grupo.visible_if_option_slugs) && grupo.visible_if_option_slugs.length > 0){
                    const hasMatch = selected.some(opcion => coincideSeleccionConSlugs(opcion, grupo.visible_if_option_slugs));
                    if(!hasMatch){ return false; }
                }
            }

            if(!grupo.visible_if_option_id){ return true; }
            return obtenerSeleccionadas().some(opcion => Number(opcion.opcion_id) === Number(grupo.visible_if_option_id));
        }

        function limpiarSeleccionesOcultas(){
            if(!productoConfigActual){ return; }
            gruposConfigurablesActuales().forEach(grupo => {
                if(!esGrupoVisible(grupo)){ delete seleccionesConfigActual[grupo.key]; }
            });
        }

        function nombreGuardadoOpcion(grupo, opcion){
            return `${grupo.nombre}: ${etiquetaOpcion(opcion)}`;
        }

        function opcionesSeleccionadasVisibles(){
            if(!productoConfigActual){ return []; }
            return gruposConfigurablesActuales()
                .filter(grupo => esGrupoVisible(grupo))
                .flatMap(grupo => (seleccionesConfigActual[grupo.key] || []).map(opcion => ({
                    opcion_id: opcion.opcion_id ?? null,
                    nombre: nombreGuardadoOpcion(grupo, opcion),
                    incremento_precio: Number(opcion.incremento_precio || 0),
                    incremento_costo: Number(opcion.incremento_costo || 0)
                })));
        }

        function extrasCatalogo(){
            if(!productoConfigActual || !productoConfigActual.usa_extras){
                return [];
            }

            const permitidos = Array.isArray(productoConfigActual.extra_ids_permitidos)
                ? productoConfigActual.extra_ids_permitidos.map(id => Number(id))
                : [];
            const obligatorios = new Set(Array.isArray(productoConfigActual.extra_ids_obligatorios)
                ? productoConfigActual.extra_ids_obligatorios.map(id => Number(id))
                : []);
            const usarFiltro = permitidos.length > 0;
            const permitidosSet = new Set(permitidos);

            const extras = (extrasCatalogoBase || [])
                .filter(extra => !usarFiltro || permitidosSet.has(Number(extra.id)))
                .map(extra => ({
                    key: `extra_${extra.id}`,
                    extra_id: Number(extra.id),
                    nombre: (extra.nombre || '').trim(),
                    precio: Number(extra.precio || 0),
                    permite_cantidad: Boolean(extra.permite_cantidad ?? true),
                    obligatorio: obligatorios.has(Number(extra.id)),
                    is_otro: normalizarClave(extra.nombre || '') === 'otro'
                }));

            if(!extras.some(extra => extra.is_otro)){
                extras.push({
                    key: 'extra_otro_virtual',
                    extra_id: null,
                    nombre: 'Otro',
                    precio: 0,
                    permite_cantidad: true,
                    obligatorio: false,
                    is_otro: true
                });
            }

            const otros = extras.filter(extra => extra.is_otro);
            const restantes = extras.filter(extra => !extra.is_otro);

            return [...otros, ...restantes];
        }

        function extrasSeleccionadosLista(){
            if(!productoConfigActual || !productoConfigActual.usa_extras){
                return [];
            }

            const catalogo = extrasCatalogo();
            const seleccionados = [];

            catalogo.forEach(extra => {
                const rawCantidad = Number(extrasSeleccionados[extra.key] || 0);
                const cantidad = extra.permite_cantidad ? rawCantidad : (rawCantidad > 0 ? 1 : 0);
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

            if(esProductoManualOtro(productoConfigActual)){
                const precioManual = Number(otroManualPrecio || 0);
                return Number.isFinite(precioManual) && precioManual >= 0 ? precioManual : 0;
            }

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

            if(esProductoManualOtro(productoConfigActual)){
                modalConfigTotal.textContent = `Producto manual | Precio final: $${precio.toFixed(2)}`;
                return;
            }

            modalConfigTotal.textContent = `Modalidad: ${productoConfigActual.es_comida_dia ? etiquetaComidaDelDia(productoConfigActual.nombre, opcionesSeleccionadasVisibles()) : nombreModalidad} | Precio final: $${precio.toFixed(2)}`;
        }

        function renderExtrasSection(){
            const wrapper = document.createElement('div');
            wrapper.className = 'grupo-config';
            wrapper.innerHTML = '<h4>Extras</h4>';

            if(!productoConfigActual?.usa_extras){
                const hint = document.createElement('div');
                hint.className = 'grupo-ayuda';
                hint.textContent = 'Este producto no tiene extras habilitados.';
                wrapper.appendChild(hint);
                return wrapper;
            }

            const catalogo = extrasCatalogo();
            const tieneObligatorios = catalogo.some(extra => extra.obligatorio);

            const toggleRow = document.createElement('label');
            toggleRow.className = 'toggle-row';
            const toggle = document.createElement('input');
            toggle.type = 'checkbox';
            toggle.checked = extrasActivos || tieneObligatorios;
            toggle.disabled = tieneObligatorios;
            toggle.addEventListener('change', function(){
                extrasActivos = this.checked;
                if(!extrasActivos){
                    extrasSeleccionados = {};
                    extraOtroNombre = '';
                    extraOtroPrecio = '';
                    extrasBusqueda = '';
                }
                renderModalConfiguracion();
            });
            const toggleText = document.createElement('span');
            toggleText.textContent = tieneObligatorios
                ? 'Hay extras obligatorios para este producto'
                : 'Agregar extras';
            toggleRow.appendChild(toggle);
            toggleRow.appendChild(toggleText);
            wrapper.appendChild(toggleRow);

            if(!(extrasActivos || tieneObligatorios)){
                const hint = document.createElement('div');
                hint.className = 'grupo-ayuda';
                hint.textContent = 'Activa para seleccionar uno o varios extras.';
                wrapper.appendChild(hint);
                return wrapper;
            }

            const searchWrap = document.createElement('div');
            searchWrap.className = 'extras-buscar';
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Buscar extra...';
            searchInput.value = extrasBusqueda;
            searchWrap.appendChild(searchInput);

            const list = document.createElement('div');
            list.className = 'extras-list';

            catalogo.forEach(extra => {
                const item = document.createElement('div');
                item.className = 'extra-item';

                const label = document.createElement('label');
                const input = document.createElement('input');
                input.type = 'checkbox';
                const cantidadActual = Number(extrasSeleccionados[extra.key] || 0);
                input.checked = cantidadActual > 0 || extra.obligatorio;
                input.disabled = extra.obligatorio;
                input.addEventListener('change', function(){
                    if(extra.obligatorio){
                        extrasSeleccionados[extra.key] = Math.max(1, Number(extrasSeleccionados[extra.key] || 1));
                    } else {
                        extrasSeleccionados[extra.key] = this.checked ? 1 : 0;
                    }
                    renderModalConfiguracion();
                });

                const text = document.createElement('span');
                text.textContent = extra.obligatorio ? `${extra.nombre} (Obligatorio)` : extra.nombre;

                label.appendChild(input);
                label.appendChild(text);

                const right = document.createElement('div');
                right.className = 'extra-right';

                const price = document.createElement('span');
                price.className = 'extra-price';
                price.textContent = extra.is_otro ? 'Manual' : `+$${Number(extra.precio || 0).toFixed(2)}`;
                right.appendChild(price);

                const qty = Math.max(extra.obligatorio ? 1 : 0, Number(extrasSeleccionados[extra.key] || 0));
                if(qty > 0){
                    extrasSeleccionados[extra.key] = qty;

                    if(extra.permite_cantidad){
                        const qtyWrap = document.createElement('div');
                        qtyWrap.className = 'extra-cantidad';

                        const minus = document.createElement('button');
                        minus.type = 'button';
                        minus.textContent = '-';
                        minus.disabled = extra.obligatorio && qty <= 1;
                        minus.addEventListener('click', function(){
                            const minimo = extra.obligatorio ? 1 : 0;
                            const nueva = Math.max(minimo, Number(extrasSeleccionados[extra.key] || 0) - 1);
                            extrasSeleccionados[extra.key] = nueva;
                            renderModalConfiguracion();
                        });

                        const qtyText = document.createElement('span');
                        qtyText.textContent = String(qty);

                        const plus = document.createElement('button');
                        plus.type = 'button';
                        plus.textContent = '+';
                        plus.addEventListener('click', function(){
                            extrasSeleccionados[extra.key] = Number(extrasSeleccionados[extra.key] || 0) + 1;
                            renderModalConfiguracion();
                        });

                        qtyWrap.appendChild(minus);
                        qtyWrap.appendChild(qtyText);
                        qtyWrap.appendChild(plus);
                        right.appendChild(qtyWrap);
                    }

                    const subtotal = document.createElement('span');
                    subtotal.className = 'extra-subtotal';
                    const unit = extra.is_otro ? Number(extraOtroPrecio || 0) : Number(extra.precio || 0);
                    subtotal.textContent = `$${(Math.max(0, unit) * qty).toFixed(2)}`;
                    right.appendChild(subtotal);
                }

                item.dataset.extraNombre = normalizarTexto(extra.nombre || '');
                item.dataset.extraObligatorio = extra.obligatorio ? '1' : '0';
                item.dataset.extraSeleccionado = qty > 0 ? '1' : '0';

                item.appendChild(label);
                item.appendChild(right);
                list.appendChild(item);
            });

            const emptySearchState = document.createElement('div');
            emptySearchState.className = 'extras-sin-resultados';
            emptySearchState.textContent = 'No se encontraron extras con ese texto.';

            const aplicarFiltroExtras = () => {
                const term = normalizarTexto(searchInput.value || '');
                let visibles = 0;

                list.querySelectorAll('.extra-item').forEach(item => {
                    const nombre = item.dataset.extraNombre || '';
                    const esObligatorio = item.dataset.extraObligatorio === '1';
                    const estaSeleccionado = item.dataset.extraSeleccionado === '1';
                    const coincide = term === '' || nombre.includes(term) || esObligatorio || estaSeleccionado;

                    item.style.display = coincide ? '' : 'none';
                    if(coincide){ visibles++; }
                });

                emptySearchState.style.display = visibles === 0 ? 'block' : 'none';
            };

            searchInput.addEventListener('input', function(){
                extrasBusqueda = this.value;
                aplicarFiltroExtras();
            });

            const otro = catalogo.find(extra => extra.is_otro);
            let otherFields = null;
            if(otro && Number(extrasSeleccionados[otro.key] || 0) > 0){
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
                otherFields = fields;
            }

            wrapper.appendChild(searchWrap);
            if(otherFields){
                wrapper.appendChild(otherFields);
            }
            wrapper.appendChild(list);
            wrapper.appendChild(emptySearchState);
            aplicarFiltroExtras();

            return wrapper;
        }

        function renderNotasSection(){
            const wrapper = document.createElement('div');
            wrapper.className = 'grupo-config nota-area';
            wrapper.innerHTML = '<h4>Notas</h4>';

            if(!productoConfigActual?.usa_notas){
                const hint = document.createElement('div');
                hint.className = 'grupo-ayuda';
                hint.textContent = 'Este producto no permite notas.';
                wrapper.appendChild(hint);
                return wrapper;
            }

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

        function renderOtroManualSection(){
            const wrapper = document.createElement('div');
            wrapper.className = 'grupo-config';
            wrapper.innerHTML = '<h4>Producto manual</h4><div class="grupo-ayuda">Captura descripcion, precio y tipo de preparacion para imprimir la comanda en el area correcta.</div>';

            const grid = document.createElement('div');
            grid.className = 'otro-manual-grid';

            const descLabel = document.createElement('label');
            descLabel.className = 'full';
            descLabel.textContent = 'Descripcion personalizada *';
            const descInput = document.createElement('input');
            descInput.type = 'text';
            descInput.maxLength = 120;
            descInput.placeholder = 'Ej. Pan de elote';
            descInput.value = otroManualDescripcion;
            descInput.addEventListener('input', function(){
                otroManualDescripcion = this.value;
            });
            descLabel.appendChild(descInput);

            const precioLabel = document.createElement('label');
            precioLabel.textContent = 'Precio manual *';
            const precioInput = document.createElement('input');
            precioInput.type = 'number';
            precioInput.min = '0';
            precioInput.step = '0.01';
            precioInput.placeholder = '0.00';
            precioInput.value = otroManualPrecio;
            precioInput.addEventListener('input', function(){
                otroManualPrecio = this.value;
                actualizarResumenModal();
            });
            precioLabel.appendChild(precioInput);

            const areaLabel = document.createElement('label');
            areaLabel.textContent = 'Tipo *';
            const areaSelect = document.createElement('select');
            areaSelect.innerHTML = '<option value=\"\">Selecciona...</option><option value=\"cocina\">Cocina</option><option value=\"barra\">Barra</option>';
            areaSelect.value = otroManualArea;
            areaSelect.addEventListener('change', function(){
                otroManualArea = this.value;
            });
            areaLabel.appendChild(areaSelect);

            grid.appendChild(descLabel);
            grid.appendChild(precioLabel);
            grid.appendChild(areaLabel);
            wrapper.appendChild(grid);

            return wrapper;
        }

        function renderModalConfiguracion(){
            if(!productoConfigActual){ return; }

            limpiarSeleccionesOcultas();
            modalConfigTitulo.textContent = productoConfigActual.nombre;
            if(esProductoManualOtro(productoConfigActual)){
                modalConfigSubtitulo.textContent = edicionActual
                    ? 'Edita descripcion, precio y tipo de preparacion.'
                    : 'Captura descripcion, precio manual y tipo (cocina o barra).';
            } else if(edicionActual){
                modalConfigSubtitulo.textContent = 'Edita modalidad, opciones, extras y nota del producto.';
            } else {
                modalConfigSubtitulo.textContent = productoConfigActual.es_comida_dia
                    ? `Selecciona los tiempos de ${etiquetaComidaDelDia(productoConfigActual.nombre, opcionesSeleccionadasVisibles()).toLowerCase()}.`
                    : 'Selecciona modalidad y opciones. Extras y notas son opcionales.';
            }
            modalConfigBody.innerHTML = '';

            if(esProductoManualOtro(productoConfigActual)){
                modalConfigBody.appendChild(renderOtroManualSection());
                actualizarResumenModal();
                return;
            }

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

            const gruposVisibles = gruposConfigurablesActuales().filter(grupo => esGrupoVisible(grupo));
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

            if(productoConfigActual.usa_extras){
                modalConfigBody.appendChild(renderExtrasSection());
            }
            modalConfigBody.appendChild(renderNotasSection());
            actualizarResumenModal();
        }

        function encontrarGrupoPorNombre(nombreGrupo){
            const key = normalizarClave(nombreGrupo || '');
            return gruposConfigurablesActuales().find(grupo => normalizarClave(grupo.nombre || '') === key) || null;
        }

        function encontrarOpcionEnGrupo(grupo, valor){
            const key = normalizarClave(valor || '');
            return (grupo?.options || []).find(opcion => {
                const labelKey = normalizarClave(etiquetaOpcion(opcion));
                const nombreKey = normalizarClave(opcion.nombre || '');
                return labelKey === key || nombreKey === key;
            }) || null;
        }

        function precargarOpcionesDesdeItem(item){
            seleccionesConfigActual = {};
            const grupos = gruposConfigurablesActuales();
            const opcionesPorId = new Map();

            grupos.forEach(grupo => {
                (grupo.options || []).forEach(opcion => {
                    opcionesPorId.set(Number(opcion.opcion_id), { grupo, opcion });
                });
            });

            (item.opciones || []).forEach(seleccion => {
                let grupo = null;
                let opcion = null;
                const opcionId = Number(seleccion.opcion_id || 0);

                if(opcionId > 0 && opcionesPorId.has(opcionId)){
                    const match = opcionesPorId.get(opcionId);
                    grupo = match.grupo;
                    opcion = match.opcion;
                } else if(seleccion.nombre){
                    const entradas = extraerEntradasOpcion([{ nombre: seleccion.nombre }]);
                    const entrada = entradas[0];
                    if(entrada){
                        grupo = encontrarGrupoPorNombre(entrada.groupLabel || entrada.groupKey);
                        opcion = encontrarOpcionEnGrupo(grupo, entrada.value);
                    }
                }

                if(!grupo || !opcion){ return; }

                const actuales = [...(seleccionesConfigActual[grupo.key] || [])];
                if(actuales.some(actual => (actual.key || '') === (opcion.key || ''))){
                    return;
                }

                const permiteMultiple = Boolean(grupo.multiple) && !esGrupoSalsa(grupo);
                if(permiteMultiple){
                    actuales.push(opcion);
                } else {
                    actuales.splice(0, actuales.length, opcion);
                }

                seleccionesConfigActual[grupo.key] = actuales;
            });
        }

        function precargarExtrasDesdeItem(item){
            extrasSeleccionados = {};
            extrasActivos = false;
            extraOtroNombre = '';
            extraOtroPrecio = '';
            extrasBusqueda = '';

            const catalogo = extrasCatalogo();
            const extrasById = new Map(
                catalogo
                    .filter(extra => Number(extra.extra_id || 0) > 0)
                    .map(extra => [Number(extra.extra_id), extra])
            );

            (item.extras || []).forEach(extra => {
                const cantidad = Math.max(1, Number(extra.cantidad || 1));
                const extraId = Number(extra.extra_id || 0);

                if(extraId > 0 && extrasById.has(extraId)){
                    const catalogExtra = extrasById.get(extraId);
                    extrasSeleccionados[catalogExtra.key] = cantidad;
                    extrasActivos = true;
                    return;
                }

                const otro = catalogo.find(entry => entry.is_otro);
                if(!otro){ return; }

                extrasSeleccionados[otro.key] = cantidad;
                extraOtroNombre = (extra.nombre_personalizado || extra.nombre || '').toString().trim();
                extraOtroPrecio = String(Number(extra.precio_unitario ?? extra.precio ?? 0));
                extrasActivos = true;
            });

            catalogo
                .filter(extra => extra.obligatorio)
                .forEach(extra => {
                    extrasSeleccionados[extra.key] = Math.max(1, Number(extrasSeleccionados[extra.key] || 1));
                    extrasActivos = true;
                });
        }

        function abrirModalConfiguracion(producto, context = null){
            productoConfigActual = producto;
            modalidadActual = modalidadPorDefecto(producto);
            seleccionesConfigActual = {};
            extrasActivos = false;
            notasActivas = false;
            extrasSeleccionados = {};
            extraOtroNombre = '';
            extraOtroPrecio = '';
            extrasBusqueda = '';
            notaActual = '';
            otroManualDescripcion = '';
            otroManualPrecio = '';
            otroManualArea = '';
            edicionActual = context ? {
                source: context.source,
                index: context.index,
                itemOriginal: JSON.parse(JSON.stringify(context.item || {})),
            } : null;

            const obligatorios = extrasCatalogo().filter(extra => extra.obligatorio);
            if(obligatorios.length > 0){
                extrasActivos = true;
                obligatorios.forEach(extra => { extrasSeleccionados[extra.key] = Math.max(1, Number(extrasSeleccionados[extra.key] || 1)); });
            }

            if(!producto.usa_notas){
                notasActivas = false;
                notaActual = '';
            }

            if(edicionActual?.itemOriginal){
                const item = edicionActual.itemOriginal;
                modalidadActual = item.modalidad || modalidadActual;
                if(esProductoManualOtro(producto)){
                    otroManualDescripcion = (item.otro_descripcion || item.nombre || '').toString();
                    otroManualPrecio = String(Number(item.precio ?? item.precio_manual ?? 0));
                    otroManualArea = (item.otro_area || '').toString();
                } else {
                    precargarOpcionesDesdeItem(item);
                    precargarExtrasDesdeItem(item);
                    notaActual = (item.nota || '').toString();
                    notasActivas = Boolean(producto.usa_notas) && notaActual.trim() !== '';
                }
            }

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
            extrasBusqueda = '';
            notaActual = '';
            otroManualDescripcion = '';
            otroManualPrecio = '';
            otroManualArea = '';
            edicionActual = null;
        }

        function validarConfiguracion(){
            if(!productoConfigActual){ return false; }

            if(esProductoManualOtro(productoConfigActual)){
                const descripcion = (otroManualDescripcion || '').trim();
                if(descripcion === ''){
                    alert('Captura la descripcion del producto.');
                    return false;
                }

                const precio = Number(otroManualPrecio);
                if(!Number.isFinite(precio) || precio < 0){
                    alert('Captura un precio manual valido mayor o igual a 0.');
                    return false;
                }

                if(!['cocina', 'barra'].includes((otroManualArea || '').toLowerCase())){
                    alert('Selecciona el tipo de preparacion: cocina o barra.');
                    return false;
                }

                return true;
            }

            for(const grupo of gruposConfigurablesActuales()){
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

            if(productoConfigActual.usa_extras){
                const obligatorios = extrasCatalogo().filter(extra => extra.obligatorio);
                for(const extra of obligatorios){
                    if(Number(extrasSeleccionados[extra.key] || 0) < 1){
                        alert(`Debes seleccionar el extra obligatorio ${extra.nombre}`);
                        return false;
                    }
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
            if(esProductoManualOtro(productoConfigActual)){
                const descripcion = (otroManualDescripcion || '').trim();
                const precioManual = Number(otroManualPrecio || 0);
                const area = (otroManualArea || '').toLowerCase();
                const cantidad = edicionActual
                    ? Math.max(1, Number(edicionActual.itemOriginal?.cantidad || 1))
                    : 1;

                return {
                    id: productoConfigActual.id,
                    producto_nombre: productoConfigActual.nombre,
                    es_comida_dia: false,
                    es_otro_manual: true,
                    otro_descripcion: descripcion,
                    otro_area: area,
                    modalidad: 'solo',
                    nombre: descripcion,
                    detalle_cliente: [],
                    precio_base: precioManual,
                    incremento_modalidad: 0,
                    precio: precioManual,
                    cantidad,
                    opciones: [],
                    extras: [],
                    nota: null
                };
            }

            const opciones = opcionesSeleccionadasVisibles();
            const extras = extrasSeleccionadosLista();
            const cantidad = edicionActual
                ? Math.max(1, Number(edicionActual.itemOriginal?.cantidad || 1))
                : 1;
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
                nombre: nombreComercial(productoConfigActual, modalidadActual, opciones),
                detalle_cliente: detalleCliente,
                precio_base: precioBase,
                incremento_modalidad: incrementoModo,
                precio,
                cantidad,
                opciones,
                extras,
                nota: nota || null
            };
        }

        function mismaConfiguracion(a, b){
            if(!a || !b){ return false; }
            return firmaItem(a) === firmaItem(b)
                && Number(a.precio || 0).toFixed(2) === Number(b.precio || 0).toFixed(2);
        }

        function aplicarEdicionConfigurada(item){
            if(!edicionActual){
                agregarATicket(item);
                return;
            }

            const { source, index, itemOriginal } = edicionActual;
            if(source === 'nuevo'){
                if(mismaConfiguracion(itemOriginal, item)){
                    ticketNuevo[index] = item;
                } else {
                    ticketNuevo.splice(index, 1);
                    combinarLineaEnLista(ticketNuevo, item);
                }
            } else if(source === 'base'){
                if(mismaConfiguracion(itemOriginal, item)){
                    ticketBase[index] = item;
                } else {
                    ticketBase.splice(index, 1);
                    combinarLineaEnLista(ticketNuevo, item);
                }
            }

            dibujarTicket();
        }

        function editarItemTicket(source, index){
            const lista = source === 'base' ? ticketBase : ticketNuevo;
            const item = lista[index];
            if(!item){
                alert('No se encontro el producto para editar.');
                return;
            }

            const producto = productosMap.get(String(item.id));
            if(!producto){
                alert('No se pudo cargar la configuracion del producto.');
                return;
            }

            abrirModalConfiguracion(producto, { source, index, item });
        }

        function editarBase(index){ editarItemTicket('base', index); }
        function editarNuevo(index){ editarItemTicket('nuevo', index); }

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
            aplicarEdicionConfigurada(construirItemConfigurado());
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
                    div.innerHTML = `<div class="item-info">${descripcionItem(item)}</div><div class="acciones"><div class="acciones-top"><button type="button" class="btn-accion btn-editar" onclick="editarBase(${index})" title="Editar">&#9998;</button><div class="controles-cantidad"><button class="btn-cantidad" onclick="restarBase(${index})">-</button><span class="cantidad-numero">${item.cantidad}</span><button class="btn-cantidad" onclick="sumarBase(${index})">+</button></div><button type="button" class="btn-accion btn-eliminar" onclick="eliminarBase(${index})" title="Eliminar">&times;</button></div><div class="acciones-total">$${subtotal.toFixed(2)}</div></div>`;
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
                    div.innerHTML = `<div class="item-info">${descripcionItem(item)}</div><div class="acciones"><div class="acciones-top"><button type="button" class="btn-accion btn-editar" onclick="editarNuevo(${index})" title="Editar">&#9998;</button><div class="controles-cantidad"><button class="btn-cantidad" onclick="restarNuevo(${index})">-</button><span class="cantidad-numero">${item.cantidad}</span><button class="btn-cantidad" onclick="sumarNuevo(${index})">+</button></div><button type="button" class="btn-accion btn-eliminar" onclick="eliminarNuevo(${index})" title="Eliminar">&times;</button></div><div class="acciones-total">$${subtotal.toFixed(2)}</div></div>`;
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


