<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orden_detalle_componentes')) {
            return;
        }

        Schema::create('orden_detalle_componentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_detalle_id')->constrained('orden_detalles')->cascadeOnDelete();
            $table->enum('area', ['cocina', 'barra']);
            $table->string('descripcion');
            $table->integer('cantidad');
            $table->boolean('impreso')->default(false);
            $table->timestamps();

            $table->index(['area', 'impreso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_detalle_componentes');
    }
};
