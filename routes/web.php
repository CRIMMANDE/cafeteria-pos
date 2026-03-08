<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PosController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\OrdenController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mesas', [MesaController::class, 'index']);
Route::get('/pos/mesa/{mesa}', [PosController::class, 'orden']);
Route::post('/orden/guardar', [OrdenController::class, 'guardar']);
Route::get('/orden/mesa/{mesa}', [OrdenController::class, 'mesa']);
Route::post('/orden/cerrar', [OrdenController::class, 'cerrar']);