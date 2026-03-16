<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\AdminMenuDiaController;
use App\Http\Controllers\AdminSalesCutController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mesas', [MesaController::class, 'index']);
Route::get('/pos/mesa/{mesa}', [PosController::class, 'orden']);
Route::get('/pos/llevar', [PosController::class, 'llevar']);
Route::get('/pos/empleados', [PosController::class, 'empleados']);

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
});

Route::post('/orden/guardar', [OrdenController::class, 'guardar']);
Route::post('/orden/imprimir-ticket', [OrdenController::class, 'imprimirTicket']);
Route::get('/orden/mesa/{mesa}', [OrdenController::class, 'mesa']);
Route::post('/orden/cerrar', [OrdenController::class, 'cerrar']);
Route::post('/orden/recuperar', [OrdenController::class, 'recuperar']);
Route::get('/orden/imprimir/{mesa}', [OrdenController::class, 'imprimir']);
Route::get('/cocina', [AreaController::class, 'cocina']);
Route::get('/barra', [AreaController::class, 'barra']);
Route::post('/{area}/mesa/{mesa}/reimprimir', [AreaController::class, 'reimprimir'])
    ->whereIn('area', ['cocina', 'barra']);
Route::get('/{area}/mesa/{mesa}/imprimir', [AreaController::class, 'imprimir'])
    ->whereIn('area', ['cocina', 'barra']);
