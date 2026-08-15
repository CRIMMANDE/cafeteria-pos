<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage2HistoricalMigrationTest extends TestCase
{
    public function test_historical_rows_are_preserved_and_assigned_directly_to_bruma(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        $this->runMigrationFilesBeforeStageTwo();

        $now = now();
        DB::table('mesas')->insert([
            ['id' => 1, 'numero' => 1, 'tipo' => 'mesa', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9998, 'numero' => 9998, 'tipo' => 'empleados', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 9999, 'numero' => 9999, 'tipo' => 'llevar', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('ordens')->insert([
            ['id' => 100, 'mesa_id' => 1, 'tipo' => 'mesa', 'total' => 50, 'estado' => 'pagada', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 105, 'mesa_id' => null, 'tipo' => 'llevar', 'total' => 25, 'estado' => 'abierta', 'created_at' => $now, 'updated_at' => $now],
        ]);

        $productoId = DB::table('productos')->value('id');
        $detalleId = DB::table('orden_detalles')->insertGetId([
            'orden_id' => 100, 'producto_id' => $productoId, 'cantidad' => 1, 'precio' => 50,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $pagoId = DB::table('pagos')->insertGetId([
            'orden_id' => 100, 'monto' => 50, 'metodo' => 'efectivo', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $countsBefore = collect(['mesas', 'ordens', 'pagos', 'orden_detalles'])
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()]);

        $this->runStageTwoMigrationFiles();

        $brumaId = DB::table('sucursales')->where('codigo', 'bruma')->value('id');
        $brumitaId = DB::table('sucursales')->where('codigo', 'brumita')->value('id');

        $this->assertNotNull($brumaId);
        $this->assertNotNull($brumitaId);
        $this->assertSame($countsBefore['mesas'], DB::table('mesas')->whereIn('id', [1, 9998, 9999])->count());
        $this->assertFalse(DB::table('mesas')->whereIn('id', [1, 9998, 9999])->where('sucursal_id', '<>', $brumaId)->exists());
        $this->assertFalse(DB::table('ordens')->whereIn('id', [100, 105])->where('sucursal_id', '<>', $brumaId)->exists());
        $this->assertSame($brumaId, DB::table('ordens')->where('id', 105)->value('sucursal_id'));

        $this->assertSame($countsBefore['ordens'], DB::table('ordens')->whereIn('id', [100, 105])->count());
        $this->assertSame($countsBefore['pagos'], DB::table('pagos')->where('id', $pagoId)->count());
        $this->assertSame($countsBefore['orden_detalles'], DB::table('orden_detalles')->where('id', $detalleId)->count());
        $this->assertTrue(DB::table('mesas')->where('id', 9998)->whereNull('numero')->exists());
        $this->assertTrue(DB::table('mesas')->where('id', 9999)->whereNull('numero')->exists());
        $this->assertNull(DB::table('pagos')->where('id', $pagoId)->value('monto_recibido'));
        $this->assertNull(DB::table('pagos')->where('id', $pagoId)->value('cambio'));
        $this->assertFalse(Schema::hasColumn('ordens', 'folio'));

        $newOrderId = DB::table('ordens')->insertGetId([
            'sucursal_id' => $brumaId, 'mesa_id' => 1, 'tipo' => 'mesa', 'total' => 1,
            'estado' => 'abierta', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->assertGreaterThan(105, $newOrderId);

        $this->assertSame([1, 2, 3, 4], DB::table('mesas')->where('sucursal_id', $brumitaId)->where('tipo', 'mesa')->orderBy('numero')->pluck('numero')->all());
        $this->assertSame(1, DB::table('mesas')->where('sucursal_id', $brumitaId)->whereNull('numero')->where('tipo', 'llevar')->count());
        $this->assertSame(1, DB::table('mesas')->where('sucursal_id', $brumitaId)->whereNull('numero')->where('tipo', 'empleados')->count());
    }

    private function runMigrationFilesBeforeStageTwo(): void
    {
        foreach ($this->migrationFiles() as $file) {
            if (basename($file) >= '2026_08_06_000001') {
                continue;
            }

            (require $file)->up();
        }
    }

    private function runStageTwoMigrationFiles(): void
    {
        foreach ($this->migrationFiles() as $file) {
            if (basename($file) < '2026_08_06_000001') {
                continue;
            }

            (require $file)->up();
        }
    }

    /** @return list<string> */
    private function migrationFiles(): array
    {
        $files = glob(database_path('migrations/*.php')) ?: [];
        sort($files, SORT_STRING);

        return $files;
    }
}
