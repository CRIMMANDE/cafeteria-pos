<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\OrdenDetalleComponente;
use App\Models\Sucursal;
use App\Services\OrderPreparationComponentService;
use App\Services\ThermalPrinter\AreaCommandFormatter;
use App\Services\ThermalPrinter\AreaCommandPrintService;
use App\Services\ThermalPrinter\PrinterDestinationResolver;
use App\Services\ThermalPrinter\PrintResult;
use App\Services\ThermalPrinter\RawEscPosPrinter;
use App\Services\ThermalPrinter\ThermalPrinterService;
use App\Services\ThermalPrinter\TicketFormatter;
use Database\Seeders\SucursalesMesasSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class Stage5BranchPrintingTest extends TestCase
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

    public function test_destination_resolver_uses_branch_codes_for_tickets_and_preserves_area_destinations(): void
    {
        $resolver = app(PrinterDestinationResolver::class);
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');

        $this->assertSame('ventas', $resolver->ticketFor($this->order($bruma, $this->physicalTable($bruma, 1))));
        $this->assertSame('barra', $resolver->ticketFor($this->order($brumita, $this->physicalTable($brumita, 1))));
        $this->assertSame('cocina', $resolver->area('cocina'));
        $this->assertSame('barra', $resolver->area('barra'));

        $unknown = Sucursal::query()->create(['nombre' => 'OTRA', 'codigo' => 'otra', 'activa' => true]);
        $unknownTable = Mesa::query()->create(['sucursal_id' => $unknown->id, 'numero' => 1, 'tipo' => 'mesa']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No existe un destino de ticket válido para la sucursal otra.');
        $resolver->ticketFor($this->order($unknown, $unknownTable));
    }

    public function test_ticket_formatter_prints_branch_visible_table_folio_and_optional_reference(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaOrder = $this->order($bruma, $this->physicalTable($bruma, 1));
        $brumitaTable = $this->physicalTable($brumita, 1);
        $brumitaOrder = $this->order($brumita, $brumitaTable, 'Eduardo - Hidalgo 24');
        $formatter = new TicketFormatter($this->formatterConfig());

        $brumaPayload = $formatter->buildOrderTicket($brumaOrder);
        $this->assertStringContainsString('Sucursal: BRUMA', $brumaPayload);
        $this->assertStringContainsString((string) $brumaOrder->id, $brumaPayload);
        $this->assertMatchesRegularExpression('/Mesa:\s+1/', $brumaPayload);
        $this->assertStringNotContainsString('Referencia:', $brumaPayload);

        $brumitaPayload = $formatter->buildOrderTicket($brumitaOrder);
        $this->assertGreaterThan(9999, $brumitaTable->id);
        $this->assertStringContainsString('Sucursal: BRUMITA', $brumitaPayload);
        $this->assertStringContainsString('Referencia: Eduardo - Hidalgo 24', $brumitaPayload);
        $this->assertMatchesRegularExpression('/Mesa:\s+1/', $brumitaPayload);
        $this->assertStringNotContainsString('Mesa:'.$brumitaTable->id, $brumitaPayload);
        $this->assertStringNotContainsString('Mesa: '.$brumitaTable->id, $brumitaPayload);
    }

    public function test_ticket_formatter_labels_takeaway_and_employees_and_omits_blank_references(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $formatter = new TicketFormatter($this->formatterConfig());

        $brumaTakeaway = $this->order($bruma, $this->specialTable($bruma, 'llevar'), 'Eduardo');
        $brumitaTakeaway = $this->order($brumita, $this->specialTable($brumita, 'llevar'), 'María');
        $employee = $this->order($brumita, $this->specialTable($brumita, 'empleados'));

        $brumaPayload = $formatter->buildOrderTicket($brumaTakeaway);
        $this->assertStringContainsString('Sucursal: BRUMA', $brumaPayload);
        $this->assertStringContainsString('P/LLEVAR', $brumaPayload);
        $this->assertStringContainsString('Referencia: Eduardo', $brumaPayload);

        $brumitaPayload = $formatter->buildOrderTicket($brumitaTakeaway);
        $this->assertStringContainsString('Sucursal: BRUMITA', $brumitaPayload);
        $this->assertStringContainsString('P/LLEVAR', $brumitaPayload);
        $this->assertStringContainsString('Referencia: Maria', $brumitaPayload);

        $employeePayload = $formatter->buildOrderTicket($employee);
        $this->assertStringContainsString('EMPLEADOS', $employeePayload);
        $this->assertStringContainsString('Sucursal: BRUMITA', $employeePayload);
        $this->assertStringNotContainsString('Referencia:', $employeePayload);

        foreach ([null, '', '   '] as $reference) {
            $brumaTakeaway->update(['referencia' => $reference]);
            $this->assertStringNotContainsString('Referencia:', $formatter->buildOrderTicket($brumaTakeaway->fresh()));
        }
    }

    public function test_thermal_service_selects_ticket_printer_without_sending_real_output(): void
    {
        config([
            'impresoras.ventas.test_destination' => 'ventas',
            'impresoras.barra.test_destination' => 'barra',
            'impresoras.ventas.store_logo_path' => 'C:\\missing-stage5-logo.png',
        ]);

        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaOrder = $this->order($bruma, $this->physicalTable($bruma, 1));
        $brumitaOrder = $this->order($brumita, $this->physicalTable($brumita, 1), 'María');
        $printer = Mockery::mock(RawEscPosPrinter::class);

        $printer->shouldReceive('send')->once()->ordered()->withArgs(
            fn (string $payload, array $config, ?string $fallback) => str_contains($payload, 'Sucursal: BRUMA')
                && $config['test_destination'] === 'ventas'
                && $fallback === route('pos.orden.printable', ['sucursal' => $bruma, 'orden' => $brumaOrder])
        )->andReturn(new PrintResult(true, true, 'ok'));
        $printer->shouldReceive('send')->once()->ordered()->withArgs(
            fn (string $payload, array $config, ?string $fallback) => str_contains($payload, 'Sucursal: BRUMITA')
                && str_contains($payload, 'Referencia: Maria')
                && $config['test_destination'] === 'barra'
                && $fallback === route('pos.orden.printable', ['sucursal' => $brumita, 'orden' => $brumitaOrder])
        )->andReturn(new PrintResult(true, true, 'ok'));

        $service = new ThermalPrinterService($printer, app(PrinterDestinationResolver::class));

        $this->assertTrue($service->printOrder($brumaOrder)->printed);
        $this->assertTrue($service->printOrder($brumitaOrder)->printed);
        $this->assertSame('abierta', $brumaOrder->fresh()->estado);
        $this->assertSame('abierta', $brumitaOrder->fresh()->estado);
        $this->assertSame('María', $brumitaOrder->fresh()->referencia);
        $this->assertSame(0, DB::table('pagos')->count());
    }

    public function test_command_format_and_destinations_keep_area_routing_and_do_not_mark_failed_components(): void
    {
        config([
            'impresoras.cocina.test_destination' => 'cocina',
            'impresoras.barra.test_destination' => 'barra',
        ]);

        $brumita = $this->branch('brumita');
        $order = $this->order($brumita, $this->specialTable($brumita, 'llevar'), 'Calle Hidalgo 18');
        [$detail, $kitchen, $bar] = $this->addComponents($order);
        $printer = Mockery::mock(RawEscPosPrinter::class);

        $printer->shouldReceive('send')->once()->ordered()->withArgs(
            fn (string $payload, array $config, ?string $fallback) => str_contains($payload, 'Sucursal: BRUMITA')
                && str_contains($payload, 'Referencia: Calle Hidalgo 18')
                && $config['test_destination'] === 'cocina'
                && $fallback === route('area.order.printable', ['area' => 'cocina', 'orden' => $order])
        )->andReturn(new PrintResult(true, false, 'falló', error: 'simulado'));
        $printer->shouldReceive('send')->once()->ordered()->withArgs(
            fn (string $payload, array $config, ?string $fallback) => str_contains($payload, 'Sucursal: BRUMITA')
                && str_contains($payload, 'P/LLEVAR')
                && $config['test_destination'] === 'barra'
                && $fallback === route('area.order.printable', ['area' => 'barra', 'orden' => $order])
        )->andReturn(new PrintResult(true, true, 'ok'));

        $service = new AreaCommandPrintService(
            $printer,
            app(OrderPreparationComponentService::class),
            app(PrinterDestinationResolver::class)
        );

        $failedResult = $service->printNewItems($order, 'cocina', [$detail->id]);
        $this->assertFalse($failedResult->printed);
        $this->assertArrayNotHasKey('error', $failedResult->toPublicArray());
        $this->assertFalse((bool) $kitchen->fresh()->impreso);
        $this->assertTrue($service->printNewItems($order, 'barra', [$detail->id])->printed);
        $this->assertTrue((bool) $bar->fresh()->impreso);
        $this->assertFalse((bool) $kitchen->fresh()->impreso);

        $formatted = (new AreaCommandFormatter($this->formatterConfig()))->build(
            $order->fresh(['sucursal', 'mesa']),
            collect([['descripcion' => 'PRODUCTO', 'cantidad' => 1, 'detalle' => []]]),
            'cocina',
            $service->formatMesaLabelForOrder($order)
        );
        $this->assertStringContainsString('Sucursal: BRUMITA', $formatted);
        $this->assertStringContainsString('Referencia: Calle Hidalgo 18', $formatted);

        $order->update(['referencia' => '   ']);
        $withoutReference = (new AreaCommandFormatter($this->formatterConfig()))->build(
            $order->fresh(['sucursal', 'mesa']),
            collect([['descripcion' => 'PRODUCTO', 'cantidad' => 1, 'detalle' => []]]),
            'cocina',
            $service->formatMesaLabelForOrder($order)
        );
        $this->assertStringNotContainsString('Referencia:', $withoutReference);
    }

    public function test_html_fallback_uses_order_id_visible_table_and_rejects_cross_branch_access(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumitaTable = $this->physicalTable($brumita, 1);
        $order = $this->order($brumita, $brumitaTable, 'María');
        $url = route('pos.orden.printable', ['sucursal' => $brumita, 'orden' => $order]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Sucursal: BRUMITA')
            ->assertSee('Referencia: María')
            ->assertSee('Pedido: Mesa 1')
            ->assertSee('Ticket: #'.$order->id)
            ->assertDontSee('Mesa '.$brumitaTable->id);

        $this->get(route('pos.orden.printable', ['sucursal' => $bruma, 'orden' => $order]))
            ->assertNotFound();

        $this->get(route('legacy.orden.printable', ['mesa' => $brumitaTable]))
            ->assertRedirect($url);
    }

    public function test_area_panel_and_fallbacks_identify_same_numbered_tables_by_order(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaOrder = $this->order($bruma, $this->physicalTable($bruma, 1), 'Bruma ref');
        $brumitaOrder = $this->order($brumita, $this->physicalTable($brumita, 1), 'Brumita ref');
        $this->addComponents($brumaOrder);
        $this->addComponents($brumitaOrder);

        $panel = $this->get('/cocina');
        $panel->assertOk()
            ->assertSee('Sucursal: BRUMA')
            ->assertSee('Sucursal: BRUMITA')
            ->assertSee(route('area.order.reprint', ['area' => 'cocina', 'orden' => $brumaOrder]), false)
            ->assertSee(route('area.order.reprint', ['area' => 'cocina', 'orden' => $brumitaOrder]), false)
            ->assertDontSee('/cocina/mesa/', false);

        $this->get(route('area.order.printable', ['area' => 'cocina', 'orden' => $brumaOrder]))
            ->assertOk()
            ->assertSee('MESA 1 #'.$brumaOrder->id)
            ->assertSee('Sucursal: BRUMA')
            ->assertSee('Referencia: Bruma ref')
            ->assertDontSee('Brumita ref');

        $this->get(route('area.order.printable', ['area' => 'cocina', 'orden' => $brumitaOrder]))
            ->assertOk()
            ->assertSee('MESA 1 #'.$brumitaOrder->id)
            ->assertSee('Sucursal: BRUMITA')
            ->assertSee('Referencia: Brumita ref')
            ->assertDontSee('Bruma ref');

        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')->once()->withArgs(
            fn (string $payload, array $config, ?string $fallback) => str_contains($payload, 'Sucursal: BRUMA')
                && str_contains($payload, '#'.$brumaOrder->id)
                && $fallback === route('area.order.printable', ['area' => 'cocina', 'orden' => $brumaOrder])
        )->andReturn(new PrintResult(true, false, 'Fallo controlado', error: 'detalle interno simulado'));
        $this->app->instance(RawEscPosPrinter::class, $printer);

        $this->postJson(route('area.order.reprint', ['area' => 'cocina', 'orden' => $brumaOrder]))
            ->assertOk()
            ->assertJsonPath('orden_id', $brumaOrder->id)
            ->assertJsonPath('printed', false)
            ->assertJsonMissingPath('error');
    }

    private function branch(string $code): Sucursal
    {
        return Sucursal::query()->where('codigo', $code)->sole();
    }

    private function physicalTable(Sucursal $branch, int $number): Mesa
    {
        return Mesa::query()->forSucursal($branch)->where('tipo', 'mesa')->where('numero', $number)->sole();
    }

    private function specialTable(Sucursal $branch, string $type): Mesa
    {
        return Mesa::query()->forSucursal($branch)->where('tipo', $type)->whereNull('numero')->sole();
    }

    private function order(Sucursal $branch, Mesa $table, ?string $reference = null): Orden
    {
        return Orden::query()->create([
            'sucursal_id' => $branch->id,
            'mesa_id' => $table->id,
            'tipo' => $table->tipo,
            'referencia' => $reference,
            'estado' => 'abierta',
            'total' => 0,
            'desc_empleado' => $table->tipo === 'empleados',
        ]);
    }

    private function formatterConfig(): array
    {
        return [
            'characters_per_line' => 48,
            'store_logo_path' => 'C:\\missing-stage5-logo.png',
            'cut_at_end' => false,
            'open_drawer' => false,
        ];
    }

    private function addComponents(Orden $order): array
    {
        $categoryId = DB::table('categorias')->insertGetId([
            'nombre' => 'Etapa 5 '.$order->id,
            'tipo' => 'cocina',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('productos')->insertGetId([
            'nombre' => 'Producto impresión '.$order->id,
            'precio' => 20,
            'costo' => 10,
            'categoria_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $detail = OrdenDetalle::query()->create([
            'orden_id' => $order->id,
            'producto_id' => $productId,
            'cantidad' => 1,
            'modalidad' => 'solo',
            'precio_base' => 20,
            'incremento_modalidad' => 0,
            'precio' => 20,
            'impreso' => false,
        ]);
        $kitchen = OrdenDetalleComponente::query()->create([
            'orden_detalle_id' => $detail->id,
            'area' => 'cocina',
            'descripcion' => 'PRODUCTO COCINA',
            'cantidad' => 1,
            'impreso' => false,
        ]);
        $bar = OrdenDetalleComponente::query()->create([
            'orden_detalle_id' => $detail->id,
            'area' => 'barra',
            'descripcion' => 'PRODUCTO BARRA',
            'cantidad' => 1,
            'impreso' => false,
        ]);

        return [$detail, $kitchen, $bar];
    }
}
