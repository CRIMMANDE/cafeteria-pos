<?php

namespace App\Http\Controllers;

use App\Models\Extra;
use App\Models\Mesa;
use App\Models\Opcion;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\OrdenDetalleExtra;
use App\Models\OrdenDetalleOpcion;
use App\Models\Producto;
use App\Services\OrderLinePresentationService;
use App\Services\OrderPreparationComponentService;
use App\Services\ThermalPrinter\AreaCommandPrintService;
use App\Services\ThermalPrinter\ThermalPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrdenController extends Controller
{
    public function __construct(
        private readonly OrderPreparationComponentService $componentService,
        private readonly OrderLinePresentationService $linePresentationService,
    ) {
    }

    public function guardar(Request $request, AreaCommandPrintService $areaCommandPrintService)
    {
        $mesaId = (int) $request->mesa;
        $this->ensureSpecialMesaExists($mesaId);

        [$orden, $newDetailIds] = DB::transaction(function () use ($mesaId, $request) {
            return $this->syncOpenOrder(
                $mesaId,
                $request->input('productos', []),
                $request->input('productosNuevos', [])
            );
        });

        $commandResults = $this->printNewAreaCommands($orden, $newDetailIds, $areaCommandPrintService);

        return response()->json([
            'ok' => true,
            'orden_id' => $orden->id,
            'command_results' => $commandResults,
        ]);
    }

    public function imprimirTicket(Request $request, ThermalPrinterService $thermalPrinterService)
    {
        $mesaId = (int) $request->mesa;
        $this->ensureSpecialMesaExists($mesaId);

        [$orden] = DB::transaction(function () use ($mesaId, $request) {
            return $this->syncOpenOrder(
                $mesaId,
                $request->input('productos', []),
                []
            );
        });

        $result = $thermalPrinterService->printOrder($orden->load(['detalles.producto', 'detalles.opciones', 'detalles.extras.extra', 'pagos']));

        return response()->json(array_merge([
            'orden_id' => $orden->id,
        ], $result->toArray()));
    }

    public function mesa($mesa)
    {
        $mesaId = (int) $mesa;
        $this->ensureSpecialMesaExists($mesaId);

        $orden = Orden::where('mesa_id', $mesaId)
            ->where('estado', 'abierta')
            ->with(['detalles.producto', 'detalles.extras.extra', 'detalles.opciones'])
            ->first();

        if (!$orden) {
            return response()->json([]);
        }

        return response()->json(array_values(array_map(function (array $line) {
            unset($line['signature'], $line['detail_ids']);

            return $line;
        }, $this->currentOrderLines($orden))));
    }

    public function cerrar(Request $request)
    {
        $mesaId = (int) $request->mesa;
        $this->ensureSpecialMesaExists($mesaId);

        $metodoPago = $this->normalizePaymentMethod($request->input('metodo_pago'));
        if (!$metodoPago) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes seleccionar un metodo de pago valido',
            ], 422);
        }

        $orden = Orden::where('mesa_id', $mesaId)
            ->where('estado', 'abierta')
            ->first();

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay una orden abierta para esta mesa',
            ], 404);
        }

        [$orden] = DB::transaction(function () use ($orden, $request, $metodoPago) {
            [$orden] = $this->syncExistingOrder(
                $orden,
                $request->input('productos', []),
                [],
                'pagada'
            );

            $orden->update([
                'metodo_pago' => $metodoPago,
            ]);

            $orden->pagos()->create([
                'monto' => $orden->total,
                'metodo' => $metodoPago,
            ]);

            return [$orden->fresh(['detalles.producto', 'pagos'])];
        });

        return response()->json([
            'ok' => true,
            'message' => 'Cuenta cerrada correctamente',
            'orden_id' => $orden->id,
        ]);
    }

    public function recuperar(Request $request)
    {
        $folio = (int) $request->input('folio');

        if ($folio <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes capturar un folio valido',
            ], 422);
        }

        $orden = Orden::where('id', $folio)
            ->where('estado', 'pagada')
            ->first();

        if (!$orden) {
            return response()->json([
                'ok' => false,
                'message' => 'No existe una cuenta pagada con ese folio',
            ], 404);
        }

        $tipoOrden = $this->resolveOrderTypeForOrder($orden);
        $mesaId = (int) $orden->mesa_id;
        $this->ensureSpecialMesaExists($mesaId);

        $abierta = Orden::where('mesa_id', $mesaId)
            ->where('estado', 'abierta')
            ->where('id', '!=', $orden->id)
            ->first();

        if ($abierta) {
            return response()->json([
                'ok' => false,
                'message' => $this->activeOrderMessage($tipoOrden, $mesaId),
            ], 400);
        }

        DB::transaction(function () use ($orden, $tipoOrden) {
            $orden->pagos()->delete();

            $orden->update([
                'tipo' => $tipoOrden,
                'desc_empleado' => $tipoOrden === 'empleados',
                'estado' => 'abierta',
                'metodo_pago' => null,
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => 'Cuenta recuperada correctamente',
            'redirect_url' => $this->redirectUrlForOrder($orden->fresh()),
        ]);
    }

    public function imprimir($mesa)
    {
        $mesaId = (int) $mesa;
        $this->ensureSpecialMesaExists($mesaId);

        $orden = Orden::where('mesa_id', $mesaId)
            ->where('estado', 'abierta')
            ->with(['detalles.producto', 'detalles.opciones', 'detalles.extras.extra'])
            ->first();

        if (!$orden) {
            return redirect('/mesas')->with('error', 'No hay orden abierta para esta mesa');
        }

        $productos = [];

        foreach ($orden->detalles as $det) {
            if (!$det->producto) {
                continue;
            }

            $nombre = (bool) $det->es_otro_manual
                ? trim((string) ($det->nombre_personalizado ?: $det->producto->nombre))
                : $this->linePresentationService->commercialName(
                    $det->producto->nombre,
                    $det->opciones->pluck('nombre')->all(),
                    $det->modalidad,
                    $this->isComidaDiaProduct($det->producto)
                );
            $detalleCliente = $this->buildClientDetailLines($det, true);
            $key = strtolower($nombre) . '|' . number_format((float) $det->precio, 2, '.', '') . '|' . implode('|', $detalleCliente);

            if (!isset($productos[$key])) {
                $productos[$key] = [
                    'nombre' => $nombre,
                    'detalle_cliente' => $detalleCliente,
                    'cantidad' => 0,
                    'precio' => (float) $det->precio,
                    'subtotal' => 0,
                ];
            }

            $productos[$key]['cantidad'] += (int) $det->cantidad;
            $productos[$key]['subtotal'] += ((float) $det->precio * (int) $det->cantidad);
        }

        return view('pos.imprimir', [
            'mesa' => $mesaId,
            'mesaLabel' => $this->mesaLabelForType($this->resolveOrderTypeForOrder($orden), $mesaId),
            'esParaLlevar' => $this->resolveOrderTypeForOrder($orden) === 'llevar',
            'orden' => $orden,
            'productos' => array_values($productos),
        ]);
    }

    private function printNewAreaCommands(Orden $orden, array $newDetailIds, AreaCommandPrintService $areaCommandPrintService): array
    {
        $results = [];

        foreach (['cocina', 'barra'] as $area) {
            $result = $areaCommandPrintService->printNewItems($orden, $area, $newDetailIds);
            $results[$area] = $result->toArray();
        }

        return $results;
    }

    private function syncOpenOrder(int $mesaId, array $productos, array $productosNuevos): array
    {
        $tipo = $this->resolveOrderTypeByMesa($mesaId);
        $orden = Orden::where('mesa_id', $mesaId)
            ->where('estado', 'abierta')
            ->first();

        if (!$orden) {
            $orden = Orden::create([
                'mesa_id' => $mesaId,
                'tipo' => $tipo,
                'estado' => 'abierta',
                'total' => 0,
                'desc_empleado' => $tipo === 'empleados',
                'metodo_pago' => null,
            ]);
        } else {
            $orden->update([
                'tipo' => $tipo,
                'desc_empleado' => $tipo === 'empleados',
            ]);
        }

        return $this->syncExistingOrder($orden, $productos, $productosNuevos, 'abierta');
    }

    private function syncExistingOrder(Orden $orden, array $productos, array $productosNuevos, string $estado): array
    {
        $tipoOrden = $this->resolveOrderTypeForOrder($orden);
        $currentLines = $this->currentOrderLines($orden);
        $targetLines = $this->prepareLineItems($productos, $tipoOrden);
        $newLines = $this->prepareLineItems($productosNuevos, $tipoOrden);

        $currentQuantities = [];
        foreach ($currentLines as $signature => $line) {
            $currentQuantities[$signature] = (int) $line['cantidad'];
        }

        $targetQuantities = [];
        foreach ($targetLines as $signature => $line) {
            $targetQuantities[$signature] = (int) $line['cantidad'];
        }

        $newQuantities = [];
        foreach ($newLines as $signature => $line) {
            $newQuantities[$signature] = (int) $line['cantidad'];
        }
        $hasExplicitNewLines = $newQuantities !== [];

        $newDetailIds = [];
        $signatures = array_unique(array_merge(array_keys($currentLines), array_keys($targetLines)));

        foreach ($signatures as $signature) {
            $currentQty = $currentQuantities[$signature] ?? 0;
            $targetQty = $targetQuantities[$signature] ?? 0;
            $delta = $targetQty - $currentQty;

            if ($delta > 0) {
                $line = $targetLines[$signature] ?? null;
                if (!$line) {
                    continue;
                }

                $pendingQty = $hasExplicitNewLines
                    ? min($delta, $newQuantities[$signature] ?? 0)
                    : $delta;
                $printedQty = $delta - $pendingQty;

                if ($pendingQty > 0) {
                    $detail = $this->createOrderDetail($orden, $line, $pendingQty, false);
                    $newDetailIds[] = $detail->id;
                }

                if ($printedQty > 0) {
                    $this->createOrderDetail($orden, $line, $printedQty, true);
                }
            }

            if ($delta < 0) {
                $detailIds = $currentLines[$signature]['detail_ids'] ?? [];
                $this->reduceLineQuantity($detailIds, abs($delta));
            }
        }

        $total = (float) $orden->detalles()
            ->selectRaw('COALESCE(SUM(cantidad * precio), 0) as total')
            ->value('total');

        $orden->update([
            'total' => $total,
            'estado' => $estado,
        ]);

        return [
            $orden->fresh(['detalles.producto.categoria', 'detalles.extras.extra', 'detalles.opciones.opcion.grupoOpcion', 'detalles.componentes', 'pagos']),
            $newDetailIds,
        ];
    }

    private function createOrderDetail(Orden $orden, array $line, int $cantidad, bool $impreso): OrdenDetalle
    {
        $detail = OrdenDetalle::create([
            'orden_id' => $orden->id,
            'producto_id' => $line['id'],
            'es_otro_manual' => (bool) ($line['es_otro_manual'] ?? false),
            'nombre_personalizado' => $line['nombre_personalizado'] ?? null,
            'area_preparacion' => $line['area_preparacion'] ?? null,
            'precio_manual' => $line['precio_manual'] ?? null,
            'cantidad' => $cantidad,
            'modalidad' => $line['modalidad'],
            'precio_base' => $line['precio_base'],
            'incremento_modalidad' => $line['incremento_modalidad'],
            'precio' => $line['precio'],
            'nota' => $line['nota'],
            'impreso' => $impreso,
        ]);

        foreach ($line['opciones'] as $opcion) {
            OrdenDetalleOpcion::create([
                'orden_detalle_id' => $detail->id,
                'opcion_id' => $opcion['opcion_id'],
                'nombre' => $opcion['nombre'],
                'incremento_precio' => $opcion['incremento_precio'],
                'incremento_costo' => $opcion['incremento_costo'],
            ]);
        }

        foreach ($line['extras'] as $extra) {
            OrdenDetalleExtra::create([
                'orden_detalle_id' => $detail->id,
                'extra_id' => $extra['extra_id'],
                'cantidad' => $extra['cantidad'],
                'nombre_personalizado' => $extra['nombre_personalizado'],
                'precio_unitario' => $extra['precio_unitario'],
                'subtotal' => $extra['subtotal'],
                'precio' => $extra['subtotal'],
                'nota' => $extra['nota'],
            ]);
        }

        $this->componentService->regenerateForDetail($detail, $impreso);

        return $detail->fresh(['producto.categoria', 'opciones.opcion.grupoOpcion', 'extras.extra', 'componentes']);
    }

    private function reduceLineQuantity(array $detailIds, int $quantityToReduce): void
    {
        if ($quantityToReduce <= 0 || $detailIds === []) {
            return;
        }

        $details = OrdenDetalle::query()
            ->whereIn('id', $detailIds)
            ->with('componentes')
            ->orderByDesc('id')
            ->get();

        foreach ($details as $detail) {
            if ($quantityToReduce <= 0) {
                break;
            }

            if ((int) $detail->cantidad <= $quantityToReduce) {
                $quantityToReduce -= (int) $detail->cantidad;
                $detail->delete();
                continue;
            }

            $detail->update([
                'cantidad' => (int) $detail->cantidad - $quantityToReduce,
            ]);

            $printed = $detail->componentes->where('impreso', true)->isNotEmpty() || (bool) $detail->impreso;
            $this->componentService->regenerateForDetail($detail->fresh(), $printed);

            $quantityToReduce = 0;
        }
    }

    private function currentOrderLines(Orden $orden): array
    {
        $orden->loadMissing(['detalles.producto', 'detalles.extras.extra', 'detalles.opciones']);

        $lines = [];

        foreach ($orden->detalles as $detail) {
            if (!$detail->producto) {
                continue;
            }

            $isOtroManual = (bool) $detail->es_otro_manual;
            $displayName = $isOtroManual
                ? trim((string) ($detail->nombre_personalizado ?: $detail->producto->nombre))
                : $this->linePresentationService->commercialName(
                    $detail->producto->nombre,
                    $detail->opciones->pluck('nombre')->all(),
                    $detail->modalidad,
                    $this->isComidaDiaProduct($detail->producto)
                );

            $line = [
                'id' => (int) $detail->producto_id,
                'producto_nombre' => $detail->producto->nombre,
                'es_comida_dia' => $this->isComidaDiaProduct($detail->producto),
                'modalidad' => $isOtroManual ? 'solo' : ($detail->modalidad ?: 'solo'),
                'nombre' => $displayName,
                'precio_base' => (float) ($detail->precio_base ?? ($isOtroManual ? $detail->precio : 0)),
                'incremento_modalidad' => (float) ($isOtroManual ? 0 : ($detail->incremento_modalidad ?? 0)),
                'precio' => (float) $detail->precio,
                'cantidad' => (int) $detail->cantidad,
                'nota' => $isOtroManual ? null : $this->normalizeNote($detail->nota),
                'detalle_cliente' => $isOtroManual ? [] : $this->buildClientDetailLines($detail),
                'es_otro_manual' => $isOtroManual,
                'otro_descripcion' => $detail->nombre_personalizado,
                'otro_area' => $detail->area_preparacion,
                'precio_manual' => (float) ($detail->precio_manual ?? $detail->precio),
                'extras' => $detail->extras->map(function (OrdenDetalleExtra $extra) {
                    $cantidad = (int) ($extra->cantidad ?? 1);
                    $precioUnitario = (float) ($extra->precio_unitario ?? $extra->precio ?? 0);
                    $subtotal = (float) ($extra->subtotal ?? (($extra->precio ?? 0) ?: ($precioUnitario * $cantidad)));

                    return [
                        'extra_id' => $extra->extra_id ? (int) $extra->extra_id : null,
                        'cantidad' => $cantidad,
                        'nombre_personalizado' => $extra->nombre_personalizado,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => $subtotal,
                        'precio' => $subtotal,
                        'nota' => $this->normalizeNote($extra->nota),
                    ];
                })->all(),
                'opciones' => $detail->opciones->map(function (OrdenDetalleOpcion $opcion) {
                    return [
                        'opcion_id' => $opcion->opcion_id ? (int) $opcion->opcion_id : null,
                        'nombre' => $opcion->nombre,
                        'incremento_precio' => (float) $opcion->incremento_precio,
                        'incremento_costo' => (float) $opcion->incremento_costo,
                    ];
                })->all(),
            ];

            $signature = $this->buildLineSignature($line);

            if (!isset($lines[$signature])) {
                $line['cantidad'] = 0;
                $line['signature'] = $signature;
                $line['detail_ids'] = [];
                $lines[$signature] = $line;
            }

            $lines[$signature]['cantidad'] += (int) $detail->cantidad;
            $lines[$signature]['detail_ids'][] = (int) $detail->id;
        }

        return $lines;
    }

    private function prepareLineItems(array $productos, string $tipoOrden): array
    {
        $rawItems = [];
        $productIds = [];
        $extraIds = [];
        $optionIds = [];

        foreach ($productos as $producto) {
            $productId = (int) ($producto['id'] ?? $producto['producto_id'] ?? 0);
            $cantidad = (int) ($producto['cantidad'] ?? 0);

            if ($productId <= 0 || $cantidad <= 0) {
                continue;
            }

            $rawExtras = [];
            foreach (($producto['extras'] ?? []) as $extra) {
                $extraId = (int) ($extra['id'] ?? $extra['extra_id'] ?? 0);
                if ($extraId > 0) {
                    $extraIds[] = $extraId;
                }

                $rawExtras[] = [
                    'extra_id' => $extraId > 0 ? $extraId : null,
                    'cantidad' => max(1, (int) ($extra['cantidad'] ?? 1)),
                    'nombre_personalizado' => $extra['nombre_personalizado'] ?? $extra['nombre'] ?? null,
                    'precio_unitario' => isset($extra['precio_unitario'])
                        ? (float) $extra['precio_unitario']
                        : (isset($extra['precio']) ? (float) $extra['precio'] : 0),
                    'nota' => $this->normalizeNote($extra['nota'] ?? null),
                ];
            }

            $rawOpciones = [];
            foreach (($producto['opciones'] ?? []) as $opcion) {
                $optionId = (int) ($opcion['id'] ?? $opcion['opcion_id'] ?? 0);
                if ($optionId > 0) {
                    $optionIds[] = $optionId;
                }

                $rawOpciones[] = [
                    'opcion_id' => $optionId > 0 ? $optionId : null,
                    'nombre' => $opcion['nombre'] ?? null,
                    'incremento_precio' => isset($opcion['incremento_precio']) ? (float) $opcion['incremento_precio'] : 0,
                    'incremento_costo' => isset($opcion['incremento_costo']) ? (float) $opcion['incremento_costo'] : 0,
                ];
            }

            $productIds[] = $productId;
            $rawItems[] = [
                'id' => $productId,
                'cantidad' => $cantidad,
                'modalidad' => $this->normalizeModalidadInput($producto['modalidad'] ?? null),
                'precio' => isset($producto['precio']) ? (float) $producto['precio'] : null,
                'precio_base' => isset($producto['precio_base']) ? (float) $producto['precio_base'] : null,
                'incremento_modalidad' => isset($producto['incremento_modalidad']) ? (float) $producto['incremento_modalidad'] : null,
                'nota' => $this->normalizeNote($producto['nota'] ?? null),
                'es_otro_manual' => (bool) ($producto['es_otro_manual'] ?? false),
                'nombre_personalizado' => $this->nullableString($producto['nombre_personalizado'] ?? $producto['otro_descripcion'] ?? null),
                'area_preparacion' => $this->normalizeAreaPreparacion($producto['area_preparacion'] ?? $producto['otro_area'] ?? null),
                'precio_manual' => isset($producto['precio_manual']) ? (float) $producto['precio_manual'] : null,
                'extras' => $rawExtras,
                'opciones' => $rawOpciones,
            ];
        }

        if ($rawItems === []) {
            return [];
        }

        $productosMap = Producto::query()
            ->whereIn('id', array_values(array_unique($productIds)))
            ->with([
                'gruposOpciones' => fn ($query) => $query
                    ->where('activo', true)
                    ->orderBy('prioridad_visual')
                    ->orderBy('orden_visual')
                    ->orderBy('orden'),
                'extras' => fn ($query) => $query
                    ->where('extras.activo', true)
                    ->wherePivot('activo', true),
            ])
            ->get()
            ->keyBy('id');

        $extrasMap = Extra::query()
            ->whereIn('id', array_values(array_unique($extraIds)))
            ->get()
            ->keyBy('id');

        $opcionesMap = Opcion::query()
            ->whereIn('id', array_values(array_unique($optionIds)))
            ->with('grupoOpcion')
            ->get()
            ->keyBy('id');

        $lines = [];

        foreach ($rawItems as $item) {
            /** @var Producto|null $producto */
            $producto = $productosMap->get($item['id']);
            if (!$producto) {
                continue;
            }

            $isOtroManual = $this->isManualOtherProduct($producto);
            $modalidad = $isOtroManual ? 'solo' : $this->normalizeModalidadForProduct($producto, $item['modalidad']);
            $esComidaDia = $this->isComidaDiaProduct($producto);

            if ($isOtroManual) {
                $descripcion = trim((string) ($item['nombre_personalizado'] ?? ''));
                if ($descripcion === '') {
                    throw ValidationException::withMessages([
                        'productos' => ['Debes capturar la descripcion del producto "Otro".'],
                    ]);
                }

                $areaPreparacion = $this->normalizeAreaPreparacion($item['area_preparacion'] ?? null);
                if ($areaPreparacion === null) {
                    throw ValidationException::withMessages([
                        'productos' => ['Debes seleccionar el tipo de preparacion (cocina o barra) para "Otro".'],
                    ]);
                }

                $precioManualRaw = $item['precio_manual'] ?? $item['precio'] ?? null;
                if ($precioManualRaw === null || !is_numeric($precioManualRaw)) {
                    throw ValidationException::withMessages([
                        'productos' => ['Debes capturar un precio manual valido para "Otro".'],
                    ]);
                }

                $precioManual = (float) $precioManualRaw;
                if ($precioManual < 0) {
                    throw ValidationException::withMessages([
                        'productos' => ['El precio manual de "Otro" debe ser mayor o igual a 0.'],
                    ]);
                }

                $line = [
                    'id' => (int) $producto->id,
                    'producto_nombre' => $producto->nombre,
                    'es_comida_dia' => false,
                    'modalidad' => 'solo',
                    'nombre' => $descripcion,
                    'detalle_cliente' => [],
                    'cantidad' => (int) $item['cantidad'],
                    'nota' => null,
                    'precio_base' => $precioManual,
                    'incremento_modalidad' => 0.0,
                    'precio' => $precioManual,
                    'extras' => [],
                    'opciones' => [],
                    'es_otro_manual' => true,
                    'nombre_personalizado' => $descripcion,
                    'area_preparacion' => $areaPreparacion,
                    'precio_manual' => $precioManual,
                ];

                $signature = $this->buildLineSignature($line);
                if (!isset($lines[$signature])) {
                    $line['signature'] = $signature;
                    $lines[$signature] = $line;
                } else {
                    $lines[$signature]['cantidad'] += (int) $item['cantidad'];
                }

                continue;
            }

            $precioBase = $tipoOrden === 'empleados'
                ? (float) $producto->costo
                : (float) $producto->precio;
            $incrementoModalidad = $this->modalityIncrementForProduct($producto, $modalidad);

            $allowedExtras = $producto->extras->filter(fn ($extra) => (bool) ($extra->pivot?->activo ?? true));
            $allowedExtraIds = $allowedExtras->pluck('id')->map(fn ($id) => (int) $id)->all();
            $allowAllCatalogExtras = $allowedExtraIds === [];
            $allowedExtraLookup = $allowAllCatalogExtras ? [] : array_fill_keys($allowedExtraIds, true);

            $extras = [];
            foreach ($item['extras'] as $extra) {
                if (!(bool) $producto->usa_extras) {
                    continue;
                }

                if ($extra['extra_id']) {
                    /** @var Extra|null $catalogExtra */
                    $catalogExtra = $extrasMap->get($extra['extra_id']);
                    if (!$catalogExtra) {
                        continue;
                    }

                    if (!$allowAllCatalogExtras && !isset($allowedExtraLookup[(int) $catalogExtra->id])) {
                        continue;
                    }

                    $extras[] = [
                        'extra_id' => (int) $catalogExtra->id,
                        'cantidad' => max(1, (int) ($extra['cantidad'] ?? 1)),
                        'nombre_personalizado' => $catalogExtra->nombre,
                        'precio_unitario' => (float) $catalogExtra->precio,
                        'subtotal' => (float) $catalogExtra->precio * max(1, (int) ($extra['cantidad'] ?? 1)),
                        'precio' => (float) $catalogExtra->precio * max(1, (int) ($extra['cantidad'] ?? 1)),
                        'nota' => $extra['nota'],
                    ];
                    continue;
                }

                if (!$extra['nombre_personalizado']) {
                    continue;
                }

                $extras[] = [
                    'extra_id' => null,
                    'cantidad' => max(1, (int) ($extra['cantidad'] ?? 1)),
                    'nombre_personalizado' => $extra['nombre_personalizado'],
                    'precio_unitario' => (float) ($extra['precio_unitario'] ?? 0),
                    'subtotal' => (float) ($extra['precio_unitario'] ?? 0) * max(1, (int) ($extra['cantidad'] ?? 1)),
                    'precio' => (float) ($extra['precio_unitario'] ?? 0) * max(1, (int) ($extra['cantidad'] ?? 1)),
                    'nota' => $extra['nota'],
                ];
            }

            $requiredExtraIds = $allowedExtras
                ->filter(fn ($extra) => (bool) ($extra->pivot?->obligatorio ?? false))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($requiredExtraIds !== []) {
                $selectedExtraIds = collect($extras)
                    ->pluck('extra_id')
                    ->filter(fn ($id) => (int) $id > 0)
                    ->map(fn ($id) => (int) $id)
                    ->all();

                foreach ($requiredExtraIds as $requiredExtraId) {
                    if (in_array($requiredExtraId, $selectedExtraIds, true)) {
                        continue;
                    }

                    $requiredName = (string) ($allowedExtras->firstWhere('id', $requiredExtraId)?->nombre ?? 'Extra requerido');
                    throw ValidationException::withMessages([
                        'productos' => ['Falta seleccionar el extra obligatorio ' . $requiredName . ' para ' . $producto->nombre . '.'],
                    ]);
                }
            }

            $opciones = [];
            foreach ($item['opciones'] as $opcion) {
                if ($opcion['opcion_id']) {
                    /** @var Opcion|null $catalogOption */
                    $catalogOption = $opcionesMap->get($opcion['opcion_id']);
                    if (!$catalogOption) {
                        continue;
                    }

                    $opciones[] = [
                        'opcion_id' => (int) $catalogOption->id,
                        'nombre' => $opcion['nombre'] ?: $catalogOption->nombre,
                        'incremento_precio' => (float) $catalogOption->incremento_precio,
                        'incremento_costo' => (float) $catalogOption->incremento_costo,
                    ];
                    continue;
                }

                if (!$opcion['nombre']) {
                    continue;
                }

                $opciones[] = [
                    'opcion_id' => null,
                    'nombre' => $opcion['nombre'],
                    'incremento_precio' => (float) $opcion['incremento_precio'],
                    'incremento_costo' => (float) $opcion['incremento_costo'],
                ];
            }

            $this->validateRequiredOptionGroups($producto, $modalidad, $opciones, $opcionesMap);

            $unitPrice = $precioBase
                + $incrementoModalidad
                + collect($opciones)->sum($tipoOrden === 'empleados' ? 'incremento_costo' : 'incremento_precio')
                + collect($extras)->sum('subtotal');

            $line = [
                'id' => (int) $producto->id,
                'producto_nombre' => $producto->nombre,
                'es_comida_dia' => $esComidaDia,
                'modalidad' => $modalidad,
                'nombre' => $this->linePresentationService->commercialName(
                    $producto->nombre,
                    array_column($opciones, 'nombre'),
                    $modalidad,
                    $esComidaDia
                ),
                'detalle_cliente' => $this->linePresentationService->clientDetailLines(
                    $producto->nombre,
                    array_column($opciones, 'nombre'),
                    $modalidad,
                    $esComidaDia
                ),
                'cantidad' => (int) $item['cantidad'],
                'nota' => (bool) $producto->usa_notas ? $item['nota'] : null,
                'precio_base' => $precioBase,
                'incremento_modalidad' => $incrementoModalidad,
                'precio' => (float) $unitPrice,
                'extras' => $extras,
                'opciones' => $opciones,
                'es_otro_manual' => false,
                'nombre_personalizado' => null,
                'area_preparacion' => null,
                'precio_manual' => null,
            ];

            $signature = $this->buildLineSignature($line);

            if (!isset($lines[$signature])) {
                $line['signature'] = $signature;
                $lines[$signature] = $line;
                continue;
            }

            $lines[$signature]['cantidad'] += (int) $item['cantidad'];
        }

        return $lines;
    }

    private function buildLineSignature(array $line): string
    {
        $extras = array_map(function (array $extra) {
            return [
                'extra_id' => $extra['extra_id'] ?? null,
                'cantidad' => max(1, (int) ($extra['cantidad'] ?? 1)),
                'nombre_personalizado' => $extra['nombre_personalizado'] ?? null,
                'precio_unitario' => round((float) ($extra['precio_unitario'] ?? ($extra['precio'] ?? 0)), 2),
                'subtotal' => round((float) ($extra['subtotal'] ?? ($extra['precio'] ?? 0)), 2),
                'nota' => $this->normalizeNote($extra['nota'] ?? null),
            ];
        }, $line['extras'] ?? []);

        usort($extras, fn (array $a, array $b) => strcmp(json_encode($a), json_encode($b)));

        $opciones = array_map(function (array $opcion) {
            return [
                'opcion_id' => $opcion['opcion_id'] ?? null,
                'nombre' => $opcion['nombre'] ?? null,
                'incremento_precio' => round((float) ($opcion['incremento_precio'] ?? 0), 2),
                'incremento_costo' => round((float) ($opcion['incremento_costo'] ?? 0), 2),
            ];
        }, $line['opciones'] ?? []);

        usort($opciones, fn (array $a, array $b) => strcmp(json_encode($a), json_encode($b)));

        $isOtroManual = (bool) ($line['es_otro_manual'] ?? false);
        $otroManual = $isOtroManual ? [
            'es_otro_manual' => true,
            'nombre_personalizado' => trim((string) ($line['nombre_personalizado'] ?? $line['otro_descripcion'] ?? '')),
            'area_preparacion' => $this->normalizeAreaPreparacion($line['area_preparacion'] ?? $line['otro_area'] ?? null),
            'precio_manual' => round((float) ($line['precio_manual'] ?? ($line['precio'] ?? 0)), 2),
        ] : ['es_otro_manual' => false];

        return json_encode([
            'id' => (int) ($line['id'] ?? 0),
            'modalidad' => $line['modalidad'] ?? 'solo',
            'nota' => $this->normalizeNote($line['nota'] ?? null),
            'otro_manual' => $otroManual,
            'extras' => $extras,
            'opciones' => $opciones,
        ]);
    }

    private function normalizeModalidadInput(mixed $modalidad): ?string
    {
        if (!is_string($modalidad)) {
            return null;
        }

        $modalidad = strtolower(trim($modalidad));

        return in_array($modalidad, ['solo', 'desayuno', 'comida'], true) ? $modalidad : null;
    }

    private function normalizeModalidadForProduct(Producto $producto, ?string $modalidad): string
    {
        if ($this->isComidaDiaProduct($producto)) {
            return 'comida';
        }

        if ($modalidad === 'desayuno' && (bool) $producto->permite_desayuno) {
            return 'desayuno';
        }

        if ($modalidad === 'comida' && (bool) $producto->permite_comida) {
            return 'comida';
        }

        if ((bool) $producto->permite_solo) {
            return 'solo';
        }

        if ((bool) $producto->permite_desayuno) {
            return 'desayuno';
        }

        if ((bool) $producto->permite_comida) {
            return 'comida';
        }

        return 'solo';
    }

    private function modalityIncrementForProduct(Producto $producto, string $modalidad): float
    {
        return match ($modalidad) {
            'desayuno' => (float) $producto->incremento_desayuno,
            'comida' => $this->isComidaDiaProduct($producto) ? 0.0 : (float) $producto->incremento_comida,
            default => 0.0,
        };
    }

    private function isComidaDiaProduct(?Producto $producto): bool
    {
        return (bool) ($producto?->es_comida_dia) || $this->linePresentationService->isComida($producto?->nombre);
    }

    private function resolveOrderTypeByMesa(int $mesaId): string
    {
        if (Mesa::isEmployee($mesaId)) {
            return 'empleados';
        }

        if (Mesa::isTakeaway($mesaId)) {
            return 'llevar';
        }

        return 'mesa';
    }

    private function resolveOrderTypeForOrder(Orden $orden): string
    {
        if (in_array($orden->tipo, ['mesa', 'llevar', 'empleados'], true)) {
            return $orden->tipo;
        }

        if ((bool) $orden->desc_empleado) {
            return 'empleados';
        }

        return $this->resolveOrderTypeByMesa((int) $orden->mesa_id);
    }

    private function mesaLabelForType(string $tipo, int $mesaId): string
    {
        return match ($tipo) {
            'empleados' => 'EMPLEADOS',
            'llevar' => 'P/LLEVAR',
            default => 'Mesa ' . $mesaId,
        };
    }

    private function redirectUrlForOrder(Orden $orden): string
    {
        return match ($this->resolveOrderTypeForOrder($orden)) {
            'empleados' => '/pos/empleados',
            'llevar' => '/pos/llevar',
            default => '/pos/mesa/' . $orden->mesa_id,
        };
    }

    private function activeOrderMessage(string $tipo, int $mesaId): string
    {
        return match ($tipo) {
            'empleados' => 'Primero necesitas cerrar la cuenta activa de empleados',
            'llevar' => 'Primero necesitas cerrar la cuenta activa de para llevar',
            default => 'Primero necesitas cerrar la cuenta activa de esa mesa',
        };
    }

    private function normalizePaymentMethod(mixed $metodo): ?string
    {
        if (!is_string($metodo)) {
            return null;
        }

        $metodo = strtolower(trim($metodo));

        return in_array($metodo, ['efectivo', 'tarjeta'], true) ? $metodo : null;
    }

    private function validateRequiredOptionGroups(Producto $producto, string $modalidad, array $opciones, \Illuminate\Support\Collection $opcionesMap): void
    {
        $selectedOptionIds = collect($opciones)
            ->pluck('opcion_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $selectedGroupKeysByName = collect($opciones)
            ->map(fn (array $opcion) => $this->extractGroupKeyFromOptionName($opcion['nombre'] ?? null))
            ->filter()
            ->values()
            ->all();

        foreach ($producto->gruposOpciones as $grupo) {
            if (!$this->isGroupEnabledForModalidad($grupo->modalidad, $modalidad)) {
                continue;
            }

            if (!$this->isGroupDependencySatisfied($grupo->solo_si_opcion_id, $selectedOptionIds)) {
                continue;
            }

            $groupKey = $this->normalizeGroupKey((string) $grupo->nombre);
            $isRequired = (bool) $grupo->obligatorio || $groupKey === 'salsa';
            if (!$isRequired) {
                continue;
            }

            $groupId = (int) $grupo->id;

            $hasSelectionById = collect($opciones)->contains(function (array $opcion) use ($groupId, $opcionesMap) {
                $optionId = (int) ($opcion['opcion_id'] ?? 0);
                if ($optionId <= 0) {
                    return false;
                }

                /** @var Opcion|null $catalogOption */
                $catalogOption = $opcionesMap->get($optionId);

                return $catalogOption && (int) $catalogOption->grupo_opcion_id === $groupId;
            });

            $hasSelectionByName = in_array($groupKey, $selectedGroupKeysByName, true);

            if ($hasSelectionById || $hasSelectionByName) {
                continue;
            }

            throw ValidationException::withMessages([
                'productos' => ['Falta seleccionar una opcion para ' . $grupo->nombre . ' en ' . $producto->nombre . '.'],
            ]);
        }
    }

    private function isGroupEnabledForModalidad(?string $groupModalidad, string $modalidad): bool
    {
        $groupModalidad = $this->normalizeGroupKey((string) ($groupModalidad ?: 'todas'));

        return $groupModalidad === '' || $groupModalidad === 'todas' || $groupModalidad === $modalidad;
    }

    private function isGroupDependencySatisfied(?int $parentOptionId, array $selectedOptionIds): bool
    {
        $parentOptionId = (int) $parentOptionId;

        return $parentOptionId <= 0 || in_array($parentOptionId, $selectedOptionIds, true);
    }

    private function extractGroupKeyFromOptionName(mixed $optionName): ?string
    {
        if (!is_string($optionName)) {
            return null;
        }

        $optionName = trim($optionName);
        if ($optionName === '' || !str_contains($optionName, ':')) {
            return null;
        }

        [$groupLabel] = array_pad(explode(':', $optionName, 2), 2, '');
        $groupKey = $this->normalizeGroupKey($groupLabel);

        return $groupKey !== '' ? $groupKey : null;
    }

    private function normalizeAreaPreparacion(mixed $area): ?string
    {
        if (!is_string($area)) {
            return null;
        }

        $normalized = strtolower(trim($area));

        return in_array($normalized, ['cocina', 'barra'], true) ? $normalized : null;
    }

    private function isManualOtherProduct(?Producto $producto): bool
    {
        if (!$producto) {
            return false;
        }

        return $this->normalizeGroupKey((string) $producto->sku) === 'otro'
            || $this->normalizeGroupKey((string) $producto->nombre) === 'otro';
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeGroupKey(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtolower(trim($ascii));
        $ascii = preg_replace('/\s+/', '_', $ascii) ?? $ascii;

        return preg_replace('/[^a-z0-9_\+]/', '', $ascii) ?? $ascii;
    }

    private function normalizeNote(mixed $nota): ?string
    {
        if (!is_string($nota)) {
            return null;
        }

        $nota = trim($nota);

        return $nota === '' ? null : $nota;
    }

    private function ensureSpecialMesaExists(int $mesaId): void
    {
        if (Mesa::isEmployee($mesaId)) {
            Mesa::ensureEmployeeMesa();
        }

        if (Mesa::isTakeaway($mesaId)) {
            Mesa::ensureTakeawayMesa();
        }
    }

    private function buildClientDetailLines(OrdenDetalle $detail, bool $includeNote = false): array
    {
        if ((bool) $detail->es_otro_manual) {
            return [];
        }

        $lines = $this->linePresentationService->clientDetailLines(
            $detail->producto?->nombre,
            $detail->opciones->pluck('nombre')->all(),
            $detail->modalidad,
            $this->isComidaDiaProduct($detail->producto)
        );

        foreach ($detail->extras as $extra) {
            $name = trim((string) ($extra->nombre_personalizado ?: $extra->extra?->nombre ?: ''));
            if ($name !== '') {
                $cantidad = max(1, (int) ($extra->cantidad ?? 1));
                $lines[] = $name . ' x' . $cantidad;
            }
        }

        if ($includeNote && $detail->nota) {
            $lines[] = 'Nota: ' . $detail->nota;
        }

        return array_values(array_filter($lines, fn ($line) => trim((string) $line) !== ''));
    }
}





