<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Extra;
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
                'gruposOpciones' => fn ($query) => $query->where('activo', true)->orderBy('orden'),
                'gruposOpciones.opciones' => fn ($query) => $query->where('activo', true)->orderBy('id'),
            ])
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
            ->orderBy('nombre')
            ->get();

        $productosPos = $productos
            ->map(fn (Producto $producto) => $this->buildProductoPosData($producto, $esEmpleado, $menuDiaTercerTiempo))
            ->values();

        $categorias = Categoria::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $extras = Extra::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'precio']);

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
            'puedeRecuperar' => false,
        ]);
    }

    private function buildProductoPosData(Producto $producto, bool $esEmpleado, Collection $menuDiaTercerTiempo): array
    {
        $esComidaDia = (bool) $producto->es_comida_dia || $this->linePresentationService->isComida($producto->nombre);

        $grupos = $producto->gruposOpciones
            ->where('activo', true)
            ->filter(fn ($grupo) => !$esComidaDia || $this->normalize($grupo->nombre) !== 'modalidad')
            ->sortBy('orden')
            ->values()
            ->map(function ($grupo) {
                return [
                    'key' => 'grupo_' . $grupo->id,
                    'nombre' => $grupo->nombre,
                    'is_salsa' => $this->normalize($grupo->nombre) === 'salsa',
                    'modalidad' => $grupo->modalidad ?: 'todas',
                    'obligatorio' => (bool) $grupo->obligatorio,
                    'multiple' => (bool) $grupo->multiple,
                    'visible_if_option_id' => $grupo->solo_si_opcion_id ? (int) $grupo->solo_si_opcion_id : null,
                    'options' => $grupo->opciones
                        ->where('activo', true)
                        ->values()
                        ->map(function ($opcion) {
                            return [
                                'key' => 'opcion_' . $opcion->id,
                                'opcion_id' => (int) $opcion->id,
                                'nombre' => $opcion->nombre,
                                'label' => $this->linePresentationService->optionLabel($opcion->nombre),
                                'incremento_precio' => (float) $opcion->incremento_precio,
                                'incremento_costo' => (float) $opcion->incremento_costo,
                            ];
                        })
                        ->all(),
                ];
            })
            ->all();

        if ($esComidaDia) {
            $grupos = $this->appendComidaDynamicGroups($grupos, $menuDiaTercerTiempo);
        }

        return [
            'id' => (int) $producto->id,
            'nombre' => $producto->nombre,
            'precio_venta' => (float) ($esEmpleado ? $producto->costo : $producto->precio),
            'categoria_id' => (int) $producto->categoria_id,
            'permite_solo' => (bool) $producto->permite_solo,
            'permite_desayuno' => (bool) $producto->permite_desayuno,
            'permite_comida' => (bool) $producto->permite_comida,
            'incremento_desayuno' => (float) $producto->incremento_desayuno,
            'incremento_comida' => (float) $producto->incremento_comida,
            'es_comida_dia' => $esComidaDia,
            'grupos' => array_values($grupos),
        ];
    }

    private function appendComidaDynamicGroups(array $grupos, Collection $menuDiaTercerTiempo): array
    {
        $grupos[] = [
            'key' => 'menu_dia_tercer_tiempo',
            'nombre' => 'Tercer tiempo',
            'modalidad' => 'comida',
            'obligatorio' => true,
            'multiple' => false,
            'visible_if_option_id' => null,
            'options' => $menuDiaTercerTiempo->map(function (MenuDiaOpcion $opcion) {
                return [
                    'key' => 'menu_' . $opcion->id,
                    'opcion_id' => null,
                    'nombre' => 'Tercer tiempo: ' . $opcion->nombre,
                    'label' => $opcion->nombre,
                    'incremento_precio' => 0,
                    'incremento_costo' => 0,
                ];
            })->all(),
        ];

        return $grupos;
    }

    private function normalize(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $ascii = strtolower(trim($ascii));

        return preg_replace('/\s+/', '_', $ascii) ?? $ascii;
    }
}
