<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\OrdenController;
use App\Http\Controllers\AdminMenuDiaController;
use App\Http\Controllers\AdminProductoController;
use App\Http\Controllers\AdminCategoriaController;
use App\Http\Controllers\AdminGrupoOpcionController;
use App\Http\Controllers\AdminOpcionController;
use App\Http\Controllers\AdminExtraController;

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

    Route::get('/productos', [AdminProductoController::class, 'index']);
    Route::post('/productos', [AdminProductoController::class, 'store']);
    Route::put('/productos/{producto}', [AdminProductoController::class, 'update']);
    Route::post('/productos/{producto}/toggle', [AdminProductoController::class, 'toggle']);

    Route::get('/categorias', [AdminCategoriaController::class, 'index']);
    Route::post('/categorias', [AdminCategoriaController::class, 'store']);
    Route::put('/categorias/{categoria}', [AdminCategoriaController::class, 'update']);
    Route::post('/categorias/{categoria}/toggle', [AdminCategoriaController::class, 'toggle']);

    Route::get('/grupos-opciones', [AdminGrupoOpcionController::class, 'index']);
    Route::post('/grupos-opciones', [AdminGrupoOpcionController::class, 'store']);
    Route::put('/grupos-opciones/{grupoOpcion}', [AdminGrupoOpcionController::class, 'update']);
    Route::post('/grupos-opciones/{grupoOpcion}/toggle', [AdminGrupoOpcionController::class, 'toggle']);

    Route::get('/opciones', [AdminOpcionController::class, 'index']);
    Route::post('/opciones', [AdminOpcionController::class, 'store']);
    Route::put('/opciones/{opcion}', [AdminOpcionController::class, 'update']);
    Route::post('/opciones/{opcion}/toggle', [AdminOpcionController::class, 'toggle']);

    Route::get('/extras', [AdminExtraController::class, 'index']);
    Route::post('/extras', [AdminExtraController::class, 'store']);
    Route::put('/extras/{extra}', [AdminExtraController::class, 'update']);
    Route::post('/extras/{extra}/toggle', [AdminExtraController::class, 'toggle']);
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
