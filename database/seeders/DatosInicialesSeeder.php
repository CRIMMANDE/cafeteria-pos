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
        $extras = $this->seedExtras();

        $this->seedMenuDia();
        $this->seedCapuccinoConfig($productos['capuccino']);
        $this->seedPaqueteConfig($productos['paquete_chilaquiles']);
        $this->touchComidaPricing($productos['comida']);
    }

    private function seedMesas(): void
    {
        foreach (range(1, 10) as $numero) {
            DB::table('mesas')->updateOrInsert(
                ['numero' => $numero],
                ['numero' => $numero]
            );
        }
    }

    private function seedCategorias(): array
    {
        $bebidasId = $this->firstOrCreateCategoria('Bebidas', 'barra');
        $comidaId = $this->firstOrCreateCategoria('Comida', 'cocina');

        return [
            'bebidas' => $bebidasId,
            'comida' => $comidaId,
        ];
    }

    private function seedProductos(array $categorias): array
    {
        return [
            'cafe_americano' => $this->firstOrCreateProducto('Cafe Americano', 35, 16, $categorias['bebidas']),
            'capuccino' => $this->firstOrCreateProducto('Capuccino', 45, 18, $categorias['bebidas']),
            'sandwich' => $this->firstOrCreateProducto('Sandwich', 60, 32, $categorias['comida']),
            'comida' => $this->firstOrCreateProducto('Comida', 95, 58, $categorias['comida']),
            'enchiladas_verdes' => $this->firstOrCreateProducto('Enchiladas Verdes', 88, 49, $categorias['comida']),
            'chilaquiles_verdes_pollo' => $this->firstOrCreateProducto('Chilaquiles Verdes con Pollo', 92, 52, $categorias['comida']),
            'paquete_chilaquiles' => $this->firstOrCreateProducto('Paquete Chilaquiles Verdes con Pollo', 145, 86, $categorias['comida']),
            'milanesa_pollo' => $this->firstOrCreateProducto('Milanesa de Pollo', 98, 59, $categorias['comida']),
            'albondigas' => $this->firstOrCreateProducto('Albondigas', 96, 57, $categorias['comida']),
            'pechuga_empanizada' => $this->firstOrCreateProducto('Pechuga Empanizada', 99, 61, $categorias['comida']),
            'picadillo' => $this->firstOrCreateProducto('Picadillo', 89, 54, $categorias['comida']),
        ];
    }

    private function seedExtras(): array
    {
        return [
            'huevo_extra' => $this->firstOrCreateExtra('Huevo extra', 12),
            'queso_extra' => $this->firstOrCreateExtra('Queso extra', 10),
            'leche_almendra' => $this->firstOrCreateExtra('Leche de almendra', 8),
            'shot_extra' => $this->firstOrCreateExtra('Shot extra', 15),
        ];
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

        $this->firstOrCreateMenuDiaOpcion('comida_tercer_tiempo', 'Opcion inactiva de prueba', $hoy, false);
    }

    private function seedCapuccinoConfig(int $productoId): void
    {
        $grupoLeche = $this->firstOrCreateGrupo($productoId, 'Leche', true, false, 10);
        $grupoSabor = $this->firstOrCreateGrupo($productoId, 'Sabor', false, false, 20);

        $this->firstOrCreateOpcion($grupoLeche, 'Leche: Entera', 0, 0, 'ENT');
        $this->firstOrCreateOpcion($grupoLeche, 'Leche: Deslactosada', 0, 0, 'DES');
        $this->firstOrCreateOpcion($grupoLeche, 'Leche: Almendra', 8, 8, 'ALM');

        $this->firstOrCreateOpcion($grupoSabor, 'Sabor: Vainilla', 6, 6, null);
        $this->firstOrCreateOpcion($grupoSabor, 'Sabor: Avellana', 6, 6, null);
        $this->firstOrCreateOpcion($grupoSabor, 'Sabor: Caramelo', 6, 6, null);
    }

    private function seedPaqueteConfig(int $productoId): void
    {
        $grupoBebida = $this->firstOrCreateGrupo($productoId, 'Bebida del paquete', true, false, 10);
        $grupoFruta = $this->firstOrCreateGrupo($productoId, 'Fruta del paquete', true, false, 20);

        $this->firstOrCreateOpcion($grupoBebida, 'Bebida: Cafe', 0, 0, null);
        $this->firstOrCreateOpcion($grupoBebida, 'Bebida: Cafe Americano', 0, 0, null);

        $this->firstOrCreateOpcion($grupoFruta, 'Fruta: Papaya', 0, 0, null);
        $this->firstOrCreateOpcion($grupoFruta, 'Fruta: Melon', 0, 0, null);
        $this->firstOrCreateOpcion($grupoFruta, 'Fruta: Fruta Mixta con Granola', 0, 0, null);
    }

    private function touchComidaPricing(int $productoId): void
    {
        DB::table('productos')
            ->where('id', $productoId)
            ->update([
                'precio' => 95,
                'costo' => 58,
                'updated_at' => now(),
            ]);
    }

    private function firstOrCreateCategoria(string $nombre, string $tipo): int
    {
        $id = DB::table('categorias')->where('nombre', $nombre)->value('id');
        if ($id) {
            DB::table('categorias')->where('id', $id)->update(['tipo' => $tipo]);
            return (int) $id;
        }

        return (int) DB::table('categorias')->insertGetId([
            'nombre' => $nombre,
            'tipo' => $tipo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstOrCreateProducto(string $nombre, float $precio, float $costo, int $categoriaId): int
    {
        $id = DB::table('productos')->where('nombre', $nombre)->value('id');
        if ($id) {
            DB::table('productos')->where('id', $id)->update([
                'precio' => $precio,
                'costo' => $costo,
                'categoria_id' => $categoriaId,
                'updated_at' => now(),
            ]);
            return (int) $id;
        }

        return (int) DB::table('productos')->insertGetId([
            'nombre' => $nombre,
            'precio' => $precio,
            'costo' => $costo,
            'categoria_id' => $categoriaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function firstOrCreateExtra(string $nombre, float $precio): int
    {
        $id = DB::table('extras')->where('nombre', $nombre)->value('id');
        if ($id) {
            DB::table('extras')->where('id', $id)->update([
                'precio' => $precio,
                'activo' => true,
                'updated_at' => now(),
            ]);
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
            DB::table('menu_dia_opciones')->where('id', $id)->update([
                'activo' => $activo,
                'updated_at' => now(),
            ]);
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

    private function firstOrCreateGrupo(int $productoId, string $nombre, bool $obligatorio, bool $multiple, int $orden): int
    {
        $id = DB::table('grupos_opciones')
            ->where('producto_id', $productoId)
            ->where('nombre', $nombre)
            ->value('id');

        if ($id) {
            DB::table('grupos_opciones')->where('id', $id)->update([
                'obligatorio' => $obligatorio,
                'multiple' => $multiple,
                'orden' => $orden,
                'updated_at' => now(),
            ]);
            return (int) $id;
        }

        return (int) DB::table('grupos_opciones')->insertGetId([
            'producto_id' => $productoId,
            'nombre' => $nombre,
            'obligatorio' => $obligatorio,
            'multiple' => $multiple,
            'orden' => $orden,
            'solo_si_opcion_id' => null,
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
