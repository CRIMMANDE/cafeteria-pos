<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\OrdenDetalleComponente;
use App\Models\Sucursal;
use App\Services\ThermalPrinter\PrintResult;
use App\Services\ThermalPrinter\RawEscPosPrinter;
use Database\Seeders\SucursalesMesasSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ProductOtherAreaCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->seed(SucursalesMesasSeeder::class);
    }

    public function test_failed_manual_bar_component_is_retried_with_next_bar_command(): void
    {
        config(['impresoras.barra.test_destination' => 'barra']);
        $branch = $this->branch('brumita');
        $table = $this->table($branch, 'empleados');
        $otherId = $this->product('Otro', 'otro', 'barra');
        $normalId = $this->product('Normal barra', 'normal-barra', 'barra');
        $payloadOther = $this->manualPayload($otherId, 'Prueba barra', '14.00', 'barra');
        $payloadNormal = $this->normalPayload($normalId);
        $payloads = [];
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')
            ->twice()
            ->andReturnUsing(function (string $payload, array $config) use (&$payloads) {
                $this->assertSame('barra', $config['test_destination']);
                $payloads[] = $payload;

                return count($payloads) === 1
                    ? new PrintResult(true, false, 'fallo simulado', error: 'simulado')
                    : new PrintResult(true, true, 'ok', 'usb');
            });
        $this->app->instance(RawEscPosPrinter::class, $printer);

        $this->store($branch, $table, [$payloadOther], [$payloadOther])->assertOk();

        $order = Orden::query()->forSucursal($branch)->where('mesa_id', $table->id)->sole();
        $manualDetail = OrdenDetalle::query()->where('orden_id', $order->id)->where('es_otro_manual', true)->sole();
        $manualComponent = $manualDetail->componentes()->sole();
        $this->assertSame('barra', $manualComponent->area);
        $this->assertFalse((bool) $manualComponent->impreso);

        $this->store($branch, $table, [$payloadOther, $payloadNormal], [$payloadNormal])->assertOk();

        $normalDetail = OrdenDetalle::query()->where('orden_id', $order->id)->where('producto_id', $normalId)->sole();
        $normalComponent = $normalDetail->componentes()->sole();
        $this->assertTrue((bool) $manualComponent->fresh()->impreso);
        $this->assertTrue((bool) $normalComponent->fresh()->impreso);
        $this->assertStringContainsString('PRUEBA BARRA', $payloads[1]);
        $this->assertStringContainsString('NORMAL BARRA', $payloads[1]);
    }

    public function test_manual_bar_product_persists_and_prints_for_both_branches_and_all_order_types(): void
    {
        config(['impresoras.barra.test_destination' => 'barra']);
        $otherId = $this->product('Otro', 'otro', 'barra');
        $contexts = [
            ['bruma', 'empleados', null],
            ['brumita', 'empleados', null],
            ['bruma', 'mesa', 1],
            ['brumita', 'mesa', 1],
            ['bruma', 'llevar', null],
            ['brumita', 'llevar', null],
        ];
        $printedPayloads = [];
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')
            ->times(count($contexts))
            ->andReturnUsing(function (string $payload, array $config) use (&$printedPayloads) {
                $this->assertSame('barra', $config['test_destination']);
                $printedPayloads[] = $payload;

                return new PrintResult(true, true, 'ok', 'usb');
            });
        $this->app->instance(RawEscPosPrinter::class, $printer);

        foreach ($contexts as $index => [$branchCode, $type, $number]) {
            $branch = $this->branch($branchCode);
            $table = $this->table($branch, $type, $number);
            $name = "Otro {$branchCode} {$type}";
            $payload = $this->manualPayload($otherId, $name, '14.00', 'barra');

            $this->store($branch, $table, [$payload], [$payload])
                ->assertOk()
                ->assertJsonPath('command_results.barra.printed', true)
                ->assertJsonPath('command_results.cocina.printed', false);

            $order = Orden::query()->forSucursal($branch)->where('mesa_id', $table->id)->sole();
            $detail = OrdenDetalle::query()->where('orden_id', $order->id)->sole();
            $component = $detail->componentes()->sole();

            $this->assertSame($type, $order->tipo);
            $this->assertTrue((bool) $detail->es_otro_manual);
            $this->assertSame($name, $detail->nombre_personalizado);
            $this->assertSame('barra', $detail->area_preparacion);
            $this->assertEquals(14.0, $detail->precio);
            $this->assertSame('barra', $component->area);
            $this->assertSame(strtoupper($name), $component->descripcion);
            $this->assertSame(1, (int) $component->cantidad);
            $this->assertTrue((bool) $component->impreso);
            $this->assertStringContainsString(strtoupper($name), $printedPayloads[$index]);
        }
    }

    public function test_manual_kitchen_and_normal_products_keep_their_area_routing(): void
    {
        config([
            'impresoras.cocina.test_destination' => 'cocina',
            'impresoras.barra.test_destination' => 'barra',
        ]);
        $branch = $this->branch('bruma');
        $table = $this->table($branch, 'mesa', 2);
        $otherId = $this->product('Otro', 'otro', 'cocina');
        $normalKitchenId = $this->product('Normal cocina', 'normal-cocina', 'cocina');
        $normalBarId = $this->product('Normal barra', 'normal-barra', 'barra');
        $manual = $this->manualPayload($otherId, 'Otro cocina', '9.00', 'cocina');
        $normalKitchen = $this->normalPayload($normalKitchenId);
        $normalBar = $this->normalPayload($normalBarId);
        $sent = [];
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')->twice()->andReturnUsing(function (string $payload, array $config) use (&$sent) {
            $sent[$config['test_destination']] = $payload;

            return new PrintResult(true, true, 'ok', 'usb');
        });
        $this->app->instance(RawEscPosPrinter::class, $printer);

        $this->store(
            $branch,
            $table,
            [$manual, $normalKitchen, $normalBar],
            [$manual, $normalKitchen, $normalBar]
        )->assertOk();

        $this->assertStringContainsString('OTRO COCINA', $sent['cocina']);
        $this->assertStringContainsString('NORMAL COCINA', $sent['cocina']);
        $this->assertStringNotContainsString('NORMAL BARRA', $sent['cocina']);
        $this->assertStringContainsString('NORMAL BARRA', $sent['barra']);
        $this->assertStringNotContainsString('OTRO COCINA', $sent['barra']);

        $areas = OrdenDetalleComponente::query()->orderBy('id')->pluck('area')->all();
        $this->assertSame(['cocina', 'cocina', 'barra'], $areas);
        $this->assertSame(3, OrdenDetalleComponente::query()->where('impreso', true)->count());
    }

    public function test_invalid_manual_area_is_rejected_without_order_component_or_print(): void
    {
        $branch = $this->branch('brumita');
        $table = $this->table($branch, 'empleados');
        $otherId = $this->product('Otro', 'otro', 'barra');
        $invalid = $this->manualPayload($otherId, 'Manipulado', '5.00', 'almacen');
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldNotReceive('send');
        $this->app->instance(RawEscPosPrinter::class, $printer);

        $this->store($branch, $table, [$invalid], [$invalid])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('productos');

        $this->assertSame(0, Orden::query()->count());
        $this->assertSame(0, OrdenDetalle::query()->count());
        $this->assertSame(0, OrdenDetalleComponente::query()->count());
    }

    public function test_changing_manual_area_before_success_removes_obsolete_component(): void
    {
        config([
            'impresoras.cocina.test_destination' => 'cocina',
            'impresoras.barra.test_destination' => 'barra',
        ]);
        $branch = $this->branch('bruma');
        $table = $this->table($branch, 'llevar');
        $otherId = $this->product('Otro', 'otro', 'cocina');
        $kitchen = $this->manualPayload($otherId, 'Cambio de área', '11.00', 'cocina');
        $bar = $this->manualPayload($otherId, 'Cambio de área', '11.00', 'barra');
        $destinations = [];
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')->twice()->andReturnUsing(function (string $payload, array $config) use (&$destinations) {
            $destinations[] = $config['test_destination'];

            return count($destinations) === 1
                ? new PrintResult(true, false, 'fallo simulado', error: 'simulado')
                : new PrintResult(true, true, 'ok', 'usb');
        });
        $this->app->instance(RawEscPosPrinter::class, $printer);

        $this->store($branch, $table, [$kitchen], [$kitchen])->assertOk();
        $this->assertSame('cocina', OrdenDetalleComponente::query()->sole()->area);
        $this->assertFalse((bool) OrdenDetalleComponente::query()->sole()->impreso);

        $this->store($branch, $table, [$bar], [$bar])->assertOk();

        $this->assertSame(['cocina', 'barra'], $destinations);
        $this->assertSame(1, OrdenDetalle::query()->count());
        $this->assertSame(1, OrdenDetalleComponente::query()->count());
        $component = OrdenDetalleComponente::query()->sole();
        $this->assertSame('barra', $component->area);
        $this->assertTrue((bool) $component->impreso);
    }

    private function store(Sucursal $branch, Mesa $table, array $products, array $newProducts)
    {
        return $this->postJson(route('pos.orden.store', ['sucursal' => $branch, 'mesa' => $table]), [
            'productos' => $products,
            'productosNuevos' => $newProducts,
        ]);
    }

    private function branch(string $code): Sucursal
    {
        return Sucursal::query()->where('codigo', $code)->sole();
    }

    private function table(Sucursal $branch, string $type, ?int $number = null): Mesa
    {
        $query = Mesa::query()->forSucursal($branch)->where('tipo', $type);

        if ($type === 'mesa') {
            $query->where('numero', $number);
        }

        return $query->sole();
    }

    private function product(string $name, string $sku, string $area): int
    {
        $categoryId = DB::table('categorias')->insertGetId([
            'slug' => 'test-'.$sku,
            'nombre' => 'Test '.$name,
            'tipo' => $area,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('productos')->insertGetId([
            'sku' => $sku,
            'nombre' => $name,
            'precio' => '20.00',
            'costo' => '10.00',
            'permite_solo' => true,
            'activo' => true,
            'categoria_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function manualPayload(int $productId, string $name, string $price, string $area): array
    {
        return [
            'id' => $productId,
            'cantidad' => 1,
            'modalidad' => 'solo',
            'es_otro_manual' => true,
            'nombre_personalizado' => $name,
            'area_preparacion' => $area,
            'precio_manual' => $price,
            'precio' => $price,
            'opciones' => [],
            'extras' => [],
        ];
    }

    private function normalPayload(int $productId): array
    {
        return [
            'id' => $productId,
            'cantidad' => 1,
            'modalidad' => 'solo',
            'opciones' => [],
            'extras' => [],
        ];
    }
}
