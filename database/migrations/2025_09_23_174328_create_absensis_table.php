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
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_kuliah_id')
                ->constrained('sesi_kuliah')
                ->onDelete('cascade');
            $table->foreignId('mahasiswa_id')
                ->constrained('users', 'id')
                ->onDelete('cascade');
            $table->timestamp('waktu_absensi');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alfa']);
            $table->decimal('latitude', 10,2);
            $table->decimal('longitude', 10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
