# Desain API Daftar Kelas Tahun Akademik Aktif

## Context
Flow lama pendaftaran kelas mahasiswa memakai `POST /api/kelas/daftar` dengan `kode_kelas` dan `npm`. Flow baru tidak memakai kode kelas. Mahasiswa harus melihat semua kelas pada Tahun Akademik aktif dari CMS Pengaturan, lalu memilih kelas untuk didaftarkan.

Tahun Akademik aktif sudah disimpan di tabel `settings` lewat key `tahun_akademik_aktif_id` dan helper `App\Models\Setting::activeTahunAkademikId()`.

## Keputusan
- Tambah route baru untuk flow baru.
- Route lama `POST /api/kelas/daftar` tetap ada sementara untuk kompatibilitas mobile lama.
- List kelas tersedia menampilkan semua kelas pada Tahun Akademik aktif.
- Kelas yang sudah didaftarkan tetap tampil, dengan flag `sudah_terdaftar`.
- Pendaftaran kelas baru memakai user dari token Sanctum, bukan `npm` dari request body.

## API baru

### 1. List kelas tersedia

Route:

```php
GET /api/kelas/tersedia
```

Controller:

```php
App\Http\Controllers\Api\KelasController::kelasTersedia
```

Behavior:
1. Ambil mahasiswa dari `$request->user()`.
2. Ambil Tahun Akademik aktif dari `Setting::activeTahunAkademikId()`.
3. Jika Tahun Akademik aktif belum diatur, return `422`.
4. Ambil semua `Kelas` dengan `tahun_akademik_id` sama dengan Tahun Akademik aktif.
5. Load relasi `matakuliah`, `dosen`, `prodi`, `tahunAkademik`, `jadwal.ruangan`, `jadwal.jam`.
6. Untuk setiap kelas, hitung apakah mahasiswa sudah punya row di `kelas_matakuliah_mahasiswa`.
7. Return data kelas dengan flag `sudah_terdaftar`.

Response sukses:

```json
{
  "status": true,
  "message": "Daftar kelas tahun akademik aktif",
  "data": [
    {
      "id": 1,
      "nama_kelas": "Pemrograman Web - A",
      "sudah_terdaftar": false,
      "prodi": {
        "id": 1,
        "nama_prodi": "Teknik Informatika"
      },
      "dosen": {
        "id": 2,
        "nidn": "0114046501",
        "nama": "Nama Dosen"
      },
      "matakuliah": [
        {
          "id": 1,
          "kode_matkul": "IF101",
          "nama_matkul": "Pemrograman Web",
          "sks": 3,
          "semester": 5
        }
      ],
      "tahun_akademik": {
        "id": 1,
        "nama_tahun": "2025/2026"
      },
      "jadwal": [
        {
          "id": 1,
          "hari": "senin",
          "tipe_pertemuan": "teori",
          "ruangan": {
            "id": 1,
            "nama_ruangan": "Ruang 101"
          },
          "jam": {
            "id": 1,
            "kode_jam": "J1",
            "jam_mulai": "08:00:00",
            "jam_selesai": "09:40:00"
          }
        }
      ]
    }
  ],
  "meta": {
    "total": 1,
    "tahun_akademik_aktif_id": "1"
  }
}
```

Jika Tahun Akademik aktif belum diatur:

```json
{
  "status": false,
  "message": "Tahun Akademik aktif belum diatur."
}
```

HTTP status: `422`.

### 2. Daftar ke kelas pilihan

Route:

```php
POST /api/kelas/{kelasId}/daftar
```

Controller:

```php
App\Http\Controllers\Api\KelasController::daftarKelasById
```

Behavior:
1. Ambil mahasiswa dari `$request->user()`.
2. Ambil Tahun Akademik aktif dari `Setting::activeTahunAkademikId()`.
3. Jika Tahun Akademik aktif belum diatur, return `422`.
4. Cari kelas berdasarkan `{kelasId}` dan `tahun_akademik_id` aktif.
5. Jika kelas tidak ditemukan, return `404`.
6. Cek duplicate enrollment di `kelas_matakuliah_mahasiswa`.
7. Jika sudah terdaftar, return `400`.
8. Insert `kelas_id` dan `mahasiswa_id` ke `kelas_matakuliah_mahasiswa`.
9. Return detail kelas.

Request body: kosong.

Response sukses: `201`.

Error:
- `422`: Tahun Akademik aktif belum diatur.
- `404`: kelas tidak ditemukan di Tahun Akademik aktif.
- `400`: mahasiswa sudah terdaftar.

## Route lama

Route lama tetap ada:

```php
POST /api/kelas/daftar
```

Behavior lama tetap memakai `kode_kelas` dan `npm`. Route ini dianggap kompatibilitas sementara. Mobile baru harus memakai route baru.

## File yang akan diubah
- `routes/mahasiswa-api.php`
- `app/Http/Controllers/Api/KelasController.php`

Opsional jika ingin rapi:
- `app/Http/Resources/KelasTersediaResource.php`

## Testing
Minimal verification:
1. `php -l app/Http/Controllers/Api/KelasController.php`
2. `php artisan route:list --path=api/kelas`
3. Manual API check:
   - login mahasiswa untuk token
   - `GET /api/kelas/tersedia`
   - `POST /api/kelas/{kelasId}/daftar`
   - ulangi `POST` yang sama dan pastikan return duplicate `400`
   - pastikan kelas tahun akademik lama tidak bisa didaftarkan lewat route baru

## Scope yang tidak dikerjakan
- Tidak menghapus route lama.
- Tidak mengubah flow CMS.
- Tidak menambah search/pagination sampai data kelas besar.
