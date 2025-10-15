<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post(
    '/kelas/daftar',
    [App\Http\Controllers\Api\KelasController::class, 'daftarKelas']
)->middleware('auth:sanctum');

Route::get(
    '/kelas/mahasiswa/',
    [App\Http\Controllers\Api\KelasController::class, 'kelasByMahasiswa']
)->middleware('auth:sanctum');

Route::get(
    '/sesi-absensi/aktif',
    [App\Http\Controllers\Api\AbsensiController::class, 'sesiAbsensiAktif']
)->middleware('auth:sanctum');

Route::post(
    '/sesi-absensi/hadir',
    [App\Http\Controllers\Api\AbsensiController::class, 'absensi']
)->middleware('auth:sanctum');

Route::get(
    '/sesi-absensi/{jadwalId}/riwayat',
    [App\Http\Controllers\Api\AbsensiController::class, 'riwayatAbsensi']
)->middleware('auth:sanctum');
