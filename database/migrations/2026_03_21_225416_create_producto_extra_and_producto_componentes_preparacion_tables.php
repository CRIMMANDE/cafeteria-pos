<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('producto_extra')) {
            Schema::create('producto_extra', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $table->foreignId('extra_id')->constrained('extras')->cascadeOnDelete();
                $table->boolean('obligatorio')->default(false);
                $table->unsignedInteger('orden_visual')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->unique(['producto_id', 'extra_id'], 'ux_producto_extra');
                $table->index(['producto_id', 'activo'], 'idx_producto_extra_producto_activo');
            });
        }

        if (!Schema::hasTable('producto_componentes_preparacion')) {
            Schema::create('producto_componentes_preparacion', function (Blueprint $table) {
                $table->id();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                $table->string('modalidad', 20)->default('todas');
                $table->enum('area', ['cocina', 'barra']);
                $table->string('nombre_componente');
                $table->decimal('cantidad', 10, 2)->default(1);
                $table->unsignedInteger('orden')->default(0);
                $table->boolean('activo')->default(true);
                $table->timestamps();

                $table->index(['producto_id', 'modalidad', 'activo'], 'idx_componentes_producto_modalidad');
                $table->index(['area', 'activo'], 'idx_componentes_area_activo');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_componentes_preparacion');
        Schema::dropIfExists('producto_extra');
    }
};
