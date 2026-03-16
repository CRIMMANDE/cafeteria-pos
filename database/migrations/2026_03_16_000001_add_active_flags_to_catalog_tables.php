<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'activo')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('costo');
            });
        }

        if (!Schema::hasColumn('categorias', 'activo')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('tipo');
            });
        }

        if (!Schema::hasColumn('grupos_opciones', 'activo')) {
            Schema::table('grupos_opciones', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('solo_si_opcion_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grupos_opciones', 'activo')) {
            Schema::table('grupos_opciones', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }

        if (Schema::hasColumn('categorias', 'activo')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }

        if (Schema::hasColumn('productos', 'activo')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }
    }
};
