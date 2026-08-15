<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Sucursal;
use Database\Seeders\SucursalesMesasSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Stage3BranchOperationsTest extends TestCase
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

    public function test_selector_lists_only_active_branches_and_uses_codes_in_links(): void
    {
        $response = $this->get(route('pos.sucursales.index'));

        $response->assertOk()
            ->assertSee('BRUMA')
            ->assertSee('BRUMITA')
            ->assertSee('/sucursales/bruma/mesas', false)
            ->assertSee('/sucursales/brumita/mesas', false);

        $this->branch('brumita')->update(['activa' => false]);

        $this->get(route('pos.sucursales.index'))
            ->assertOk()
            ->assertSee('BRUMA')
            ->assertDontSee('BRUMITA');
    }

    public function test_each_branch_panel_shows_its_real_physical_tables_and_shared_actions(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');

        $brumaResponse = $this->get(route('pos.mesas.index', ['sucursal' => $bruma]));
        $brumaResponse->assertOk()
            ->assertSee('BRUMA')
            ->assertSee('P/LLEVAR')
            ->assertSee('EMPLEADOS')
            ->assertSee('Recuperar cuenta')
            ->assertSee('Sucursales');
        foreach (range(1, 10) as $numero) {
            $brumaResponse->assertSee("Mesa {$numero}");
        }

        $brumitaResponse = $this->get(route('pos.mesas.index', ['sucursal' => $brumita]));
        $brumitaResponse->assertOk()
            ->assertSee('BRUMITA')
            ->assertSee('Mesa 1')
            ->assertSee('Mesa 2')
            ->assertSee('Mesa 3')
            ->assertSee('Mesa 4')
            ->assertDontSee('>10000<', false)
            ->assertDontSee('>10001<', false)
            ->assertSee('P/LLEVAR')
            ->assertSee('EMPLEADOS')
            ->assertSee('Recuperar cuenta')
            ->assertSee('Sucursales');
    }

    public function test_same_visible_table_number_opens_and_tracks_occupancy_independently(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaOne = $this->physicalTable($bruma, 1);
        $brumitaOne = $this->physicalTable($brumita, 1);

        $this->assertNotSame($brumaOne->id, $brumitaOne->id);

        Orden::query()->create([
            'sucursal_id' => $bruma->id,
            'mesa_id' => $brumaOne->id,
            'tipo' => 'mesa',
            'estado' => 'abierta',
            'total' => 0,
        ]);

        $this->get(route('pos.mesas.show', ['sucursal' => $bruma, 'mesa' => $brumaOne]))->assertOk()->assertSee('Sucursal: BRUMA');
        $this->get(route('pos.mesas.show', ['sucursal' => $brumita, 'mesa' => $brumitaOne]))->assertOk()->assertSee('Sucursal: BRUMITA');

        $this->get(route('pos.mesas.index', ['sucursal' => $bruma]))
            ->assertOk()
            ->assertSee('<div class="mesa ocupada">', false);
        $this->get(route('pos.mesas.index', ['sucursal' => $brumita]))
            ->assertOk()
            ->assertDontSee('<div class="mesa ocupada">', false);
    }

    public function test_takeaway_and_employee_orders_are_independent_and_employee_prices_use_cost(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaTakeaway = $this->specialTable($bruma, 'llevar');
        $brumitaTakeaway = $this->specialTable($brumita, 'llevar');
        $brumaEmployee = $this->specialTable($bruma, 'empleados');
        $brumitaEmployee = $this->specialTable($brumita, 'empleados');

        foreach ([
            [$bruma, $brumaTakeaway, 'pos.llevar.show'],
            [$brumita, $brumitaTakeaway, 'pos.llevar.show'],
            [$bruma, $brumaEmployee, 'pos.empleados.show'],
            [$brumita, $brumitaEmployee, 'pos.empleados.show'],
        ] as [$sucursal, $mesa, $routeName]) {
            $this->get(route($routeName, ['sucursal' => $sucursal]))->assertOk();
            $this->postJson(route('pos.orden.store', ['sucursal' => $sucursal, 'mesa' => $mesa]), [
                'productos' => [],
                'productosNuevos' => [],
            ])->assertOk();
        }

        $this->assertSame(1, Orden::query()->forSucursal($bruma)->where('mesa_id', $brumaTakeaway->id)->where('tipo', 'llevar')->count());
        $this->assertSame(1, Orden::query()->forSucursal($brumita)->where('mesa_id', $brumitaTakeaway->id)->where('tipo', 'llevar')->count());
        $this->assertSame(1, Orden::query()->forSucursal($bruma)->where('mesa_id', $brumaEmployee->id)->where('tipo', 'empleados')->count());
        $this->assertSame(1, Orden::query()->forSucursal($brumita)->where('mesa_id', $brumitaEmployee->id)->where('tipo', 'empleados')->count());

        $categoriaId = DB::table('categorias')->insertGetId([
            'nombre' => 'Prueba', 'tipo' => 'cocina', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('productos')->insert([
            'nombre' => 'Producto empleado', 'precio' => 50, 'costo' => 12, 'categoria_id' => $categoriaId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get(route('pos.empleados.show', ['sucursal' => $brumita]))
            ->assertOk()
            ->assertSee('$ 12.00')
            ->assertDontSee('$ 50.00');
    }

    public function test_new_save_flow_assigns_route_branch_and_rejects_manipulated_context(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaOne = $this->physicalTable($bruma, 1);
        $brumitaOne = $this->physicalTable($brumita, 1);
        $storeUrl = route('pos.orden.store', ['sucursal' => $brumita, 'mesa' => $brumitaOne]);

        $response = $this->postJson($storeUrl, ['productos' => [], 'productosNuevos' => []]);

        $response->assertOk()
            ->assertJsonPath('redirect_url', route('pos.mesas.index', ['sucursal' => $brumita]));
        $this->assertDatabaseHas('ordens', [
            'sucursal_id' => $brumita->id,
            'mesa_id' => $brumitaOne->id,
            'tipo' => 'mesa',
            'estado' => 'abierta',
        ]);

        $orden = Orden::query()->forSucursal($brumita)->where('mesa_id', $brumitaOne->id)->sole();
        $orden->update(['referencia' => 'Referencia preservada']);
        $this->postJson($storeUrl, ['productos' => [], 'productosNuevos' => []])->assertOk();
        $this->assertSame('Referencia preservada', $orden->fresh()->referencia);

        $this->postJson($storeUrl, [
            'mesa' => $brumaOne->id,
            'productos' => [],
            'productosNuevos' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('mesa');

        $this->postJson($storeUrl, [
            'sucursal_id' => $bruma->id,
            'productos' => [],
            'productosNuevos' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('sucursal_id');

        $this->postJson($storeUrl, [
            'tipo' => 'llevar',
            'productos' => [],
            'productosNuevos' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('tipo');
    }

    public function test_cross_branch_open_load_save_and_close_are_blocked(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaOne = $this->physicalTable($bruma, 1);
        $brumitaOne = $this->physicalTable($brumita, 1);

        Orden::query()->create([
            'sucursal_id' => $brumita->id,
            'mesa_id' => $brumitaOne->id,
            'tipo' => 'mesa',
            'estado' => 'abierta',
            'total' => 0,
        ]);

        $crossParameters = ['sucursal' => $bruma, 'mesa' => $brumitaOne];
        $this->get(route('pos.mesas.show', $crossParameters))->assertNotFound();
        $this->getJson(route('pos.orden.open', $crossParameters))->assertNotFound();
        $this->postJson(route('pos.orden.store', $crossParameters), ['productos' => [], 'productosNuevos' => []])->assertNotFound();
        $this->postJson(route('pos.orden.close', $crossParameters), ['productos' => [], 'metodo_pago' => 'efectivo'])->assertNotFound();

        $this->assertDatabaseMissing('ordens', ['sucursal_id' => $bruma->id, 'mesa_id' => $brumitaOne->id]);
        $this->assertSame(0, Orden::query()->where('mesa_id', $brumaOne->id)->count());
    }

    public function test_close_returns_to_the_same_branch_and_never_closes_another_branch(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaOne = $this->physicalTable($bruma, 1);
        $brumitaOne = $this->physicalTable($brumita, 1);
        $brumaOrder = $this->paidOrOpenOrder($bruma, $brumaOne, 'abierta');
        $brumitaOrder = $this->paidOrOpenOrder($brumita, $brumitaOne, 'abierta');

        $this->postJson(route('pos.orden.close', ['sucursal' => $brumita, 'mesa' => $brumitaOne]), [
            'productos' => [],
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '10.00',
        ])->assertOk()->assertJsonPath('redirect_url', route('pos.mesas.index', ['sucursal' => $brumita]));

        $this->assertSame('abierta', $brumaOrder->fresh()->estado);
        $this->assertSame('pagada', $brumitaOrder->fresh()->estado);
        $this->assertSame(1, $brumitaOrder->fresh()->pagos()->count());
    }

    public function test_recovery_is_scoped_and_redirects_each_operation_to_its_branch_context(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaPhysical = $this->physicalTable($bruma, 1);
        $brumitaPhysical = $this->physicalTable($brumita, 1);
        $brumitaTakeaway = $this->specialTable($brumita, 'llevar');
        $brumitaEmployee = $this->specialTable($brumita, 'empleados');

        $brumaOrder = $this->paidOrOpenOrder($bruma, $brumaPhysical, 'pagada');
        $brumitaOrder = $this->paidOrOpenOrder($brumita, $brumitaPhysical, 'pagada');
        $takeawayOrder = $this->paidOrOpenOrder($brumita, $brumitaTakeaway, 'pagada');
        $employeeOrder = $this->paidOrOpenOrder($brumita, $brumitaEmployee, 'pagada');

        foreach ([$brumaOrder, $brumitaOrder, $takeawayOrder, $employeeOrder] as $order) {
            $order->pagos()->create(['monto' => 10, 'metodo' => 'efectivo']);
        }

        $this->postJson(route('pos.orden.recover', ['sucursal' => $brumita]), ['folio' => $brumaOrder->id])
            ->assertNotFound()
            ->assertJsonPath('message', 'Folio no encontrado en esta sucursal');
        $this->assertSame('pagada', $brumaOrder->fresh()->estado);

        $this->postJson(route('pos.orden.recover', ['sucursal' => $bruma]), ['folio' => $brumaOrder->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('pos.mesas.show', ['sucursal' => $bruma, 'mesa' => $brumaPhysical]));
        $this->postJson(route('pos.orden.recover', ['sucursal' => $brumita]), ['folio' => $brumitaOrder->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('pos.mesas.show', ['sucursal' => $brumita, 'mesa' => $brumitaPhysical]));
        $this->postJson(route('pos.orden.recover', ['sucursal' => $brumita]), ['folio' => $takeawayOrder->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('pos.llevar.show', ['sucursal' => $brumita]));
        $this->postJson(route('pos.orden.recover', ['sucursal' => $brumita]), ['folio' => $employeeOrder->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('pos.empleados.show', ['sucursal' => $brumita]));

        foreach ([$brumaOrder, $brumitaOrder, $takeawayOrder, $employeeOrder] as $order) {
            $this->assertSame('abierta', $order->fresh()->estado);
            $this->assertNull($order->fresh()->metodo_pago);
            $this->assertSame(0, $order->fresh()->pagos()->count());
        }
    }

    public function test_duplicate_open_orders_return_a_controlled_conflict(): void
    {
        $bruma = $this->branch('bruma');
        $mesa = $this->physicalTable($bruma, 1);
        $this->paidOrOpenOrder($bruma, $mesa, 'abierta');
        $this->paidOrOpenOrder($bruma, $mesa, 'abierta');

        $this->get(route('pos.mesas.show', ['sucursal' => $bruma, 'mesa' => $mesa]))->assertStatus(409);
        $this->getJson(route('pos.orden.open', ['sucursal' => $bruma, 'mesa' => $mesa]))
            ->assertStatus(409)
            ->assertJsonPath('message', 'Existen varias órdenes abiertas para la misma mesa y sucursal.');
        $this->get(route('pos.mesas.index', ['sucursal' => $bruma]))->assertStatus(409);
    }

    public function test_legacy_routes_only_redirect_to_the_new_bruma_flow(): void
    {
        $bruma = $this->branch('bruma');
        $brumaOne = $this->physicalTable($bruma, 1);
        $brumitaOne = $this->physicalTable($this->branch('brumita'), 1);

        $this->get('/')->assertRedirect('/sucursales');
        $this->get('/mesas')->assertRedirect('/sucursales');
        $this->get('/pos/mesa/'.$brumaOne->id)
            ->assertRedirect(route('pos.mesas.show', ['sucursal' => $bruma, 'mesa' => $brumaOne]));
        $this->get('/pos/llevar')->assertRedirect(route('pos.llevar.show', ['sucursal' => $bruma]));
        $this->get('/pos/empleados')->assertRedirect(route('pos.empleados.show', ['sucursal' => $bruma]));
        $this->get('/pos/mesa/'.$brumitaOne->id)->assertNotFound();
    }

    private function branch(string $codigo): Sucursal
    {
        return Sucursal::query()->where('codigo', $codigo)->sole();
    }

    private function physicalTable(Sucursal $sucursal, int $numero): Mesa
    {
        return Mesa::query()->forSucursal($sucursal)->where('tipo', 'mesa')->where('numero', $numero)->sole();
    }

    private function specialTable(Sucursal $sucursal, string $tipo): Mesa
    {
        return Mesa::query()->forSucursal($sucursal)->where('tipo', $tipo)->whereNull('numero')->sole();
    }

    private function paidOrOpenOrder(Sucursal $sucursal, Mesa $mesa, string $estado): Orden
    {
        return Orden::query()->create([
            'sucursal_id' => $sucursal->id,
            'mesa_id' => $mesa->id,
            'tipo' => $mesa->tipo,
            'estado' => $estado,
            'total' => 10,
            'metodo_pago' => $estado === 'pagada' ? 'efectivo' : null,
            'desc_empleado' => $mesa->tipo === 'empleados',
        ]);
    }
}
