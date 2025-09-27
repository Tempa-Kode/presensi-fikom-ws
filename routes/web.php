<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('login'); });
Route::post('/login', [App\Http\Controllers\AuthController::class, 'login'])->name('admin.login');
Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard')->middleware('auth');
