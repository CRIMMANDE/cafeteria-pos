<?php

namespace Tests\Feature;

use App\Console\Commands\LimpiarDatosPosCommand;
use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\Pago;
use App\Models\Sucursal;
use Database\Seeders\DatosInicialesSeeder;
use Database\Seeders\SucursalesMesasSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class Stage2BranchDatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    public function test_branch_and_table_initialization_is_idempotent_and_scoped_by_natural_keys(): void
    {
        $this->seed(SucursalesMesasSeeder::class);
        $this->seed(SucursalesMesasSeeder::class);

        $bruma = Sucursal::query()->where('codigo', 'bruma')->sole();
        $brumita = Sucursal::query()->where('codigo', 'brumita')->sole();

        $this->assertSame(1, Sucursal::query()->where('codigo', 'bruma')->count());
        $this->assertSame(1, Sucursal::query()->where('codigo', 'brumita')->count());
        $this->assertSame(2, Sucursal::query()->count());
        $this->assertTrue($bruma->activa);
        $this->assertTrue($brumita->activa);

        $this->assertSame([1, 2, 3, 4], Mesa::query()->forSucursal($brumita)->where('tipo', 'mesa')->orderBy('numero')->pluck('numero')->all());
        $this->assertTrue(Mesa::query()->forSucursal($bruma)->where('tipo', 'mesa')->where('numero', 1)->exists());
        $this->assertTrue(Mesa::query()->forSucursal($brumita)->where('tipo', 'mesa')->where('numero', 1)->exists());

        $brumitaOne = Mesa::query()->forSucursal($brumita)->where('tipo', 'mesa')->where('numero', 1)->sole();
        $this->assertNotSame(1, $brumitaOne->id);
        $this->assertNotSame($brumitaOne->numero, $brumitaOne->id);

        foreach ([$bruma, $brumita] as $sucursal) {
            $this->assertSame(1, Mesa::query()->forSucursal($sucursal)->whereNull('numero')->where('tipo', 'llevar')->count());
            $this->assertSame(1, Mesa::query()->forSucursal($sucursal)->whereNull('numero')->where('tipo', 'empleados')->count());
            $this->assertTrue(Mesa::specialForSucursal($sucursal, 'llevar')->is(Mesa::ensureTakeawayForSucursal($sucursal)));
            $this->assertTrue(Mesa::specialForSucursal($sucursal, 'empleados')->is(Mesa::ensureEmployeeForSucursal($sucursal)));
        }

        $this->assertSame($bruma->id, Mesa::query()->findOrFail(Mesa::EMPLOYEE_ID)->sucursal_id);
        $this->assertSame($bruma->id, Mesa::query()->findOrFail(Mesa::TAKEAWAY_ID)->sucursal_id);
        $this->assertFalse(Mesa::query()->forSucursal($brumita)->whereIn('id', [Mesa::EMPLOYEE_ID, Mesa::TAKEAWAY_ID])->exists());

        try {
            Mesa::query()->create(['sucursal_id' => $brumita->id, 'numero' => 1, 'tipo' => 'mesa']);
            $this->fail('La restricción debía impedir una Mesa 1 duplicada en BRUMITA.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    public function test_initialization_does_not_assume_numeric_branch_ids(): void
    {
        $brumaId = Sucursal::query()->where('codigo', 'bruma')->value('id');
        $brumitaId = Sucursal::query()->where('codigo', 'brumita')->value('id');

        Schema::disableForeignKeyConstraints();
        DB::table('sucursales')->where('id', $brumaId)->update(['id' => 41]);
        DB::table('sucursales')->where('id', $brumitaId)->update(['id' => 42]);
        DB::table('mesas')->where('sucursal_id', $brumaId)->update(['sucursal_id' => 41]);
        DB::table('mesas')->where('sucursal_id', $brumitaId)->update(['sucursal_id' => 42]);
        DB::table('ordens')->where('sucursal_id', $brumaId)->update(['sucursal_id' => 41]);
        DB::table('ordens')->where('sucursal_id', $brumitaId)->update(['sucursal_id' => 42]);
        Schema::enableForeignKeyConstraints();

        $this->seed(SucursalesMesasSeeder::class);
        $this->seed(SucursalesMesasSeeder::class);

        $this->assertSame(41, Sucursal::query()->where('codigo', 'bruma')->value('id'));
        $this->assertSame(42, Sucursal::query()->where('codigo', 'brumita')->value('id'));
        $this->assertSame(1, Sucursal::query()->where('codigo', 'bruma')->count());
        $this->assertSame(1, Sucursal::query()->where('codigo', 'brumita')->count());
        $this->assertSame(1, Mesa::query()->forSucursal(42)->whereNull('numero')->where('tipo', 'llevar')->count());
        $this->assertSame(1, Mesa::query()->forSucursal(42)->whereNull('numero')->where('tipo', 'empleados')->count());
    }

    public function test_schema_has_required_unique_keys_indexes_and_restricted_foreign_keys(): void
    {
        $mesaIndexes = collect(Schema::getIndexes('mesas'));
        $orderIndexes = collect(Schema::getIndexes('ordens'));

        $this->assertTrue($mesaIndexes->contains(
            fn (array $index): bool => $index['columns'] === ['sucursal_id', 'numero'] && $index['unique']
        ));
        $this->assertTrue($orderIndexes->contains(fn (array $index): bool => $index['columns'] === ['sucursal_id', 'estado', 'mesa_id']));
        $this->assertTrue($orderIndexes->contains(fn (array $index): bool => $index['columns'] === ['sucursal_id', 'estado', 'tipo']));
        $this->assertTrue($orderIndexes->contains(fn (array $index): bool => $index['columns'] === ['sucursal_id', 'created_at']));

        foreach (['mesas', 'ordens'] as $table) {
            $column = collect(Schema::getColumns($table))->firstWhere('name', 'sucursal_id');
            $foreign = collect(Schema::getForeignKeys($table))->first(
                fn (array $key): bool => $key['columns'] === ['sucursal_id'] && $key['foreign_table'] === 'sucursales'
            );

            $this->assertFalse($column['nullable']);
            $this->assertNotNull($foreign);
            $this->assertContains(strtolower($foreign['on_delete']), ['restrict', 'no action']);
        }

        $bruma = Sucursal::query()->where('codigo', 'bruma')->sole();
        try {
            $bruma->delete();
            $this->fail('La llave foránea debía impedir eliminar una sucursal con mesas u órdenes.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        try {
            Sucursal::query()->create(['nombre' => 'Duplicada', 'codigo' => 'bruma', 'activa' => true]);
            $this->fail('El código de sucursal debía ser único.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    public function test_orders_payments_and_children_resolve_the_branch_through_the_order(): void
    {
        $brumita = Sucursal::query()->where('codigo', 'brumita')->sole();
        $mesa = Mesa::query()->forSucursal($brumita)->where('tipo', 'mesa')->where('numero', 1)->sole();
        $referencia = str_repeat('R', 150);

        $orden = Orden::query()->create([
            'sucursal_id' => $brumita->id,
            'mesa_id' => $mesa->id,
            'tipo' => 'mesa',
            'referencia' => null,
            'total' => 125.50,
            'estado' => 'abierta',
        ]);
        $this->assertTrue($orden->sucursal->is($brumita));
        $this->assertNull($orden->referencia);

        $orden->update(['referencia' => $referencia]);
        $this->assertSame($referencia, $orden->fresh()->referencia);
        $this->assertTrue(Orden::query()->forSucursal($brumita)->findOrFail($orden->id)->is($orden));

        $legacyCompatibleOrder = Orden::query()->create([
            'mesa_id' => Mesa::query()->where('id', 1)->value('id'),
            'tipo' => 'mesa',
            'total' => 1,
            'estado' => 'abierta',
        ]);
        $this->assertSame(
            Sucursal::query()->where('codigo', 'bruma')->value('id'),
            $legacyCompatibleOrder->sucursal_id
        );

        $categoriaId = DB::table('categorias')->insertGetId([
            'nombre' => 'Prueba', 'tipo' => 'cocina', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $productoId = DB::table('productos')->insertGetId([
            'nombre' => 'Producto de prueba', 'precio' => 125.50, 'costo' => 50, 'categoria_id' => $categoriaId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $detalle = OrdenDetalle::query()->create([
            'orden_id' => $orden->id, 'producto_id' => $productoId, 'cantidad' => 1, 'precio' => 125.50,
        ]);
        $pago = Pago::query()->create([
            'orden_id' => $orden->id,
            'monto' => '125.50',
            'monto_recibido' => '200.00',
            'cambio' => '74.50',
            'metodo' => 'efectivo',
        ]);

        $this->assertTrue($detalle->orden->sucursal->is($brumita));
        $this->assertTrue($pago->orden->sucursal->is($brumita));
        $this->assertSame('125.50', $pago->monto);
        $this->assertSame('200.00', $pago->monto_recibido);
        $this->assertSame('74.50', $pago->cambio);

        foreach (['pagos', 'orden_detalles', 'orden_detalle_extras', 'orden_detalle_opciones', 'orden_detalle_componentes'] as $table) {
            $this->assertFalse(Schema::hasColumn($table, 'sucursal_id'));
        }

        $this->assertTrue(Schema::hasColumn('pagos', 'monto_recibido'));
        $this->assertTrue(Schema::hasColumn('pagos', 'cambio'));
        $migration = file_get_contents(database_path('migrations/2026_08_06_000004_add_cash_fields_to_pagos_table.php'));
        $this->assertStringContainsString("decimal('monto_recibido', 10, 2)->nullable()", $migration);
        $this->assertStringContainsString("decimal('cambio', 10, 2)->nullable()", $migration);

        $legacyPago = Pago::query()->create(['orden_id' => $orden->id, 'monto' => '1.00', 'metodo' => 'tarjeta']);
        $this->assertNull($legacyPago->monto_recibido);
        $this->assertNull($legacyPago->cambio);
    }

    public function test_cleanup_removes_all_transactional_rows_and_preserves_master_data(): void
    {
        $this->seed(DatosInicialesSeeder::class);

        $sucursalCount = DB::table('sucursales')->count();
        $mesaCount = DB::table('mesas')->count();
        $catalogCounts = collect(['categorias', 'productos', 'extras', 'grupos_opciones', 'opciones', 'menu_dia_opciones'])
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()]);

        DB::table('gastos')->insert([
            'fecha_gasto' => '2026-08-06', 'descripcion' => 'Conservar', 'monto' => 10,
            'status' => 'activo', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $bruma = Sucursal::query()->where('codigo', 'bruma')->sole();
        $mesa = Mesa::query()->forSucursal($bruma)->where('tipo', 'mesa')->firstOrFail();
        $orden = Orden::query()->create([
            'sucursal_id' => $bruma->id, 'mesa_id' => $mesa->id, 'tipo' => 'mesa',
            'total' => 50, 'estado' => 'pagada',
        ]);
        $detalle = OrdenDetalle::query()->create([
            'orden_id' => $orden->id,
            'producto_id' => DB::table('productos')->value('id'),
            'cantidad' => 1,
            'precio' => 50,
        ]);
        DB::table('pagos')->insert(['orden_id' => $orden->id, 'monto' => 50, 'metodo' => 'efectivo', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('orden_detalle_componentes')->insert(['orden_detalle_id' => $detalle->id, 'area' => 'cocina', 'descripcion' => 'Componente', 'cantidad' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('orden_detalle_extras')->insert([
            'orden_detalle_id' => $detalle->id, 'extra_id' => DB::table('extras')->value('id'), 'precio' => 1,
            'cantidad' => 1, 'precio_unitario' => 1, 'subtotal' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('orden_detalle_opciones')->insert([
            'orden_detalle_id' => $detalle->id, 'opcion_id' => DB::table('opciones')->value('id'), 'nombre' => 'Opción',
            'incremento_precio' => 0, 'incremento_costo' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame([
            'pagos',
            'orden_detalle_componentes',
            'orden_detalle_extras',
            'orden_detalle_opciones',
            'orden_detalles',
        ], LimpiarDatosPosCommand::TRANSACTIONAL_CHILD_TABLES);

        $this->artisan('pos:limpiar-datos', ['--force' => true])->assertSuccessful();

        foreach ([...LimpiarDatosPosCommand::TRANSACTIONAL_CHILD_TABLES, 'ordens'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "La tabla {$table} debía quedar vacía.");
        }
        $this->assertSame($sucursalCount, DB::table('sucursales')->count());
        $this->assertSame($mesaCount, DB::table('mesas')->count());
        $catalogCounts->each(fn (int $count, string $table) => $this->assertSame($count, DB::table($table)->count()));
        $this->assertSame(1, DB::table('gastos')->count());
    }
}
