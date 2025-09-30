<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('login'); });
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('admin.login');
Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard')->middleware('auth');

Route::prefix('data')->middleware('auth')->group(function () {
    Route::prefix('dosen')->group(function () {
        Route::get('/', [App\Http\Controllers\DosenController::class, 'index'])->name('data.dosen');
        Route::get('/tambah', [App\Http\Controllers\DosenController::class, 'create'])->name('data.dosen.create');
        Route::post('/simpan', [App\Http\Controllers\DosenController::class, 'store'])->name('data.dosen.store');
        Route::get('/{id}/edit', [App\Http\Controllers\DosenController::class, 'edit'])->name('data.dosen.edit');
        Route::put('/{id}/update', [App\Http\Controllers\DosenController::class, 'update'])->name('data.dosen.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\DosenController::class, 'destroy'])->name('data.dosen.delete');
    });
    Route::prefix('prodi')->group(function () {
        Route::get('/', [App\Http\Controllers\ProdiController::class, 'index'])->name('data.prodi');
        Route::get('/tambah', [App\Http\Controllers\ProdiController::class, 'create'])->name('data.prodi.create');
        Route::post('/simpan', [App\Http\Controllers\ProdiController::class, 'store'])->name('data.prodi.store');
        Route::get('/{id}/edit', [App\Http\Controllers\ProdiController::class, 'edit'])->name('data.prodi.edit');
        Route::put('/{id}/update', [App\Http\Controllers\ProdiController::class, 'update'])->name('data.prodi.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\ProdiController::class, 'destroy'])->name('data.prodi.delete');
    });
    Route::prefix('tahun_akademik')->group(function () {
        Route::get('/', [App\Http\Controllers\TahunAkademikController::class, 'index'])->name('data.tahun_akademik');
        Route::get('/tambah', [App\Http\Controllers\TahunAkademikController::class, 'create'])->name('data.tahun_akademik.create');
        Route::post('/simpan', [App\Http\Controllers\TahunAkademikController::class, 'store'])->name('data.tahun_akademik.store');
        Route::get('/{id}/edit', [App\Http\Controllers\TahunAkademikController::class, 'edit'])->name('data.tahun_akademik.edit');
        Route::put('/{id}/update', [App\Http\Controllers\TahunAkademikController::class, 'update'])->name('data.tahun_akademik.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\TahunAkademikController::class, 'destroy'])->name('data.tahun_akademik.delete');
    });
})->name('data.');
