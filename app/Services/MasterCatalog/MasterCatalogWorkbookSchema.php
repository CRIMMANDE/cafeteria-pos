<?php

namespace App\Services\MasterCatalog;

class MasterCatalogWorkbookSchema
{
    /**
     * @return array<int, array{name: string, required_columns: array<int, string>, columns: array<int, string>, examples: array<int, array<string, mixed>>}>
     */
    public function sheets(): array
    {
        return [
            [
                'name' => 'categorias',
                'required_columns' => ['slug', 'nombre', 'tipo'],
                'columns' => ['slug', 'nombre', 'tipo', 'activo', 'orden'],
                'examples' => [
                    ['slug' => 'bebidas', 'nombre' => 'Bebidas', 'tipo' => 'barra', 'activo' => '1', 'orden' => '10'],
                    ['slug' => 'comida', 'nombre' => 'Comida', 'tipo' => 'cocina', 'activo' => '1', 'orden' => '20'],
                ],
            ],
            [
                'name' => 'productos',
                'required_columns' => ['sku', 'nombre', 'categoria_slug', 'precio', 'costo'],
                'columns' => [
                    'sku',
                    'nombre',
                    'categoria_slug',
                    'precio',
                    'costo',
                    'activo',
                    'permite_solo',
                    'permite_desayuno',
                    'permite_comida',
                    'incremento_desayuno',
                    'incremento_comida',
                    'usa_menu_dia',
                    'usa_extras',
                    'usa_notas',
                    'usa_salsa',
                    'orden',
                ],
                'examples' => [
                    [
                        'sku' => 'chilaquiles',
                        'nombre' => 'Chilaquiles',
                        'categoria_slug' => 'comida',
                        'precio' => '92',
                        'costo' => '52',
                        'activo' => '1',
                        'permite_solo' => '1',
                        'permite_desayuno' => '1',
                        'permite_comida' => '1',
                        'incremento_desayuno' => '53',
                        'incremento_comida' => '35',
                        'usa_menu_dia' => '0',
                        'usa_extras' => '1',
                        'usa_notas' => '1',
                        'usa_salsa' => '1',
                        'orden' => '10',
                    ],
                    [
                        'sku' => 'capuccino',
                        'nombre' => 'Capuccino',
                        'categoria_slug' => 'bebidas',
                        'precio' => '45',
                        'costo' => '18',
                        'activo' => '1',
                        'permite_solo' => '1',
                        'permite_desayuno' => '0',
                        'permite_comida' => '0',
                        'incremento_desayuno' => '0',
                        'incremento_comida' => '0',
                        'usa_menu_dia' => '0',
                        'usa_extras' => '1',
                        'usa_notas' => '1',
                        'usa_salsa' => '0',
                        'orden' => '20',
                    ],
                ],
            ],
            [
                'name' => 'extras',
                'required_columns' => ['slug', 'nombre', 'precio'],
                'columns' => ['slug', 'nombre', 'precio', 'activo', 'permite_cantidad', 'orden'],
                'examples' => [
                    ['slug' => 'huevo-extra', 'nombre' => 'Huevo extra', 'precio' => '12', 'activo' => '1', 'permite_cantidad' => '1', 'orden' => '10'],
                    ['slug' => 'shot-extra', 'nombre' => 'Shot extra', 'precio' => '15', 'activo' => '1', 'permite_cantidad' => '1', 'orden' => '20'],
                    ['slug' => 'otro', 'nombre' => 'Otro', 'precio' => '0', 'activo' => '1', 'permite_cantidad' => '1', 'orden' => '90'],
                ],
            ],
            [
                'name' => 'grupos_opciones',
                'required_columns' => ['slug', 'nombre'],
                'columns' => [
                    'slug',
                    'nombre',
                    'tipo',
                    'obligatorio',
                    'multiple',
                    'activo',
                    'orden_visual',
                    'scope_modalidad',
                    'area_aplicacion',
                    'es_grupo_salsa',
                    'prioridad_visual',
                ],
                'examples' => [
                    [
                        'slug' => 'salsa',
                        'nombre' => 'Salsa',
                        'tipo' => 'seleccion_unica',
                        'obligatorio' => '1',
                        'multiple' => '0',
                        'activo' => '1',
                        'orden_visual' => '5',
                        'scope_modalidad' => 'todas',
                        'area_aplicacion' => 'cocina',
                        'es_grupo_salsa' => '1',
                        'prioridad_visual' => '-100',
                    ],
                    [
                        'slug' => 'leche',
                        'nombre' => 'Leche',
                        'tipo' => 'seleccion_unica',
                        'obligatorio' => '1',
                        'multiple' => '0',
                        'activo' => '1',
                        'orden_visual' => '10',
                        'scope_modalidad' => 'todas',
                        'area_aplicacion' => 'barra',
                        'es_grupo_salsa' => '0',
                        'prioridad_visual' => '0',
                    ],
                ],
            ],
            [
                'name' => 'opciones',
                'required_columns' => ['grupo_slug', 'slug', 'nombre'],
                'columns' => ['grupo_slug', 'slug', 'nombre', 'precio', 'costo', 'activo', 'orden', 'codigo_corto'],
                'examples' => [
                    ['grupo_slug' => 'salsa', 'slug' => 'roja', 'nombre' => 'Salsa: Roja', 'precio' => '0', 'costo' => '0', 'activo' => '1', 'orden' => '10', 'codigo_corto' => ''],
                    ['grupo_slug' => 'salsa', 'slug' => 'verde', 'nombre' => 'Salsa: Verde', 'precio' => '0', 'costo' => '0', 'activo' => '1', 'orden' => '20', 'codigo_corto' => ''],
                    ['grupo_slug' => 'salsa', 'slug' => 'dos-salsas', 'nombre' => 'Salsa: Dos salsas', 'precio' => '0', 'costo' => '0', 'activo' => '1', 'orden' => '30', 'codigo_corto' => ''],
                    ['grupo_slug' => 'leche', 'slug' => 'almendra', 'nombre' => 'Leche: Almendra', 'precio' => '8', 'costo' => '8', 'activo' => '1', 'orden' => '30', 'codigo_corto' => 'ALM'],
                ],
            ],
            [
                'name' => 'producto_grupo_opcion',
                'required_columns' => ['producto_sku', 'grupo_slug'],
                'columns' => ['producto_sku', 'grupo_slug', 'obligatorio', 'multiple', 'scope_modalidad', 'orden_visual', 'activo'],
                'examples' => [
                    ['producto_sku' => 'chilaquiles', 'grupo_slug' => 'salsa', 'obligatorio' => '1', 'multiple' => '0', 'scope_modalidad' => 'todas', 'orden_visual' => '5', 'activo' => '1'],
                    ['producto_sku' => 'capuccino', 'grupo_slug' => 'leche', 'obligatorio' => '1', 'multiple' => '0', 'scope_modalidad' => 'todas', 'orden_visual' => '10', 'activo' => '1'],
                ],
            ],
            [
                'name' => 'componentes_preparacion',
                'required_columns' => ['producto_sku', 'area', 'nombre_componente', 'cantidad'],
                'columns' => ['producto_sku', 'modalidad', 'area', 'nombre_componente', 'cantidad', 'orden', 'activo'],
                'examples' => [
                    ['producto_sku' => 'capuccino', 'modalidad' => 'todas', 'area' => 'barra', 'nombre_componente' => 'CAPUCCINO', 'cantidad' => '1', 'orden' => '10', 'activo' => '1'],
                    ['producto_sku' => 'chilaquiles', 'modalidad' => 'desayuno', 'area' => 'cocina', 'nombre_componente' => 'CHILAQUILES', 'cantidad' => '1', 'orden' => '10', 'activo' => '1'],
                ],
            ],
            [
                'name' => 'menu_dia_opciones',
                'required_columns' => ['slug', 'tipo', 'nombre'],
                'columns' => ['slug', 'tipo', 'nombre', 'fecha', 'activo', 'orden'],
                'examples' => [
                    ['slug' => 'milanesa-pollo', 'tipo' => 'comida_tercer_tiempo', 'nombre' => 'Milanesa de Pollo', 'fecha' => '', 'activo' => '1', 'orden' => '10'],
                    ['slug' => 'albondigas', 'tipo' => 'comida_tercer_tiempo', 'nombre' => 'Albondigas', 'fecha' => '', 'activo' => '1', 'orden' => '20'],
                ],
            ],
            [
                'name' => 'readme',
                'required_columns' => ['seccion', 'descripcion'],
                'columns' => ['seccion', 'descripcion'],
                'examples' => $this->readmeRows(),
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function requiredSheetNames(): array
    {
        return collect($this->sheets())
            ->pluck('name')
            ->all();
    }

    /**
     * @return array{name: string, required_columns: array<int, string>, columns: array<int, string>, examples: array<int, array<string, mixed>>}
     */
    public function sheet(string $name): array
    {
        $sheet = collect($this->sheets())->firstWhere('name', $name);

        if (!is_array($sheet)) {
            throw new \InvalidArgumentException('Hoja no soportada: ' . $name);
        }

        return $sheet;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readmeRows(): array
    {
        return [
            ['seccion' => 'Objetivo', 'descripcion' => 'Este archivo es la fuente de verdad del catalogo base del POS.'],
            ['seccion' => 'Orden recomendado', 'descripcion' => '1) categorias, 2) productos, 3) extras, 4) grupos_opciones, 5) opciones, 6) producto_grupo_opcion, 7) componentes_preparacion, 8) menu_dia_opciones.'],
            ['seccion' => 'Claves estables', 'descripcion' => 'Las relaciones se hacen con slug y sku. No uses IDs manuales.'],
            ['seccion' => 'Booleanos', 'descripcion' => 'Usa 1/0, true/false, si/no.'],
            ['seccion' => 'Salsa', 'descripcion' => 'Define grupo slug "salsa", sus opciones y asigna solo a productos que aplican en producto_grupo_opcion.'],
            ['seccion' => 'Capuccino', 'descripcion' => 'Asigna grupos como leche y sabor al SKU capuccino en producto_grupo_opcion.'],
            ['seccion' => 'Validacion', 'descripcion' => 'Antes de importar se valida duplicados, referencias cruzadas, precios negativos y columnas obligatorias.'],
            ['seccion' => 'Comando importar', 'descripcion' => 'php artisan pos:importar-catalogo-maestro database/catalogos/catalogo_maestro.xlsx'],
            ['seccion' => 'Comando plantilla', 'descripcion' => 'php artisan pos:generar-catalogo-maestro-template --force'],
        ];
    }
}

