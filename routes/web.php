<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('login'); });
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('admin.login');
Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard')->middleware('auth');

Route::prefix('data')->middleware('auth')->group(function () {
    Route::prefix('dosen')->group(function () {
        Route::get('/', [App\Http\Controllers\DosenController::class, 'index'])->name('data.dosen');
        Route::get('/create', [App\Http\Controllers\DosenController::class, 'create'])->name('data.dosen.create');
        Route::post('/store', [App\Http\Controllers\DosenController::class, 'store'])->name('data.dosen.store');
        Route::get('/{id}/edit', [App\Http\Controllers\DosenController::class, 'edit'])->name('data.dosen.edit');
        Route::put('/{id}/update', [App\Http\Controllers\DosenController::class, 'update'])->name('data.dosen.update');
        Route::delete('/{id}/delete', [App\Http\Controllers\DosenController::class, 'destroy'])->name('data.dosen.delete');
    });
})->name('data.');
