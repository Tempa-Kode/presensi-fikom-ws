# Kaprodi Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Beri dosen yang tercatat sebagai `kaprodi_id` pada tabel `prodi` akses ke tab "Kaprodi" tambahan di app dosen, menampilkan daftar kelas di prodinya dengan filter semester dan rekap absensi per kelas.

**Architecture:** Backend Laravel menambah helper `isKaprodi()`/relasi `prodiDiketuai()` di model `User`, field `is_kaprodi` + `prodi_diketuai` di response login (`DosenResource`), dan endpoint baru `GET /kaprodi/kelas` dengan resource `KaprodiKelasResource` yang menghitung rekap absensi lintas sesi. Mobile React Native menambah tab kondisional "Kaprodi" (disembunyikan via `href: null` untuk dosen non-kaprodi) dan halaman baru dengan filter semester.

**Tech Stack:** Laravel (PHP), Eloquent ORM, Pest/PHPUnit untuk test backend, React Native + Expo, TypeScript, React Native Paper.

## Global Constraints

- Kaprodi BUKAN nilai enum `role` terpisah — status ditentukan dinamis dari `prodi.kaprodi_id === user.id`, dari spec Keputusan Desain #1.
- Dosen login seperti biasa ke `/(dosen)`; tab "Kaprodi" muncul kondisional, bukan alur login terpisah — dari spec Keputusan Desain #2.
- Scope: daftar kelas + filter semester + rekap absensi (hadir/izin/sakit/alfa) per kelas — dari spec Keputusan Desain #3.
- Tidak ada proses "pengiriman" eksplisit saat sesi ditutup; kaprodi akses data lewat query langsung kapan saja — dari spec Keputusan Desain #4.
- Filter semester bekerja di level `matakuliah.semester`, bukan level `kelas` — dari spec Catatan Desain Kunci.
- Rekap absensi dihitung dari seluruh riwayat sesi kelas tersebut, bukan hanya sesi terbaru — dari spec Catatan Desain Kunci.

---

### Task 1: Backend — `User::prodiDiketuai()` dan `isKaprodi()`

**Files:**
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php` (tambah field yang belum ada: `nidn`, `role`, `nama`, `prodi_id`)
- Test: `tests/Unit/UserIsKaprodiTest.php`

**Interfaces:**
- Consumes: model `Prodi` (kolom `kaprodi_id`, sudah ada)
- Produces: `User::prodiDiketuai()` (relasi `HasOne` ke `Prodi`), `User::isKaprodi(): bool` — dikonsumsi oleh Task 2 (resource login) dan Task 3 (controller kaprodi)

- [ ] **Step 1: Update `UserFactory` agar bisa membuat user dengan field lengkap**

Factory saat ini hanya mengisi `name`, `email`, `password`, dll. (field standar Laravel default), tapi model `User` aplikasi ini pakai kolom `nama`, `nidn`, `npm`, `role`, `prodi_id`. Baca dulu [database/factories/UserFactory.php](database/factories/UserFactory.php) untuk konfirmasi kolom yang sudah ada, lalu ubah `definition()` menjadi:

```php
public function definition(): array
{
    return [
        'nama' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'nidn' => fake()->unique()->numerify('##########'),
        'role' => 'dosen',
        'password' => static::$password ??= Hash::make('password'),
        'remember_token' => Str::random(10),
    ];
}
```

Hapus baris `'email_verified_at' => now(),` jika kolom tersebut tidak ada di migration `users` (cek [database/migrations/0001_01_01_000000_create_users_table.php](database/migrations/0001_01_01_000000_create_users_table.php) — kalau kolom `email_verified_at` memang ada di skema, biarkan tetap ada; kalau tidak ada, hapus baris tersebut supaya tidak error saat insert).

Tambahkan state baru untuk membuat mahasiswa jika dibutuhkan test lain nanti:

```php
public function mahasiswa(): static
{
    return $this->state(fn (array $attributes) => [
        'role' => 'mahasiswa',
        'nidn' => null,
        'npm' => fake()->unique()->numerify('##########'),
    ]);
}
```

- [ ] **Step 2: Tulis failing test untuk `isKaprodi()`**

Buat file `tests/Unit/UserIsKaprodiTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsKaprodiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_yang_menjadi_kaprodi_prodi_mengembalikan_true(): void
    {
        $dosen = User::factory()->create();
        Prodi::factory()->create(['kaprodi_id' => $dosen->id]);

        $this->assertTrue($dosen->isKaprodi());
    }

    public function test_dosen_biasa_mengembalikan_false(): void
    {
        $dosen = User::factory()->create();

        $this->assertFalse($dosen->isKaprodi());
    }

    public function test_prodi_diketuai_mengembalikan_prodi_yang_benar(): void
    {
        $dosen = User::factory()->create();
        $prodi = Prodi::factory()->create(['kaprodi_id' => $dosen->id]);

        $this->assertTrue($dosen->prodiDiketuai->is($prodi));
    }
}
```

**Catatan:** Jika `Prodi` belum punya factory, buat dulu `database/factories/ProdiFactory.php`:

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProdiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode_prodi' => fake()->unique()->bothify('PR##'),
            'nama_prodi' => fake()->words(3, true),
        ];
    }
}
```

Dan tambahkan `use HasFactory;` beserta import-nya di [app/Models/Prodi.php](app/Models/Prodi.php) jika belum ada trait tersebut.

- [ ] **Step 3: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=UserIsKaprodiTest`
Expected: FAIL — `Call to undefined method App\Models\User::isKaprodi()`

- [ ] **Step 4: Implementasikan `prodiDiketuai()` dan `isKaprodi()` di `User.php`**

Tambahkan import di bagian atas [app/Models/User.php](app/Models/User.php) jika belum ada:
```php
use Illuminate\Database\Eloquent\Relations\HasOne;
```

Tambahkan method berikut setelah method `prodi()` yang sudah ada:

```php
public function prodiDiketuai() : HasOne
{
    return $this->hasOne(Prodi::class, 'kaprodi_id');
}

public function isKaprodi() : bool
{
    return $this->prodiDiketuai()->exists();
}
```

- [ ] **Step 5: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=UserIsKaprodiTest`
Expected: PASS — 3 tests, 0 failures

- [ ] **Step 6: Commit**

```bash
git add app/Models/User.php database/factories/UserFactory.php database/factories/ProdiFactory.php app/Models/Prodi.php tests/Unit/UserIsKaprodiTest.php
git commit -m "feat: add User::isKaprodi() and prodiDiketuai() relation"
```

---

### Task 2: Backend — sertakan `is_kaprodi` di response login

**Files:**
- Modify: `app/Http/Resources/DosenResource.php`
- Test: `tests/Feature/LoginKaprodiFieldTest.php`

**Interfaces:**
- Consumes: `User::prodiDiketuai` (Task 1)
- Produces: response login (`DosenResource`) menyertakan `is_kaprodi: boolean` dan `prodi_diketuai: {id, nama_prodi} | null` — dikonsumsi oleh Task 6 (mobile tab kondisional)

- [ ] **Step 1: Tulis failing test**

Buat file `tests/Feature/LoginKaprodiFieldTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginKaprodiFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_dosen_kaprodi_menyertakan_is_kaprodi_true(): void
    {
        $dosen = User::factory()->create(['password' => Hash::make('rahasia123')]);
        $prodi = Prodi::factory()->create(['kaprodi_id' => $dosen->id, 'nama_prodi' => 'Teknik Informatika']);

        $response = $this->postJson('/api/login', [
            'credential' => $dosen->nidn,
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_kaprodi', true);
        $response->assertJsonPath('data.prodi_diketuai.nama_prodi', 'Teknik Informatika');
    }

    public function test_login_dosen_biasa_menyertakan_is_kaprodi_false(): void
    {
        $dosen = User::factory()->create(['password' => Hash::make('rahasia123')]);

        $response = $this->postJson('/api/login', [
            'credential' => $dosen->nidn,
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_kaprodi', false);
        $response->assertJsonPath('data.prodi_diketuai', null);
    }
}
```

**Catatan:** Sesuaikan path route login (`/api/login`) dengan yang aktual — cek `routes/api.php` untuk konfirmasi prefix dan nama route [LoginController](app/Http/Controllers/Api/LoginController.php) yang sudah ada.

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=LoginKaprodiFieldTest`
Expected: FAIL — `is_kaprodi` key tidak ada di response JSON

- [ ] **Step 3: Update `DosenResource.php`**

Ganti isi method `toArray()` di [app/Http/Resources/DosenResource.php](app/Http/Resources/DosenResource.php):

```php
public function toArray(Request $request): array
{
    $prodiDiketuai = $this->resource->prodiDiketuai;

    return [
        'status' => $this->status,
        'message' => $this->message,
        'data' => [
            'id' => $this->id,
            'nidn' => $this->nidn,
            'email' => $this->email,
            'nama' => $this->nama,
            'role' => $this->role,
            'foto' => $this->foto ? url($this->foto) : null,
            'is_kaprodi' => $prodiDiketuai !== null,
            'prodi_diketuai' => $prodiDiketuai ? [
                'id' => $prodiDiketuai->id,
                'nama_prodi' => $prodiDiketuai->nama_prodi,
            ] : null,
        ]
    ];
}
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=LoginKaprodiFieldTest`
Expected: PASS — 2 tests, 0 failures

- [ ] **Step 5: Jalankan seluruh test suite untuk regresi**

Run: `php artisan test`
Expected: semua test PASS, termasuk test dari Task 1 dan fitur OTP sebelumnya (jika ada)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Resources/DosenResource.php tests/Feature/LoginKaprodiFieldTest.php
git commit -m "feat: include is_kaprodi and prodi_diketuai in login response"
```

---

### Task 3: Backend — endpoint `GET /kaprodi/kelas` dengan filter semester

**Files:**
- Create: `app/Http/Controllers/Api/KaprodiController.php`
- Create: `app/Http/Resources/KaprodiKelasResource.php`
- Modify: `routes/dosen-api.php` (tambah route baru)
- Test: `tests/Feature/KaprodiKelasTest.php`

**Interfaces:**
- Consumes: `User::prodiDiketuai` (Task 1), model `Kelas`/`Jadwal`/`SesiKuliah`/`Absensi`/`Matakuliah` (existing)
- Produces: `GET /kaprodi/kelas?semester=X` mengembalikan daftar kelas di prodi yang diketuai dengan rekap absensi — dikonsumsi oleh Task 7 (mobile halaman kaprodi)

- [ ] **Step 1: Tulis failing test**

Buat file `tests/Feature/KaprodiKelasTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Matakuliah;
use App\Models\MatakuliahKelas;
use App\Models\Prodi;
use App\Models\SesiKuliah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaprodiKelasTest extends TestCase
{
    use RefreshDatabase;

    private function buatKelasDenganMatakuliah(Prodi $prodi, int $semester): Kelas
    {
        $dosenPengampu = User::factory()->create();
        $kelas = Kelas::factory()->create([
            'prodi_id' => $prodi->id,
            'dosen_id' => $dosenPengampu->id,
        ]);
        $matakuliah = Matakuliah::factory()->create(['semester' => $semester]);
        MatakuliahKelas::create(['kelas_id' => $kelas->id, 'matkul_id' => $matakuliah->id]);

        return $kelas;
    }

    public function test_dosen_non_kaprodi_mendapat_403(): void
    {
        $dosen = User::factory()->create();

        $response = $this->actingAs($dosen)->getJson('/api/kaprodi/kelas');

        $response->assertStatus(403);
    }

    public function test_kaprodi_hanya_melihat_kelas_di_prodinya(): void
    {
        $kaprodi = User::factory()->create();
        $prodiSendiri = Prodi::factory()->create(['kaprodi_id' => $kaprodi->id]);
        $prodiLain = Prodi::factory()->create();

        $kelasSendiri = $this->buatKelasDenganMatakuliah($prodiSendiri, 3);
        $this->buatKelasDenganMatakuliah($prodiLain, 3);

        $response = $this->actingAs($kaprodi)->getJson('/api/kaprodi/kelas');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $kelasSendiri->id);
    }

    public function test_filter_semester_hanya_mengembalikan_kelas_yang_cocok(): void
    {
        $kaprodi = User::factory()->create();
        $prodi = Prodi::factory()->create(['kaprodi_id' => $kaprodi->id]);

        $kelasSemester3 = $this->buatKelasDenganMatakuliah($prodi, 3);
        $this->buatKelasDenganMatakuliah($prodi, 5);

        $response = $this->actingAs($kaprodi)->getJson('/api/kaprodi/kelas?semester=3');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $kelasSemester3->id);
    }

    public function test_rekap_absensi_menghitung_dari_seluruh_sesi(): void
    {
        $kaprodi = User::factory()->create();
        $prodi = Prodi::factory()->create(['kaprodi_id' => $kaprodi->id]);
        $kelas = $this->buatKelasDenganMatakuliah($prodi, 3);

        $jadwal = Jadwal::factory()->create(['kelas_id' => $kelas->id]);
        $sesi1 = SesiKuliah::factory()->create(['jadwal_id' => $jadwal->id, 'status_absensi' => 'tutup']);
        $sesi2 = SesiKuliah::factory()->create(['jadwal_id' => $jadwal->id, 'status_absensi' => 'tutup']);

        $mahasiswa1 = User::factory()->mahasiswa()->create();
        $mahasiswa2 = User::factory()->mahasiswa()->create();

        Absensi::create(['sesi_kuliah_id' => $sesi1->id, 'mahasiswa_id' => $mahasiswa1->id, 'status' => 'hadir', 'waktu_absensi' => now()]);
        Absensi::create(['sesi_kuliah_id' => $sesi1->id, 'mahasiswa_id' => $mahasiswa2->id, 'status' => 'alfa', 'waktu_absensi' => now()]);
        Absensi::create(['sesi_kuliah_id' => $sesi2->id, 'mahasiswa_id' => $mahasiswa1->id, 'status' => 'hadir', 'waktu_absensi' => now()]);
        Absensi::create(['sesi_kuliah_id' => $sesi2->id, 'mahasiswa_id' => $mahasiswa2->id, 'status' => 'izin', 'waktu_absensi' => now()]);

        $response = $this->actingAs($kaprodi)->getJson('/api/kaprodi/kelas');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.rekap_absensi.hadir', 2);
        $response->assertJsonPath('data.0.rekap_absensi.izin', 1);
        $response->assertJsonPath('data.0.rekap_absensi.alfa', 1);
        $response->assertJsonPath('data.0.rekap_absensi.total_sesi', 2);
    }
}
```

**Catatan:** Sesuaikan nama factory (`Kelas::factory()`, `Matakuliah::factory()`, `Jadwal::factory()`, `SesiKuliah::factory()`) dengan yang tersedia — jika belum ada, buat factory sederhana untuk masing-masing model mengikuti kolom `$fillable` yang sudah didefinisikan di model masing-masing (lihat [app/Models/Kelas.php](app/Models/Kelas.php), [app/Models/Matakuliah.php](app/Models/Matakuliah.php), [app/Models/Jadwal.php](app/Models/Jadwal.php), [app/Models/SesiKuliah.php](app/Models/SesiKuliah.php)). `MatakuliahKelas` dibuat langsung dengan `::create()` karena hanya tabel pivot sederhana.

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --filter=KaprodiKelasTest`
Expected: FAIL — route `/api/kaprodi/kelas` tidak ditemukan (404) atau class `KaprodiController` tidak ada

- [ ] **Step 3: Buat `KaprodiKelasResource.php`**

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KaprodiKelasResource extends JsonResource
{
    public $status;
    public $message;

    public function __construct($status, $message, $resource)
    {
        parent::__construct($resource);
        $this->status = $status;
        $this->message = $message;
    }

    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->resource->map(function ($kelas) {
                $semuaAbsensi = $kelas->jadwal->flatMap(fn($j) => $j->sesiKuliah)
                    ->flatMap(fn($sesi) => $sesi->absensi);

                return [
                    'id' => $kelas->id,
                    'nama_kelas' => $kelas->nama_kelas,
                    'kode_kelas' => $kelas->kode_kelas,
                    'dosen' => [
                        'id' => $kelas->dosen->id,
                        'nama' => $kelas->dosen->nama,
                    ],
                    'matakuliah' => $kelas->matakuliah->map(fn($mk) => [
                        'id' => $mk->id,
                        'nama_matkul' => $mk->nama_matkul,
                        'semester' => $mk->semester,
                        'sks' => $mk->sks,
                    ]),
                    'rekap_absensi' => [
                        'total_sesi' => $kelas->jadwal->sum(fn($j) => $j->sesiKuliah->count()),
                        'hadir' => $semuaAbsensi->where('status', 'hadir')->count(),
                        'izin' => $semuaAbsensi->where('status', 'izin')->count(),
                        'sakit' => $semuaAbsensi->where('status', 'sakit')->count(),
                        'alfa' => $semuaAbsensi->where('status', 'alfa')->count(),
                    ],
                ];
            }),
            'meta' => [
                'total_kelas' => $this->resource->count(),
                'prodi' => $this->resource->first()?->prodi->nama_prodi,
            ]
        ];
    }
}
```

- [ ] **Step 4: Buat `KaprodiController.php`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Kelas;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\KaprodiKelasResource;

class KaprodiController extends Controller
{
    public function daftarKelas(Request $request)
    {
        $dosen = $request->user();
        $prodiDiketuai = $dosen->prodiDiketuai;

        if (!$prodiDiketuai) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak terdaftar sebagai kaprodi.'
            ], 403);
        }

        $activeId = Setting::activeTahunAkademikId();

        $kelasQuery = Kelas::where('prodi_id', $prodiDiketuai->id)
            ->when($activeId, fn($q) => $q->where('tahun_akademik_id', $activeId))
            ->with(['dosen', 'prodi', 'matakuliah', 'jadwal.sesiKuliah.absensi']);

        if ($request->filled('semester')) {
            $kelasQuery->whereHas('matakuliah', function ($q) use ($request) {
                $q->where('semester', $request->semester);
            });
        }

        $kelas = $kelasQuery->get();

        return (new KaprodiKelasResource(
            true,
            'Daftar kelas program studi',
            $kelas
        ))->response()->setStatusCode(200);
    }
}
```

- [ ] **Step 5: Tambah route di `routes/dosen-api.php`**

Tambahkan di akhir [routes/dosen-api.php](routes/dosen-api.php):

```php
Route::get(
    '/kaprodi/kelas',
    [App\Http\Controllers\Api\KaprodiController::class, 'daftarKelas']
)->middleware('auth:sanctum');
```

- [ ] **Step 6: Jalankan test, pastikan lulus**

Run: `php artisan test --filter=KaprodiKelasTest`
Expected: PASS — 4 tests, 0 failures

- [ ] **Step 7: Jalankan seluruh test suite untuk regresi**

Run: `php artisan test`
Expected: semua test PASS

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Api/KaprodiController.php app/Http/Resources/KaprodiKelasResource.php routes/dosen-api.php tests/Feature/KaprodiKelasTest.php
git commit -m "feat: add GET /kaprodi/kelas endpoint with semester filter and attendance summary"
```

---

### Task 4: Mobile — API client function untuk daftar kelas kaprodi

**Files:**
- Create: `lib/models/kaprodi.ts`

**Interfaces:**
- Consumes: `GET /kaprodi/kelas?semester=X` (Task 3)
- Produces: `getDaftarKelasKaprodi(semester?: string): Promise<any>` — dikonsumsi oleh Task 7 (halaman kaprodi)

- [ ] **Step 1: Buat file `lib/models/kaprodi.ts`**

Ikuti pola yang sudah ada di [lib/models/kelas.ts](lib/models/kelas.ts) (fungsi async yang memanggil `api` dari `apiConfig`):

```typescript
import { api } from "../apiConfig";

/**
 * Daftar kelas untuk kaprodi, dengan filter semester opsional
 */
export async function getDaftarKelasKaprodi(semester?: string) {
  try {
    const params = semester ? { semester } : {};
    const response = await api.get(`/kaprodi/kelas`, { params });
    return response.data;
  } catch (error: any) {
    console.info("Error fetching daftar kelas kaprodi:", error);
    if (error?.response?.status === 403) {
      throw new Error("Anda tidak terdaftar sebagai kaprodi.");
    }
    throw new Error("Gagal mengambil daftar kelas kaprodi.");
  }
}
```

- [ ] **Step 2: Verifikasi tidak ada error TypeScript**

Run: `cd mobile && npx tsc --noEmit`
Expected: tidak ada error baru terkait `lib/models/kaprodi.ts`

- [ ] **Step 3: Commit**

```bash
git add lib/models/kaprodi.ts
git commit -m "feat: add getDaftarKelasKaprodi api client function"
```

---

### Task 5: Mobile — tab kondisional "Kaprodi" di layout dosen

**Files:**
- Modify: `app/(dosen)/_layout.tsx`

**Interfaces:**
- Consumes: `getUserData()` dari `lib/auth-context.ts` (existing), field `is_kaprodi` dari Task 2
- Produces: tab bar dosen menampilkan tab "Kaprodi" hanya jika `userData.is_kaprodi === true` — dikonsumsi oleh Task 7 (halaman harus ada di route `app/(dosen)/kaprodi/index.tsx` agar tab ini valid)

- [ ] **Step 1: Update `app/(dosen)/_layout.tsx`**

Tambahkan import dan state di bagian atas file, dan tambahkan `<Tabs.Screen name="kaprodi">` sebelum tab "profil". Berikut isi lengkap file setelah diubah:

```tsx
import Feather from "@expo/vector-icons/Feather";
import MaterialIcons from "@expo/vector-icons/MaterialIcons";
import { router, Tabs } from "expo-router";
import { useEffect, useState } from "react";
import { useTheme } from "react-native-paper";
import { getUserData } from "@/lib/auth-context";

export default function DosenLayout() {
  const theme = useTheme();
  const [isKaprodi, setIsKaprodi] = useState(false);

  useEffect(() => {
    (async () => {
      const userData = await getUserData();
      setIsKaprodi(!!userData?.is_kaprodi);
    })();
  }, []);

  return (
    <Tabs
      screenOptions={{
        headerStyle: { backgroundColor: theme.colors.background },
        headerShadowVisible: false,
        tabBarStyle: {
          borderTopWidth: 0,
          borderRadius: 16,
          elevation: 1,
          shadowOpacity: 4,
        },
        tabBarActiveTintColor: theme.colors.primary,
        tabBarInactiveTintColor: "#666666",
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: "Home",
          headerShown: false,
          tabBarIcon: ({ color }) => (
            <MaterialIcons name="home" size={24} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="kelas"
        options={{
          title: "Kelas",
          headerShown: false,
          tabBarIcon: ({ color }) => (
            <MaterialIcons name="class" size={24} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="kaprodi"
        options={{
          title: "Kaprodi",
          headerShown: false,
          href: isKaprodi ? undefined : null,
          tabBarIcon: ({ color }) => (
            <MaterialIcons name="admin-panel-settings" size={24} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="profil"
        options={{
          title: "Profil Dosen",
          headerShown: true,
          headerTitleAlign: "center",
          headerLeft: () => (
            <Feather
              name="arrow-left"
              size={24}
              color={theme.colors.primary}
              onPress={() => router.back()}
              style={{ marginLeft: 16 }}
            />
          ),
          tabBarIcon: ({ color }) => (
            <MaterialIcons name="person" size={24} color={color} />
          ),
        }}
      />
    </Tabs>
  );
}
```

- [ ] **Step 2: Verifikasi manual — dosen non-kaprodi**

1. Login sebagai dosen yang BUKAN kaprodi (pastikan `prodi.kaprodi_id` tidak menunjuk ke dosen ini di database).
2. Expected: tab bar dosen menampilkan 3 tab (Home, Kelas, Profil Dosen) — TIDAK ada tab "Kaprodi".

**Catatan:** Halaman `app/(dosen)/kaprodi/index.tsx` belum ada di titik ini (dibuat di Task 7) — jika `isKaprodi` bernilai `true` untuk akun test Anda, Expo Router akan menunjukkan error "no route found" saat tab tersebut ditekan. Ini normal dan akan selesai setelah Task 7. Jika ingin verifikasi tab tersembunyi SAJA, gunakan akun dosen non-kaprodi terlebih dahulu.

- [ ] **Step 3: Commit**

```bash
git add "app/(dosen)/_layout.tsx"
git commit -m "feat: add conditional kaprodi tab to dosen layout"
```

---

### Task 6: Mobile — komponen kartu kelas kaprodi

**Files:**
- Create: `components/KaprodiKelasCard.tsx`

**Interfaces:**
- Consumes: data per-kelas dari response `GET /kaprodi/kelas` (Task 3) — shape: `{id, nama_kelas, kode_kelas, dosen: {nama}, matakuliah: [{nama_matkul, semester, sks}], rekap_absensi: {total_sesi, hadir, izin, sakit, alfa}}`
- Produces: komponen `<KaprodiKelasCard kelas={...} />` — dikonsumsi oleh Task 7 (halaman kaprodi)

- [ ] **Step 1: Buat `components/KaprodiKelasCard.tsx`**

```tsx
import { StyleSheet, View } from "react-native";
import { Card, Text } from "react-native-paper";

type MatakuliahItem = {
  id: number;
  nama_matkul: string;
  semester: number;
  sks: number;
};

type RekapAbsensi = {
  total_sesi: number;
  hadir: number;
  izin: number;
  sakit: number;
  alfa: number;
};

type KaprodiKelasCardProps = {
  kelas: {
    id: number;
    nama_kelas: string;
    kode_kelas: string;
    dosen: { nama: string };
    matakuliah: MatakuliahItem[];
    rekap_absensi: RekapAbsensi;
  };
};

export default function KaprodiKelasCard({ kelas }: KaprodiKelasCardProps) {
  const matkulLabel = kelas.matakuliah
    .map((mk) => `${mk.nama_matkul} (Sem ${mk.semester})`)
    .join(", ");

  return (
    <Card style={styles.card}>
      <Card.Content>
        <Text variant="titleMedium">{kelas.nama_kelas}</Text>
        <Text variant="bodySmall" style={styles.subtitle}>
          Kode: {kelas.kode_kelas} | Dosen: {kelas.dosen.nama}
        </Text>
        <Text variant="bodySmall" style={styles.subtitle}>
          {matkulLabel}
        </Text>

        <View style={styles.rekapRow}>
          <Text style={styles.rekapItem}>
            Hadir: {kelas.rekap_absensi.hadir}
          </Text>
          <Text style={styles.rekapItem}>
            Izin: {kelas.rekap_absensi.izin}
          </Text>
          <Text style={styles.rekapItem}>
            Sakit: {kelas.rekap_absensi.sakit}
          </Text>
          <Text style={styles.rekapItem}>
            Alfa: {kelas.rekap_absensi.alfa}
          </Text>
        </View>
        <Text variant="bodySmall" style={styles.totalSesi}>
          Total {kelas.rekap_absensi.total_sesi} sesi tercatat
        </Text>
      </Card.Content>
    </Card>
  );
}

const styles = StyleSheet.create({
  card: {
    marginVertical: 6,
  },
  subtitle: {
    color: "#4B5563",
    marginTop: 4,
  },
  rekapRow: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 10,
    marginTop: 10,
  },
  rekapItem: {
    backgroundColor: "#F3F4F6",
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
    color: "#374151",
    fontSize: 12,
    fontWeight: "600",
  },
  totalSesi: {
    marginTop: 8,
    color: "#6B7280",
  },
});
```

- [ ] **Step 2: Verifikasi tidak ada error TypeScript**

Run: `cd mobile && npx tsc --noEmit`
Expected: tidak ada error baru terkait `components/KaprodiKelasCard.tsx`

- [ ] **Step 3: Commit**

```bash
git add components/KaprodiKelasCard.tsx
git commit -m "feat: add KaprodiKelasCard component"
```

---

### Task 7: Mobile — halaman daftar kelas kaprodi dengan filter semester

**Files:**
- Create: `app/(dosen)/kaprodi/index.tsx`
- Create: `app/(dosen)/kaprodi/_layout.tsx`

**Interfaces:**
- Consumes: `getDaftarKelasKaprodi(semester?: string)` (Task 4), `<KaprodiKelasCard kelas={...} />` (Task 6)
- Produces: halaman lengkap di route `/(dosen)/kaprodi` — melengkapi tab yang didaftarkan di Task 5

- [ ] **Step 1: Buat `app/(dosen)/kaprodi/_layout.tsx`**

Ikuti pola stack layout sederhana yang sudah dipakai di [app/(kaprodi)/_layout.tsx](app/(kaprodi)/_layout.tsx):

```tsx
import { Stack } from "expo-router";

export default function KaprodiTabLayout() {
  return (
    <Stack>
      <Stack.Screen
        name="index"
        options={{
          headerShown: true,
          title: "Dashboard Kaprodi",
        }}
      />
    </Stack>
  );
}
```

- [ ] **Step 2: Buat `app/(dosen)/kaprodi/index.tsx`**

Ikuti pola filter dropdown yang sudah ada di [app/(dosen)/kelas/index.tsx](app/(dosen)/kelas/index.tsx) (menu semester alih-alih hari):

```tsx
import KaprodiKelasCard from "@/components/KaprodiKelasCard";
import { getDaftarKelasKaprodi } from "@/lib/models/kaprodi";
import { useEffect, useMemo, useState } from "react";
import { RefreshControl, ScrollView, StyleSheet, View } from "react-native";
import { Button, Menu, Text, useTheme } from "react-native-paper";
import { SafeAreaProvider } from "react-native-safe-area-context";

const SEMESTER_OPTIONS = [
  { label: "Semua Semester", value: "" },
  { label: "Semester 1", value: "1" },
  { label: "Semester 2", value: "2" },
  { label: "Semester 3", value: "3" },
  { label: "Semester 4", value: "4" },
  { label: "Semester 5", value: "5" },
  { label: "Semester 6", value: "6" },
  { label: "Semester 7", value: "7" },
  { label: "Semester 8", value: "8" },
];

export default function KaprodiIndex() {
  const theme = useTheme();
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [kelasList, setKelasList] = useState<any[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [selectedSemester, setSelectedSemester] = useState<string>("");
  const [menuVisible, setMenuVisible] = useState<boolean>(false);
  const [namaProdi, setNamaProdi] = useState<string | null>(null);

  const loadKelas = async (semester: string) => {
    try {
      setLoading(true);
      setError(null);
      const result = await getDaftarKelasKaprodi(semester || undefined);
      if (result.status) {
        setKelasList(result.data);
        setNamaProdi(result.meta?.prodi ?? null);
      } else {
        setError(result.message || "Gagal memuat daftar kelas.");
      }
    } catch (err: any) {
      setError(err.message || "Terjadi kesalahan saat memuat data.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadKelas(selectedSemester);
  }, [selectedSemester]);

  const onRefresh = async () => {
    setRefreshing(true);
    await loadKelas(selectedSemester);
    setRefreshing(false);
  };

  const getSelectedSemesterLabel = () => {
    const selected = SEMESTER_OPTIONS.find(
      (option) => option.value === selectedSemester
    );
    return selected ? selected.label : "Pilih Semester";
  };

  return (
    <SafeAreaProvider style={styles.container}>
      {namaProdi && (
        <Text variant="titleMedium" style={styles.prodiTitle}>
          Program Studi: {namaProdi}
        </Text>
      )}

      <View style={styles.filterContainer}>
        <Menu
          visible={menuVisible}
          onDismiss={() => setMenuVisible(false)}
          anchor={
            <Button
              mode="outlined"
              onPress={() => setMenuVisible(!menuVisible)}
              icon="filter-variant"
              style={styles.filterButton}
              contentStyle={styles.filterButtonContent}
            >
              {getSelectedSemesterLabel()}
            </Button>
          }
          anchorPosition="bottom"
        >
          {SEMESTER_OPTIONS.map((option) => (
            <Menu.Item
              key={option.value}
              onPress={() => {
                setSelectedSemester(option.value);
                setMenuVisible(false);
              }}
              title={option.label}
              leadingIcon={
                selectedSemester === option.value ? "check" : undefined
              }
            />
          ))}
        </Menu>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={onRefresh}
            colors={[theme.colors.primary]}
            tintColor={theme.colors.primary}
          />
        }
      >
        {loading ? (
          <Text>Memuat daftar kelas...</Text>
        ) : error ? (
          <Text style={styles.errorText}>{error}</Text>
        ) : kelasList.length === 0 ? (
          <View style={styles.emptyContainer}>
            <Text variant="bodyLarge" style={styles.emptyText}>
              Tidak ada kelas
              {selectedSemester ? ` pada semester ${selectedSemester}` : ""}
            </Text>
          </View>
        ) : (
          kelasList.map((kelas) => (
            <KaprodiKelasCard key={kelas.id} kelas={kelas} />
          ))
        )}
      </ScrollView>
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 16,
  },
  prodiTitle: {
    marginBottom: 12,
    fontWeight: "700",
  },
  filterContainer: {
    marginBottom: 16,
  },
  filterButton: {
    marginBottom: 8,
  },
  filterButtonContent: {
    justifyContent: "flex-start",
  },
  errorText: {
    color: "#B00020",
  },
  emptyContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 40,
  },
  emptyText: {
    color: "#666",
    textAlign: "center",
  },
});
```

- [ ] **Step 3: Verifikasi tidak ada error TypeScript**

Run: `cd mobile && npx tsc --noEmit`
Expected: tidak ada error baru terkait file yang dibuat di task ini

- [ ] **Step 4: Verifikasi manual end-to-end**

1. Di database, pastikan user dosen test Anda tercatat sebagai `kaprodi_id` di salah satu baris `prodi`.
2. Login sebagai dosen tersebut di mobile app.
3. Expected: tab "Kaprodi" muncul di tab bar (4 tab total: Home, Kelas, Kaprodi, Profil Dosen).
4. Tap tab "Kaprodi".
5. Expected: header "Dashboard Kaprodi" tampil, nama prodi tampil di atas, daftar kelas di prodi tersebut tampil sebagai kartu.
6. Tap filter semester, pilih semester tertentu.
7. Expected: daftar kelas ter-filter sesuai semester yang dipilih (hanya kelas dengan matakuliah semester tersebut).
8. Verifikasi angka rekap absensi (hadir/izin/sakit/alfa) di kartu cocok dengan data aktual di database untuk kelas tersebut.
9. Login dengan akun dosen BUKAN kaprodi — pastikan tab "Kaprodi" TIDAK muncul sama sekali.

- [ ] **Step 5: Commit**

```bash
git add "app/(dosen)/kaprodi/"
git commit -m "feat: add kaprodi dashboard page with semester filter"
```

---

## Spec Coverage Check

- Deteksi status kaprodi dinamis dari `prodi.kaprodi_id` → Task 1
- `is_kaprodi` + `prodi_diketuai` di response login → Task 2
- Endpoint daftar kelas + filter semester (level matakuliah) + rekap absensi lintas sesi → Task 3
- Tab kondisional (bukan alur login terpisah), disembunyikan total via `href: null` → Task 5
- Halaman kaprodi: daftar kelas + filter semester + rekap absensi per kelas → Task 4, 6, 7
- Error 403 untuk dosen non-kaprodi yang akses endpoint kaprodi → Task 3 (test `test_dosen_non_kaprodi_mendapat_403`)
- Testing manual (tab tersembunyi utk non-kaprodi, filter semester, kecocokan angka rekap) → Task 5 Step 2, Task 7 Step 4

Semua item dari spec tercakup. Tidak ada gap.
