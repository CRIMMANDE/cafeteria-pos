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
        if (!Schema::hasColumn('ordens', 'tipo')) {
            Schema::table('ordens', function (Blueprint $table) {
                $table->enum('tipo', ['mesa', 'llevar', 'empleados'])->default('mesa')->after('mesa_id');
            });
        }

        if (!Schema::hasColumn('ordens', 'metodo_pago')) {
            Schema::table('ordens', function (Blueprint $table) {
                $table->enum('metodo_pago', ['efectivo', 'tarjeta'])->nullable()->after('desc_empleado');
            });
        }

        if (!Schema::hasColumn('extras', 'activo')) {
            Schema::table('extras', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('precio');
            });
        }

        if (!Schema::hasColumn('orden_detalle_extras', 'nota')) {
            Schema::table('orden_detalle_extras', function (Blueprint $table) {
                $table->text('nota')->nullable()->after('precio');
            });
        }

        if (!Schema::hasTable('grupos_opciones')) {
            Schema::create('grupos_opciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->constrained()->cascadeOnDelete();
                $table->string('nombre');
                $table->boolean('obligatorio')->default(false);
                $table->boolean('multiple')->default(false);
                $table->unsignedInteger('orden')->default(0);
                $table->unsignedBigInteger('solo_si_opcion_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('opciones')) {
            Schema::create('opciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grupo_opcion_id')->constrained('grupos_opciones')->cascadeOnDelete();
                $table->string('nombre');
                $table->decimal('incremento_precio', 8, 2)->default(0);
                $table->decimal('incremento_costo', 8, 2)->default(0);
                $table->string('codigo_corto')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('orden_detalle_opciones')) {
            Schema::create('orden_detalle_opciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('orden_detalle_id')->constrained()->cascadeOnDelete();
                $table->foreignId('opcion_id')->nullable()->constrained('opciones')->nullOnDelete();
                $table->string('nombre');
                $table->decimal('incremento_precio', 8, 2)->default(0);
                $table->decimal('incremento_costo', 8, 2)->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('grupos_opciones') && Schema::hasTable('opciones')) {
            Schema::table('grupos_opciones', function (Blueprint $table) {
                $table->foreign('solo_si_opcion_id')->references('id')->on('opciones')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orden_detalle_opciones')) {
            Schema::drop('orden_detalle_opciones');
        }

        if (Schema::hasTable('opciones')) {
            Schema::drop('opciones');
        }

        if (Schema::hasTable('grupos_opciones')) {
            Schema::drop('grupos_opciones');
        }

        if (Schema::hasColumn('orden_detalle_extras', 'nota')) {
            Schema::table('orden_detalle_extras', function (Blueprint $table) {
                $table->dropColumn('nota');
            });
        }

        if (Schema::hasColumn('extras', 'activo')) {
            Schema::table('extras', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }

        if (Schema::hasColumn('ordens', 'metodo_pago')) {
            Schema::table('ordens', function (Blueprint $table) {
                $table->dropColumn('metodo_pago');
            });
        }

        if (Schema::hasColumn('ordens', 'tipo')) {
            Schema::table('ordens', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
};
