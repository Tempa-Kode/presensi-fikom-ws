# Desain Fitur: Dashboard Kaprodi (Daftar Kelas + Filter Semester + Rekap Absensi)

**Tanggal:** 14 Juli 2026
**Status:** Disetujui — menunggu review akhir sebelum implementasi

---

## Ringkasan

Dosen yang tercatat sebagai `kaprodi_id` pada tabel `prodi` mendapat akses ke tab "Kaprodi" tambahan di aplikasi mobile dosen. Tab ini menampilkan daftar seluruh kelas pada program studi yang dia ketuai, dengan filter semester matakuliah, beserta rekap absensi (hadir/izin/sakit/alfa) per kelas. Tidak ada role terpisah — status kaprodi ditentukan secara dinamis dari relasi `prodi.kaprodi_id`, bukan dari kolom `role` di tabel `users`.

## Latar Belakang & Tujuan

Sebelumnya halaman kaprodi di mobile app hanya berupa dashboard kosong (nama, NIDN, role, logout). Kaprodi butuh visibilitas terhadap seluruh kelas dan kehadiran mahasiswa di program studinya untuk keperluan monitoring akademik, tanpa perlu login dengan akun/role terpisah — karena kaprodi pada dasarnya tetap seorang dosen.

## Keputusan Desain (hasil brainstorming)

1. **Model role:** Kaprodi BUKAN nilai enum `role` terpisah. Status kaprodi ditentukan dinamis: dosen dianggap kaprodi jika `id`-nya tercatat sebagai `kaprodi_id` pada salah satu baris `prodi`.
2. **Navigasi:** Dosen login seperti biasa ke `/(dosen)`. Jika terdeteksi sebagai kaprodi, muncul tab tambahan "Kaprodi" di tab bar dosen (bukan halaman/alur login terpisah).
3. **Scope fitur iterasi ini:** Daftar kelas + filter semester + rekap absensi (hadir/izin/sakit/alfa) per kelas — bukan cuma daftar kelas kosong.
4. **Alur data:** Tidak ada proses "pengiriman" eksplisit saat sesi absensi ditutup. Kaprodi mengakses data lewat query langsung terhadap kelas dan absensi yang sudah ada di database, kapan saja diminta.

## Arsitektur

### Backend

**Model `User` — tambah relasi dan helper:**

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

**`DosenResource.php` — tambah field `is_kaprodi` dan `prodi_diketuai` ke response login:**

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

Ini menyediakan info status kaprodi begitu dosen login, tanpa request tambahan.

**Endpoint baru: `GET /kaprodi/kelas?semester=X`**

Controller baru `app/Http/Controllers/Api/KaprodiController.php`, method `daftarKelas()`:

```php
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
        ->with(['dosen', 'matakuliah', 'jadwal.sesiKuliah.absensi']);

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
```

**Resource baru: `app/Http/Resources/KaprodiKelasResource.php`**

```php
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
```

**Catatan desain kunci:**
- Filter semester bekerja di level `matakuliah.semester` (bukan level `kelas`), karena semester adalah atribut matakuliah, dan satu kelas bisa punya lebih dari satu matakuliah via tabel pivot `matakuliah_kelas`.
- Rekap absensi dihitung dari **seluruh riwayat sesi** milik kelas tersebut (bukan hanya sesi yang baru ditutup), sesuai keputusan "query langsung tanpa proses pengiriman terpisah".
- Sesi yang masih berstatus "buka" tetap ikut terhitung apa adanya — mahasiswa yang sudah absen masuk hitungan, yang belum absen tidak masuk hitungan manapun sampai sesi ditutup dan proses existing menandai mereka "alfa".

### Mobile

**`app/(dosen)/_layout.tsx` — tab kondisional:**

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
    <Tabs screenOptions={{ /* unchanged */ }}>
      <Tabs.Screen name="index" options={{ /* unchanged */ }} />
      <Tabs.Screen name="kelas" options={{ /* unchanged */ }} />
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
      <Tabs.Screen name="profil" options={{ /* unchanged */ }} />
    </Tabs>
  );
}
```

`href: null` menyembunyikan tab dari tab bar SEKALIGUS memblokir navigasi langsung ke halaman itu — dosen non-kaprodi tidak bisa mengaksesnya sama sekali, bukan cuma tersembunyi secara visual.

**Halaman baru: `app/(dosen)/kaprodi/index.tsx`**

Berisi:
- Filter dropdown semester (mengikuti pola filter hari yang sudah ada di [app/(dosen)/kelas/index.tsx](mobile/app/(dosen)/kelas/index.tsx))
- List kelas di prodi yang diketuai — tiap kartu menampilkan nama kelas, dosen pengampu, matakuliah + semester, dan ringkasan rekap absensi
- Data diambil dari `GET /kaprodi/kelas?semester=X`

## Penanganan Error

| Kondisi | HTTP Status | Pesan |
|---|---|---|
| Dosen bukan kaprodi mengakses endpoint kaprodi | 403 | "Anda tidak terdaftar sebagai kaprodi." |
| Tidak ada tahun akademik aktif | mengikuti pola existing di `kelasTersedia()` — 422 "Tahun Akademik aktif belum diatur." (opsional, konsisten dengan endpoint lain jika `Setting::activeTahunAkademikId()` diperlukan strict) |
| Semester filter tidak cocok kelas manapun | 200, `data: []`, `meta.total_kelas: 0` (bukan error — daftar kosong itu valid) |

## Testing

**Unit/Feature test (backend):**
- `User::isKaprodi()` mengembalikan `true` untuk dosen yang `id`-nya = `kaprodi_id` suatu prodi, `false` untuk dosen lain.
- `GET /kaprodi/kelas` mengembalikan 403 untuk dosen non-kaprodi.
- `GET /kaprodi/kelas` mengembalikan hanya kelas dengan `prodi_id` sesuai prodi yang diketuai.
- `GET /kaprodi/kelas?semester=3` hanya mengembalikan kelas yang punya matakuliah semester 3.
- Rekap absensi (`hadir`/`izin`/`sakit`/`alfa`) menghitung benar dari data lintas sesi.
- `DosenResource` menyertakan `is_kaprodi: true` untuk dosen kaprodi dan `is_kaprodi: false` untuk dosen biasa.

**Testing manual:**
- Login sebagai dosen biasa → pastikan tab "Kaprodi" TIDAK muncul di tab bar.
- Login sebagai dosen yang juga kaprodi → pastikan tab "Kaprodi" muncul.
- Buka tab Kaprodi → pastikan daftar kelas sesuai prodi yang diketuai (bukan prodi lain).
- Ganti filter semester → daftar kelas ter-filter sesuai.
- Verifikasi angka rekap absensi (hadir/izin/sakit/alfa) cocok dengan data yang terlihat di halaman "Rekap Absensi" dosen pengampu kelas tersebut.

## Ruang Lingkup

Fitur ini terbatas pada:
- Deteksi status kaprodi dinamis dari `prodi.kaprodi_id`.
- Tab kondisional di app dosen.
- Endpoint daftar kelas + filter semester + rekap absensi ringkas per kelas.

Di luar ruang lingkup (tidak dibahas/dibangun di iterasi ini):
- Notifikasi/proses "pengiriman" eksplisit saat sesi absensi ditutup (keputusan Anda: query langsung, tanpa proses ini).
- Detail absensi per mahasiswa di level kaprodi (hanya rekap angka agregat per kelas untuk iterasi ini).
- Halaman dekan (di luar scope permintaan ini, meski struktur mirip bisa dipakai nanti).
- Export PDF/Excel rekap kaprodi.
