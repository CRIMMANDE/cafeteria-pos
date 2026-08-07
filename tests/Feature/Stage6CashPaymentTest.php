<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Pago;
use App\Models\Sucursal;
use App\Services\ThermalPrinter\PrinterDestinationResolver;
use App\Services\ThermalPrinter\PrintResult;
use App\Services\ThermalPrinter\RawEscPosPrinter;
use App\Services\ThermalPrinter\ThermalPrinterService;
use App\Services\ThermalPrinter\TicketFormatter;
use Database\Seeders\SucursalesMesasSeeder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class Stage6CashPaymentTest extends TestCase
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

    public function test_cash_exact_change_and_decimal_cases_are_persisted_in_cents(): void
    {
        $this->assertCloseDoesNotPrint();
        $bruma = $this->branch('bruma');
        $cases = [
            [1, '185.00', '185.00', '0.00'],
            [2, '185.00', '200.00', '15.00'],
            [3, '185.50', '200.00', '14.50'],
            [4, '185.50', '185.50', '0.00'],
        ];

        foreach ($cases as [$tableNumber, $total, $received, $change]) {
            $table = $this->physicalTable($bruma, $tableNumber);
            $order = $this->openOrder($bruma, $table);
            $productId = $this->product($total, $total);

            $this->close($bruma, $table, $productId, [
                'metodo_pago' => 'efectivo',
                'monto_recibido' => $received,
            ])->assertOk()
                ->assertJsonPath('redirect_url', route('pos.mesas.index', ['sucursal' => $bruma]))
                ->assertJsonMissingPath('printed')
                ->assertJsonMissingPath('fallback_url');

            $payment = $order->fresh()->pagos()->sole();
            $this->assertSame('pagada', $order->fresh()->estado);
            $this->assertSame('efectivo', $order->fresh()->metodo_pago);
            $this->assertSame($total, $payment->monto);
            $this->assertSame($received, $payment->monto_recibido);
            $this->assertSame($change, $payment->cambio);
        }
    }

    public function test_cash_missing_insufficient_or_malformed_never_closes_or_creates_payment(): void
    {
        $bruma = $this->branch('bruma');
        $invalidValues = [
            null,
            '180.00',
            '200.001',
            '-1',
            'texto',
            'NaN',
            'Infinity',
            '1e3',
            '1,000.00',
        ];

        foreach ($invalidValues as $index => $received) {
            $table = $this->physicalTable($bruma, $index + 1);
            $order = $this->openOrder($bruma, $table);
            $productId = $this->product('185.00', '185.00');
            $payment = ['metodo_pago' => 'efectivo'];

            if ($received !== null) {
                $payment['monto_recibido'] = $received;
            }

            $this->close($bruma, $table, $productId, $payment)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('monto_recibido');

            $this->assertSame('abierta', $order->fresh()->estado);
            $this->assertNull($order->fresh()->metodo_pago);
            $this->assertSame(0, $order->pagos()->count());
        }
    }

    public function test_card_preserves_current_flow_and_ignores_manipulated_cash_amount(): void
    {
        $this->assertCloseDoesNotPrint();
        $bruma = $this->branch('bruma');

        foreach ([[1, []], [2, ['monto_recibido' => '9999.00', 'cambio' => '9814.00']]] as [$number, $extra]) {
            $table = $this->physicalTable($bruma, $number);
            $order = $this->openOrder($bruma, $table);
            $productId = $this->product('185.00', '185.00');

            $this->close($bruma, $table, $productId, [
                'metodo_pago' => 'tarjeta',
                ...$extra,
            ])->assertOk()
                ->assertJsonMissingPath('printed')
                ->assertJsonMissingPath('fallback_url');

            $payment = $order->fresh()->pagos()->sole();
            $this->assertSame('pagada', $order->fresh()->estado);
            $this->assertSame('tarjeta', $payment->metodo);
            $this->assertSame('185.00', $payment->monto);
            $this->assertNull($payment->monto_recibido);
            $this->assertNull($payment->cambio);
        }
    }

    public function test_branch_and_same_visible_table_are_isolated_during_close(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaTable = $this->physicalTable($bruma, 1);
        $brumitaTable = $this->physicalTable($brumita, 1);
        $brumaOrder = $this->openOrder($bruma, $brumaTable);
        $brumitaOrder = $this->openOrder($brumita, $brumitaTable);
        $productId = $this->product('50.00', '20.00');

        $this->close($brumita, $brumitaTable, $productId, [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '100.00',
        ])->assertOk();

        $this->assertSame('abierta', $brumaOrder->fresh()->estado);
        $this->assertSame(0, $brumaOrder->pagos()->count());
        $this->assertSame('pagada', $brumitaOrder->fresh()->estado);
        $this->assertSame('50.00', $brumitaOrder->pagos()->sole()->cambio);

        $this->postJson(route('pos.orden.close', ['sucursal' => $bruma, 'mesa' => $brumitaTable]), [
            'productos' => $this->productsPayload($productId),
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '100.00',
        ])->assertNotFound();

        $this->assertSame('abierta', $brumaOrder->fresh()->estado);
        $this->assertSame(0, $brumaOrder->pagos()->count());
    }

    public function test_takeaway_reference_and_employee_cost_survive_cash_close(): void
    {
        $bruma = $this->branch('bruma');
        $takeaway = $this->specialTable($bruma, 'llevar');
        $employee = $this->specialTable($bruma, 'empleados');
        $takeawayOrder = $this->openOrder($bruma, $takeaway, 'Eduardo');
        $employeeOrder = $this->openOrder($bruma, $employee);
        $productId = $this->product('185.00', '50.00');

        $this->close($bruma, $takeaway, $productId, [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '200.00',
        ])->assertOk();
        $this->close($bruma, $employee, $productId, [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '100.00',
        ])->assertOk();

        $this->assertSame('Eduardo', $takeawayOrder->fresh()->referencia);
        $this->assertSame('185.00', $takeawayOrder->pagos()->sole()->monto);
        $this->assertSame('15.00', $takeawayOrder->pagos()->sole()->cambio);
        $this->assertSame('50.00', $employeeOrder->pagos()->sole()->monto);
        $this->assertSame('50.00', $employeeOrder->pagos()->sole()->cambio);
    }

    public function test_double_close_creates_one_payment_and_never_prints_even_with_manipulated_flag(): void
    {
        $this->assertCloseDoesNotPrint();
        $bruma = $this->branch('bruma');
        $table = $this->physicalTable($bruma, 1);
        $order = $this->openOrder($bruma, $table);
        $productId = $this->product('185.00', '185.00');
        $payload = [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '200.00',
            'imprimir_ticket' => true,
        ];

        $this->close($bruma, $table, $productId, $payload)
            ->assertOk()
            ->assertJsonMissingPath('printed')
            ->assertJsonMissingPath('fallback_url');

        $this->close($bruma, $table, $productId, $payload)
            ->assertConflict()
            ->assertJsonPath('message', 'La cuenta ya fue cerrada.');

        $payment = $order->fresh()->pagos()->sole();
        $this->assertSame('pagada', $order->fresh()->estado);
        $this->assertSame(1, $order->pagos()->count());
        $this->assertSame('200.00', $payment->monto_recibido);
        $this->assertSame('15.00', $payment->cambio);
    }

    public function test_independent_ticket_print_can_repeat_for_both_branches_without_closing_or_paying(): void
    {
        config([
            'impresoras.ventas.test_destination' => 'ventas',
            'impresoras.barra.test_destination' => 'barra',
        ]);

        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $cases = [
            [$bruma, $this->physicalTable($bruma, 1)],
            [$brumita, $this->physicalTable($brumita, 1)],
        ];
        $productId = $this->product('185.00', '185.00');
        $destinations = [];
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')->times(4)->withArgs(
            function (string $payload, array $config, ?string $fallback) use (&$destinations): bool {
                $destinations[] = $config['test_destination'] ?? null;

                return str_contains($payload, 'Sucursal:') && $fallback !== null;
            }
        )->andReturn(new PrintResult(true, true, 'ok'));
        $this->app->instance(RawEscPosPrinter::class, $printer);

        foreach ($cases as [$branch, $table]) {
            $order = $this->openOrder($branch, $table);

            for ($copy = 0; $copy < 2; $copy++) {
                $this->postJson(route('pos.orden.print-ticket', ['sucursal' => $branch, 'mesa' => $table]), [
                    'productos' => $this->productsPayload($productId),
                ])->assertOk()
                    ->assertJsonPath('printed', true)
                    ->assertJsonPath('redirect_url', route('pos.mesas.index', ['sucursal' => $branch]));
            }

            $this->assertSame('abierta', $order->fresh()->estado);
            $this->assertNull($order->fresh()->metodo_pago);
            $this->assertSame(0, $order->pagos()->count());
            $this->assertEquals(185.0, $order->fresh()->total);
        }

        $this->assertSame(['ventas', 'ventas', 'barra', 'barra'], $destinations);
    }

    public function test_manual_ticket_print_failure_keeps_open_order_and_returns_existing_fallback(): void
    {
        $bruma = $this->branch('bruma');
        $table = $this->physicalTable($bruma, 1);
        $order = $this->openOrder($bruma, $table);
        $productId = $this->product('185.00', '185.00');
        $printer = Mockery::mock(RawEscPosPrinter::class);
        $printer->shouldReceive('send')->once()->withArgs(
            fn (string $payload, array $config, ?string $fallback): bool => $fallback === route('pos.orden.printable', [
                'sucursal' => $bruma,
                'orden' => $order,
            ])
        )->andReturnUsing(
            fn (string $payload, array $config, ?string $fallback) => new PrintResult(
                true,
                false,
                'Fallo de impresión simulado',
                fallbackUrl: $fallback,
                error: 'interno'
            )
        );
        $this->app->instance(RawEscPosPrinter::class, $printer);

        $this->postJson(route('pos.orden.print-ticket', ['sucursal' => $bruma, 'mesa' => $table]), [
            'productos' => $this->productsPayload($productId),
        ])->assertOk()
            ->assertJsonPath('printed', false)
            ->assertJsonPath('fallback_url', route('pos.orden.printable', ['sucursal' => $bruma, 'orden' => $order]))
            ->assertJsonMissingPath('error');

        $this->assertSame('abierta', $order->fresh()->estado);
        $this->assertNull($order->fresh()->metodo_pago);
        $this->assertSame(0, $order->pagos()->count());
    }

    public function test_recovery_removes_cash_data_and_requires_a_new_received_amount(): void
    {
        $brumita = $this->branch('brumita');
        $takeaway = $this->specialTable($brumita, 'llevar');
        $order = $this->openOrder($brumita, $takeaway, 'María');
        $productId = $this->product('185.00', '185.00');

        $this->close($brumita, $takeaway, $productId, [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '200.00',
        ])->assertOk();
        $this->assertSame('15.00', $order->pagos()->sole()->cambio);

        $this->postJson(route('pos.orden.recover', ['sucursal' => $brumita]), ['folio' => $order->id])
            ->assertOk();

        $this->assertSame('abierta', $order->fresh()->estado);
        $this->assertSame('María', $order->fresh()->referencia);
        $this->assertSame(0, $order->pagos()->count());

        $this->close($brumita, $takeaway, $productId, ['metodo_pago' => 'efectivo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('monto_recibido');
        $this->assertSame('abierta', $order->fresh()->estado);
        $this->assertSame(0, $order->pagos()->count());

        $this->close($brumita, $takeaway, $productId, [
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '190.00',
        ])->assertOk();

        $newPayment = $order->fresh()->pagos()->sole();
        $this->assertSame('190.00', $newPayment->monto_recibido);
        $this->assertSame('5.00', $newPayment->cambio);
        $this->assertSame('María', $order->fresh()->referencia);
    }

    public function test_escpos_and_html_tickets_show_only_persisted_cash_information(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $formatter = new TicketFormatter($this->formatterConfig());

        $cashOrder = $this->paidOrderWithPayment($bruma, $this->physicalTable($bruma, 1), 'efectivo', '200.00', '15.00', 'Eduardo');
        $exactOrder = $this->paidOrderWithPayment($bruma, $this->physicalTable($bruma, 2), 'efectivo', '185.00', '0.00');
        $cardOrder = $this->paidOrderWithPayment($brumita, $this->physicalTable($brumita, 1), 'tarjeta');
        $historicalOrder = $this->paidOrderWithPayment($brumita, $this->physicalTable($brumita, 2), 'efectivo');

        $cashTicket = $formatter->buildOrderTicket($cashOrder);
        $this->assertStringContainsString('Sucursal: BRUMA', $cashTicket);
        $this->assertStringContainsString('Referencia: Eduardo', $cashTicket);
        $this->assertMatchesRegularExpression('/Recibido: \$\s+200\.00/', $cashTicket);
        $this->assertMatchesRegularExpression('/Cambio: \$\s+15\.00/', $cashTicket);

        $this->assertMatchesRegularExpression('/Cambio: \$\s+0\.00/', $formatter->buildOrderTicket($exactOrder));

        foreach ([$cardOrder, $historicalOrder] as $order) {
            $ticket = $formatter->buildOrderTicket($order);
            $this->assertStringNotContainsString('Recibido:', $ticket);
            $this->assertStringNotContainsString('Cambio:', $ticket);
        }

        $this->get(route('pos.orden.printable', ['sucursal' => $bruma, 'orden' => $cashOrder]))
            ->assertOk()
            ->assertSee('Sucursal: BRUMA')
            ->assertSee('Referencia: Eduardo')
            ->assertSee('Recibido')
            ->assertSee('$200.00')
            ->assertSee('Cambio')
            ->assertSee('$15.00');
        $this->get(route('pos.orden.printable', ['sucursal' => $brumita, 'orden' => $cardOrder]))
            ->assertOk()
            ->assertDontSee('Recibido')
            ->assertDontSee('Cambio');
        $this->get(route('pos.orden.printable', ['sucursal' => $brumita, 'orden' => $historicalOrder]))
            ->assertOk()
            ->assertDontSee('Recibido')
            ->assertDontSee('Cambio');

        $resolver = app(PrinterDestinationResolver::class);
        $this->assertSame('ventas', $resolver->ticketFor($cashOrder));
        $this->assertSame('barra', $resolver->ticketFor($cardOrder));
    }

    public function test_payment_modal_exposes_cash_feedback_and_double_submit_protection(): void
    {
        $bruma = $this->branch('bruma');

        $this->get(route('pos.mesas.show', ['sucursal' => $bruma, 'mesa' => $this->physicalTable($bruma, 1)]))
            ->assertOk()
            ->assertSee('Total: $')
            ->assertSee('Monto recibido')
            ->assertSee('inputmode="decimal"', false)
            ->assertSee('Cambio: —')
            ->assertSee('Monto insuficiente')
            ->assertSee('cierreEnProceso', false)
            ->assertSee('Procesando...')
            ->assertDontSee('imprimir_ticket: true', false)
            ->assertSee('id="imprimir-cuenta"', false)
            ->assertSee('fetch(rutasPos.imprimirTicket', false);
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

    private function openOrder(Sucursal $branch, Mesa $table, ?string $reference = null): Orden
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

    private function product(string $price, string $cost): int
    {
        $categoryId = DB::table('categorias')->insertGetId([
            'nombre' => 'Cobro '.uniqid(),
            'tipo' => 'cocina',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('productos')->insertGetId([
            'nombre' => 'Producto '.uniqid(),
            'precio' => $price,
            'costo' => $cost,
            'categoria_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function productsPayload(int $productId): array
    {
        return [[
            'id' => $productId,
            'cantidad' => 1,
            'modalidad' => 'solo',
            'opciones' => [],
            'extras' => [],
        ]];
    }

    private function close(Sucursal $branch, Mesa $table, int $productId, array $payment)
    {
        return $this->postJson(route('pos.orden.close', ['sucursal' => $branch, 'mesa' => $table]), [
            'productos' => $this->productsPayload($productId),
            ...$payment,
        ]);
    }

    private function assertCloseDoesNotPrint(): void
    {
        $thermalPrinter = Mockery::mock(ThermalPrinterService::class);
        $thermalPrinter->shouldNotReceive('printOrder');
        $this->app->instance(ThermalPrinterService::class, $thermalPrinter);

        $rawPrinter = Mockery::mock(RawEscPosPrinter::class);
        $rawPrinter->shouldNotReceive('send');
        $this->app->instance(RawEscPosPrinter::class, $rawPrinter);
    }

    private function paidOrderWithPayment(
        Sucursal $branch,
        Mesa $table,
        string $method,
        ?string $received = null,
        ?string $change = null,
        ?string $reference = null
    ): Orden {
        $order = Orden::query()->create([
            'sucursal_id' => $branch->id,
            'mesa_id' => $table->id,
            'tipo' => $table->tipo,
            'referencia' => $reference,
            'estado' => 'pagada',
            'total' => '185.00',
            'metodo_pago' => $method,
            'desc_empleado' => $table->tipo === 'empleados',
        ]);
        Pago::query()->create([
            'orden_id' => $order->id,
            'monto' => '185.00',
            'monto_recibido' => $received,
            'cambio' => $change,
            'metodo' => $method,
        ]);

        return $order;
    }

    private function formatterConfig(): array
    {
        return [
            'characters_per_line' => 48,
            'store_logo_path' => 'C:\\missing-stage6-logo.png',
            'cut_at_end' => false,
            'open_drawer' => false,
        ];
    }
}
