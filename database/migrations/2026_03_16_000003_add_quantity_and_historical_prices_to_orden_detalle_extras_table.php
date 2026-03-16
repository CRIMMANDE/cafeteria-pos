<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_detalle_extras', function (Blueprint $table) {
            if (!Schema::hasColumn('orden_detalle_extras', 'cantidad')) {
                $table->unsignedInteger('cantidad')->default(1)->after('extra_id');
            }

            if (!Schema::hasColumn('orden_detalle_extras', 'precio_unitario')) {
                $table->decimal('precio_unitario', 10, 2)->default(0)->after('nombre_personalizado');
            }

            if (!Schema::hasColumn('orden_detalle_extras', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('precio_unitario');
            }
        });

        DB::table('orden_detalle_extras')->update([
            'cantidad' => DB::raw('CASE WHEN cantidad IS NULL OR cantidad < 1 THEN 1 ELSE cantidad END'),
            'precio_unitario' => DB::raw('CASE WHEN precio_unitario IS NULL OR precio_unitario = 0 THEN COALESCE(precio, 0) ELSE precio_unitario END'),
            'subtotal' => DB::raw('CASE WHEN subtotal IS NULL OR subtotal = 0 THEN COALESCE(precio, 0) ELSE subtotal END'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orden_detalle_extras', function (Blueprint $table) {
            if (Schema::hasColumn('orden_detalle_extras', 'subtotal')) {
                $table->dropColumn('subtotal');
            }

            if (Schema::hasColumn('orden_detalle_extras', 'precio_unitario')) {
                $table->dropColumn('precio_unitario');
            }

            if (Schema::hasColumn('orden_detalle_extras', 'cantidad')) {
                $table->dropColumn('cantidad');
            }
        });
    }
};
