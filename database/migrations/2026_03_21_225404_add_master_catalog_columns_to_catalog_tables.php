<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $this->addCategoriasColumns();
        $this->addProductosColumns();
        $this->addExtrasColumns();
        $this->addGruposColumns();
        $this->addOpcionesColumns();
        $this->addMenuDiaColumns();

        $this->backfillCategorias();
        $this->backfillProductos();
        $this->backfillExtras();
        $this->backfillGrupos();
        $this->backfillOpciones();
        $this->backfillMenuDia();
    }

    public function down(): void
    {
        Schema::table('menu_dia_opciones', function (Blueprint $table) {
            if (Schema::hasColumn('menu_dia_opciones', 'orden')) {
                $table->dropColumn('orden');
            }

            if (Schema::hasColumn('menu_dia_opciones', 'slug')) {
                $table->dropColumn('slug');
            }
        });

        Schema::table('opciones', function (Blueprint $table) {
            if (Schema::hasColumn('opciones', 'orden')) {
                $table->dropColumn('orden');
            }

            if (Schema::hasColumn('opciones', 'slug')) {
                $table->dropUnique('ux_opciones_grupo_slug');
                $table->dropColumn('slug');
            }
        });

        Schema::table('grupos_opciones', function (Blueprint $table) {
            if (Schema::hasColumn('grupos_opciones', 'prioridad_visual')) {
                $table->dropColumn('prioridad_visual');
            }

            if (Schema::hasColumn('grupos_opciones', 'es_grupo_salsa')) {
                $table->dropColumn('es_grupo_salsa');
            }

            if (Schema::hasColumn('grupos_opciones', 'area_aplicacion')) {
                $table->dropColumn('area_aplicacion');
            }

            if (Schema::hasColumn('grupos_opciones', 'scope_modalidad')) {
                $table->dropColumn('scope_modalidad');
            }

            if (Schema::hasColumn('grupos_opciones', 'orden_visual')) {
                $table->dropColumn('orden_visual');
            }

            if (Schema::hasColumn('grupos_opciones', 'tipo')) {
                $table->dropColumn('tipo');
            }

            if (Schema::hasColumn('grupos_opciones', 'slug')) {
                $table->dropUnique('ux_grupos_producto_slug');
                $table->dropColumn('slug');
            }
        });

        Schema::table('extras', function (Blueprint $table) {
            if (Schema::hasColumn('extras', 'orden')) {
                $table->dropColumn('orden');
            }

            if (Schema::hasColumn('extras', 'permite_cantidad')) {
                $table->dropColumn('permite_cantidad');
            }

            if (Schema::hasColumn('extras', 'slug')) {
                $table->dropUnique('extras_slug_unique');
                $table->dropColumn('slug');
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            foreach (['orden', 'usa_salsa', 'usa_notas', 'usa_extras', 'usa_menu_dia'] as $column) {
                if (Schema::hasColumn('productos', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('productos', 'sku')) {
                $table->dropUnique('productos_sku_unique');
                $table->dropColumn('sku');
            }
        });

        Schema::table('categorias', function (Blueprint $table) {
            if (Schema::hasColumn('categorias', 'orden')) {
                $table->dropColumn('orden');
            }

            if (Schema::hasColumn('categorias', 'slug')) {
                $table->dropUnique('categorias_slug_unique');
                $table->dropColumn('slug');
            }
        });
    }

    private function addCategoriasColumns(): void
    {
        Schema::table('categorias', function (Blueprint $table) {
            if (!Schema::hasColumn('categorias', 'slug')) {
                $table->string('slug')->nullable()->after('id');
            }

            if (!Schema::hasColumn('categorias', 'orden')) {
                $table->unsignedInteger('orden')->default(0)->after('activo');
            }
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    private function addProductosColumns(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            if (!Schema::hasColumn('productos', 'sku')) {
                $table->string('sku')->nullable()->after('id');
            }

            if (!Schema::hasColumn('productos', 'usa_menu_dia')) {
                $table->boolean('usa_menu_dia')->default(false)->after('es_comida_dia');
            }

            if (!Schema::hasColumn('productos', 'usa_extras')) {
                $table->boolean('usa_extras')->default(true)->after('usa_menu_dia');
            }

            if (!Schema::hasColumn('productos', 'usa_notas')) {
                $table->boolean('usa_notas')->default(true)->after('usa_extras');
            }

            if (!Schema::hasColumn('productos', 'usa_salsa')) {
                $table->boolean('usa_salsa')->default(false)->after('usa_notas');
            }

            if (!Schema::hasColumn('productos', 'orden')) {
                $table->unsignedInteger('orden')->default(0)->after('usa_salsa');
            }
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    private function addExtrasColumns(): void
    {
        Schema::table('extras', function (Blueprint $table) {
            if (!Schema::hasColumn('extras', 'slug')) {
                $table->string('slug')->nullable()->after('id');
            }

            if (!Schema::hasColumn('extras', 'permite_cantidad')) {
                $table->boolean('permite_cantidad')->default(true)->after('activo');
            }

            if (!Schema::hasColumn('extras', 'orden')) {
                $table->unsignedInteger('orden')->default(0)->after('permite_cantidad');
            }
        });

        Schema::table('extras', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    private function addGruposColumns(): void
    {
        Schema::table('grupos_opciones', function (Blueprint $table) {
            if (!Schema::hasColumn('grupos_opciones', 'slug')) {
                $table->string('slug')->nullable()->after('producto_id');
            }

            if (!Schema::hasColumn('grupos_opciones', 'tipo')) {
                $table->string('tipo', 30)->default('seleccion_unica')->after('nombre');
            }

            if (!Schema::hasColumn('grupos_opciones', 'orden_visual')) {
                $table->unsignedInteger('orden_visual')->default(0)->after('orden');
            }

            if (!Schema::hasColumn('grupos_opciones', 'scope_modalidad')) {
                $table->string('scope_modalidad', 20)->default('todas')->after('modalidad');
            }

            if (!Schema::hasColumn('grupos_opciones', 'area_aplicacion')) {
                $table->string('area_aplicacion', 20)->nullable()->after('scope_modalidad');
            }

            if (!Schema::hasColumn('grupos_opciones', 'es_grupo_salsa')) {
                $table->boolean('es_grupo_salsa')->default(false)->after('area_aplicacion');
            }

            if (!Schema::hasColumn('grupos_opciones', 'prioridad_visual')) {
                $table->integer('prioridad_visual')->default(0)->after('es_grupo_salsa');
            }
        });

        Schema::table('grupos_opciones', function (Blueprint $table) {
            $table->unique(['producto_id', 'slug'], 'ux_grupos_producto_slug');
        });
    }

    private function addOpcionesColumns(): void
    {
        Schema::table('opciones', function (Blueprint $table) {
            if (!Schema::hasColumn('opciones', 'slug')) {
                $table->string('slug')->nullable()->after('grupo_opcion_id');
            }

            if (!Schema::hasColumn('opciones', 'orden')) {
                $table->unsignedInteger('orden')->default(0)->after('codigo_corto');
            }
        });

        Schema::table('opciones', function (Blueprint $table) {
            $table->unique(['grupo_opcion_id', 'slug'], 'ux_opciones_grupo_slug');
        });
    }

    private function addMenuDiaColumns(): void
    {
        Schema::table('menu_dia_opciones', function (Blueprint $table) {
            if (!Schema::hasColumn('menu_dia_opciones', 'slug')) {
                $table->string('slug')->nullable()->after('tipo');
            }

            if (!Schema::hasColumn('menu_dia_opciones', 'orden')) {
                $table->unsignedInteger('orden')->default(0)->after('activo');
            }
        });
    }

    private function backfillCategorias(): void
    {
        $used = [];

        foreach (DB::table('categorias')->orderBy('id')->get(['id', 'nombre', 'slug']) as $row) {
            $slug = $this->uniqueSlug($row->slug ?: $row->nombre, $used, 'categoria_' . $row->id);

            DB::table('categorias')->where('id', $row->id)->update([
                'slug' => $slug,
                'orden' => (int) ($row->id * 10),
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillProductos(): void
    {
        $used = [];
        $salsaGroupIds = DB::table('grupos_opciones')
            ->select('producto_id')
            ->whereRaw('LOWER(nombre) LIKE ?', ['%salsa%'])
            ->pluck('producto_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach (DB::table('productos')->orderBy('id')->get(['id', 'nombre', 'sku', 'es_comida_dia']) as $row) {
            $sku = $this->uniqueSlug($row->sku ?: $row->nombre, $used, 'producto_' . $row->id);

            DB::table('productos')->where('id', $row->id)->update([
                'sku' => $sku,
                'usa_menu_dia' => (bool) $row->es_comida_dia,
                'usa_extras' => true,
                'usa_notas' => true,
                'usa_salsa' => in_array((int) $row->id, $salsaGroupIds, true),
                'orden' => (int) ($row->id * 10),
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillExtras(): void
    {
        $used = [];

        foreach (DB::table('extras')->orderBy('id')->get(['id', 'nombre', 'slug']) as $row) {
            $slug = $this->uniqueSlug($row->slug ?: $row->nombre, $used, 'extra_' . $row->id);

            DB::table('extras')->where('id', $row->id)->update([
                'slug' => $slug,
                'permite_cantidad' => !str_contains(Str::lower((string) $row->nombre), 'otro'),
                'orden' => (int) ($row->id * 10),
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillGrupos(): void
    {
        $usedByProduct = [];

        $rows = DB::table('grupos_opciones')
            ->orderBy('producto_id')
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'producto_id', 'nombre', 'slug', 'multiple', 'modalidad', 'orden']);

        foreach ($rows as $row) {
            $productId = (int) $row->producto_id;
            $usedByProduct[$productId] ??= [];
            $slug = $this->uniqueSlug($row->slug ?: $row->nombre, $usedByProduct[$productId], 'grupo_' . $row->id);
            $nombre = Str::lower((string) $row->nombre);
            $multiple = (bool) $row->multiple;

            DB::table('grupos_opciones')->where('id', $row->id)->update([
                'slug' => $slug,
                'tipo' => $multiple ? 'seleccion_multiple' : 'seleccion_unica',
                'orden_visual' => (int) $row->orden,
                'scope_modalidad' => $row->modalidad ?: 'todas',
                'es_grupo_salsa' => str_contains($nombre, 'salsa'),
                'prioridad_visual' => str_contains($nombre, 'salsa') ? -100 : 0,
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillOpciones(): void
    {
        $usedByGroup = [];
        $rows = DB::table('opciones')
            ->orderBy('grupo_opcion_id')
            ->orderBy('id')
            ->get(['id', 'grupo_opcion_id', 'nombre', 'slug']);

        foreach ($rows as $row) {
            $groupId = (int) $row->grupo_opcion_id;
            $usedByGroup[$groupId] ??= [];
            $slug = $this->uniqueSlug($row->slug ?: $row->nombre, $usedByGroup[$groupId], 'opcion_' . $row->id);

            DB::table('opciones')->where('id', $row->id)->update([
                'slug' => $slug,
                'orden' => (int) ($row->id * 10),
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillMenuDia(): void
    {
        $rows = DB::table('menu_dia_opciones')
            ->orderBy('id')
            ->get(['id', 'tipo', 'nombre', 'slug']);

        foreach ($rows as $row) {
            $slug = trim((string) $row->slug) !== ''
                ? trim((string) $row->slug)
                : Str::slug((string) $row->tipo . '-' . (string) $row->nombre);

            if ($slug === '') {
                $slug = 'menu_dia_' . $row->id;
            }

            DB::table('menu_dia_opciones')->where('id', $row->id)->update([
                'slug' => $slug,
                'orden' => (int) ($row->id * 10),
                'updated_at' => now(),
            ]);
        }
    }

    private function uniqueSlug(?string $candidate, array &$used, string $fallback): string
    {
        $base = Str::slug((string) $candidate);
        if ($base === '') {
            $base = Str::slug($fallback);
        }
        if ($base === '') {
            $base = $fallback;
        }

        $slug = $base;
        $suffix = 2;
        while (in_array($slug, $used, true)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        $used[] = $slug;

        return $slug;
    }
};
