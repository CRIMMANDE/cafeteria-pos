<?php

namespace Tests\Feature;

use App\Models\Orden;
use App\Models\Sucursal;
use App\Services\SalesCut\SalesCutService;
use App\Services\SalesCut\SalesCutTicketFormatter;
use App\Services\ThermalPrinter\PrinterDestinationResolver;
use App\Services\ThermalPrinter\PrintResult;
use App\Services\ThermalPrinter\RawEscPosPrinter;
use Carbon\Carbon;
use Database\Seeders\SucursalesMesasSeeder;
use Mockery;
use Tests\TestCase;

class Stage7BranchSalesCutTest extends TestCase
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

    public function test_form_defaults_to_all_and_preserves_current_date_timezone_rules(): void
    {
        $this->order('bruma', '10.00', 'efectivo', 'pagada', '2026-08-06 10:00:00');
        $this->order('bruma', '20.00', 'efectivo', 'pagada', '2026-08-06 12:00:59');
        $this->order('bruma', '40.00', 'efectivo', 'pagada', '2026-08-06 09:59:59');
        $this->order('bruma', '80.00', 'efectivo', 'pagada', '2026-08-06 12:01:00');

        $this->get('/admin/corte-ventas')
            ->assertOk()
            ->assertSee('name="sucursal"', false)
            ->assertSee('value="bruma"', false)
            ->assertSee('value="brumita"', false)
            ->assertSee('value="todas" selected', false);

        $response = $this->get('/admin/corte-ventas?inicio=2026-08-06T10%3A00&fin=2026-08-06T12%3A00&sucursal=bruma')
            ->assertOk()
            ->assertSee('Sucursal: <strong>BRUMA</strong>', false);

        $summary = $response->viewData('resumen');
        $this->assertSame(0, $summary['inicio']->second);
        $this->assertSame(59, $summary['fin']->second);
        $this->assertSame('America/Mexico_City', $summary['inicio']->timezoneName);
        $this->assertSame(2, $summary['ordenes_count']);
        $this->assertSame(30.0, $summary['subtotal']);
    }

    public function test_bruma_cut_counts_only_paid_branch_orders_and_cash_fields_do_not_affect_sales(): void
    {
        $cash = $this->order('bruma', '185.00', 'efectivo');
        $cash->pagos()->create([
            'monto' => '185.00',
            'monto_recibido' => '500.00',
            'cambio' => '315.00',
            'metodo' => 'efectivo',
        ]);
        $this->order('bruma', '100.00', 'tarjeta');
        $this->order('bruma', '900.00', 'efectivo', 'abierta');
        $this->order('bruma', '800.00', 'tarjeta', 'cancelada');
        $this->order('brumita', '700.00', 'efectivo');

        $summary = $this->summary('bruma');

        $this->assertSame('bruma', $summary['filtro']);
        $this->assertSame('BRUMA', $summary['sucursal_nombre']);
        $this->assertSame(['bruma'], array_keys($summary['sucursales']));
        $this->assertSame(2, $summary['ordenes_count']);
        $this->assertSame(285.0, $summary['subtotal']);
        $this->assertSame(185.0, $summary['parcial_efectivo']);
        $this->assertSame(100.0, $summary['parcial_tarjeta_bruto']);
        $this->assertSame(95.0, $summary['parcial_tarjeta_neto']);
        $this->assertSame(280.0, $summary['total_final']);
    }

    public function test_brumita_cut_is_isolated_and_keeps_five_percent_card_commission(): void
    {
        $this->order('bruma', '500.00', 'efectivo');
        $this->order('brumita', '40.00', 'efectivo');
        $this->order('brumita', '100.00', 'tarjeta');

        $summary = $this->summary('brumita');

        $this->assertSame('BRUMITA', $summary['sucursal_nombre']);
        $this->assertSame(['brumita'], array_keys($summary['sucursales']));
        $this->assertSame(2, $summary['ordenes_count']);
        $this->assertSame(140.0, $summary['subtotal']);
        $this->assertSame(40.0, $summary['parcial_efectivo']);
        $this->assertSame(100.0, $summary['parcial_tarjeta_bruto']);
        $this->assertSame(95.0, $summary['parcial_tarjeta_neto']);
        $this->assertSame(135.0, $summary['total_final']);
    }

    public function test_all_cut_contains_branch_blocks_and_safe_general_totals(): void
    {
        $this->order('bruma', '25.00', 'efectivo');
        $this->order('bruma', '100.00', 'tarjeta');
        $this->order('brumita', '50.00', 'efectivo');
        $this->order('brumita', '200.00', 'tarjeta');

        $summary = $this->summary('todas');

        $this->assertSame('TODAS', $summary['sucursal_nombre']);
        $this->assertSame(['bruma', 'brumita'], array_keys($summary['sucursales']));
        $this->assertSame(2, $summary['sucursales']['bruma']['ordenes_count']);
        $this->assertSame(120.0, $summary['sucursales']['bruma']['total_final']);
        $this->assertSame(2, $summary['sucursales']['brumita']['ordenes_count']);
        $this->assertSame(240.0, $summary['sucursales']['brumita']['total_final']);
        $this->assertSame(4, $summary['ordenes_count']);
        $this->assertSame(375.0, $summary['subtotal']);
        $this->assertSame(75.0, $summary['parcial_efectivo']);
        $this->assertSame(300.0, $summary['parcial_tarjeta_bruto']);
        $this->assertSame(285.0, $summary['parcial_tarjeta_neto']);
        $this->assertSame(360.0, $summary['total_final']);

        $this->get('/admin/corte-ventas?inicio=2026-08-06T10%3A00&fin=2026-08-06T12%3A00&sucursal=todas')
            ->assertOk()
            ->assertSee('BRUMA')
            ->assertSee('BRUMITA')
            ->assertSee('TOTAL GENERAL');
    }

    public function test_valid_print_filters_resolve_destination_and_send_one_cut_each(): void
    {
        config([
            'impresoras.cocina.usb_printer_name' => 'CUT-COCINA',
            'impresoras.barra.usb_printer_name' => 'CUT-BARRA',
        ]);

        $destinations = [];
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')
            ->times(3)
            ->andReturnUsing(function (string $payload, array $config) use (&$destinations) {
                $this->assertStringContainsString('CORTE DE VENTAS', $payload);
                $destinations[] = $config['usb_printer_name'];

                return new PrintResult(true, true, 'ok', 'usb');
            });
        $this->app->instance(RawEscPosPrinter::class, $printer);

        foreach (['bruma', 'brumita', 'todas'] as $filter) {
            $this->post('/admin/corte-ventas/imprimir', $this->payload($filter))
                ->assertRedirectContains('sucursal='.$filter);
        }

        $this->assertSame(['CUT-COCINA', 'CUT-BARRA', 'CUT-COCINA'], $destinations);
    }

    public function test_printed_single_and_all_formats_show_required_blocks_without_cash_received_data(): void
    {
        $this->order('bruma', '185.00', 'efectivo');
        $this->order('brumita', '100.00', 'tarjeta');
        $formatter = new SalesCutTicketFormatter(['characters_per_line' => 48, 'cut_at_end' => false]);

        $bruma = $formatter->build($this->summary('bruma'));
        $this->assertStringContainsString('Sucursal: BRUMA', $bruma);
        $this->assertStringContainsString('Ordenes:', $bruma);
        $this->assertStringContainsString('Tarjeta bruto:', $bruma);
        $this->assertStringContainsString('Tarjeta neto:', $bruma);
        $this->assertStringNotContainsString('BRUMITA', $bruma);
        $this->assertStringNotContainsString('monto_recibido', $bruma);
        $this->assertStringNotContainsString('Cambio', $bruma);

        $all = $formatter->build($this->summary('todas'));
        $this->assertStringContainsString('Sucursal: TODAS', $all);
        $this->assertStringContainsString('BRUMA', $all);
        $this->assertStringContainsString('BRUMITA', $all);
        $this->assertStringContainsString('TOTAL GENERAL', $all);
    }

    public function test_excel_dataset_respects_each_filter_and_adds_visible_branch_column(): void
    {
        $bruma = $this->order('bruma', '10.00', 'efectivo');
        $brumita = $this->order('brumita', '20.00', 'tarjeta');
        $this->order('brumita', '30.00', 'efectivo', 'abierta');
        $service = app(SalesCutService::class);

        foreach ([
            'bruma' => [$bruma->id, 'BRUMA'],
            'brumita' => [$brumita->id, 'BRUMITA'],
        ] as $filter => [$expectedId, $expectedName]) {
            $dataset = $service->exportRows($this->start(), $this->end(), $filter);
            $branchColumn = array_search('Sucursal', $dataset['columns'], true);
            $idColumn = array_search('id', $dataset['columns'], true);

            $this->assertNotFalse($branchColumn);
            $this->assertCount(1, $dataset['rows']);
            $this->assertSame((string) $expectedId, $dataset['rows'][0][$idColumn]);
            $this->assertSame($expectedName, $dataset['rows'][0][$branchColumn]);
        }

        $all = $service->exportRows($this->start(), $this->end(), 'todas');
        $branchColumn = array_search('Sucursal', $all['columns'], true);
        $this->assertCount(2, $all['rows']);
        $this->assertSame(['BRUMA', 'BRUMITA'], array_column($all['rows'], $branchColumn));
        $this->assertNotContains('monto_recibido', $all['columns']);
        $this->assertNotContains('cambio', $all['columns']);
    }

    public function test_invalid_or_missing_filter_returns_422_without_query_print_or_export(): void
    {
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldNotReceive('send');
        $this->app->instance(RawEscPosPrinter::class, $printer);

        $this->postJson('/admin/corte-ventas/imprimir', $this->payload('inventada'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sucursal');

        $this->postJson('/admin/corte-ventas/excel', $this->payload('9'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sucursal');

        $payload = $this->payload('todas');
        unset($payload['sucursal']);
        $this->postJson('/admin/corte-ventas/imprimir', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sucursal');

        $this->getJson('/admin/corte-ventas?sucursal=otra')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sucursal');
    }

    public function test_empty_valid_cut_returns_zeroes_and_printer_routing_regression_is_preserved(): void
    {
        $summary = $this->summary('todas');

        $this->assertSame(0, $summary['ordenes_count']);
        $this->assertSame(0.0, $summary['subtotal']);
        $this->assertSame(0.0, $summary['parcial_efectivo']);
        $this->assertSame(0.0, $summary['parcial_tarjeta_bruto']);
        $this->assertSame(0.0, $summary['parcial_tarjeta_neto']);
        $this->assertSame(0.0, $summary['total_final']);
        $this->assertSame(0, $summary['sucursales']['bruma']['ordenes_count']);
        $this->assertSame(0, $summary['sucursales']['brumita']['ordenes_count']);

        $resolver = app(PrinterDestinationResolver::class);
        $brumaOrder = $this->order('bruma', '1.00', 'efectivo');
        $brumitaOrder = $this->order('brumita', '1.00', 'efectivo');

        $this->assertSame('ventas', $resolver->ticketFor($brumaOrder));
        $this->assertSame('barra', $resolver->ticketFor($brumitaOrder));
        $this->assertSame('cocina', $resolver->salesCutFor('bruma'));
        $this->assertSame('barra', $resolver->salesCutFor('brumita'));
        $this->assertSame('cocina', $resolver->salesCutFor('todas'));
    }

    private function summary(string $filter): array
    {
        return app(SalesCutService::class)->summary($this->start(), $this->end(), $filter);
    }

    private function order(
        string $branchCode,
        string $total,
        ?string $method,
        string $state = 'pagada',
        string $createdAt = '2026-08-06 11:00:00'
    ): Orden {
        $branch = Sucursal::query()->where('codigo', $branchCode)->firstOrFail();
        $order = Orden::query()->create([
            'sucursal_id' => $branch->id,
            'mesa_id' => null,
            'tipo' => 'mesa',
            'referencia' => null,
            'total' => $total,
            'estado' => $state,
            'desc_empleado' => false,
            'metodo_pago' => $method,
        ]);

        $timestamp = Carbon::parse($createdAt, config('app.timezone'));
        $order->timestamps = false;
        $order->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->save();

        return $order->fresh();
    }

    private function payload(string $filter): array
    {
        return [
            'sucursal' => $filter,
            'inicio' => '2026-08-06T10:00',
            'fin' => '2026-08-06T12:00',
        ];
    }

    private function start(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', '2026-08-06 10:00:00', config('app.timezone'));
    }

    private function end(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', '2026-08-06 12:00:59', config('app.timezone'));
    }
}
