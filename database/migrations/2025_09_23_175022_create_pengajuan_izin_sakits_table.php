<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengajuan_izin_sakit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_kuliah_id')
                ->constrained('sesi_kuliah')
                ->onDelete('cascade');
            $table->foreignId('mahasiswa_id')
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table->enum('status', ['sakit', 'izin']);
            $table->text('keterangan');
            $table->string('bukti_file_path')->nullable();
            $table->enum('status_validasi', ['pending', 'terima', 'tolak']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_izin_sakit');
    }
};
