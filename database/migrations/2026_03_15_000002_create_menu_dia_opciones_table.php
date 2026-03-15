<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('menu_dia_opciones')) {
            return;
        }

        Schema::create('menu_dia_opciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->date('fecha')->nullable();
            $table->timestamps();

            $table->index(['tipo', 'fecha', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_dia_opciones');
    }
};
