<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('sucursales')->upsert([
            ['nombre' => 'BRUMA', 'codigo' => 'bruma', 'activa' => true, 'created_at' => $now, 'updated_at' => $now],
            ['nombre' => 'BRUMITA', 'codigo' => 'brumita', 'activa' => true, 'created_at' => $now, 'updated_at' => $now],
        ], ['codigo'], ['nombre', 'activa', 'updated_at']);
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
