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
        Schema::table('sesi_kuliah', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('waktu_tutup');
            $table->decimal('longitude', 10, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sesi_kuliah', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
