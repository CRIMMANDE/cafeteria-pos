<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PosController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mesas',[PosController::class,'mesas']);

Route::get('/orden/{mesa}',[PosController::class,'orden']);