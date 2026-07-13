<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_otp_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesi_kuliah_id')
                ->constrained('sesi_kuliah')
                ->onDelete('cascade');
            $table->foreignId('mahasiswa_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('failed_count')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->unique(['sesi_kuliah_id', 'mahasiswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_otp_attempts');
    }
};
