<?php
require 'dosen-api.php';
require 'mahasiswa-api.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', App\Http\Controllers\Api\LoginController::class);
Route::post('/update-foto-profil', [App\Http\Controllers\Api\ProfilController::class, 'updateFotoProfil'])->middleware('auth:sanctum');
Route::get('/jadwal-kelas-mahasiswa', [App\Http\Controllers\Api\KelasController::class, 'jadwalKelasByMahasiswaNpm'])->middleware('auth:sanctum');
