<?php

namespace App\Services\MasterCatalog;

use App\Models\Categoria;
use App\Models\Extra;
use App\Models\GrupoOpcion;
use App\Models\MenuDiaOpcion;
use App\Models\Opcion;
use App\Models\Producto;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterCatalogImportService
{
    public function __construct(
        private readonly MasterCatalogWorkbookSchema $schema,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function import(string $absolutePath): array
    {
        if (!is_file($absolutePath)) {
            throw new MasterCatalogImportException([
                'No existe el archivo: ' . $absolutePath,
            ]);
        }

        $spreadsheet = IOFactory::load($absolutePath);
        $workbook = $this->readWorkbook($spreadsheet);
        $normalized = $this->validateAndNormalize($workbook);

        return DB::transaction(fn () => $this->persist($normalized));
    }

    /**
     * @param  \PhpOffice\PhpSpreadsheet\Spreadsheet  $spreadsheet
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function readWorkbook($spreadsheet): array
    {
        $errors = [];
        $worksheets = [];
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $worksheets[$worksheet->getTitle()] = $worksheet;
        }

        $data = [];
        foreach ($this->schema->sheets() as $sheetMeta) {
            $sheetName = $sheetMeta['name'];
            $worksheet = Arr::get($worksheets, $sheetName);

            if (!$worksheet instanceof Worksheet) {
                $errors[] = "Falta la hoja requerida '{$sheetName}'.";
                $data[$sheetName] = [];
                continue;
            }

            $data[$sheetName] = $this->readSheetRows(
                $worksheet,
                $sheetMeta['columns'],
                $sheetMeta['required_columns'],
                $sheetName,
                $errors
            );
        }

        if ($errors !== []) {
            throw new MasterCatalogImportException($errors);
        }

        return $data;
    }

    /**
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $requiredColumns
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function readSheetRows(
        Worksheet $worksheet,
        array $columns,
        array $requiredColumns,
        string $sheetName,
        array &$errors
    ): array {
        $raw = $worksheet->toArray(null, true, true, true);
        $header = $raw[1] ?? [];

        $headerMap = [];
        foreach ($header as $letter => $value) {
            $normalized = $this->normalizeHeader((string) $value);
            if ($normalized !== '') {
                $headerMap[$normalized] = $letter;
            }
        }

        foreach ($requiredColumns as $required) {
            if (!isset($headerMap[$this->normalizeHeader($required)])) {
                $errors[] = "Hoja '{$sheetName}': falta columna obligatoria '{$required}'.";
            }
        }

        $rows = [];
        $lastRow = max(array_keys($raw));

        for ($rowNumber = 2; $rowNumber <= $lastRow; $rowNumber++) {
            $row = ['_row' => $rowNumber];
            $isEmpty = true;

            foreach ($columns as $column) {
                $letter = $headerMap[$this->normalizeHeader($column)] ?? null;
                $value = $letter ? ($raw[$rowNumber][$letter] ?? null) : null;
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        $value = null;
                    }
                }

                if ($value !== null) {
                    $isEmpty = false;
                }

                $row[$column] = $value;
            }

            if (!$isEmpty) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $workbook
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function validateAndNormalize(array $workbook): array
    {
        $errors = [];

        $categories = $this->normalizeCategories($workbook['categorias'] ?? [], $errors);
        $categorySlugs = array_column($categories, 'slug');

        $products = $this->normalizeProducts($workbook['productos'] ?? [], $categorySlugs, $errors);
        $productSkus = array_column($products, 'sku');

        $extras = $this->normalizeExtras($workbook['extras'] ?? [], $errors);

        $groups = $this->normalizeGroups($workbook['grupos_opciones'] ?? [], $errors);
        $groupSlugs = array_column($groups, 'slug');

        $options = $this->normalizeOptions($workbook['opciones'] ?? [], $groupSlugs, $errors);

        $productGroup = $this->normalizeProductGroup($workbook['producto_grupo_opcion'] ?? [], $productSkus, $groupSlugs, $errors);
        $this->validateSalsaCoverage($products, $groups, $productGroup, $errors);

        $components = $this->normalizeComponents($workbook['componentes_preparacion'] ?? [], $productSkus, $errors);
        $menuDia = $this->normalizeMenuDia($workbook['menu_dia_opciones'] ?? [], $errors);

        if ($errors !== []) {
            throw new MasterCatalogImportException($errors);
        }

        return [
            'categorias' => $categories,
            'productos' => $products,
            'extras' => $extras,
            'grupos' => $groups,
            'opciones' => $options,
            'producto_grupo' => $productGroup,
            'componentes' => $components,
            'menu_dia' => $menuDia,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCategories(array $rows, array &$errors): array
    {
        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $slug = $this->requiredKey($row, 'slug', 'categorias', $line, $errors);
            $nombre = $this->requiredString($row, 'nombre', 'categorias', $line, $errors);
            $tipo = Str::lower((string) $this->requiredString($row, 'tipo', 'categorias', $line, $errors));

            if (!in_array($tipo, ['cocina', 'barra'], true)) {
                $errors[] = "categorias fila {$line}: 'tipo' debe ser cocina o barra.";
            }

            if ($slug !== null) {
                if (isset($seen[$slug])) {
                    $errors[] = "categorias fila {$line}: slug duplicado '{$slug}'.";
                }
                $seen[$slug] = true;
            }

            $normalized[] = [
                'slug' => $slug,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'categorias', $line, 'activo', $errors),
                'orden' => $this->intValue($row['orden'] ?? null, 0, 'categorias', $line, 'orden', $errors),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $categorySlugs
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeProducts(array $rows, array $categorySlugs, array &$errors): array
    {
        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $sku = $this->requiredKey($row, 'sku', 'productos', $line, $errors);
            $categoriaSlug = $this->requiredKey($row, 'categoria_slug', 'productos', $line, $errors);

            if ($sku !== null) {
                if (isset($seen[$sku])) {
                    $errors[] = "productos fila {$line}: sku duplicado '{$sku}'.";
                }
                $seen[$sku] = true;
            }

            if ($categoriaSlug !== null && !in_array($categoriaSlug, $categorySlugs, true)) {
                $errors[] = "productos fila {$line}: categoria_slug '{$categoriaSlug}' no existe.";
            }

            $normalized[] = [
                'sku' => $sku,
                'nombre' => $this->requiredString($row, 'nombre', 'productos', $line, $errors),
                'categoria_slug' => $categoriaSlug,
                'precio' => $this->decimalValue($row['precio'] ?? null, 0, 'productos', $line, 'precio', $errors),
                'costo' => $this->decimalValue($row['costo'] ?? null, 0, 'productos', $line, 'costo', $errors),
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'productos', $line, 'activo', $errors),
                'permite_solo' => $this->boolValue($row['permite_solo'] ?? null, true, 'productos', $line, 'permite_solo', $errors),
                'permite_desayuno' => $this->boolValue($row['permite_desayuno'] ?? null, false, 'productos', $line, 'permite_desayuno', $errors),
                'permite_comida' => $this->boolValue($row['permite_comida'] ?? null, false, 'productos', $line, 'permite_comida', $errors),
                'incremento_desayuno' => $this->decimalValue($row['incremento_desayuno'] ?? null, 0, 'productos', $line, 'incremento_desayuno', $errors),
                'incremento_comida' => $this->decimalValue($row['incremento_comida'] ?? null, 0, 'productos', $line, 'incremento_comida', $errors),
                'usa_menu_dia' => $this->boolValue($row['usa_menu_dia'] ?? null, false, 'productos', $line, 'usa_menu_dia', $errors),
                'usa_extras' => $this->boolValue($row['usa_extras'] ?? null, true, 'productos', $line, 'usa_extras', $errors),
                'usa_notas' => $this->boolValue($row['usa_notas'] ?? null, true, 'productos', $line, 'usa_notas', $errors),
                'usa_salsa' => $this->boolValue($row['usa_salsa'] ?? null, false, 'productos', $line, 'usa_salsa', $errors),
                'orden' => $this->intValue($row['orden'] ?? null, 0, 'productos', $line, 'orden', $errors),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeExtras(array $rows, array &$errors): array
    {
        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $slug = $this->requiredKey($row, 'slug', 'extras', $line, $errors);

            if ($slug !== null) {
                if (isset($seen[$slug])) {
                    $errors[] = "extras fila {$line}: slug duplicado '{$slug}'.";
                }
                $seen[$slug] = true;
            }

            $normalized[] = [
                'slug' => $slug,
                'nombre' => $this->requiredString($row, 'nombre', 'extras', $line, $errors),
                'precio' => $this->decimalValue($row['precio'] ?? null, 0, 'extras', $line, 'precio', $errors),
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'extras', $line, 'activo', $errors),
                'permite_cantidad' => $this->boolValue($row['permite_cantidad'] ?? null, true, 'extras', $line, 'permite_cantidad', $errors),
                'orden' => $this->intValue($row['orden'] ?? null, 0, 'extras', $line, 'orden', $errors),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeGroups(array $rows, array &$errors): array
    {
        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $slug = $this->requiredKey($row, 'slug', 'grupos_opciones', $line, $errors);
            if ($slug !== null) {
                if (isset($seen[$slug])) {
                    $errors[] = "grupos_opciones fila {$line}: slug duplicado '{$slug}'.";
                }
                $seen[$slug] = true;
            }

            $scope = Str::lower((string) ($row['scope_modalidad'] ?? 'todas'));
            if (!in_array($scope, ['todas', 'solo', 'desayuno', 'comida'], true)) {
                $errors[] = "grupos_opciones fila {$line}: scope_modalidad invalido '{$scope}'.";
            }

            $area = $row['area_aplicacion'] === null ? null : Str::lower((string) $row['area_aplicacion']);
            if ($area !== null && !in_array($area, ['cocina', 'barra'], true)) {
                $errors[] = "grupos_opciones fila {$line}: area_aplicacion debe ser cocina o barra.";
            }

            $multiple = $this->boolValue($row['multiple'] ?? null, false, 'grupos_opciones', $line, 'multiple', $errors);
            $tipo = trim((string) ($row['tipo'] ?? ''));
            if ($tipo === '') {
                $tipo = $multiple ? 'seleccion_multiple' : 'seleccion_unica';
            }

            $isSalsa = $this->boolValue($row['es_grupo_salsa'] ?? null, Str::contains((string) $slug, 'salsa'), 'grupos_opciones', $line, 'es_grupo_salsa', $errors);

            $normalized[] = [
                'slug' => $slug,
                'nombre' => $this->requiredString($row, 'nombre', 'grupos_opciones', $line, $errors),
                'tipo' => $tipo,
                'obligatorio' => $this->boolValue($row['obligatorio'] ?? null, false, 'grupos_opciones', $line, 'obligatorio', $errors),
                'multiple' => $multiple,
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'grupos_opciones', $line, 'activo', $errors),
                'orden_visual' => $this->intValue($row['orden_visual'] ?? null, 0, 'grupos_opciones', $line, 'orden_visual', $errors),
                'scope_modalidad' => $scope,
                'area_aplicacion' => $area,
                'es_grupo_salsa' => $isSalsa,
                'prioridad_visual' => $this->intValue($row['prioridad_visual'] ?? null, 0, 'grupos_opciones', $line, 'prioridad_visual', $errors, allowNegative: true),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $groupSlugs
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeOptions(array $rows, array $groupSlugs, array &$errors): array
    {
        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $groupSlug = $this->requiredKey($row, 'grupo_slug', 'opciones', $line, $errors);
            $slug = $this->requiredKey($row, 'slug', 'opciones', $line, $errors);

            if ($groupSlug !== null && !in_array($groupSlug, $groupSlugs, true)) {
                $errors[] = "opciones fila {$line}: grupo_slug '{$groupSlug}' no existe.";
            }

            if ($groupSlug !== null && $slug !== null) {
                $dupKey = $groupSlug . '|' . $slug;
                if (isset($seen[$dupKey])) {
                    $errors[] = "opciones fila {$line}: slug duplicado '{$slug}' para grupo '{$groupSlug}'.";
                }
                $seen[$dupKey] = true;
            }

            $normalized[] = [
                'grupo_slug' => $groupSlug,
                'slug' => $slug,
                'nombre' => $this->requiredString($row, 'nombre', 'opciones', $line, $errors),
                'precio' => $this->decimalValue($row['precio'] ?? null, 0, 'opciones', $line, 'precio', $errors),
                'costo' => $this->decimalValue($row['costo'] ?? null, 0, 'opciones', $line, 'costo', $errors),
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'opciones', $line, 'activo', $errors),
                'orden' => $this->intValue($row['orden'] ?? null, 0, 'opciones', $line, 'orden', $errors),
                'codigo_corto' => $this->nullableString($row['codigo_corto'] ?? null),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $productSkus
     * @param  array<int, string>  $groupSlugs
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeProductGroup(array $rows, array $productSkus, array $groupSlugs, array &$errors): array
    {
        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $productoSku = $this->requiredKey($row, 'producto_sku', 'producto_grupo_opcion', $line, $errors);
            $grupoSlug = $this->requiredKey($row, 'grupo_slug', 'producto_grupo_opcion', $line, $errors);

            if ($productoSku !== null && !in_array($productoSku, $productSkus, true)) {
                $errors[] = "producto_grupo_opcion fila {$line}: producto_sku '{$productoSku}' no existe.";
            }

            if ($grupoSlug !== null && !in_array($grupoSlug, $groupSlugs, true)) {
                $errors[] = "producto_grupo_opcion fila {$line}: grupo_slug '{$grupoSlug}' no existe.";
            }

            if ($productoSku !== null && $grupoSlug !== null) {
                $dupKey = $productoSku . '|' . $grupoSlug;
                if (isset($seen[$dupKey])) {
                    $errors[] = "producto_grupo_opcion fila {$line}: relacion duplicada {$dupKey}.";
                }
                $seen[$dupKey] = true;
            }

            $scope = $row['scope_modalidad'] === null ? null : Str::lower((string) $row['scope_modalidad']);
            if ($scope !== null && !in_array($scope, ['todas', 'solo', 'desayuno', 'comida'], true)) {
                $errors[] = "producto_grupo_opcion fila {$line}: scope_modalidad invalido '{$scope}'.";
            }

            $normalized[] = [
                'producto_sku' => $productoSku,
                'grupo_slug' => $grupoSlug,
                'obligatorio' => $this->boolValue($row['obligatorio'] ?? null, null, 'producto_grupo_opcion', $line, 'obligatorio', $errors, nullable: true),
                'multiple' => $this->boolValue($row['multiple'] ?? null, null, 'producto_grupo_opcion', $line, 'multiple', $errors, nullable: true),
                'scope_modalidad' => $scope,
                'orden_visual' => $this->intValue($row['orden_visual'] ?? null, null, 'producto_grupo_opcion', $line, 'orden_visual', $errors, nullable: true),
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'producto_grupo_opcion', $line, 'activo', $errors),
            ];
        }

        return $normalized;
    }



    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $productSkus
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeComponents(array $rows, array $productSkus, array &$errors): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $productoSku = $this->requiredKey($row, 'producto_sku', 'componentes_preparacion', $line, $errors);

            if ($productoSku !== null && !in_array($productoSku, $productSkus, true)) {
                $errors[] = "componentes_preparacion fila {$line}: producto_sku '{$productoSku}' no existe.";
            }

            $modalidad = Str::lower((string) ($row['modalidad'] ?? 'todas'));
            if (!in_array($modalidad, ['todas', 'solo', 'desayuno', 'comida'], true)) {
                $errors[] = "componentes_preparacion fila {$line}: modalidad invalida '{$modalidad}'.";
            }

            $area = Str::lower((string) ($row['area'] ?? ''));
            if (!in_array($area, ['cocina', 'barra'], true)) {
                $errors[] = "componentes_preparacion fila {$line}: area debe ser cocina o barra.";
            }

            $normalized[] = [
                'producto_sku' => $productoSku,
                'modalidad' => $modalidad,
                'area' => $area,
                'nombre_componente' => $this->requiredString($row, 'nombre_componente', 'componentes_preparacion', $line, $errors),
                'cantidad' => $this->decimalValue($row['cantidad'] ?? null, 1, 'componentes_preparacion', $line, 'cantidad', $errors),
                'orden' => $this->intValue($row['orden'] ?? null, 0, 'componentes_preparacion', $line, 'orden', $errors),
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'componentes_preparacion', $line, 'activo', $errors),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $errors
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMenuDia(array $rows, array &$errors): array
    {
        $normalized = [];
        $seen = [];

        foreach ($rows as $row) {
            $line = (int) $row['_row'];
            $slug = $this->requiredKey($row, 'slug', 'menu_dia_opciones', $line, $errors);
            $tipo = $this->requiredString($row, 'tipo', 'menu_dia_opciones', $line, $errors);
            $fecha = $this->nullableString($row['fecha'] ?? null);

            if ($fecha !== null && strtotime($fecha) === false) {
                $errors[] = "menu_dia_opciones fila {$line}: fecha invalida '{$fecha}'.";
            }

            if ($slug !== null && $tipo !== null) {
                $dupKey = Str::lower($tipo) . '|' . $slug . '|' . ($fecha ?? '');
                if (isset($seen[$dupKey])) {
                    $errors[] = "menu_dia_opciones fila {$line}: registro duplicado '{$dupKey}'.";
                }
                $seen[$dupKey] = true;
            }

            $normalized[] = [
                'slug' => $slug,
                'tipo' => $tipo,
                'nombre' => $this->requiredString($row, 'nombre', 'menu_dia_opciones', $line, $errors),
                'fecha' => $fecha,
                'activo' => $this->boolValue($row['activo'] ?? null, true, 'menu_dia_opciones', $line, 'activo', $errors),
                'orden' => $this->intValue($row['orden'] ?? null, 0, 'menu_dia_opciones', $line, 'orden', $errors),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @param  array<int, array<string, mixed>>  $groups
     * @param  array<int, array<string, mixed>>  $relations
     * @param  array<int, string>  $errors
     */
    private function validateSalsaCoverage(array $products, array $groups, array $relations, array &$errors): void
    {
        $salsaGroupSlugs = collect($groups)
            ->filter(fn (array $group) => (bool) $group['es_grupo_salsa'])
            ->pluck('slug')
            ->all();

        $relationsByProduct = collect($relations)->groupBy('producto_sku');

        foreach ($products as $product) {
            if (!(bool) $product['usa_salsa']) {
                continue;
            }

            $assigned = $relationsByProduct->get($product['sku'], collect())
                ->pluck('grupo_slug')
                ->all();

            if (!collect($assigned)->contains(fn (string $slug) => in_array($slug, $salsaGroupSlugs, true))) {
                $errors[] = "productos sku {$product['sku']}: usa_salsa=1 requiere al menos un grupo de salsa en producto_grupo_opcion.";
            }
        }
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $catalog
     * @return array<string, int>
     */
    private function persist(array $catalog): array
    {
        $now = now();

        $categoryRows = collect($catalog['categorias'])->map(function (array $row) use ($now) {
            return [
                'slug' => $row['slug'],
                'nombre' => $row['nombre'],
                'tipo' => $row['tipo'],
                'activo' => $row['activo'],
                'orden' => $row['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();
        Categoria::query()->upsert($categoryRows, ['slug'], ['nombre', 'tipo', 'activo', 'orden', 'updated_at']);

        $categorySlugs = array_column($catalog['categorias'], 'slug');
        if ($categorySlugs !== []) {
            Categoria::query()->whereNotIn('slug', $categorySlugs)->update(['activo' => false, 'updated_at' => $now]);
        }
        $categoryIdBySlug = Categoria::query()->pluck('id', 'slug')->toArray();

        $productRows = collect($catalog['productos'])->map(function (array $row) use ($categoryIdBySlug, $now) {
            return [
                'sku' => $row['sku'],
                'nombre' => $row['nombre'],
                'categoria_id' => $categoryIdBySlug[$row['categoria_slug']] ?? null,
                'precio' => $row['precio'],
                'costo' => $row['costo'],
                'activo' => $row['activo'],
                'permite_solo' => $row['permite_solo'],
                'permite_desayuno' => $row['permite_desayuno'],
                'permite_comida' => $row['permite_comida'],
                'incremento_desayuno' => $row['incremento_desayuno'],
                'incremento_comida' => $row['incremento_comida'],
                'usa_menu_dia' => $row['usa_menu_dia'],
                'es_comida_dia' => $row['usa_menu_dia'],
                'usa_extras' => $row['usa_extras'],
                'usa_notas' => $row['usa_notas'],
                'usa_salsa' => $row['usa_salsa'],
                'orden' => $row['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        Producto::query()->upsert($productRows, ['sku'], [
            'nombre', 'categoria_id', 'precio', 'costo', 'activo',
            'permite_solo', 'permite_desayuno', 'permite_comida',
            'incremento_desayuno', 'incremento_comida',
            'usa_menu_dia', 'es_comida_dia', 'usa_extras', 'usa_notas', 'usa_salsa',
            'orden', 'updated_at',
        ]);

        $productSkus = array_column($catalog['productos'], 'sku');
        if ($productSkus !== []) {
            Producto::query()->whereNotIn('sku', $productSkus)->update(['activo' => false, 'updated_at' => $now]);
        }
        $productIdBySku = Producto::query()->pluck('id', 'sku')->toArray();

        $extraRows = collect($catalog['extras'])->map(function (array $row) use ($now) {
            return [
                'slug' => $row['slug'],
                'nombre' => $row['nombre'],
                'precio' => $row['precio'],
                'activo' => $row['activo'],
                'permite_cantidad' => $row['permite_cantidad'],
                'orden' => $row['orden'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();
        Extra::query()->upsert($extraRows, ['slug'], ['nombre', 'precio', 'activo', 'permite_cantidad', 'orden', 'updated_at']);

        $extraSlugs = array_column($catalog['extras'], 'slug');
        if ($extraSlugs !== []) {
            Extra::query()->whereNotIn('slug', $extraSlugs)->update(['activo' => false, 'updated_at' => $now]);
        }

        $groupsBySlug = collect($catalog['grupos'])->keyBy('slug');
        $optionsByGroupSlug = collect($catalog['opciones'])->groupBy('grupo_slug');
        $assignedGroupSlugsByProduct = [];
        $groupCounter = 0;
        $optionCounter = 0;

        foreach ($catalog['producto_grupo'] as $relation) {
            $productId = $productIdBySku[$relation['producto_sku']] ?? null;
            $groupTemplate = $groupsBySlug->get($relation['grupo_slug']);
            if (!$productId || !is_array($groupTemplate)) {
                continue;
            }

            $group = GrupoOpcion::query()->updateOrCreate(
                ['producto_id' => $productId, 'slug' => $groupTemplate['slug']],
                [
                    'nombre' => $groupTemplate['nombre'],
                    'tipo' => $groupTemplate['tipo'],
                    'obligatorio' => $relation['obligatorio'] ?? $groupTemplate['obligatorio'],
                    'multiple' => $relation['multiple'] ?? $groupTemplate['multiple'],
                    'orden' => $relation['orden_visual'] ?? $groupTemplate['orden_visual'],
                    'orden_visual' => $relation['orden_visual'] ?? $groupTemplate['orden_visual'],
                    'modalidad' => $relation['scope_modalidad'] ?? $groupTemplate['scope_modalidad'],
                    'scope_modalidad' => $relation['scope_modalidad'] ?? $groupTemplate['scope_modalidad'],
                    'area_aplicacion' => $groupTemplate['area_aplicacion'],
                    'es_grupo_salsa' => $groupTemplate['es_grupo_salsa'],
                    'prioridad_visual' => $groupTemplate['prioridad_visual'],
                    'solo_si_opcion_id' => null,
                    'activo' => $relation['activo'] && $groupTemplate['activo'],
                ]
            );

            $groupCounter++;
            $assignedGroupSlugsByProduct[$productId] ??= [];
            $assignedGroupSlugsByProduct[$productId][] = $groupTemplate['slug'];

            $groupOptions = $optionsByGroupSlug->get($groupTemplate['slug'], collect());
            $activeOptionSlugs = [];

            foreach ($groupOptions as $optionTemplate) {
                Opcion::query()->updateOrCreate(
                    ['grupo_opcion_id' => $group->id, 'slug' => $optionTemplate['slug']],
                    [
                        'nombre' => $optionTemplate['nombre'],
                        'incremento_precio' => $optionTemplate['precio'],
                        'incremento_costo' => $optionTemplate['costo'],
                        'codigo_corto' => $optionTemplate['codigo_corto'],
                        'orden' => $optionTemplate['orden'],
                        'activo' => $optionTemplate['activo'],
                    ]
                );
                $optionCounter++;
                $activeOptionSlugs[] = $optionTemplate['slug'];
            }

            $staleOptions = Opcion::query()->where('grupo_opcion_id', $group->id);
            if ($activeOptionSlugs !== []) {
                $staleOptions->whereNotIn('slug', $activeOptionSlugs);
            }
            $staleOptions->update(['activo' => false, 'updated_at' => $now]);
        }

        foreach ($productIdBySku as $productId) {
            $activeGroupSlugs = $assignedGroupSlugsByProduct[$productId] ?? [];
            $staleGroups = GrupoOpcion::query()->where('producto_id', $productId);
            if ($activeGroupSlugs !== []) {
                $staleGroups->whereNotIn('slug', $activeGroupSlugs);
            }
            $staleGroups->update(['activo' => false, 'updated_at' => $now]);
        }
        // Sin hoja producto_extra: los extras son globales y usa_extras del producto controla si se muestran.
        // Limpiamos relaciones heredadas para evitar restricciones antiguas por producto.
        DB::table('producto_extra')->delete();

        $componentRowsByProduct = collect($catalog['componentes'])->groupBy('producto_sku');
        foreach ($productIdBySku as $sku => $productId) {
            DB::table('producto_componentes_preparacion')->where('producto_id', $productId)->delete();

            $rows = $componentRowsByProduct->get($sku, collect());
            if ($rows->isEmpty()) {
                continue;
            }

            $payload = $rows->map(function (array $row) use ($productId, $now) {
                return [
                    'producto_id' => $productId,
                    'modalidad' => $row['modalidad'],
                    'area' => $row['area'],
                    'nombre_componente' => $row['nombre_componente'],
                    'cantidad' => $row['cantidad'],
                    'orden' => $row['orden'],
                    'activo' => $row['activo'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            DB::table('producto_componentes_preparacion')->insert($payload);
        }

        $menuIds = [];
        foreach ($catalog['menu_dia'] as $row) {
            $menuItem = MenuDiaOpcion::query()->updateOrCreate(
                ['tipo' => $row['tipo'], 'slug' => $row['slug'], 'fecha' => $row['fecha']],
                ['nombre' => $row['nombre'], 'activo' => $row['activo'], 'orden' => $row['orden']]
            );

            $menuIds[] = $menuItem->id;
        }

        if ($menuIds !== []) {
            MenuDiaOpcion::query()->whereNotIn('id', $menuIds)->update(['activo' => false, 'updated_at' => $now]);
        }

        return [
            'categorias' => count($categoryRows),
            'productos' => count($productRows),
            'extras' => count($extraRows),
            'grupos_producto' => $groupCounter,
            'opciones_grupo' => $optionCounter,
            'componentes_preparacion' => count($catalog['componentes']),
            'menu_dia_opciones' => count($catalog['menu_dia']),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function requiredKey(array $row, string $column, string $sheet, int $line, array &$errors): ?string
    {
        $value = $this->nullableString($row[$column] ?? null);

        if ($value === null) {
            $errors[] = "{$sheet} fila {$line}: '{$column}' es obligatorio.";
            return null;
        }

        return Str::slug($value);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function requiredString(array $row, string $column, string $sheet, int $line, array &$errors): ?string
    {
        $value = $this->nullableString($row[$column] ?? null);

        if ($value === null) {
            $errors[] = "{$sheet} fila {$line}: '{$column}' es obligatorio.";
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/\s+/', '_', $value) ?? $value;

        return preg_replace('/[^a-z0-9_]/', '', $value) ?? $value;
    }

    private function boolValue(
        mixed $value,
        ?bool $default,
        string $sheet,
        int $line,
        string $column,
        array &$errors,
        bool $nullable = false
    ): ?bool {
        if ($value === null || $value === '') {
            return $nullable ? $default : ($default ?? false);
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'si', 's', 'yes', 'y'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
            return false;
        }

        $errors[] = "{$sheet} fila {$line}: '{$column}' no es booleano valido.";

        return $default;
    }

    private function decimalValue(
        mixed $value,
        float $default,
        string $sheet,
        int $line,
        string $column,
        array &$errors
    ): float {
        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_numeric($value)) {
            $errors[] = "{$sheet} fila {$line}: '{$column}' debe ser numerico.";
            return $default;
        }

        $numeric = (float) $value;
        if ($numeric < 0) {
            $errors[] = "{$sheet} fila {$line}: '{$column}' no puede ser negativo.";
        }

        return max(0, $numeric);
    }

    private function intValue(
        mixed $value,
        ?int $default,
        string $sheet,
        int $line,
        string $column,
        array &$errors,
        bool $allowNegative = false,
        bool $nullable = false
    ): ?int {
        if ($value === null || $value === '') {
            return $nullable ? $default : ($default ?? 0);
        }

        if (!is_numeric($value)) {
            $errors[] = "{$sheet} fila {$line}: '{$column}' debe ser entero.";
            return $default;
        }

        $numeric = (int) $value;
        if (!$allowNegative && $numeric < 0) {
            $errors[] = "{$sheet} fila {$line}: '{$column}' no puede ser negativo.";
            $numeric = 0;
        }

        return $numeric;
    }
}

