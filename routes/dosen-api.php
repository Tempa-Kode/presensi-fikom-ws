<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get(
    '/kelas/dosen/{nidn}',
    [App\Http\Controllers\Api\KelasController::class, 'kelasByDosen']
)->middleware('auth:sanctum');

