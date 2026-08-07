<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('id');
        });

        $brumaId = $this->branchId('bruma');
        $brumitaId = $this->branchId('brumita');
        $now = now();

        DB::table('mesas')->update(['sucursal_id' => $brumaId]);

        foreach ([9998 => 'empleados', 9999 => 'llevar'] as $id => $tipo) {
            if (DB::table('mesas')->where('id', $id)->exists()) {
                DB::table('mesas')->where('id', $id)->update([
                    'sucursal_id' => $brumaId,
                    'numero' => null,
                    'tipo' => $tipo,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('mesas')->insert([
                    'id' => $id,
                    'sucursal_id' => $brumaId,
                    'numero' => null,
                    'tipo' => $tipo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ([1, 2] as $numero) {
            DB::table('mesas')->updateOrInsert(
                ['sucursal_id' => $brumitaId, 'tipo' => 'mesa', 'numero' => $numero],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        foreach (['llevar', 'empleados'] as $tipo) {
            if (! DB::table('mesas')->where('sucursal_id', $brumitaId)->whereNull('numero')->where('tipo', $tipo)->exists()) {
                DB::table('mesas')->insert([
                    'sucursal_id' => $brumitaId,
                    'numero' => null,
                    'tipo' => $tipo,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (DB::table('mesas')->whereNull('sucursal_id')->exists()) {
            throw new \RuntimeException('No se pudo asignar una sucursal a todas las mesas.');
        }

        $duplicatePhysicalTable = DB::table('mesas')
            ->select('sucursal_id', 'numero')
            ->whereNotNull('numero')
            ->groupBy('sucursal_id', 'numero')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicatePhysicalTable !== null) {
            throw new \RuntimeException('Hay números de mesa duplicados dentro de una sucursal.');
        }

        Schema::table('mesas', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable(false)->change();
            $table->foreign('sucursal_id', 'mesas_sucursal_fk')
                ->references('id')->on('sucursales')->restrictOnDelete();
            $table->unique(['sucursal_id', 'numero'], 'mesas_sucursal_numero_unique');
            $table->index(['sucursal_id', 'tipo'], 'mesas_sucursal_tipo_idx');
        });
    }

    public function down(): void
    {
        $brumaId = DB::table('sucursales')->where('codigo', 'bruma')->value('id');
        $brumitaId = DB::table('sucursales')->where('codigo', 'brumita')->value('id');

        if ($brumitaId !== null) {
            $brumitaMesaIds = DB::table('mesas')->where('sucursal_id', $brumitaId)->pluck('id');

            if ($brumitaMesaIds->isNotEmpty() && DB::table('ordens')->whereIn('mesa_id', $brumitaMesaIds)->exists()) {
                throw new \RuntimeException('No es seguro revertir: existen órdenes asociadas a mesas de BRUMITA.');
            }

            DB::table('mesas')->where('sucursal_id', $brumitaId)->delete();
        }

        if ($brumaId !== null) {
            DB::table('mesas')->where('id', 9998)->where('sucursal_id', $brumaId)->update(['numero' => 9998]);
            DB::table('mesas')->where('id', 9999)->where('sucursal_id', $brumaId)->update(['numero' => 9999]);
        }

        Schema::table('mesas', function (Blueprint $table) {
            $table->dropUnique('mesas_sucursal_numero_unique');
            $table->dropIndex('mesas_sucursal_tipo_idx');
            $table->dropForeign('mesas_sucursal_fk');
            $table->dropColumn('sucursal_id');
        });
    }

    private function branchId(string $codigo): int
    {
        $id = DB::table('sucursales')->where('codigo', $codigo)->value('id');

        if ($id === null) {
            throw new \RuntimeException("No existe la sucursal {$codigo}.");
        }

        return (int) $id;
    }
};
