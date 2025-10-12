<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post(
    '/kelas/daftar',
    [App\Http\Controllers\Api\KelasController::class, 'daftarKelas']
)->middleware('auth:sanctum');
