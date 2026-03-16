<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatosInicialesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMesas();
        $categorias = $this->seedCategorias();
        $productos = $this->seedProductos($categorias);
        $this->seedExtras();
        $this->seedMenuDia();
        $this->seedCapuccinoConfig($productos['capuccino']);
        $this->seedChilaquilesConfig($productos['chilaquiles']);
        $this->seedHuevosRancherosConfig($productos['huevos_rancheros']);
        $this->seedComidaConfig($productos['comida']);
        $this->deactivateLegacyPackageProduct('Paquete Chilaquiles');
    }

    private function seedMesas(): void
    {
        foreach (range(1, 10) as $numero) {
            DB::table('mesas')->updateOrInsert(['numero' => $numero], ['numero' => $numero]);
        }

        DB::table('mesas')->updateOrInsert(
            ['id' => 9998],
            ['numero' => 9998, 'tipo' => 'empleados', 'updated_at' => now()]
        );

        DB::table('mesas')->updateOrInsert(
            ['id' => 9999],
            ['numero' => 9999, 'tipo' => 'llevar', 'updated_at' => now()]
        );
    }

    private function seedCategorias(): array
    {
        return [
            'bebidas' => $this->firstOrCreateCategoria('Bebidas', 'barra'),
            'comida' => $this->firstOrCreateCategoria('Comida', 'cocina'),
        ];
    }

    private function seedProductos(array $categorias): array
    {
        return [
            'cafe_americano' => $this->firstOrCreateProducto('Cafe Americano', 35, 16, $categorias['bebidas']),
            'capuccino' => $this->firstOrCreateProducto('Capuccino', 45, 18, $categorias['bebidas']),
            'sandwich' => $this->firstOrCreateProducto('Sandwich', 60, 32, $categorias['comida']),
            'comida' => $this->firstOrCreateProducto('Comida', 95, 58, $categorias['comida'], [
                'permite_solo' => false,
                'permite_desayuno' => false,
                'permite_comida' => true,
                'incremento_comida' => 0,
                'es_comida_dia' => true,
            ]),
            'enchiladas_verdes' => $this->firstOrCreateProducto('Enchiladas Verdes', 88, 49, $categorias['comida']),
            'chilaquiles' => $this->firstOrCreateProducto('Chilaquiles', 92, 52, $categorias['comida'], [
                'permite_solo' => true,
                'permite_desayuno' => true,
                'permite_comida' => true,
                'incremento_desayuno' => 53,
                'incremento_comida' => 35,
                'es_comida_dia' => false,
            ]),
            'huevos_rancheros' => $this->firstOrCreateProducto('Huevos rancheros', 84, 47, $categorias['comida'], [
                'permite_solo' => true,
                'permite_desayuno' => true,
                'permite_comida' => false,
                'incremento_desayuno' => 30,
                'incremento_comida' => 0,
                'es_comida_dia' => false,
            ]),
            'paquete_chilaquiles' => $this->firstOrCreateProducto('Paquete Chilaquiles', 145, 86, $categorias['comida'], [
                'activo' => false,
                'permite_solo' => false,
                'permite_desayuno' => false,
                'permite_comida' => false,
            ]),
            'milanesa_pollo' => $this->firstOrCreateProducto('Milanesa de Pollo', 98, 59, $categorias['comida']),
            'albondigas' => $this->firstOrCreateProducto('Albondigas', 96, 57, $categorias['comida']),
            'pechuga_empanizada' => $this->firstOrCreateProducto('Pechuga Empanizada', 99, 61, $categorias['comida']),
            'picadillo' => $this->firstOrCreateProducto('Picadillo', 89, 54, $categorias['comida']),
        ];
    }

    private function seedExtras(): void
    {
        $this->firstOrCreateExtra('Huevo extra', 12);
        $this->firstOrCreateExtra('Queso extra', 10);
        $this->firstOrCreateExtra('Leche de almendra', 8);
        $this->firstOrCreateExtra('Shot extra', 15);
        $this->firstOrCreateExtra('Otro', 0);
    }

    private function seedMenuDia(): void
    {
        $hoy = now()->toDateString();
        $manana = now()->addDay()->toDateString();

        foreach (['Milanesa de Pollo', 'Albondigas'] as $nombre) {
            $this->firstOrCreateMenuDiaOpcion('comida_tercer_tiempo', $nombre, $hoy, true);
        }

        foreach (['Pechuga Empanizada', 'Picadillo'] as $nombre) {
            $this->firstOrCreateMenuDiaOpcion('comida_tercer_tiempo', $nombre, $manana, true);
        }
    }

    private function seedCapuccinoConfig(int $productoId): void
    {
        $grupoLeche = $this->firstOrCreateGrupo($productoId, 'Leche', true, false, 10, 'todas');
        $grupoSabor = $this->firstOrCreateGrupo($productoId, 'Sabor', false, false, 20, 'todas');

        $this->firstOrCreateOpcion($grupoLeche, 'Leche: Entera', 0, 0, 'ENT');
        $this->firstOrCreateOpcion($grupoLeche, 'Leche: Deslactosada', 0, 0, 'DES');
        $this->firstOrCreateOpcion($grupoLeche, 'Leche: Almendra', 8, 8, 'ALM');

        $this->firstOrCreateOpcion($grupoSabor, 'Sabor: Vainilla', 6, 6, null);
        $this->firstOrCreateOpcion($grupoSabor, 'Sabor: Avellana', 6, 6, null);
        $this->firstOrCreateOpcion($grupoSabor, 'Sabor: Caramelo', 6, 6, null);
    }

    private function seedChilaquilesConfig(int $productoId): void
    {
        $grupoSalsa = $this->firstOrCreateGrupo($productoId, 'Salsa', true, false, 5, 'todas');
        $grupoBebida = $this->firstOrCreateGrupo($productoId, 'Bebida del paquete', false, false, 10, 'desayuno');
        $grupoFruta = $this->firstOrCreateGrupo($productoId, 'Fruta del paquete', false, false, 20, 'desayuno');
        $grupoGranola = $this->firstOrCreateGrupo($productoId, 'Granola', false, false, 30, 'desayuno');
        $grupoPrimer = $this->firstOrCreateGrupo($productoId, 'Primer tiempo', false, false, 40, 'comida');
        $grupoSegundo = $this->firstOrCreateGrupo($productoId, 'Segundo tiempo', false, false, 50, 'comida');

        $this->firstOrCreateOpcion($grupoSalsa, 'Salsa: Roja', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSalsa, 'Salsa: Verdes', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSalsa, 'Salsa: Dos salsas', 0, 0, null);

        $this->firstOrCreateOpcion($grupoBebida, 'Bebida del paquete: Cafe americano', 0, 0, null);
        $this->firstOrCreateOpcion($grupoBebida, 'Bebida del paquete: Cafe de olla', 0, 0, null);
        $this->firstOrCreateOpcion($grupoBebida, 'Bebida del paquete: Te', 0, 0, null);

        $this->firstOrCreateOpcion($grupoFruta, 'Fruta del paquete: Papaya', 0, 0, null);
        $this->firstOrCreateOpcion($grupoFruta, 'Fruta del paquete: Melon', 0, 0, null);
        $this->firstOrCreateOpcion($grupoFruta, 'Fruta del paquete: Fruta mixta', 0, 0, null);

        $this->firstOrCreateOpcion($grupoGranola, 'Granola: Con granola', 0, 0, null);

        $this->firstOrCreateOpcion($grupoPrimer, 'Primer tiempo: Sopa', 0, 0, null);
        $this->firstOrCreateOpcion($grupoPrimer, 'Primer tiempo: Arroz', 0, 0, null);
        $this->firstOrCreateOpcion($grupoPrimer, 'Primer tiempo: Pasta', 0, 0, null);

        $this->firstOrCreateOpcion($grupoSegundo, 'Segundo tiempo: Sopa', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSegundo, 'Segundo tiempo: Arroz', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSegundo, 'Segundo tiempo: Pasta', 0, 0, null);
    }

    private function seedHuevosRancherosConfig(int $productoId): void
    {
        $grupoSalsa = $this->firstOrCreateGrupo($productoId, 'Salsa', true, false, 5, 'todas');

        $this->firstOrCreateOpcion($grupoSalsa, 'Salsa: Roja', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSalsa, 'Salsa: Verdes', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSalsa, 'Salsa: Dos salsas', 0, 0, null);
    }

    private function seedComidaConfig(int $productoId): void
    {
        $grupoPrimer = $this->firstOrCreateGrupo($productoId, 'Primer tiempo', false, false, 10, 'comida');
        $grupoSegundo = $this->firstOrCreateGrupo($productoId, 'Segundo tiempo', false, false, 20, 'comida');

        $this->firstOrCreateOpcion($grupoPrimer, 'Primer tiempo: Sopa', 0, 0, null);
        $this->firstOrCreateOpcion($grupoPrimer, 'Primer tiempo: Arroz', 0, 0, null);
        $this->firstOrCreateOpcion($grupoPrimer, 'Primer tiempo: Pasta', 0, 0, null);

        $this->firstOrCreateOpcion($grupoSegundo, 'Segundo tiempo: Sopa', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSegundo, 'Segundo tiempo: Arroz', 0, 0, null);
        $this->firstOrCreateOpcion($grupoSegundo, 'Segundo tiempo: Pasta', 0, 0, null);
    }

    private function deactivateLegacyPackageProduct(string $nombre): void
    {
        DB::table('productos')->where('nombre', $nombre)->update(['activo' => false, 'updated_at' => now()]);
    }

    private function firstOrCreateCategoria(string $nombre, string $tipo): int
    {
        $id = DB::table('categorias')->where('nombre', $nombre)->value('id');
        if ($id) {
            DB::table('categorias')->where('id', $id)->update(['tipo' => $tipo, 'activo' => true, 'updated_at' => now()]);
            return (int) $id;
        }

        return (int) DB::table('categorias')->insertGetId([
            'nombre' => $nombre,
            'tipo' => $tipo,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstOrCreateProducto(string $nombre, float $precio, float $costo, int $categoriaId, array $overrides = []): int
    {
        $payload = array_merge([
            'precio' => $precio,
            'costo' => $costo,
            'categoria_id' => $categoriaId,
            'activo' => true,
            'permite_solo' => true,
            'permite_desayuno' => false,
            'permite_comida' => false,
            'incremento_desayuno' => 0,
            'incremento_comida' => 0,
            'es_comida_dia' => false,
            'updated_at' => now(),
        ], $overrides);

        $id = DB::table('productos')->where('nombre', $nombre)->value('id');
        if ($id) {
            DB::table('productos')->where('id', $id)->update($payload);
            return (int) $id;
        }

        $payload['nombre'] = $nombre;
        $payload['created_at'] = now();

        return (int) DB::table('productos')->insertGetId($payload);
    }

    private function firstOrCreateExtra(string $nombre, float $precio): int
    {
        $id = DB::table('extras')->where('nombre', $nombre)->value('id');
        if ($id) {
            DB::table('extras')->where('id', $id)->update(['precio' => $precio, 'activo' => true, 'updated_at' => now()]);
            return (int) $id;
        }

        return (int) DB::table('extras')->insertGetId([
            'nombre' => $nombre,
            'precio' => $precio,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstOrCreateMenuDiaOpcion(string $tipo, string $nombre, string $fecha, bool $activo): int
    {
        $id = DB::table('menu_dia_opciones')
            ->where('tipo', $tipo)
            ->where('nombre', $nombre)
            ->whereDate('fecha', $fecha)
            ->value('id');

        if ($id) {
            DB::table('menu_dia_opciones')->where('id', $id)->update(['activo' => $activo, 'updated_at' => now()]);
            return (int) $id;
        }

        return (int) DB::table('menu_dia_opciones')->insertGetId([
            'tipo' => $tipo,
            'nombre' => $nombre,
            'activo' => $activo,
            'fecha' => $fecha,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstOrCreateGrupo(int $productoId, string $nombre, bool $obligatorio, bool $multiple, int $orden, string $modalidad): int
    {
        $id = DB::table('grupos_opciones')
            ->where('producto_id', $productoId)
            ->where('nombre', $nombre)
            ->value('id');

        if ($id) {
            DB::table('grupos_opciones')->where('id', $id)->update([
                'modalidad' => $modalidad,
                'obligatorio' => $obligatorio,
                'multiple' => $multiple,
                'orden' => $orden,
                'activo' => true,
                'updated_at' => now(),
            ]);
            return (int) $id;
        }

        return (int) DB::table('grupos_opciones')->insertGetId([
            'producto_id' => $productoId,
            'nombre' => $nombre,
            'modalidad' => $modalidad,
            'obligatorio' => $obligatorio,
            'multiple' => $multiple,
            'orden' => $orden,
            'solo_si_opcion_id' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstOrCreateOpcion(int $grupoId, string $nombre, float $incrementoPrecio, float $incrementoCosto, ?string $codigoCorto): int
    {
        $id = DB::table('opciones')
            ->where('grupo_opcion_id', $grupoId)
            ->where('nombre', $nombre)
            ->value('id');

        if ($id) {
            DB::table('opciones')->where('id', $id)->update([
                'incremento_precio' => $incrementoPrecio,
                'incremento_costo' => $incrementoCosto,
                'codigo_corto' => $codigoCorto,
                'activo' => true,
                'updated_at' => now(),
            ]);
            return (int) $id;
        }

        return (int) DB::table('opciones')->insertGetId([
            'grupo_opcion_id' => $grupoId,
            'nombre' => $nombre,
            'incremento_precio' => $incrementoPrecio,
            'incremento_costo' => $incrementoCosto,
            'codigo_corto' => $codigoCorto,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
