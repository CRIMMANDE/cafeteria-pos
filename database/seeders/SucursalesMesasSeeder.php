<?php

namespace Database\Seeders;

use App\Models\Mesa;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SucursalesMesasSeeder extends Seeder
{
    public function run(): void
    {
        $bruma = Sucursal::query()->updateOrCreate(
            ['codigo' => 'bruma'],
            ['nombre' => 'BRUMA', 'activa' => true]
        );
        $brumita = Sucursal::query()->updateOrCreate(
            ['codigo' => 'brumita'],
            ['nombre' => 'BRUMITA', 'activa' => true]
        );

        DB::table('mesas')->whereNull('sucursal_id')->update(['sucursal_id' => $bruma->id]);

        if (Schema::hasColumn('ordens', 'sucursal_id')) {
            DB::table('ordens')->whereNull('sucursal_id')->update(['sucursal_id' => $bruma->id]);
        }

        foreach (range(1, 10) as $numero) {
            Mesa::query()->firstOrCreate(
                ['sucursal_id' => $bruma->id, 'tipo' => 'mesa', 'numero' => $numero]
            );
        }

        $this->ensureLegacySpecial($bruma, Mesa::EMPLOYEE_ID, 'empleados');
        $this->ensureLegacySpecial($bruma, Mesa::TAKEAWAY_ID, 'llevar');

        foreach ([1, 2] as $numero) {
            Mesa::query()->firstOrCreate(
                ['sucursal_id' => $brumita->id, 'tipo' => 'mesa', 'numero' => $numero]
            );
        }

        Mesa::ensureEmployeeForSucursal($brumita);
        Mesa::ensureTakeawayForSucursal($brumita);
    }

    private function ensureLegacySpecial(Sucursal $sucursal, int $id, string $tipo): void
    {
        $mesa = Mesa::query()->find($id);

        if ($mesa === null) {
            $mesa = new Mesa(['sucursal_id' => $sucursal->id, 'numero' => null, 'tipo' => $tipo]);
            $mesa->id = $id;
        } else {
            $mesa->fill(['sucursal_id' => $sucursal->id, 'numero' => null, 'tipo' => $tipo]);
        }

        $mesa->save();
    }
}
