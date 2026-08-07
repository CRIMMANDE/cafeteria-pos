<?php

use App\Http\Controllers\AdminExpenseCutController;
use App\Http\Controllers\AdminGastoController;
use App\Http\Controllers\AdminMenuDiaController;
use App\Http\Controllers\AdminSalesCutController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\PosController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/sucursales')->name('pos.root');
Route::get('/sucursales', [MesaController::class, 'sucursales'])->name('pos.sucursales.index');

Route::prefix('/sucursales/{sucursal:codigo}')
    ->scopeBindings()
    ->group(function () {
        Route::get('/mesas', [MesaController::class, 'index'])->name('pos.mesas.index');
        Route::get('/mesa/{mesa}', [PosController::class, 'orden'])->name('pos.mesas.show');
        Route::get('/llevar', [PosController::class, 'llevar'])->name('pos.llevar.show');
        Route::post('/llevar/iniciar', [PosController::class, 'iniciarLlevar'])->name('pos.llevar.start');
        Route::get('/empleados', [PosController::class, 'empleados'])->name('pos.empleados.show');

        Route::post('/orden/guardar/{mesa}', [OrdenController::class, 'guardar'])->name('pos.orden.store');
        Route::post('/orden/imprimir-ticket/{mesa}', [OrdenController::class, 'imprimirTicket'])->name('pos.orden.print-ticket');
        Route::get('/orden/{orden}/imprimir', [OrdenController::class, 'imprimir'])
            ->withoutScopedBindings()
            ->name('pos.orden.printable');
        Route::get('/orden/mesa/{mesa}', [OrdenController::class, 'mesa'])->name('pos.orden.open');
        Route::post('/orden/cerrar/{mesa}', [OrdenController::class, 'cerrar'])->name('pos.orden.close');
        Route::post('/orden/recuperar', [OrdenController::class, 'recuperar'])->name('pos.orden.recover');
    });

Route::redirect('/mesas', '/sucursales')->name('legacy.mesas');
Route::get('/pos/mesa/{mesa}', [PosController::class, 'legacyOrden'])->name('legacy.pos.mesa');
Route::get('/pos/llevar', [PosController::class, 'legacyLlevar'])->name('legacy.pos.llevar');
Route::get('/pos/empleados', [PosController::class, 'legacyEmpleados'])->name('legacy.pos.empleados');

Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return view('admin.index');
    });

    Route::get('/menu-dia', [AdminMenuDiaController::class, 'index']);
    Route::post('/menu-dia', [AdminMenuDiaController::class, 'store']);
    Route::post('/menu-dia/{menuDiaOpcion}/toggle', [AdminMenuDiaController::class, 'toggle']);

    Route::get('/corte-ventas', [AdminSalesCutController::class, 'index']);
    Route::post('/corte-ventas/imprimir', [AdminSalesCutController::class, 'print']);
    Route::post('/corte-ventas/excel', [AdminSalesCutController::class, 'exportExcel']);

    Route::get('/gastos', [AdminGastoController::class, 'index']);
    Route::post('/gastos', [AdminGastoController::class, 'store']);
    Route::post('/gastos/{gasto}/actualizar', [AdminGastoController::class, 'update']);
    Route::post('/gastos/{gasto}/cancelar', [AdminGastoController::class, 'cancel']);

    Route::get('/corte-gastos', [AdminExpenseCutController::class, 'index']);
    Route::post('/corte-gastos/imprimir', [AdminExpenseCutController::class, 'print']);
    Route::post('/corte-gastos/excel', [AdminExpenseCutController::class, 'exportExcel']);
});

Route::get('/orden/imprimir/{mesa}', [OrdenController::class, 'imprimirLegacyMesa'])->name('legacy.orden.printable');
Route::get('/cocina', [AreaController::class, 'cocina']);
Route::get('/barra', [AreaController::class, 'barra']);
Route::post('/{area}/orden/{orden}/reimprimir', [AreaController::class, 'reimprimir'])
    ->whereIn('area', ['cocina', 'barra'])
    ->name('area.order.reprint');
Route::get('/{area}/orden/{orden}/imprimir', [AreaController::class, 'imprimir'])
    ->whereIn('area', ['cocina', 'barra'])
    ->name('area.order.printable');
