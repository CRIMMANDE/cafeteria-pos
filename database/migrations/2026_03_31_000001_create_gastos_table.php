<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_gasto');
            $table->text('descripcion');
            $table->decimal('monto', 10, 2);
            $table->enum('status', ['activo', 'cancelado'])->default('activo');
            $table->timestamp('cancelado_at')->nullable();
            $table->timestamps();

            $table->index('fecha_gasto');
            $table->index(['status', 'fecha_gasto']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
