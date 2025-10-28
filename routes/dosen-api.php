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
    '/absensi/kelas/{jadwalId}',
    [App\Http\Controllers\Api\KelasController::class, 'absensiByKelas']
)->middleware('auth:sanctum');

Route::get(
    '/absensi/sesi/{sesiId}',
    [App\Http\Controllers\Api\AbsensiController::class, 'daftarAbsensiBySesi']
)->middleware('auth:sanctum');

Route::put(
    '/absensi/sesi/{sesiId}/mahasiswa/{mahasiswaId}',
    [App\Http\Controllers\Api\AbsensiController::class, 'editStatusAbsensi']
)->middleware('auth:sanctum');

Route::get(
    '/pengajuan-izin-sakit/sesi/{sesiId}',
    [App\Http\Controllers\Api\AbsensiController::class, 'pengajuanIzinSakitBySesi']
)->middleware('auth:sanctum');

Route::put(
    '/pengajuan-izin-sakit/{pengajuanId}/validasi',
    [App\Http\Controllers\Api\AbsensiController::class, 'validasiPengajuanIzinSakit']
)->middleware('auth:sanctum');
