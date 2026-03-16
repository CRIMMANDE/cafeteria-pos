<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('permite_solo')->default(true)->after('costo');
            $table->boolean('permite_desayuno')->default(false)->after('permite_solo');
            $table->boolean('permite_comida')->default(false)->after('permite_desayuno');
            $table->decimal('incremento_desayuno', 10, 2)->default(0)->after('permite_comida');
            $table->decimal('incremento_comida', 10, 2)->default(0)->after('incremento_desayuno');
            $table->boolean('es_comida_dia')->default(false)->after('incremento_comida');
        });

        Schema::table('grupos_opciones', function (Blueprint $table) {
            $table->string('modalidad', 20)->default('todas')->after('nombre');
        });

        Schema::table('orden_detalles', function (Blueprint $table) {
            $table->string('modalidad', 20)->default('solo')->after('cantidad');
            $table->decimal('precio_base', 10, 2)->default(0)->after('modalidad');
            $table->decimal('incremento_modalidad', 10, 2)->default(0)->after('precio_base');
        });
    }

    public function down(): void
    {
        Schema::table('orden_detalles', function (Blueprint $table) {
            $table->dropColumn(['modalidad', 'precio_base', 'incremento_modalidad']);
        });

        Schema::table('grupos_opciones', function (Blueprint $table) {
            $table->dropColumn('modalidad');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'permite_solo',
                'permite_desayuno',
                'permite_comida',
                'incremento_desayuno',
                'incremento_comida',
                'es_comida_dia',
            ]);
        });
    }
};
