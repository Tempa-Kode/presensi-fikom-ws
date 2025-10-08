<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () { return view('login'); })->name('login');
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
    Route::prefix('matakuliah')->group(function () {
        Route::get('/', [App\Http\Controllers\MatakuliahController::class, 'index'])->name('data.matakuliah');
        Route::get('/tambah', [App\Http\Controllers\MatakuliahController::class, 'create'])->name('data.matakuliah.create');
        Route::post('/simpan', [App\Http\Controllers\MatakuliahController::class, 'store'])->name('data.matakuliah.store');
        Route::get('/{id}/edit', [App\Http\Controllers\MatakuliahController::class, 'edit'])->name('data.matakuliah.edit');
        Route::put('/{id}/update', [App\Http\Controllers\MatakuliahController::class, 'update'])->name('data.matakuliah.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\MatakuliahController::class, 'destroy'])->name('data.matakuliah.delete');
    });
    Route::prefix('ruangan')->group(function () {
        Route::get('/', [App\Http\Controllers\RuanganController::class, 'index'])->name('data.ruangan');
        Route::get('/tambah', [App\Http\Controllers\RuanganController::class, 'create'])->name('data.ruangan.create');
        Route::post('/simpan', [App\Http\Controllers\RuanganController::class, 'store'])->name('data.ruangan.store');
        Route::get('/{id}/edit', [App\Http\Controllers\RuanganController::class, 'edit'])->name('data.ruangan.edit');
        Route::put('/{id}/update', [App\Http\Controllers\RuanganController::class, 'update'])->name('data.ruangan.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\RuanganController::class, 'destroy'])->name('data.ruangan.delete');
    });
    Route::prefix('jam')->group(function () {
        Route::get('/', [App\Http\Controllers\JamController::class, 'index'])->name('data.jam');
        Route::get('/tambah', [App\Http\Controllers\JamController::class, 'create'])->name('data.jam.create');
        Route::post('/simpan', [App\Http\Controllers\JamController::class, 'store'])->name('data.jam.store');
        Route::get('/{id}/edit', [App\Http\Controllers\JamController::class, 'edit'])->name('data.jam.edit');
        Route::put('/{id}/update', [App\Http\Controllers\JamController::class, 'update'])->name('data.jam.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\JamController::class, 'destroy'])->name('data.jam.delete');
    });
    Route::prefix('kelas')->group(function () {
        Route::get('/', [App\Http\Controllers\KelasController::class, 'index'])->name('data.kelas');
        Route::get('/tambah', [App\Http\Controllers\KelasController::class, 'create'])->name('data.kelas.create');
        Route::post('/simpan', [App\Http\Controllers\KelasController::class, 'store'])->name('data.kelas.store');
        Route::get('/{id}/edit', [App\Http\Controllers\KelasController::class, 'edit'])->name('data.kelas.edit');
        Route::put('/{id}/update', [App\Http\Controllers\KelasController::class, 'update'])->name('data.kelas.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\KelasController::class, 'destroy'])->name('data.kelas.delete');
    });
    Route::prefix('mahasiswa')->group(function () {
        Route::get('/', [App\Http\Controllers\MahasiswaController::class, 'index'])->name('data.mahasiswa');
        Route::get('/tambah', [App\Http\Controllers\MahasiswaController::class, 'create'])->name('data.mahasiswa.create');
        Route::post('/simpan', [App\Http\Controllers\MahasiswaController::class, 'store'])->name('data.mahasiswa.store');
        Route::get('/{id}/edit', [App\Http\Controllers\MahasiswaController::class, 'edit'])->name('data.mahasiswa.edit');
        Route::put('/{id}/update', [App\Http\Controllers\MahasiswaController::class, 'update'])->name('data.mahasiswa.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\MahasiswaController::class, 'destroy'])->name('data.mahasiswa.delete');
    });
    Route::prefix('jadwal')->group(function () {
        Route::get('/', [App\Http\Controllers\JadwalController::class, 'index'])->name('data.jadwal');
        Route::get('/tambah', [App\Http\Controllers\JadwalController::class, 'create'])->name('data.jadwal.create');
        Route::post('/simpan', [App\Http\Controllers\JadwalController::class, 'store'])->name('data.jadwal.store');
        Route::get('/{id}/edit', [App\Http\Controllers\JadwalController::class, 'edit'])->name('data.jadwal.edit');
        Route::put('/{id}/update', [App\Http\Controllers\JadwalController::class, 'update'])->name('data.jadwal.update');
        Route::delete('/{id}/hapus', [App\Http\Controllers\JadwalController::class, 'destroy'])->name('data.jadwal.delete');
    });
})->name('data.');
