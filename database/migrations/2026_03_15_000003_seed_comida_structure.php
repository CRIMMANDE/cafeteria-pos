<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoriaId = DB::table('categorias')
            ->where('tipo', 'cocina')
            ->orderBy('id')
            ->value('id');

        if (!$categoriaId) {
            $categoriaId = DB::table('categorias')->insertGetId([
                'nombre' => 'Comida',
                'tipo' => 'cocina',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $productoId = DB::table('productos')
            ->whereRaw('LOWER(nombre) = ?', ['comida'])
            ->value('id');

        if (!$productoId) {
            $productoId = DB::table('productos')->insertGetId([
                'nombre' => 'Comida',
                'precio' => 0,
                'costo' => 0,
                'categoria_id' => $categoriaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $primerGrupoId = $this->firstOrCreateGroup($productoId, 'Primer tiempo', false, false, 10);
        $segundoGrupoId = $this->firstOrCreateGroup($productoId, 'Segundo tiempo', false, false, 20);
        $modalidadGrupoId = $this->firstOrCreateGroup($productoId, 'Modalidad', false, false, 30);

        $this->firstOrCreateOption($primerGrupoId, 'Primer tiempo: Sopa', 0, 0);
        $this->firstOrCreateOption($primerGrupoId, 'Primer tiempo: Arroz', 0, 0);
        $this->firstOrCreateOption($primerGrupoId, 'Primer tiempo: Pasta', 0, 0);

        $this->firstOrCreateOption($segundoGrupoId, 'Segundo tiempo: Sopa', 0, 0);
        $this->firstOrCreateOption($segundoGrupoId, 'Segundo tiempo: Arroz', 0, 0);
        $this->firstOrCreateOption($segundoGrupoId, 'Segundo tiempo: Pasta', 0, 0);

        $this->firstOrCreateOption($modalidadGrupoId, 'Modalidad: Comida del dia', 0, 0);
        $this->firstOrCreateOption($modalidadGrupoId, 'Modalidad: Comida + platillo de la carta', 35, 35);
    }

    public function down(): void
    {
    }

    private function firstOrCreateGroup(int $productoId, string $nombre, bool $obligatorio, bool $multiple, int $orden): int
    {
        $grupoId = DB::table('grupos_opciones')
            ->where('producto_id', $productoId)
            ->where('nombre', $nombre)
            ->value('id');

        if ($grupoId) {
            return (int) $grupoId;
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

    private function firstOrCreateOption(int $grupoId, string $nombre, float $incrementoPrecio, float $incrementoCosto): int
    {
        $opcionId = DB::table('opciones')
            ->where('grupo_opcion_id', $grupoId)
            ->where('nombre', $nombre)
            ->value('id');

        if ($opcionId) {
            return (int) $opcionId;
        }

        return (int) DB::table('opciones')->insertGetId([
            'grupo_opcion_id' => $grupoId,
            'nombre' => $nombre,
            'incremento_precio' => $incrementoPrecio,
            'incremento_costo' => $incrementoCosto,
            'codigo_corto' => null,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
