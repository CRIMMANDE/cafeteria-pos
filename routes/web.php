<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\AdminMenuDiaController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mesas', [MesaController::class, 'index']);
Route::get('/pos/mesa/{mesa}', [PosController::class, 'orden']);
Route::get('/pos/llevar', [PosController::class, 'llevar']);
Route::get('/pos/empleados', [PosController::class, 'empleados']);
Route::get('/admin/menu-dia', [AdminMenuDiaController::class, 'index']);
Route::post('/admin/menu-dia', [AdminMenuDiaController::class, 'store']);
Route::post('/admin/menu-dia/{menuDiaOpcion}/toggle', [AdminMenuDiaController::class, 'toggle']);
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
