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

Route::get(
    '/absensi/kelas/{kelasId}',
    [App\Http\Controllers\Api\KelasController::class, 'absensiByKelas']
)->middleware('auth:sanctum');

Route::get(
    '/absensi/kelas/{kelasId}/sesi/{sesiId}',
    [App\Http\Controllers\Api\AbsensiController::class, 'daftarAbsensiBySesi']
)->middleware('auth:sanctum');

Route::put(
    '/absensi/sesi/{sesiId}/mahasiswa/{mahasiswaId}',
    [App\Http\Controllers\Api\AbsensiController::class, 'editStatusAbsensi']
)->middleware('auth:sanctum');

Route::get(
    '/pengajuan-izin-sakit/kelas/{kelasId}',
    [App\Http\Controllers\Api\AbsensiController::class, 'pengajuanIzinSakitByKelas']
)->middleware('auth:sanctum');
