<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $brumitaId = DB::table('sucursales')
            ->where('codigo', 'brumita')
            ->value('id');

        if ($brumitaId === null) {
            throw new \RuntimeException('No existe la sucursal brumita.');
        }

        $now = now();

        foreach ([3, 4] as $numero) {
            if (! DB::table('mesas')
                ->where('sucursal_id', $brumitaId)
                ->where('tipo', 'mesa')
                ->where('numero', $numero)
                ->exists()) {
                DB::table('mesas')->insert([
                    'sucursal_id' => $brumitaId,
                    'numero' => $numero,
                    'tipo' => 'mesa',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // No eliminar mesas: podrían tener órdenes asociadas después de operar el POS.
    }
};
