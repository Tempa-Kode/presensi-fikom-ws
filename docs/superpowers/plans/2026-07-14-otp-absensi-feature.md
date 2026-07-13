# OTP Absensi Feature Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tambahkan verifikasi OTP 4 digit sebagai lapisan kedua (di samping GPS) saat mahasiswa melakukan absensi "Hadir", dengan OTP di-generate otomatis saat dosen membuka sesi dan ditampilkan permanen di kartu kelas dosen.

**Architecture:** Backend Laravel menambah kolom `otp_code` di `sesi_kuliah` dan tabel baru `absensi_otp_attempts` untuk tracking percobaan gagal per mahasiswa per sesi. Endpoint `buatSesiAbsensi()` generate OTP saat sesi dibuka; endpoint `absensi()` memvalidasi OTP sebelum GPS check, dengan lockout 5 menit setelah 3 kali salah. Mobile React Native menambah tampilan badge OTP di kartu kelas dosen (`ClassDosenItem.tsx`) dan input field OTP di modal absensi mahasiswa (`AbsensiMapModal.tsx`).

**Tech Stack:** Laravel (PHP), Eloquent ORM, Pest/PHPUnit untuk unit test backend, React Native + Expo, TypeScript, React Native Paper.

## Global Constraints

- OTP selalu 4 digit numerik dengan leading zero jika perlu (format string, bukan integer) — dari spec Ringkasan & Arsitektur.
- OTP baru di-generate setiap kali dosen membuka sesi, termasuk membuka ulang jadwal yang sama di hari yang sama — dari spec Keputusan Desain #2.
- Lockout: maksimal 3 kali salah OTP per mahasiswa per sesi, lalu terkunci 5 menit — dari spec Keputusan Desain #3-4.
- OTP dan GPS divalidasi bersamaan dalam satu request ke `absensi()` — tidak ada endpoint OTP-check terpisah — dari spec Keputusan Desain #5.
- Kegagalan GPS (di luar radius 5 meter) TIDAK dihitung sebagai percobaan OTP gagal — dari spec Backend Perubahan Endpoint poin 6 dan tabel Penanganan Error.
- Setelah absensi sukses, record `AbsensiOtpAttempt` terkait dihapus — dari spec Backend Perubahan Endpoint poin 7.

---

### Task 1: Migration — tambah kolom `otp_code` ke tabel `sesi_kuliah`

**Files:**
- Create: `database/migrations/2026_07_14_000001_add_otp_code_to_sesi_kuliah_table.php`
- Test: manual verification via `php artisan migrate` (skema-only change, tidak ada business logic untuk unit test)

**Interfaces:**
- Consumes: tabel `sesi_kuliah` yang sudah ada (kolom `latitude`, `longitude` sebagai referensi posisi `after()`)
- Produces: kolom `sesi_kuliah.otp_code` (string, nullable, max 4 karakter) — dikonsumsi oleh Task 3 (model `SesiKuliah`) dan Task 4 (controller)

- [ ] **Step 1: Buat file migration**

Jalankan:
```bash
cd backend && php artisan make:migration add_otp_code_to_sesi_kuliah_table
```

- [ ] **Step 2: Isi migration**

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

- [ ] **Step 3: Jalankan migration**

Run: `php artisan migrate`
Expected: output menunjukkan migration `..._add_otp_code_to_sesi_kuliah_table` berhasil (`DONE`), tidak ada error.

- [ ] **Step 4: Verifikasi kolom ada**

Run: `php artisan tinker --execute="dd(Schema::getColumnListing('sesi_kuliah'));"`
Expected: array output berisi `"otp_code"` di antara nama kolom lainnya.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_14_000001_add_otp_code_to_sesi_kuliah_table.php
git commit -m "feat: add otp_code column to sesi_kuliah table"
```

---

### Task 2: Migration — buat tabel `absensi_otp_attempts`

**Files:**
- Create: `database/migrations/2026_07_14_000002_create_absensi_otp_attempts_table.php`

**Interfaces:**
- Consumes: tabel `sesi_kuliah` (foreign key `sesi_kuliah_id`) dan `users` (foreign key `mahasiswa_id`)
- Produces: tabel `absensi_otp_attempts` dengan kolom `id`, `sesi_kuliah_id`, `mahasiswa_id`, `failed_count`, `locked_until`, `created_at`, `updated_at`, dan unique constraint `(sesi_kuliah_id, mahasiswa_id)` — dikonsumsi oleh Task 3 (model `AbsensiOtpAttempt`) dan Task 4 (controller)

- [ ] **Step 1: Buat file migration**

Jalankan:
```bash
cd backend && php artisan make:migration create_absensi_otp_attempts_table
```

- [ ] **Step 2: Isi migration**

```php
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
```

- [ ] **Step 3: Jalankan migration**

Run: `php artisan migrate`
Expected: output menunjukkan migration `..._create_absensi_otp_attempts_table` berhasil (`DONE`).

- [ ] **Step 4: Verifikasi tabel ada**

Run: `php artisan tinker --execute="dd(Schema::hasTable('absensi_otp_attempts'));"`
Expected: output `true`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_14_000002_create_absensi_otp_attempts_table.php
git commit -m "feat: create absensi_otp_attempts table"
```

---

### Task 3: Models — update `SesiKuliah`, buat `AbsensiOtpAttempt`

**Files:**
- Modify: `app/Models/SesiKuliah.php`
- Create: `app/Models/AbsensiOtpAttempt.php`
- Test: `tests/Unit/AbsensiOtpAttemptTest.php`

**Interfaces:**
- Consumes: kolom `otp_code` dari Task 1, tabel `absensi_otp_attempts` dari Task 2
- Produces: `SesiKuliah::otpAttempts()` relation (HasMany), `AbsensiOtpAttempt` model dengan fillable `sesi_kuliah_id`, `mahasiswa_id`, `failed_count`, `locked_until` (cast `datetime`) dan relasi `sesiKuliah()` (BelongsTo), `mahasiswa()` (BelongsTo ke `User`) — dikonsumsi oleh Task 4 (controller)

- [ ] **Step 1: Tulis test untuk model `AbsensiOtpAttempt`**

Buat file `tests/Unit/AbsensiOtpAttemptTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\AbsensiOtpAttempt;
use App\Models\Jadwal;
use App\Models\SesiKuliah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbsensiOtpAttemptTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_until_dapat_di_cast_ke_carbon_instance(): void
    {
        $jadwal = Jadwal::factory()->create();
        $sesi = SesiKuliah::factory()->create(['jadwal_id' => $jadwal->id]);
        $mahasiswa = User::factory()->create();

        $attempt = AbsensiOtpAttempt::create([
            'sesi_kuliah_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'failed_count' => 1,
            'locked_until' => now()->addMinutes(5),
        ]);

        $this->assertInstanceOf(Carbon::class, $attempt->locked_until);
    }

    public function test_relasi_sesi_kuliah_dan_mahasiswa_berfungsi(): void
    {
        $jadwal = Jadwal::factory()->create();
        $sesi = SesiKuliah::factory()->create(['jadwal_id' => $jadwal->id]);
        $mahasiswa = User::factory()->create();

        $attempt = AbsensiOtpAttempt::create([
            'sesi_kuliah_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
        ]);

        $this->assertTrue($attempt->sesiKuliah->is($sesi));
        $this->assertTrue($attempt->mahasiswa->is($mahasiswa));
    }
}
```

**Catatan:** Jika `Jadwal` atau `SesiKuliah` belum punya factory, buat factory sederhana dulu (`php artisan make:factory JadwalFactory` / `SesiKuliahFactory`) yang mengisi kolom required minimal (`kelas_id`, `ruangan_id`, `jam_id`, `hari` untuk Jadwal; `jadwal_id`, `tanggal`, `status_absensi` untuk SesiKuliah), mengikuti pola factory yang sudah ada di `database/factories/UserFactory.php`.

- [ ] **Step 2: Jalankan test, pastikan gagal (model belum ada)**

Run: `php artisan test --filter=AbsensiOtpAttemptTest`
Expected: FAIL — `Class "App\Models\AbsensiOtpAttempt" not found`

- [ ] **Step 3: Buat model `AbsensiOtpAttempt`**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiOtpAttempt extends Model
{
    protected $table = 'absensi_otp_attempts';

    protected $fillable = [
        'sesi_kuliah_id',
        'mahasiswa_id',
        'failed_count',
        'locked_until',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    public function sesiKuliah(): BelongsTo
    {
        return $this->belongsTo(SesiKuliah::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }
}
```

- [ ] **Step 4: Update model `SesiKuliah`**

Modify `app/Models/SesiKuliah.php` — tambah `otp_code` ke `$fillable` dan tambah relasi `otpAttempts()`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiKuliah extends Model
{
    protected $table = 'sesi_kuliah';

    protected $fillable = [
        'jadwal_id',
        'tanggal',
        'status_absensi',
        'waktu_buka',
        'waktu_tutup',
        'latitude',
        'longitude',
        'otp_code',
    ];

    public function jadwal() : BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function absensi() : HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function pengajuanIzinSakit() : HasMany
    {
        return $this->hasMany(PengajuanIzinSakit::class);
    }

    public function otpAttempts() : HasMany
    {
        return $this->hasMany(AbsensiOtpAttempt::class);
    }
}
```

- [ ] **Step 5: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=AbsensiOtpAttemptTest`
Expected: PASS — 2 tests, 0 failures

- [ ] **Step 6: Commit**

```bash
git add app/Models/AbsensiOtpAttempt.php app/Models/SesiKuliah.php tests/Unit/AbsensiOtpAttemptTest.php database/factories/
git commit -m "feat: add AbsensiOtpAttempt model and SesiKuliah otp relation"
```

---

### Task 4: Backend — generate OTP saat `buatSesiAbsensi()`

**Files:**
- Modify: `app/Http/Controllers/Api/AbsensiController.php` (method `buatSesiAbsensi`, sekitar baris 36-92)
- Test: `tests/Feature/BuatSesiAbsensiOtpTest.php`

**Interfaces:**
- Consumes: `SesiKuliah::create()` dari model existing (Task 3 menambah `otp_code` ke fillable)
- Produces: response `buatSesiAbsensi()` menyertakan `otp_code` 4-digit string — dikonsumsi oleh Task 6 (mobile: dosen melihat OTP)

- [ ] **Step 1: Tulis failing test**

Buat file `tests/Feature/BuatSesiAbsensiOtpTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Jadwal;
use App\Models\SesiKuliah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuatSesiAbsensiOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_sesi_absensi_menghasilkan_otp_4_digit(): void
    {
        $dosen = User::factory()->create();
        $jadwal = Jadwal::factory()->create();

        $response = $this->actingAs($dosen)
            ->postJson('/api/sesi-absensi/buat', [
                'jadwal_id' => $jadwal->id,
            ]);

        $response->assertStatus(201);

        $sesi = SesiKuliah::first();
        $this->assertNotNull($sesi->otp_code);
        $this->assertSame(4, strlen($sesi->otp_code));
        $this->assertMatchesRegularExpression('/^\d{4}$/', $sesi->otp_code);
    }

    public function test_membuka_ulang_sesi_di_hari_berbeda_menghasilkan_otp_berbeda(): void
    {
        $dosen = User::factory()->create();
        $jadwal = Jadwal::factory()->create();

        $this->actingAs($dosen)->postJson('/api/sesi-absensi/buat', ['jadwal_id' => $jadwal->id]);
        $sesiPertama = SesiKuliah::first();
        $sesiPertama->update(['status_absensi' => 'tutup']);

        // simulasikan hari berbeda dengan tanggal berbeda secara langsung di DB
        // (regenerasi OTP untuk hari yang sama sudah dicakup test manual di spec)
        $sesiPertama->update(['tanggal' => now()->subDay()->toDateString()]);

        $this->actingAs($dosen)->postJson('/api/sesi-absensi/buat', ['jadwal_id' => $jadwal->id]);
        $sesiKedua = SesiKuliah::latest('id')->first();

        $this->assertNotEquals($sesiPertama->id, $sesiKedua->id);
        $this->assertNotNull($sesiKedua->otp_code);
    }
}
```

**Catatan:** Sesuaikan route `'/api/sesi-absensi/buat'` dengan nama route aktual di `routes/api.php` untuk `buatSesiAbsensi` jika berbeda — cek dengan `php artisan route:list --path=sesi-absensi` sebelum menjalankan test.

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=BuatSesiAbsensiOtpTest`
Expected: FAIL — assertion `assertNotNull($sesi->otp_code)` gagal karena kolom masih null (belum di-generate)

- [ ] **Step 3: Update `buatSesiAbsensi()` di `AbsensiController.php`**

Di dalam method `buatSesiAbsensi`, cari blok:
```php
$validasi['tanggal'] = $date;
$validasi['status_absensi'] = 'buka';
$validasi['waktu_buka'] = Carbon::now();
```

Ubah menjadi:
```php
$validasi['tanggal'] = $date;
$validasi['status_absensi'] = 'buka';
$validasi['waktu_buka'] = Carbon::now();
$validasi['otp_code'] = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=BuatSesiAbsensiOtpTest`
Expected: PASS — 2 tests, 0 failures

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Api/AbsensiController.php tests/Feature/BuatSesiAbsensiOtpTest.php
git commit -m "feat: generate 4-digit otp code when opening attendance session"
```

---

### Task 5: Backend — validasi OTP + lockout di `absensi()`

**Files:**
- Modify: `app/Http/Controllers/Api/AbsensiController.php` (method `absensi`, sekitar baris 241-331)
- Test: `tests/Feature/AbsensiOtpValidationTest.php`

**Interfaces:**
- Consumes: `AbsensiOtpAttempt` model (Task 3), kolom `otp_code` di `SesiKuliah` (Task 1/4)
- Produces: endpoint `POST /sesi-absensi/hadir` menerima field `otp_code` di request, mengembalikan `422` untuk OTP salah, `429` untuk lockout, dan tetap `201` + record `Absensi` untuk sukses — dikonsumsi oleh Task 7 (mobile: mahasiswa submit OTP)

- [ ] **Step 1: Tulis failing test**

Buat file `tests/Feature/AbsensiOtpValidationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\AbsensiOtpAttempt;
use App\Models\Jadwal;
use App\Models\KelasMatakuliahMahasiswa;
use App\Models\SesiKuliah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbsensiOtpValidationTest extends TestCase
{
    use RefreshDatabase;

    private function buatSesiDenganMahasiswaTerdaftar(string $otp): array
    {
        $jadwal = Jadwal::factory()->create();
        $mahasiswa = User::factory()->create();

        $sesi = SesiKuliah::factory()->create([
            'jadwal_id' => $jadwal->id,
            'status_absensi' => 'buka',
            'otp_code' => $otp,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        KelasMatakuliahMahasiswa::factory()->create([
            'kelas_id' => $jadwal->kelas_id,
            'mahasiswa_id' => $mahasiswa->id,
        ]);

        return [$sesi, $mahasiswa];
    }

    public function test_otp_salah_mengembalikan_422_dan_menaikkan_failed_count(): void
    {
        [$sesi, $mahasiswa] = $this->buatSesiDenganMahasiswaTerdaftar('1234');

        $response = $this->actingAs($mahasiswa)->postJson('/api/sesi-absensi/hadir', [
            'sesi_kuliah_id' => $sesi->id,
            'otp_code' => '0000',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(422);

        $attempt = AbsensiOtpAttempt::where('sesi_kuliah_id', $sesi->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        $this->assertSame(1, $attempt->failed_count);
    }

    public function test_gagal_3_kali_mengaktifkan_lockout_5_menit(): void
    {
        [$sesi, $mahasiswa] = $this->buatSesiDenganMahasiswaTerdaftar('1234');

        foreach (range(1, 3) as $i) {
            $this->actingAs($mahasiswa)->postJson('/api/sesi-absensi/hadir', [
                'sesi_kuliah_id' => $sesi->id,
                'otp_code' => '0000',
                'latitude' => -6.200000,
                'longitude' => 106.816666,
            ]);
        }

        $attempt = AbsensiOtpAttempt::where('sesi_kuliah_id', $sesi->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        $this->assertSame(0, $attempt->failed_count);
        $this->assertNotNull($attempt->locked_until);
        $this->assertTrue($attempt->locked_until->isFuture());
    }

    public function test_request_saat_lockout_mengembalikan_429(): void
    {
        [$sesi, $mahasiswa] = $this->buatSesiDenganMahasiswaTerdaftar('1234');

        AbsensiOtpAttempt::create([
            'sesi_kuliah_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'failed_count' => 0,
            'locked_until' => Carbon::now()->addMinutes(5),
        ]);

        $response = $this->actingAs($mahasiswa)->postJson('/api/sesi-absensi/hadir', [
            'sesi_kuliah_id' => $sesi->id,
            'otp_code' => '1234',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(429);
    }

    public function test_request_setelah_lockout_lewat_diterima(): void
    {
        [$sesi, $mahasiswa] = $this->buatSesiDenganMahasiswaTerdaftar('1234');

        AbsensiOtpAttempt::create([
            'sesi_kuliah_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'failed_count' => 0,
            'locked_until' => Carbon::now()->subMinute(),
        ]);

        $response = $this->actingAs($mahasiswa)->postJson('/api/sesi-absensi/hadir', [
            'sesi_kuliah_id' => $sesi->id,
            'otp_code' => '1234',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(201);
    }

    public function test_otp_benar_menghapus_attempt_record_setelah_sukses(): void
    {
        [$sesi, $mahasiswa] = $this->buatSesiDenganMahasiswaTerdaftar('1234');

        $response = $this->actingAs($mahasiswa)->postJson('/api/sesi-absensi/hadir', [
            'sesi_kuliah_id' => $sesi->id,
            'otp_code' => '1234',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseMissing('absensi_otp_attempts', [
            'sesi_kuliah_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
        ]);
    }

    public function test_gps_di_luar_radius_tidak_menaikkan_failed_count_otp(): void
    {
        [$sesi, $mahasiswa] = $this->buatSesiDenganMahasiswaTerdaftar('1234');

        $response = $this->actingAs($mahasiswa)->postJson('/api/sesi-absensi/hadir', [
            'sesi_kuliah_id' => $sesi->id,
            'otp_code' => '1234',
            'latitude' => -6.300000, // jauh dari lokasi sesi
            'longitude' => 106.900000,
        ]);

        $response->assertStatus(403);

        $attempt = AbsensiOtpAttempt::where('sesi_kuliah_id', $sesi->id)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        $this->assertSame(0, $attempt->failed_count);
    }
}
```

**Catatan:** Sesuaikan nama route `/api/sesi-absensi/hadir` dan factory `KelasMatakuliahMahasiswa::factory()` dengan yang aktual di project — cek `routes/api.php` dan buat factory jika belum ada, mengikuti kolom fillable di `app/Models/KelasMatakuliahMahasiswa.php`.

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=AbsensiOtpValidationTest`
Expected: FAIL — semua 6 test gagal karena request tidak mengenal field `otp_code` dan tidak ada logic lockout (kemungkinan error validasi "otp_code field is required" atau langsung lolos ke GPS check tanpa OTP check)

- [ ] **Step 3: Update method `absensi()` di `AbsensiController.php`**

Ganti seluruh method `absensi()` (baris 241-331) dengan versi berikut:

```php
public function absensi(Request $request)
{
    $validasi = $request->validate([
        'sesi_kuliah_id' => 'required|exists:sesi_kuliah,id',
        'otp_code' => 'required|digits:4',
        'latitude' => 'nullable|numeric',
        'longitude' => 'nullable|numeric',
    ]);

    $mahasiswa = $request->user();

    $sesi = SesiKuliah::where('id', $validasi['sesi_kuliah_id'])
        ->with('jadwal.ruangan')
        ->where('status_absensi', 'buka')
        ->first();

    if (!$sesi) {
        return response()->json([
            'status' => false,
            'message' => 'Sesi absensi sudah ditutup.'
        ], 404);
    }

    // Cek/siapkan record attempt tracking untuk mahasiswa ini pada sesi ini
    $attempt = AbsensiOtpAttempt::firstOrCreate(
        ['sesi_kuliah_id' => $sesi->id, 'mahasiswa_id' => $mahasiswa->id],
        ['failed_count' => 0]
    );

    // Cek apakah sedang lockout
    if ($attempt->locked_until && Carbon::now()->lt($attempt->locked_until)) {
        $sisaDetik = Carbon::now()->diffInSeconds($attempt->locked_until);
        return response()->json([
            'status' => false,
            'message' => 'Terlalu banyak percobaan salah. Coba lagi dalam ' . ceil($sisaDetik / 60) . ' menit.'
        ], 429);
    }

    // Validasi OTP
    if ($validasi['otp_code'] !== $sesi->otp_code) {
        $attempt->failed_count += 1;
        if ($attempt->failed_count >= 3) {
            $attempt->locked_until = Carbon::now()->addMinutes(5);
            $attempt->failed_count = 0;
        }
        $attempt->save();

        return response()->json([
            'status' => false,
            'message' => 'Kode OTP salah.'
        ], 422);
    }

    // cek latitude dan longitude mahasiswa apakah sesuai dengan ruangan kelas dan memiliki jarak maksimal 5 meter
    $jarak = $this->hitungJarak(
        $validasi['latitude'],
        $validasi['longitude'],
        $sesi->latitude,
        $sesi->longitude
    );

    if ($jarak > 5) {
        return response()->json([
            'status' => false,
            'message' => 'Anda berada di luar jangkauan ruangan kelas. Jarak Anda: ' . round($jarak, 1) . ' meter dari ruangan.'
        ], 403);
    }

    // Cek apakah mahasiswa terdaftar di kelas untuk sesi ini
    $kelasMatkulMhs = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
        ->whereHas('kelas.jadwal.sesiKuliah', function ($query) use ($sesi) {
            $query->where('id', $sesi->id);
        })
        ->first();

    if (!$kelasMatkulMhs) {
        return response()->json([
            'status' => false,
            'message' => 'Anda tidak terdaftar di kelas untuk sesi ini.'
        ], 403);
    }

    // Cek apakah mahasiswa sudah melakukan absensi untuk sesi ini
    $absensi = $sesi->absensi()->where('sesi_kuliah_id', $validasi['sesi_kuliah_id'])
        ->where('mahasiswa_id', $mahasiswa->id)
        ->first();
    if ($absensi) {
        return response()->json([
            'status' => false,
            'message' => 'Anda sudah melakukan absensi untuk sesi ini.'
        ], 400);
    }

    DB::beginTransaction();
    try {
        $simpanAbsensi = Absensi::create([
            'sesi_kuliah_id' => $sesi->id,
            'mahasiswa_id' => $mahasiswa->id,
            'waktu_absensi' => Carbon::now(),
            'status' => 'hadir',
            'latitude' => $validasi['latitude'] ?? null,
            'longitude' => $validasi['longitude'] ?? null,
        ]);

        $attempt->delete();

        DB::commit();

        return (new AbsensiBySesiResource(
            true,
            'Absensi berhasil dilakukan.',
            $simpanAbsensi
        ))->response()
            ->setStatusCode(201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => false,
            'message' => 'Gagal melakukan absensi.',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

Tambahkan juga import di bagian atas file jika belum ada:
```php
use App\Models\AbsensiOtpAttempt;
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=AbsensiOtpValidationTest`
Expected: PASS — 6 tests, 0 failures

- [ ] **Step 5: Jalankan seluruh test suite backend untuk regresi**

Run: `php artisan test`
Expected: semua test PASS, termasuk test lama yang tidak terkait OTP (tidak ada breaking change ke behavior existing)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/AbsensiController.php tests/Feature/AbsensiOtpValidationTest.php
git commit -m "feat: validate otp code and enforce lockout before recording attendance"
```

---

### Task 6: Mobile — tampilkan OTP di kartu kelas dosen

**Files:**
- Modify: `components/ClassDosenItem.tsx`
- Modify: `lib/models/kelas.ts` (fungsi `getCoursesByLecturer`, cek shape data yang dikembalikan)
- Modify: `app/(dosen)/kelas/index.tsx` (baris 146-153, pemanggilan `<ClassDosenItem>`)
- Modify: `app/Http/Resources/JadwalKelasDosenResource.php` (backend — pastikan `otp_code` dan `status_absensi` sesi aktif ikut di response)

**Interfaces:**
- Consumes: `otp_code` dan `status_absensi` dari response API `getCoursesByLecturer()` (disediakan Task 4 — kolom sudah ada di `sesi_kuliah`, tinggal disertakan di resource)
- Produces: `ClassDosenItem` menerima props `otpCode?: string | null` dan `statusAbsensi?: "buka" | "tutup" | null`, menampilkan badge OTP saat `statusAbsensi === "buka"`

- [ ] **Step 1: Cek & update `JadwalKelasDosenResource.php` agar menyertakan data sesi aktif**

Baca dulu `app/Http/Resources/JadwalKelasDosenResource.php` untuk lihat struktur field yang sudah ada, lalu tambahkan `otp_code` dan `status_absensi` dari sesi kuliah terbaru/aktif milik jadwal tersebut. Pola yang dipakai:

```php
'otp_code' => optional($this->sesiKuliah->where('status_absensi', 'buka')->first())->otp_code,
'status_absensi' => optional($this->sesiKuliah->where('status_absensi', 'buka')->first())->status_absensi,
```

(Sesuaikan nama relasi persis dengan yang dipakai resource ini — cek dulu relasi apa saja yang sudah di-load, misalnya lewat `with()` di controller `getJadwalByDosen`-nya, supaya query tidak N+1.)

- [ ] **Step 2: Update `ClassDosenItem.tsx`**

```tsx
import { StyleSheet, View } from "react-native";
import { Text } from "react-native-paper";

type CourseProps = {
  id: number;
  namaKelas: string;
  tipePertemuan: string;
  jadwalId?: number;
  jam?: number;
  ruangan?: number;
  kodeKelas?: string;
  otpCode?: string | null;
  statusAbsensi?: "buka" | "tutup" | null;
  onPress?: () => void;
};
export default function ClassDosenItem(props: CourseProps) {
  return (
    <View>
      <Text variant="labelMedium" style={style.tipePertemuan}>
        {props.tipePertemuan}
      </Text>
      <Text variant="titleMedium">{props.namaKelas}</Text>
      <Text variant="bodyMedium">{`${props.ruangan || 0} | Jam: ${props.jam || 0} | Kode: ${props.kodeKelas || 0}`}</Text>

      {props.statusAbsensi === "buka" && props.otpCode && (
        <View style={style.otpBadge}>
          <Text variant="labelSmall" style={style.otpLabel}>
            KODE OTP
          </Text>
          <Text variant="headlineSmall" style={style.otpValue}>
            {props.otpCode}
          </Text>
        </View>
      )}
    </View>
  );
}

const style = StyleSheet.create({
  tipePertemuan: {
    textTransform: "uppercase",
  },
  otpBadge: {
    marginTop: 12,
    padding: 12,
    backgroundColor: "#FFF3CD",
    borderRadius: 8,
    borderWidth: 2,
    borderColor: "#FFC107",
    alignItems: "center",
  },
  otpLabel: {
    color: "#856404",
    fontWeight: "bold",
  },
  otpValue: {
    color: "#000",
    fontWeight: "bold",
    letterSpacing: 8,
    marginTop: 4,
  },
});
```

- [ ] **Step 3: Update pemanggilan `<ClassDosenItem>` di `app/(dosen)/kelas/index.tsx`**

Cari blok (baris 146-153):
```tsx
<ClassDosenItem
  id={classItem.kelas.id}
  tipePertemuan={classItem.tipe_pertemuan}
  namaKelas={classItem.kelas.nama_kelas}
  ruangan={classItem.ruangan.nama_ruangan}
  kodeKelas={classItem.kelas.kode_kelas}
  jam={classItem.jam.kode_jam}
/>
```

Ubah menjadi:
```tsx
<ClassDosenItem
  id={classItem.kelas.id}
  tipePertemuan={classItem.tipe_pertemuan}
  namaKelas={classItem.kelas.nama_kelas}
  ruangan={classItem.ruangan.nama_ruangan}
  kodeKelas={classItem.kelas.kode_kelas}
  jam={classItem.jam.kode_jam}
  otpCode={classItem.otp_code}
  statusAbsensi={classItem.status_absensi}
/>
```

- [ ] **Step 4: Verifikasi manual di device/emulator**

1. Login sebagai dosen.
2. Buka sesi absensi untuk salah satu jadwal (dari `rekap-absensi.tsx`).
3. Kembali ke halaman daftar kelas dosen (`app/(dosen)/kelas/index.tsx`).
4. Expected: kartu kelas untuk jadwal tersebut menampilkan badge kuning "KODE OTP" dengan 4 digit angka.
5. Tutup sesi, refresh halaman.
6. Expected: badge OTP tidak lagi tampil.

- [ ] **Step 5: Commit**

```bash
git add components/ClassDosenItem.tsx "app/(dosen)/kelas/index.tsx"
git commit -m "feat: display otp badge on lecturer class card when session is open"
```

Commit backend resource secara terpisah (repo berbeda):
```bash
cd ../backend
git add app/Http/Resources/JadwalKelasDosenResource.php
git commit -m "feat: include otp_code and status_absensi in dosen jadwal resource"
```

---

### Task 7: Mobile — input OTP di modal absensi mahasiswa

**Files:**
- Modify: `components/AbsensiMapModal.tsx`
- Modify: `lib/models/absensi.ts` (fungsi `submitHadirHandler`, baris 16-34)
- Modify: `app/(mahasiswa)/absensiAktif.tsx` (fungsi `handleSubmitHadir`, baris 52-86)

**Interfaces:**
- Consumes: endpoint `POST /sesi-absensi/hadir` dari Task 5 (menerima `otp_code` di payload, dapat mengembalikan 422/429 error)
- Produces: `AbsensiMapModal` `onSubmit` callback dengan signature baru `(otpCode: string, latitude: number, longitude: number) => void`; `submitHadirHandler(sesiId, otpCode, latitude, longitude)`

- [ ] **Step 1: Update `AbsensiMapModal.tsx` — tambah state dan input OTP**

Baca dulu keseluruhan file `components/AbsensiMapModal.tsx` (sudah pernah dibaca sebagian, baris 1-50) untuk melihat struktur JSX modal secara lengkap sebelum edit, karena bagian render (map, tombol submit) belum sepenuhnya diketahui dari eksplorasi sebelumnya.

Tambahkan di bagian props interface:
```tsx
interface AbsensiMapModalProps {
  visible: boolean;
  onDismiss: () => void;
  onSubmit: (otpCode: string, latitude: number, longitude: number) => void;
  title: string;
}
```

Tambahkan state baru di dalam komponen (dekat `const [location, setLocation] = useState...`):
```tsx
const [otpCode, setOtpCode] = useState("");
```

Tambahkan import `TextInput` dari `react-native-paper` di baris import jika belum ada:
```tsx
import { Button, Modal, Portal, Text, TextInput, useTheme } from "react-native-paper";
```

Tambahkan `TextInput` OTP di JSX, tepat sebelum bagian yang merender peta:
```tsx
<TextInput
  label="Kode OTP dari Dosen"
  value={otpCode}
  onChangeText={setOtpCode}
  keyboardType="number-pad"
  maxLength={4}
  mode="outlined"
  placeholder="0000"
  style={{ marginBottom: 12 }}
/>
```

Update pemanggilan `onSubmit` di tombol submit — ganti dari:
```tsx
onPress={() => onSubmit(location.latitude, location.longitude)}
```
menjadi:
```tsx
onPress={() => {
  if (otpCode.length !== 4) {
    Alert.alert("Error", "Masukkan kode OTP 4 digit");
    return;
  }
  if (!location) {
    Alert.alert("Error", "Lokasi belum terdeteksi");
    return;
  }
  onSubmit(otpCode, location.latitude, location.longitude);
}}
disabled={!location || otpCode.length !== 4}
```

(Sesuaikan nama variabel tombol/disabled dengan yang benar-benar ada di file — pola di atas mengikuti struktur `location`/`loading` yang sudah terlihat dari baris 1-50 sebelumnya.)

- [ ] **Step 2: Update `submitHadirHandler` di `lib/models/absensi.ts`**

Ganti:
```typescript
export async function submitHadirHandler(
  sesiId: string,
  latitude: number,
  longitude: number
) {
  try {
    const payload = {
      sesi_kuliah_id: parseInt(sesiId),
      latitude,
      longitude,
    };

    const response = await api.post(`/sesi-absensi/hadir`, payload);

    return response.data;
  } catch (error: any) {
    throw error;
  }
}
```

menjadi:
```typescript
export async function submitHadirHandler(
  sesiId: string,
  otpCode: string,
  latitude: number,
  longitude: number
) {
  try {
    const payload = {
      sesi_kuliah_id: parseInt(sesiId),
      otp_code: otpCode,
      latitude,
      longitude,
    };

    const response = await api.post(`/sesi-absensi/hadir`, payload);

    return response.data;
  } catch (error: any) {
    throw error;
  }
}
```

- [ ] **Step 3: Update `handleSubmitHadir` di `app/(mahasiswa)/absensiAktif.tsx`**

Ganti:
```tsx
const handleSubmitHadir = async (latitude: number, longitude: number) => {
  if (!selectedSesiId) return;

  try {
    const result = await submitHadirHandler(
      selectedSesiId,
      latitude,
      longitude
    );
```

menjadi:
```tsx
const handleSubmitHadir = async (
  otpCode: string,
  latitude: number,
  longitude: number
) => {
  if (!selectedSesiId) return;

  try {
    const result = await submitHadirHandler(
      selectedSesiId,
      otpCode,
      latitude,
      longitude
    );
```

Baris `console.log("Request payload:", ...)` yang ada di bawahnya boleh ditambah `otpCode` juga untuk konsistensi log, tapi tidak wajib.

- [ ] **Step 4: Cek pemanggilan `<AbsensiMapModal>` di `absensiAktif.tsx` sudah sesuai**

Pastikan prop `onSubmit={handleSubmitHadir}` tetap terpasang tanpa perubahan (signature-nya otomatis cocok karena keduanya sudah diupdate ke 3 parameter `(otpCode, latitude, longitude)`).

- [ ] **Step 5: Verifikasi manual end-to-end**

1. Login sebagai mahasiswa yang terdaftar di kelas dengan sesi aktif.
2. Tekan tombol "Hadir".
3. Expected: modal muncul dengan field "Kode OTP dari Dosen" di atas peta.
4. Masukkan OTP salah → tekan "HADIR".
5. Expected: alert menampilkan pesan "Kode OTP salah." dari server.
6. Masukkan OTP benar (cek dari kartu kelas dosen, hasil Task 6) → tekan "HADIR".
7. Expected: toast sukses "Absensi berhasil dicatat", modal tertutup, data refresh.
8. Ulangi submit OTP salah 3 kali berturut-turut pada sesi lain (atau reset attempt via tinker) → expected pesan lockout dengan estimasi waktu tunggu.

- [ ] **Step 6: Commit**

```bash
git add components/AbsensiMapModal.tsx lib/models/absensi.ts "app/(mahasiswa)/absensiAktif.tsx"
git commit -m "feat: require otp input alongside gps when submitting attendance"
```

---

## Spec Coverage Check

- Generate OTP saat buka sesi → Task 4
- Tampilkan OTP permanen di kartu dosen → Task 6
- Input OTP wajib di modal hadir mahasiswa → Task 7
- Validasi OTP + GPS bersamaan di server → Task 5
- Lockout 3x gagal, 5 menit → Task 5
- Hapus attempt record setelah sukses → Task 5 (Step 3, `$attempt->delete()`)
- GPS gagal tidak dihitung sebagai OTP gagal → Task 5 (test `test_gps_di_luar_radius_tidak_menaikkan_failed_count_otp`, urutan check OTP-dulu-baru-GPS)
- Unit test untuk logic deterministik → Task 3, 4, 5 (model relation, generate OTP, validasi & lockout)
- Testing manual di device fisik → Task 6 Step 4, Task 7 Step 5

Semua item dari spec tercakup. Tidak ada gap.
