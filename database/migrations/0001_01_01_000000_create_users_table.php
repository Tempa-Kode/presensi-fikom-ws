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
        Schema::create('prodi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_prodi', 10)->unique();
            $table->string('nama_prodi', 50)->unique();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prodi_id')
                ->nullable()
                ->constrained('prodi')
                ->onDelete('cascade');
            $table->string('npm', 20)->nullable()->unique();
            $table->string('nidn', 20)->nullable()->unique();
            $table->string('email', 50)->nullable()->unique();
            $table->string('nama', 100);
            $table->string('password');
            $table->enum('role', ['admin', 'kaprodi', 'dekan', 'dosen', 'mahasiswa']);
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodi');
        Schema::dropIfExists('users');
    }
};
