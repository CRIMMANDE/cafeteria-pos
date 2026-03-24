<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('orden_detalles', 'es_otro_manual')) {
                $table->boolean('es_otro_manual')->default(false)->after('producto_id');
            }

            if (!Schema::hasColumn('orden_detalles', 'nombre_personalizado')) {
                $table->string('nombre_personalizado', 255)->nullable()->after('es_otro_manual');
            }

            if (!Schema::hasColumn('orden_detalles', 'area_preparacion')) {
                $table->string('area_preparacion', 10)->nullable()->after('nombre_personalizado');
            }

            if (!Schema::hasColumn('orden_detalles', 'precio_manual')) {
                $table->decimal('precio_manual', 10, 2)->nullable()->after('area_preparacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orden_detalles', function (Blueprint $table) {
            $drop = [];

            foreach (['es_otro_manual', 'nombre_personalizado', 'area_preparacion', 'precio_manual'] as $column) {
                if (Schema::hasColumn('orden_detalles', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};

