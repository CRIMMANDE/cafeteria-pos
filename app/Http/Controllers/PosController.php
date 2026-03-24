<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Extra;
use App\Models\GrupoOpcion;
use App\Models\MenuDiaOpcion;
use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Producto;
use App\Services\OrderLinePresentationService;
use Illuminate\Support\Collection;

class PosController extends Controller
{
    public function __construct(
        private readonly OrderLinePresentationService $linePresentationService,
    ) {
    }

    public function mesas()
    {
        $mesas = Mesa::all();

        return view('pos.mesas', compact('mesas'));
    }

    public function orden($mesa)
    {
        return $this->renderOrdenView((int) $mesa);
    }

    public function llevar()
    {
        Mesa::ensureTakeawayMesa();

        return $this->renderOrdenView(Mesa::TAKEAWAY_ID);
    }

    public function empleados()
    {
        Mesa::ensureEmployeeMesa();

        return $this->renderOrdenView(Mesa::EMPLOYEE_ID);
    }

    private function renderOrdenView(int $mesa)
    {
        $esParaLlevar = Mesa::isTakeaway($mesa);
        $esEmpleado = Mesa::isEmployee($mesa);

        $productos = Producto::query()
            ->where('activo', true)
            ->whereHas('categoria', fn ($query) => $query->where('activo', true))
            ->with([
                'categoria',
                'gruposOpciones' => fn ($query) => $query
                    ->where('activo', true)
                    ->orderBy('prioridad_visual')
                    ->orderBy('orden_visual')
                    ->orderBy('orden'),
                'gruposOpciones.opciones' => fn ($query) => $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('id'),
                'extras' => fn ($query) => $query
                    ->where('extras.activo', true)
                    ->wherePivot('activo', true)
                    ->orderBy('producto_extra.orden_visual')
                    ->orderBy('extras.orden')
                    ->orderBy('extras.nombre'),
            ])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get()
            ->map(function (Producto $producto) use ($esEmpleado) {
                $producto->precio_venta = $esEmpleado
                    ? (float) ($producto->costo ?? 0)
                    : (float) $producto->precio;

                return $producto;
            });

        $menuDiaTercerTiempo = MenuDiaOpcion::query()
            ->ofType('comida_tercer_tiempo')
            ->activeForDate(now())
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $desayunoGrupos = $this->loadDesayunoGroupTemplates();
        $comidaDiaGrupos = $this->loadComidaDiaGroupTemplates();

        $productosPos = $productos
            ->map(fn (Producto $producto) => $this->buildProductoPosData($producto, $esEmpleado, $menuDiaTercerTiempo, $comidaDiaGrupos))
            ->values();

        $categorias = Categoria::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $extras = Extra::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'slug', 'nombre', 'precio', 'permite_cantidad']);

        Orden::where('mesa_id', $mesa)
            ->where('estado', 'abierta')
            ->first();

        return view('pos.orden', [
            'mesa' => $mesa,
            'mesaLabel' => $esEmpleado ? 'EMPLEADOS' : ($esParaLlevar ? 'P/LLEVAR' : 'Mesa ' . $mesa),
            'esParaLlevar' => $esParaLlevar,
            'esEmpleado' => $esEmpleado,
            'productos' => $productos,
            'categorias' => $categorias,
            'productosPosJson' => $productosPos,
            'extrasPosJson' => $extras,
            'desayunoGruposJson' => $desayunoGrupos,
            'puedeRecuperar' => false,
        ]);
    }

    private function buildProductoPosData(Producto $producto, bool $esEmpleado, Collection $menuDiaTercerTiempo, array $comidaDiaGrupos): array
    {
        $esComidaDia = (bool) $producto->usa_menu_dia || (bool) $producto->es_comida_dia || $this->linePresentationService->isComida($producto->nombre);

        $allowedExtras = $producto->extras
            ->filter(fn ($extra) => (bool) ($extra->pivot?->activo ?? true))
            ->values();

        $grupos = $producto->gruposOpciones
            ->where('activo', true)
            ->filter(fn ($grupo) => !$esComidaDia || $this->normalize($grupo->nombre) !== 'modalidad')
            ->sortBy([
                ['prioridad_visual', 'asc'],
                ['orden_visual', 'asc'],
                ['orden', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(function ($grupo) {
                $groupCanonical = $this->canonicalKey((string) ($grupo->slug ?: $grupo->nombre));
                $isMealSlotGroup = in_array($groupCanonical, ['primer_tiempo', 'segundo_tiempo'], true);

                $options = $grupo->opciones
                    ->where('activo', true)
                    ->sortBy([
                        ['orden', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->values()
                    ->map(function ($opcion) use ($groupCanonical) {
                        return [
                            'key' => 'opcion_' . $opcion->id,
                            'opcion_id' => (int) $opcion->id,
                            'slug' => $opcion->slug,
                            'nombre' => $opcion->nombre,
                            'label' => $this->displayOptionLabelForGroup($groupCanonical, (string) $opcion->nombre),
                            'incremento_precio' => (float) $opcion->incremento_precio,
                            'incremento_costo' => (float) $opcion->incremento_costo,
                        ];
                    })
                    ->all();

                if ($isMealSlotGroup) {
                    $options = $this->ensureNadaOptionForMealSlot($options, (string) $grupo->nombre, 'grupo_' . $groupCanonical);
                }

                return [
                    'key' => 'grupo_' . $grupo->id,
                    'slug' => $grupo->slug,
                    'nombre' => $grupo->nombre,
                    'is_salsa' => (bool) $grupo->es_grupo_salsa || $this->normalize($grupo->nombre) === 'salsa',
                    'modalidad' => $grupo->scope_modalidad ?: ($grupo->modalidad ?: 'todas'),
                    'obligatorio' => $isMealSlotGroup ? true : (bool) $grupo->obligatorio,
                    'multiple' => (bool) $grupo->multiple,
                    'visible_if_option_id' => $grupo->solo_si_opcion_id ? (int) $grupo->solo_si_opcion_id : null,
                    'options' => $options,
                ];
            })
            ->all();

        if ($esComidaDia) {
            $grupos = $this->appendComidaDynamicGroups($producto, $grupos, $menuDiaTercerTiempo, $comidaDiaGrupos);
        }

        return [
            'id' => (int) $producto->id,
            'sku' => $producto->sku,
            'nombre' => $producto->nombre,
            'is_otro_manual' => $this->isManualOtherProduct($producto),
            'precio_venta' => (float) ($esEmpleado ? $producto->costo : $producto->precio),
            'categoria_id' => (int) $producto->categoria_id,
            'permite_solo' => (bool) $producto->permite_solo,
            'permite_desayuno' => (bool) $producto->permite_desayuno,
            'permite_comida' => (bool) $producto->permite_comida,
            'incremento_desayuno' => (float) $producto->incremento_desayuno,
            'incremento_comida' => (float) $producto->incremento_comida,
            'es_comida_dia' => $esComidaDia,
            'usa_extras' => (bool) $producto->usa_extras,
            'usa_notas' => (bool) $producto->usa_notas,
            'usa_salsa' => (bool) $producto->usa_salsa,
            'extra_ids_permitidos' => $allowedExtras->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'extra_ids_obligatorios' => $allowedExtras
                ->filter(fn ($extra) => (bool) ($extra->pivot?->obligatorio ?? false))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            'grupos' => array_values($grupos),
        ];
    }

    private function appendComidaDynamicGroups(Producto $producto, array $grupos, Collection $menuDiaTercerTiempo, array $comidaDiaGrupos): array
    {
        $skuKey = $this->canonicalKey((string) ($producto->sku ?? ''));
        $nameKey = $this->canonicalKey((string) ($producto->nombre ?? ''));
        $soloTercerTiempo = $skuKey === 'platillo' || $nameKey === 'platillo';

        $existingKeys = collect($grupos)
            ->map(fn (array $grupo) => $this->canonicalKey((string) ($grupo['slug'] ?? $grupo['nombre'] ?? '')))
            ->filter()
            ->values()
            ->all();
        $existingKeyMap = array_fill_keys($existingKeys, true);

        $appendTemplate = function (array $template, bool $obligatorio) use (&$grupos, &$existingKeyMap): void {
            $canonical = $this->canonicalKey((string) ($template['slug'] ?? $template['nombre'] ?? ''));
            if ($canonical === '' || isset($existingKeyMap[$canonical])) {
                return;
            }

            $options = collect($template['options'] ?? [])
                ->map(fn (array $opcion) => [
                    ...$opcion,
                    'incremento_precio' => 0.0,
                    'incremento_costo' => 0.0,
                ])
                ->values()
                ->all();

            if (in_array($canonical, ['primer_tiempo', 'segundo_tiempo'], true)) {
                $options = $this->ensureNadaOptionForMealSlot($options, (string) ($template['nombre'] ?? ''), 'menu_' . $canonical);
            }

            $grupos[] = [
                ...$template,
                'modalidad' => 'comida',
                'obligatorio' => $obligatorio,
                'multiple' => false,
                'visible_if_option_id' => null,
                'options' => $options,
            ];

            $existingKeyMap[$canonical] = true;
        };

        if (!$soloTercerTiempo) {
            foreach ($comidaDiaGrupos as $template) {
                $canonical = $this->canonicalKey((string) ($template['slug'] ?? $template['nombre'] ?? ''));
                if (in_array($canonical, ['primer_tiempo', 'segundo_tiempo'], true)) {
                    $appendTemplate($template, true);
                }
            }

            if (!isset($existingKeyMap['primer_tiempo'])) {
                $appendTemplate([
                    'key' => 'menu_dia_primer_tiempo',
                    'slug' => 'primer-tiempo',
                    'nombre' => 'Primer tiempo',
                    'options' => [
                        ['key' => 'menu_primer_nada', 'opcion_id' => null, 'slug' => 'nada', 'nombre' => 'Primer tiempo: Nada', 'label' => 'Nada', 'incremento_precio' => 0, 'incremento_costo' => 0],
                        ['key' => 'menu_primer_sopa', 'opcion_id' => null, 'slug' => 'sopa', 'nombre' => 'Primer tiempo: Sopa', 'label' => 'Sopa', 'incremento_precio' => 0, 'incremento_costo' => 0],
                        ['key' => 'menu_primer_arroz', 'opcion_id' => null, 'slug' => 'arroz', 'nombre' => 'Primer tiempo: Arroz', 'label' => 'Arroz', 'incremento_precio' => 0, 'incremento_costo' => 0],
                        ['key' => 'menu_primer_pasta', 'opcion_id' => null, 'slug' => 'pasta', 'nombre' => 'Primer tiempo: Pasta', 'label' => 'Pasta', 'incremento_precio' => 0, 'incremento_costo' => 0],
                    ],
                ], true);
            }

            if (!isset($existingKeyMap['segundo_tiempo'])) {
                $appendTemplate([
                    'key' => 'menu_dia_segundo_tiempo',
                    'slug' => 'segundo-tiempo',
                    'nombre' => 'Segundo tiempo',
                    'options' => [
                        ['key' => 'menu_segundo_nada', 'opcion_id' => null, 'slug' => 'nada', 'nombre' => 'Segundo tiempo: Nada', 'label' => 'Nada', 'incremento_precio' => 0, 'incremento_costo' => 0],
                        ['key' => 'menu_segundo_sopa', 'opcion_id' => null, 'slug' => 'sopa', 'nombre' => 'Segundo tiempo: Sopa', 'label' => 'Sopa', 'incremento_precio' => 0, 'incremento_costo' => 0],
                        ['key' => 'menu_segundo_arroz', 'opcion_id' => null, 'slug' => 'arroz', 'nombre' => 'Segundo tiempo: Arroz', 'label' => 'Arroz', 'incremento_precio' => 0, 'incremento_costo' => 0],
                        ['key' => 'menu_segundo_pasta', 'opcion_id' => null, 'slug' => 'pasta', 'nombre' => 'Segundo tiempo: Pasta', 'label' => 'Pasta', 'incremento_precio' => 0, 'incremento_costo' => 0],
                    ],
                ], true);
            }
        }

        if (!isset($existingKeyMap['tercer_tiempo'])) {
            $grupos[] = [
                'key' => 'menu_dia_tercer_tiempo',
                'slug' => 'tercer-tiempo',
                'nombre' => 'Tercer tiempo',
                'modalidad' => 'comida',
                'obligatorio' => true,
                'multiple' => false,
                'visible_if_option_id' => null,
                'options' => $menuDiaTercerTiempo->map(function (MenuDiaOpcion $opcion) {
                    return [
                        'key' => 'menu_' . $opcion->id,
                        'opcion_id' => null,
                        'slug' => $opcion->slug,
                        'nombre' => 'Tercer tiempo: ' . $opcion->nombre,
                        'label' => $opcion->nombre,
                        'incremento_precio' => 0,
                        'incremento_costo' => 0,
                    ];
                })->all(),
            ];
        }

        return $grupos;
    }

    private function loadComidaDiaGroupTemplates(): array
    {
        return GrupoOpcion::query()
            ->whereIn('slug', [
                'primer-tiempo',
                'primer_tiempo',
                'segundo-tiempo',
                'segundo_tiempo',
            ])
            ->with(['opciones' => fn ($query) => $query
                ->where('activo', true)
                ->orderBy('orden')
                ->orderBy('id')])
            ->orderBy('prioridad_visual')
            ->orderBy('orden_visual')
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(function (GrupoOpcion $grupo) {
                return [
                    'key' => 'catalogo_grupo_' . $grupo->id,
                    'slug' => $grupo->slug,
                    'nombre' => $grupo->nombre,
                    'modalidad' => 'comida',
                    'obligatorio' => true,
                    'multiple' => false,
                    'visible_if_option_id' => null,
                    'options' => $this->ensureNadaOptionForMealSlot(
                        $grupo->opciones->map(function ($opcion) {
                            return [
                                'key' => 'catalogo_opcion_' . $opcion->id,
                                'opcion_id' => null,
                                'slug' => $opcion->slug,
                                'nombre' => $opcion->nombre,
                                'label' => $this->linePresentationService->optionLabel($opcion->nombre),
                                'incremento_precio' => 0.0,
                                'incremento_costo' => 0.0,
                            ];
                        })->all(),
                        (string) $grupo->nombre,
                        'catalogo_' . $this->canonicalKey((string) ($grupo->slug ?: $grupo->nombre))
                    ),
                ];
            })
            ->filter(fn (array $grupo) => count($grupo['options']) > 0)
            ->values()
            ->all();
    }

    private function ensureNadaOptionForMealSlot(array $options, string $groupName, string $keyPrefix): array
    {
        $normalized = collect($options)
            ->map(function (array $option) {
                $slug = $this->canonicalKey((string) ($option['slug'] ?? ''));
                $label = $this->canonicalKey((string) ($option['label'] ?? ''));
                $name = $this->canonicalKey((string) ($option['nombre'] ?? ''));

                return [$slug, $label, $name];
            })
            ->flatten()
            ->filter()
            ->values()
            ->all();

        if (in_array('nada', $normalized, true)) {
            return $options;
        }

        return array_values(array_merge([[
            'key' => $keyPrefix . '_nada',
            'opcion_id' => null,
            'slug' => 'nada',
            'nombre' => $groupName . ': Nada',
            'label' => 'Nada',
            'incremento_precio' => 0.0,
            'incremento_costo' => 0.0,
        ]], $options));
    }

    private function loadDesayunoGroupTemplates(): array
    {
        return GrupoOpcion::query()
            ->whereIn('slug', [
                'bebida-del-paquete',
                'bebida_del_paquete',
                'sabor-te',
                'sabor_te',
                'fruta-del-paquete',
                'fruta_del_paquete',
                'granola',
                'primer-tiempo',
                'primer_tiempo',
                'segundo-tiempo',
                'segundo_tiempo',
            ])
            ->with(['opciones' => fn ($query) => $query
                ->where('activo', true)
                ->orderBy('orden')
                ->orderBy('id')])
            ->orderBy('prioridad_visual')
            ->orderBy('orden_visual')
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(function (GrupoOpcion $grupo) {
                $groupCanonical = $this->canonicalKey((string) ($grupo->slug ?: $grupo->nombre));

                return [
                    'key' => 'catalogo_grupo_' . $grupo->id,
                    'slug' => $grupo->slug,
                    'nombre' => $grupo->nombre,
                    'modalidad' => $grupo->scope_modalidad ?: ($grupo->modalidad ?: 'todas'),
                    'obligatorio' => (bool) $grupo->obligatorio,
                    'multiple' => (bool) $grupo->multiple,
                    'visible_if_option_id' => $grupo->solo_si_opcion_id ? (int) $grupo->solo_si_opcion_id : null,
                    'options' => $grupo->opciones->map(function ($opcion) use ($groupCanonical) {
                        return [
                            'key' => 'catalogo_opcion_' . $opcion->id,
                            'opcion_id' => (int) $opcion->id,
                            'slug' => $opcion->slug,
                            'nombre' => $opcion->nombre,
                            'label' => $this->displayOptionLabelForGroup($groupCanonical, (string) $opcion->nombre),
                            'incremento_precio' => (float) $opcion->incremento_precio,
                            'incremento_costo' => (float) $opcion->incremento_costo,
                        ];
                    })->all(),
                ];
            })
            ->filter(fn (array $grupo) => count($grupo['options']) > 0)
            ->values()
            ->all();
    }

    private function displayOptionLabelForGroup(string $groupCanonical, string $optionName): string
    {
        $label = $this->linePresentationService->optionLabel($optionName);

        if (!in_array($groupCanonical, ['bebida_del_paquete', 'bebida'], true)) {
            return $label;
        }

        $optionKey = $this->canonicalKey($label);

        return in_array($optionKey, ['cafe_americano', 'americano', 'cafe'], true)
            ? 'Cafe'
            : $label;
    }

    private function normalize(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtolower(trim($ascii));

        return preg_replace('/\s+/', '_', $ascii) ?? $ascii;
    }

    private function canonicalKey(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtolower(trim($ascii));
        $ascii = preg_replace('/[^a-z0-9]+/', '_', $ascii) ?? $ascii;

        return trim($ascii, '_');
    }

    private function isManualOtherProduct(Producto $producto): bool
    {
        return $this->canonicalKey((string) $producto->sku) === 'otro'
            || $this->canonicalKey((string) $producto->nombre) === 'otro';
    }
}


