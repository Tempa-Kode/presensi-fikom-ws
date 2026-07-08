# Daftar Kelas Tahun Akademik Aktif Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. User requested no subagents.

**Goal:** Add new mobile API flow where students view all classes from active Tahun Akademik and enroll by selected class ID.

**Architecture:** Keep legacy `POST /api/kelas/daftar` untouched. Add two new mahasiswa API routes backed by `Api\KelasController`: one list endpoint and one enroll-by-id endpoint. Reuse `Setting::activeTahunAkademikId()` and existing Eloquent models; no new dependency.

**Tech Stack:** Laravel 12, PHP 8.2, Sanctum middleware, Eloquent, existing JSON resource style.

## Global Constraints

- Do not use subagents for implementation.
- Add new routes; keep legacy `POST /api/kelas/daftar` unchanged.
- `GET /api/kelas/tersedia` lists all classes where `tahun_akademik_id` equals `Setting::activeTahunAkademikId()`.
- Classes already enrolled by the logged-in student still appear with `sudah_terdaftar` flag.
- `POST /api/kelas/{kelasId}/daftar` uses `$request->user()` as mahasiswa; do not accept `npm` in request body.
- If active Tahun Akademik is not set, return HTTP `422` with message `Tahun Akademik aktif belum diatur.`.
- If selected class is not in active Tahun Akademik, return HTTP `404`.
- If student is already enrolled, return HTTP `400`.
- Do not remove old route.
- Do not change CMS flow.
- Do not add pagination/search now.

---

## File Structure

- Modify `routes/mahasiswa-api.php`: register two new Sanctum-protected routes.
- Modify `app/Http/Controllers/Api/KelasController.php`: add `kelasTersedia(Request $request)`, `daftarKelasById(Request $request, $kelasId)`, and small private formatter helpers.
- No resource class: controller already builds arrays in `daftarKelas()`, and this feature is two focused methods. Avoid new file until response shape is reused elsewhere.

---

### Task 1: Add available-class listing endpoint

**Files:**
- Modify: `routes/mahasiswa-api.php`
- Modify: `app/Http/Controllers/Api/KelasController.php`

**Interfaces:**
- Consumes: `App\Models\Setting::activeTahunAkademikId(): ?string`
- Consumes: `KelasMatakuliahMahasiswa` table for enrollment flag.
- Produces: `KelasController::kelasTersedia(Request $request)` route handler.

- [ ] **Step 1: Add route**

In `routes/mahasiswa-api.php`, add this route after legacy `/kelas/daftar` route:

```php
Route::get(
    '/kelas/tersedia',
    [App\Http\Controllers\Api\KelasController::class, 'kelasTersedia']
)->middleware('auth:sanctum');
```

- [ ] **Step 2: Add helper formatter methods**

In `app/Http/Controllers/Api/KelasController.php`, before final class closing brace, add:

```php
    private function formatKelasTersedia(Kelas $kelas, array $kelasTerdaftar): array
    {
        $matakuliah = $kelas->matakuliah->first();

        return [
            'id' => $kelas->id,
            'nama_kelas' => $matakuliah
                ? $matakuliah->nama_matkul . ' - ' . $kelas->nama_kelas
                : $kelas->nama_kelas,
            'sudah_terdaftar' => in_array($kelas->id, $kelasTerdaftar),
            'prodi' => [
                'id' => $kelas->prodi->id,
                'nama_prodi' => $kelas->prodi->nama_prodi,
            ],
            'dosen' => [
                'id' => $kelas->dosen->id,
                'nidn' => $kelas->dosen->nidn,
                'nama' => $kelas->dosen->nama,
            ],
            'matakuliah' => $kelas->matakuliah->map(function ($mk) {
                return [
                    'id' => $mk->id,
                    'kode_matkul' => $mk->kode_matkul,
                    'nama_matkul' => $mk->nama_matkul,
                    'sks' => $mk->sks,
                    'semester' => $mk->semester,
                ];
            })->values(),
            'tahun_akademik' => $kelas->tahunAkademik ? [
                'id' => $kelas->tahunAkademik->id,
                'nama_tahun' => $kelas->tahunAkademik->nama_tahun,
            ] : null,
            'jadwal' => $kelas->jadwal->map(function ($jadwal) {
                return [
                    'id' => $jadwal->id,
                    'hari' => $jadwal->hari,
                    'tipe_pertemuan' => $jadwal->tipe_pertemuan,
                    'ruangan' => $jadwal->ruangan ? [
                        'id' => $jadwal->ruangan->id,
                        'nama_ruangan' => 'Ruang ' . $jadwal->ruangan->nama_ruang,
                    ] : null,
                    'jam' => $jadwal->jam ? [
                        'id' => $jadwal->jam->id,
                        'kode_jam' => $jadwal->jam->kode_jam,
                        'jam_mulai' => $jadwal->jam->jam_mulai,
                        'jam_selesai' => $jadwal->jam->jam_selesai,
                    ] : null,
                ];
            })->values(),
        ];
    }
```

- [ ] **Step 3: Add `kelasTersedia` method**

In `app/Http/Controllers/Api/KelasController.php`, place before `kelasByMahasiswa()`:

```php
    #[Group('Akses Mahasiswa')]
    public function kelasTersedia(Request $request)
    {
        try {
            $mahasiswa = $request->user();
            $activeId = Setting::activeTahunAkademikId();

            if (!$activeId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tahun Akademik aktif belum diatur.',
                ], 422);
            }

            $kelasTerdaftar = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                ->pluck('kelas_id')
                ->toArray();

            $kelas = Kelas::where('tahun_akademik_id', $activeId)
                ->with(['matakuliah', 'dosen', 'prodi', 'tahunAkademik', 'jadwal.ruangan', 'jadwal.jam'])
                ->latest()
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Daftar kelas tahun akademik aktif',
                'data' => $kelas->map(fn($item) => $this->formatKelasTersedia($item, $kelasTerdaftar))->values(),
                'meta' => [
                    'total' => $kelas->count(),
                    'tahun_akademik_aktif_id' => $activeId,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
```

- [ ] **Step 4: Run focused syntax/route check**

Run:

```bash
php -l app/Http/Controllers/Api/KelasController.php
php artisan route:list --path=api/kelas
```

Expected:
- `No syntax errors detected in app/Http/Controllers/Api/KelasController.php`
- Route list includes `GET api/kelas/tersedia`.

- [ ] **Step 5: Commit task 1**

Only if user asks for commit. Otherwise leave changes staged/unstaged.

---

### Task 2: Add enroll-by-class-id endpoint

**Files:**
- Modify: `routes/mahasiswa-api.php`
- Modify: `app/Http/Controllers/Api/KelasController.php`

**Interfaces:**
- Consumes: `Setting::activeTahunAkademikId()`.
- Consumes: `KelasTersedia` formatter from Task 1.
- Produces: `KelasController::daftarKelasById(Request $request, $kelasId)` route handler.

- [ ] **Step 1: Add route**

In `routes/mahasiswa-api.php`, add this route after `GET /kelas/tersedia`:

```php
Route::post(
    '/kelas/{kelasId}/daftar',
    [App\Http\Controllers\Api\KelasController::class, 'daftarKelasById']
)->middleware('auth:sanctum');
```

- [ ] **Step 2: Add `daftarKelasById` method**

In `app/Http/Controllers/Api/KelasController.php`, place after `kelasTersedia()`:

```php
    #[Group('Akses Mahasiswa')]
    public function daftarKelasById(Request $request, $kelasId)
    {
        try {
            $mahasiswa = $request->user();
            $activeId = Setting::activeTahunAkademikId();

            if (!$activeId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tahun Akademik aktif belum diatur.',
                ], 422);
            }

            $kelas = Kelas::where('id', $kelasId)
                ->where('tahun_akademik_id', $activeId)
                ->with(['matakuliah', 'dosen', 'prodi', 'tahunAkademik', 'jadwal.ruangan', 'jadwal.jam'])
                ->first();

            if (!$kelas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kelas tidak ditemukan pada Tahun Akademik aktif.',
                ], 404);
            }

            $existingEnrollment = KelasMatakuliahMahasiswa::where('kelas_id', $kelas->id)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();

            if ($existingEnrollment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda sudah terdaftar di kelas ini.',
                ], 400);
            }

            KelasMatakuliahMahasiswa::create([
                'kelas_id' => $kelas->id,
                'mahasiswa_id' => $mahasiswa->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendaftar ke kelas.',
                'data' => $this->formatKelasTersedia($kelas, [$kelas->id]),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
```

- [ ] **Step 3: Confirm legacy route unchanged**

Check `routes/mahasiswa-api.php` still contains:

```php
Route::post(
    '/kelas/daftar',
    [App\Http\Controllers\Api\KelasController::class, 'daftarKelas']
)->middleware('auth:sanctum');
```

Check `KelasController::daftarKelas()` still validates `kode_kelas` and `npm`.

- [ ] **Step 4: Run focused syntax/route check**

Run:

```bash
php -l app/Http/Controllers/Api/KelasController.php
php artisan route:list --path=api/kelas
```

Expected:
- `No syntax errors detected in app/Http/Controllers/Api/KelasController.php`
- Route list includes `GET api/kelas/tersedia`.
- Route list includes `POST api/kelas/{kelasId}/daftar`.
- Route list still includes `POST api/kelas/daftar`.

- [ ] **Step 5: Manual API checks**

After running migrations and setting active Tahun Akademik:

```bash
# 1. Login mahasiswa, copy bearer token.
# 2. List classes.
curl -H "Authorization: Bearer <TOKEN>" http://localhost:8000/api/kelas/tersedia

# 3. Enroll into a class from response.
curl -X POST -H "Authorization: Bearer <TOKEN>" http://localhost:8000/api/kelas/<KELAS_ID>/daftar

# 4. Repeat same enrollment. Expected HTTP 400.
curl -X POST -H "Authorization: Bearer <TOKEN>" http://localhost:8000/api/kelas/<KELAS_ID>/daftar
```

Expected:
- List returns `200`, `status: true`, `data[*].sudah_terdaftar` exists.
- First enroll returns `201`.
- Second enroll returns `400`.

- [ ] **Step 6: Commit task 2**

Only if user asks for commit. Otherwise leave changes staged/unstaged.

---

## Final Verification

- [ ] Run syntax check:

```bash
php -l app/Http/Controllers/Api/KelasController.php
```

Expected: `No syntax errors detected`.

- [ ] Run route check:

```bash
php artisan route:list --path=api/kelas
```

Expected routes include:
- `POST api/kelas/daftar`
- `GET api/kelas/tersedia`
- `POST api/kelas/{kelasId}/daftar`
- existing class/jadwal routes.

- [ ] Run test suite if local testing environment is healthy:

```bash
php artisan test
```

If it fails on existing scaffold `Tests\Feature\ExampleTest` for `GET /`, report exact failure and do not claim full suite passes.

- [ ] Confirm git status excludes generated scratch directories:

```bash
git status --short
```

Expected: only intended files changed.
