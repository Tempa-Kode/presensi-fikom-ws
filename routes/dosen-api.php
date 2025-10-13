<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get(
    '/kelas/dosen/{nidn}',
    [App\Http\Controllers\Api\KelasController::class, 'kelasByDosen']
)->middleware('auth:sanctum');

Route::post(
    '/sesi-absensi/buat',
    [App\Http\Controllers\Api\AbsensiController::class, 'buatSesiAbsensi']
)->middleware('auth:sanctum');

Route::post(
    '/sesi-absensi/tutup/{sesi_id}',
    [App\Http\Controllers\Api\AbsensiController::class, 'tutupSesiAbsensi']
)->middleware('auth:sanctum');
