<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id');
            $table->string('referencia', 150)->nullable()->after('tipo');
        });

        $brumaId = DB::table('sucursales')->where('codigo', 'bruma')->value('id');

        if ($brumaId === null) {
            throw new \RuntimeException('No existe la sucursal bruma para migrar las órdenes históricas.');
        }

        // La historia completa pertenece a BRUMA, incluso si mesa_id es nulo o inválido.
        DB::table('ordens')->update(['sucursal_id' => $brumaId]);

        if (DB::table('ordens')->whereNull('sucursal_id')->exists()) {
            throw new \RuntimeException('No se pudo asignar una sucursal a todas las órdenes.');
        }

        Schema::table('ordens', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable(false)->change();
            $table->foreign('sucursal_id', 'ordens_sucursal_fk')
                ->references('id')->on('sucursales')->restrictOnDelete();
            $table->index(['sucursal_id', 'estado', 'mesa_id'], 'ordens_sucursal_estado_mesa_idx');
            $table->index(['sucursal_id', 'estado', 'tipo'], 'ordens_sucursal_estado_tipo_idx');
            $table->index(['sucursal_id', 'created_at'], 'ordens_sucursal_created_idx');
        });
    }

    public function down(): void
    {
        $brumaId = DB::table('sucursales')->where('codigo', 'bruma')->value('id');

        if ($brumaId !== null && DB::table('ordens')->where('sucursal_id', '<>', $brumaId)->exists()) {
            throw new \RuntimeException('No es seguro revertir: existen órdenes de una sucursal distinta de BRUMA.');
        }

        Schema::table('ordens', function (Blueprint $table) {
            $table->dropIndex('ordens_sucursal_estado_mesa_idx');
            $table->dropIndex('ordens_sucursal_estado_tipo_idx');
            $table->dropIndex('ordens_sucursal_created_idx');
            $table->dropForeign('ordens_sucursal_fk');
            $table->dropColumn(['sucursal_id', 'referencia']);
        });
    }
};
