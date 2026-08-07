<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Sucursal;
use Database\Seeders\SucursalesMesasSeeder;
use Tests\TestCase;

class Stage4TakeawayReferenceTest extends TestCase
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

    public function test_new_takeaway_shows_optional_modal_and_navigation_does_not_create_an_order(): void
    {
        $bruma = $this->branch('bruma');
        $takeaway = $this->specialTable($bruma, 'llevar');

        $this->get(route('pos.mesas.index', ['sucursal' => $bruma]))
            ->assertOk()
            ->assertSee('Pedido para llevar')
            ->assertSee('Referencia (opcional)')
            ->assertSee('Nombre, calle, número, etc.')
            ->assertSee('maxlength="150"', false)
            ->assertSee('id="cancelar-referencia-llevar"', false);

        $this->assertDatabaseMissing('ordens', ['mesa_id' => $takeaway->id]);

        $this->post(route('pos.llevar.start', ['sucursal' => $bruma]), ['referencia' => 'Eduardo'])
            ->assertRedirect(route('pos.llevar.show', ['sucursal' => $bruma]));
        $this->assertDatabaseMissing('ordens', ['mesa_id' => $takeaway->id]);
        $this->get(route('pos.llevar.show', ['sucursal' => $bruma]))
            ->assertOk()
            ->assertSee('id="referencia-orden"', false)
            ->assertSee('value="Eduardo"', false);

        $this->post(route('pos.llevar.start', ['sucursal' => $bruma]), ['referencia' => ''])
            ->assertRedirect(route('pos.llevar.show', ['sucursal' => $bruma]));
        $this->get(route('pos.llevar.show', ['sucursal' => $bruma]))
            ->assertOk()
            ->assertSee('value=""', false);

        $this->assertDatabaseMissing('ordens', ['mesa_id' => $takeaway->id]);
    }

    public function test_existing_takeaway_opens_directly_without_initial_modal(): void
    {
        $bruma = $this->branch('bruma');
        $takeaway = $this->specialTable($bruma, 'llevar');
        $this->openOrder($bruma, $takeaway, 'Eduardo');

        $this->get(route('pos.mesas.index', ['sucursal' => $bruma]))
            ->assertOk()
            ->assertDontSee('id="modal-referencia-llevar"', false)
            ->assertDontSee('id="abrir-referencia-llevar"', false)
            ->assertSee(route('pos.llevar.show', ['sucursal' => $bruma]), false);

        $this->post(route('pos.llevar.start', ['sucursal' => $bruma]), ['referencia' => 'No reemplazar'])
            ->assertRedirect(route('pos.llevar.show', ['sucursal' => $bruma]));
        $this->get(route('pos.llevar.show', ['sucursal' => $bruma]))
            ->assertOk()
            ->assertSee('value="Eduardo"', false)
            ->assertDontSee('value="No reemplazar"', false);
    }

    public function test_takeaway_references_are_created_trimmed_and_isolated_by_branch(): void
    {
        $bruma = $this->branch('bruma');
        $brumita = $this->branch('brumita');
        $brumaTakeaway = $this->specialTable($bruma, 'llevar');
        $brumitaTakeaway = $this->specialTable($brumita, 'llevar');

        $this->save($bruma, $brumaTakeaway, ['referencia' => '   Eduardo   '])->assertOk();
        $this->save($brumita, $brumitaTakeaway, ['referencia' => '  María  '])->assertOk();

        $brumaOrder = Orden::query()->forSucursal($bruma)->where('mesa_id', $brumaTakeaway->id)->sole();
        $brumitaOrder = Orden::query()->forSucursal($brumita)->where('mesa_id', $brumitaTakeaway->id)->sole();

        $this->assertSame('Eduardo', $brumaOrder->referencia);
        $this->assertSame('María', $brumitaOrder->referencia);
        $this->assertSame('llevar', $brumaOrder->tipo);
        $this->assertSame('llevar', $brumitaOrder->tipo);
        $this->assertSame($bruma->id, $brumaOrder->sucursal_id);
        $this->assertSame($brumita->id, $brumitaOrder->sucursal_id);

        $this->save($bruma, $brumaTakeaway, ['referencia' => 'Eduardo - Hidalgo 24'])->assertOk();

        $this->assertSame('Eduardo - Hidalgo 24', $brumaOrder->fresh()->referencia);
        $this->assertSame('María', $brumitaOrder->fresh()->referencia);
    }

    public function test_reference_accepts_150_characters_rejects_151_and_normalizes_empty_to_null(): void
    {
        $bruma = $this->branch('bruma');
        $takeaway = $this->specialTable($bruma, 'llevar');
        $accepted = str_repeat('R', 150);

        $this->post(route('pos.llevar.start', ['sucursal' => $bruma]), [
            'referencia' => str_repeat('R', 151),
        ])->assertSessionHasErrors('referencia');
        $this->assertDatabaseMissing('ordens', ['mesa_id' => $takeaway->id]);

        $this->save($bruma, $takeaway, ['referencia' => $accepted])->assertOk();
        $order = Orden::query()->forSucursal($bruma)->where('mesa_id', $takeaway->id)->sole();
        $this->assertSame($accepted, $order->referencia);

        $this->save($bruma, $takeaway, ['referencia' => str_repeat('R', 151)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('referencia');
        $this->assertSame($accepted, $order->fresh()->referencia);

        $this->save($bruma, $takeaway, ['referencia' => '   '])->assertOk();
        $this->assertNull($order->fresh()->referencia);

        $this->save($bruma, $takeaway, [])->assertOk();
        $this->assertNull($order->fresh()->referencia);
    }

    public function test_absent_reference_is_preserved_and_explicit_value_can_be_edited_or_removed(): void
    {
        $bruma = $this->branch('bruma');
        $takeaway = $this->specialTable($bruma, 'llevar');
        $order = $this->openOrder($bruma, $takeaway, 'Eduardo');

        $this->save($bruma, $takeaway, [])->assertOk();
        $this->assertSame('Eduardo', $order->fresh()->referencia);

        $this->save($bruma, $takeaway, ['referencia' => ' Eduardo - Hidalgo 24 '])->assertOk();
        $this->assertSame('Eduardo - Hidalgo 24', $order->fresh()->referencia);

        $this->save($bruma, $takeaway, ['referencia' => ''])->assertOk();
        $this->assertNull($order->fresh()->referencia);
    }

    public function test_physical_and_employee_orders_ignore_reference_payloads_and_hide_the_field(): void
    {
        $bruma = $this->branch('bruma');
        $physical = Mesa::query()->forSucursal($bruma)->where('tipo', 'mesa')->where('numero', 1)->sole();
        $employee = $this->specialTable($bruma, 'empleados');

        $this->save($bruma, $physical, ['referencia' => 'No aplicar'])->assertOk();
        $this->save($bruma, $employee, ['referencia' => 'No aplicar tampoco'])->assertOk();

        $this->assertNull(Orden::query()->where('mesa_id', $physical->id)->sole()->referencia);
        $this->assertNull(Orden::query()->where('mesa_id', $employee->id)->sole()->referencia);

        $this->get(route('pos.mesas.show', ['sucursal' => $bruma, 'mesa' => $physical]))
            ->assertOk()
            ->assertDontSee('id="referencia-orden"', false);
        $this->get(route('pos.empleados.show', ['sucursal' => $bruma]))
            ->assertOk()
            ->assertDontSee('id="referencia-orden"', false);
    }

    public function test_reference_survives_reload_close_and_recovery(): void
    {
        $brumita = $this->branch('brumita');
        $takeaway = $this->specialTable($brumita, 'llevar');

        $this->save($brumita, $takeaway, ['referencia' => 'María'])->assertOk();
        $order = Orden::query()->forSucursal($brumita)->where('mesa_id', $takeaway->id)->sole();

        $this->get(route('pos.llevar.show', ['sucursal' => $brumita]))
            ->assertOk()
            ->assertSee('value="María"', false);

        $this->postJson(route('pos.orden.close', ['sucursal' => $brumita, 'mesa' => $takeaway]), [
            'productos' => [],
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '0.00',
        ])->assertOk();

        $this->assertSame('pagada', $order->fresh()->estado);
        $this->assertSame('María', $order->fresh()->referencia);

        $this->postJson(route('pos.orden.recover', ['sucursal' => $brumita]), ['folio' => $order->id])
            ->assertOk()
            ->assertJsonPath('redirect_url', route('pos.llevar.show', ['sucursal' => $brumita]));

        $this->assertSame('abierta', $order->fresh()->estado);
        $this->assertSame('María', $order->fresh()->referencia);
        $this->assertSame($brumita->id, $order->fresh()->sucursal_id);
        $this->get(route('pos.llevar.show', ['sucursal' => $brumita]))
            ->assertOk()
            ->assertSee('value="María"', false);
    }

    public function test_concurrent_initial_reference_does_not_replace_an_order_created_by_another_device(): void
    {
        $bruma = $this->branch('bruma');
        $takeaway = $this->specialTable($bruma, 'llevar');
        $order = $this->openOrder($bruma, $takeaway, 'Referencia del otro dispositivo');

        $this->save($bruma, $takeaway, [
            'referencia' => 'Referencia inicial atrasada',
            'referencia_inicial' => true,
        ])->assertOk();

        $this->assertSame('Referencia del otro dispositivo', $order->fresh()->referencia);
        $this->assertSame(1, Orden::query()->forSucursal($bruma)->where('mesa_id', $takeaway->id)->where('estado', 'abierta')->count());

        $this->postJson(route('pos.orden.close', ['sucursal' => $bruma, 'mesa' => $takeaway]), [
            'productos' => [],
            'metodo_pago' => 'efectivo',
            'monto_recibido' => '0.00',
            'referencia' => 'Otra referencia inicial atrasada',
            'referencia_inicial' => true,
        ])->assertOk();

        $this->assertSame('Referencia del otro dispositivo', $order->fresh()->referencia);
    }

    private function branch(string $code): Sucursal
    {
        return Sucursal::query()->where('codigo', $code)->sole();
    }

    private function specialTable(Sucursal $branch, string $type): Mesa
    {
        return Mesa::query()->forSucursal($branch)->where('tipo', $type)->whereNull('numero')->sole();
    }

    private function openOrder(Sucursal $branch, Mesa $table, ?string $reference): Orden
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

    private function save(Sucursal $branch, Mesa $table, array $payload)
    {
        return $this->postJson(route('pos.orden.store', ['sucursal' => $branch, 'mesa' => $table]), [
            'productos' => [],
            'productosNuevos' => [],
            ...$payload,
        ]);
    }
}
