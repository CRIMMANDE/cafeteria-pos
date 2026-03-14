<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'costo')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->decimal('costo', 8, 2)->default(0)->after('precio');
            });
        }

        if (!Schema::hasColumn('ordens', 'desc_empleado')) {
            Schema::table('ordens', function (Blueprint $table) {
                $table->boolean('desc_empleado')->default(false)->after('estado');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ordens', 'desc_empleado')) {
            Schema::table('ordens', function (Blueprint $table) {
                $table->dropColumn('desc_empleado');
            });
        }

        if (Schema::hasColumn('productos', 'costo')) {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropColumn('costo');
            });
        }
    }
};
