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
        Schema::create('orden_detalle_extras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('orden_detalle_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('extra_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('nombre_personalizado')->nullable();

            $table->decimal('precio',8,2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_detalle_extras');
    }
};
