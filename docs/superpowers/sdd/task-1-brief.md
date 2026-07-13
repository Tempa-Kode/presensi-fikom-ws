# Task 1: Migration — tambah kolom `otp_code` ke tabel `sesi_kuliah`

**Files:**
- Create: `database/migrations/2026_07_14_000001_add_otp_code_to_sesi_kuliah_table.php`
- Test: manual verification via `php artisan migrate` (skema-only change, tidak ada business logic untuk unit test)

**Interfaces:**
- Consumes: tabel `sesi_kuliah` yang sudah ada (kolom `latitude`, `longitude` sebagai referensi posisi `after()`)
- Produces: kolom `sesi_kuliah.otp_code` (string, nullable, max 4 karakter) — dikonsumsi oleh Task 3 (model `SesiKuliah`) dan Task 4 (controller)

## Steps

### Step 1: Buat file migration

Jalankan:
```bash
cd backend && php artisan make:migration add_otp_code_to_sesi_kuliah_table
```

### Step 2: Isi migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_kuliah', function (Blueprint $table) {
            $table->string('otp_code', 4)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_kuliah', function (Blueprint $table) {
            $table->dropColumn('otp_code');
        });
    }
};
```

### Step 3: Jalankan migration

Run: `php artisan migrate`
Expected: output menunjukkan migration `..._add_otp_code_to_sesi_kuliah_table` berhasil (`DONE`), tidak ada error.

### Step 4: Verifikasi kolom ada

Run: `php artisan tinker --execute="dd(Schema::getColumnListing('sesi_kuliah'));"`
Expected: array output berisi `"otp_code"` di antara nama kolom lainnya.

### Step 5: Commit

```bash
git add database/migrations/2026_07_14_000001_add_otp_code_to_sesi_kuliah_table.php
git commit -m "feat: add otp_code column to sesi_kuliah table"
```

## Global Constraints (plan-wide)

- OTP selalu 4 digit numerik dengan leading zero jika perlu (format string, bukan integer) — dari spec Ringkasan & Arsitektur.
- OTP baru di-generate setiap kali dosen membuka sesi, termasuk membuka ulang jadwal yang sama di hari yang sama — dari spec Keputusan Desain #2.
- Lockout: maksimal 3 kali salah OTP per mahasiswa per sesi, lalu terkunci 5 menit — dari spec Keputusan Desain #3-4.
- OTP dan GPS divalidasi bersamaan dalam satu request ke `absensi()` — tidak ada endpoint OTP-check terpisah — dari spec Keputusan Desain #5.
- Kegagalan GPS (di luar radius 5 meter) TIDAK dihitung sebagai percobaan OTP gagal — dari spec Backend Perubahan Endpoint poin 6 dan tabel Penanganan Error.
- Setelah absensi sukses, record `AbsensiOtpAttempt` terkait dihapus — dari spec Backend Perubahan Endpoint poin 7.

## Project Context

Anda mengerjakan backend Laravel untuk sistem absensi GPS berbasis OTP. Task 1 adalah pertama dari 7 tasks. Lihat `sesi_kuliah` migration asli di `database/migrations/2025_09_23_173736_create_sesi_kuliahs_table.php` untuk referensi struktur tabel sebelum menambah kolom.
