# Desain Fitur: OTP untuk Absensi Mahasiswa

**Tanggal:** 14 Juli 2026
**Status:** Disetujui — menunggu review akhir sebelum implementasi

---

## Ringkasan

Saat dosen membuka sesi absensi, sistem otomatis meng-generate kode OTP 4 digit yang ditampilkan permanen di kartu kelas dosen selama sesi berjalan. Mahasiswa wajib memasukkan kode OTP tersebut (bersamaan dengan validasi GPS yang sudah ada) saat menekan tombol "Hadir". Fitur ini menambah lapisan verifikasi kedua di luar GPS, untuk memastikan mahasiswa benar-benar hadir secara fisik dan mendengar/melihat kode dari dosen secara langsung.

## Latar Belakang & Tujuan

Sistem absensi saat ini hanya memvalidasi lokasi GPS (radius 5 meter dari ruangan). Ini rentan terhadap GPS spoofing atau mahasiswa yang absen dari luar kelas menggunakan lokasi palsu. Menambahkan OTP yang hanya diketahui dosen dan disampaikan langsung ke mahasiswa di kelas menutup celah ini — mahasiswa harus benar-benar berada di kelas untuk mendapatkan kodenya.

## Keputusan Desain (hasil brainstorming)

1. **Tampilan OTP:** Ditampilkan permanen di kartu kelas dosen (bukan modal sekali muncul), selama status sesi = "buka".
2. **Regenerasi OTP:** OTP baru di-generate setiap kali dosen membuka sesi — termasuk saat membuka ulang jadwal yang sama di hari yang sama.
3. **Percobaan gagal:** Maksimal 3 kali salah input per mahasiswa per sesi, lalu lockout.
4. **Durasi lockout:** 5 menit sejak percobaan ke-3 yang gagal.
5. **Alur validasi:** OTP dan GPS divalidasi bersamaan di server dalam satu request — tidak ada validasi OTP terpisah sebelum GPS check.
6. **Testing:** Fokus pada testing manual di device fisik; unit test ditambahkan untuk logic yang straightforward (validasi OTP, counter lockout, kalkulasi jarak).

## Arsitektur

### Skema Database

**Migration: tambah kolom `otp_code` ke `sesi_kuliah`**

```php
Schema::table('sesi_kuliah', function (Blueprint $table) {
    $table->string('otp_code', 4)->nullable()->after('longitude');
});
```

**Migration: tabel baru `absensi_otp_attempts`**

```php
Schema::create('absensi_otp_attempts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sesi_kuliah_id')->constrained('sesi_kuliah')->onDelete('cascade');
    $table->foreignId('mahasiswa_id')->constrained('users')->onDelete('cascade');
    $table->unsignedTinyInteger('failed_count')->default(0);
    $table->timestamp('locked_until')->nullable();
    $table->timestamps();
    $table->unique(['sesi_kuliah_id', 'mahasiswa_id']);
});
```

Alasan memisahkan tabel attempt dari `absensi`: `absensi` merepresentasikan kehadiran yang berhasil/tervalidasi, sedangkan attempt tracking adalah data keamanan sementara yang tidak seharusnya bercampur secara semantik dengan record kehadiran resmi.

### Model

**`SesiKuliah`** — tambah `otp_code` ke `$fillable`, tambah relasi `otpAttempts()`.

**`AbsensiOtpAttempt`** (baru) — `$fillable`: `sesi_kuliah_id`, `mahasiswa_id`, `failed_count`, `locked_until` (cast `datetime`). Relasi `belongsTo` ke `SesiKuliah` dan `User`.

### Backend — Perubahan Endpoint

**`buatSesiAbsensi()`** (`AbsensiController`) — generate `otp_code` 4 digit (`str_pad(random_int(0,9999), 4, '0', STR_PAD_LEFT)`) saat `SesiKuliah::create()`. Otomatis ikut ke response karena kolom biasa di resource.

**`absensi()`** (`AbsensiController`) — urutan validasi baru:
1. Validasi request: tambah field `otp_code` (`required|digits:4`).
2. Cek sesi masih buka (sudah ada).
3. `firstOrCreate` record `AbsensiOtpAttempt` untuk sesi+mahasiswa ini.
4. Jika `locked_until` masih di masa depan → return `429` dengan sisa waktu tunggu.
5. Jika `otp_code` request ≠ `otp_code` sesi → increment `failed_count`; jika mencapai 3, set `locked_until = now() + 5 menit` dan reset `failed_count` ke 0; return `422`.
6. Jika OTP benar → lanjut ke pengecekan jarak GPS (existing, tidak diubah), lalu pengecekan terdaftar-di-kelas dan duplikasi absensi (existing, tidak diubah).
7. Setelah `Absensi::create()` sukses → hapus record `AbsensiOtpAttempt` terkait.

Penting: kegagalan GPS (di luar radius) **tidak** dihitung sebagai percobaan OTP gagal — hanya kesalahan kode OTP yang menambah `failed_count`.

### Mobile — Perubahan Komponen

**`ClassDosenItem.tsx`** — tambah prop `otpCode` dan `statusAbsensi`. Tampilkan badge OTP (angka besar, letter-spacing lebar) hanya saat `statusAbsensi === "buka"`.

**Sumber data dosen** (`getCoursesByLecturer()` / resource Laravel terkait, misal `JadwalKelasDosenResource`) — perlu di-update untuk menyertakan `otp_code` dan `status_absensi` dari sesi aktif per jadwal.

**`AbsensiMapModal.tsx`** — tambah `TextInput` untuk OTP (4 digit, numeric) di atas peta. `onSubmit` callback berubah signature: `(otpCode, latitude, longitude)`. Tombol submit disabled sampai OTP 4 digit terisi dan lokasi terdeteksi (validasi UI saja, bukan validasi keamanan).

**`lib/models/absensi.ts`** — `submitHadirHandler` menerima parameter `otpCode` tambahan, dikirim sebagai `otp_code` di payload API.

**`app/(mahasiswa)/absensiAktif.tsx`** — `handleSubmitHadir` diperbarui untuk menerima dan menyalurkan `otpCode`.

## Penanganan Error

| Kondisi | HTTP Status | Pesan |
|---|---|---|
| OTP salah (percobaan 1-2) | 422 | "Kode OTP salah." |
| OTP salah (percobaan ke-3) | 422 | "Kode OTP salah." (+ lockout diaktifkan) |
| Sedang lockout | 429 | "Terlalu banyak percobaan salah. Coba lagi dalam X menit." |
| OTP benar, GPS di luar radius | 403 | "Anda berada di luar jangkauan ruangan kelas. Jarak Anda: X meter dari ruangan." (existing, tidak dihitung sebagai gagal OTP) |
| Sesi sudah ditutup | 404 | "Sesi absensi sudah ditutup." (existing) |
| Sudah absen di sesi ini | 400 | "Anda sudah melakukan absensi untuk sesi ini." (existing) |
| Tidak terdaftar di kelas | 403 | "Anda tidak terdaftar di kelas untuk sesi ini." (existing) |

Error dari server disalurkan ke UI mobile lewat jalur `Alert.alert` yang sudah ada di `handleSubmitHadir` — tidak perlu penanganan kode khusus untuk kasus lockout, karena pesan error dari `error.response.data.message` sudah otomatis ditampilkan.

## Testing

Prioritas: **testing manual**, dengan unit test tambahan untuk logic yang deterministik dan tidak butuh device fisik:

**Unit test (disarankan, backend):**
- Generate OTP menghasilkan string 4 digit dengan leading zero jika perlu.
- Validasi OTP salah menaikkan `failed_count`.
- `failed_count` mencapai 3 → `locked_until` ter-set dan `failed_count` reset ke 0.
- Request saat `locked_until` masih di masa depan → ditolak dengan 429.
- Request setelah `locked_until` lewat → diterima (tidak lagi 429).
- Kalkulasi jarak Haversine (fungsi `hitungJarak`) — sudah ada logic-nya, tinggal ditambah test case.

**Testing manual (wajib, di device fisik):**
- Dosen buka sesi → OTP tampil di kartu kelas → tutup sesi → OTP hilang.
- Mahasiswa input OTP benar + lokasi valid → absensi tercatat.
- Mahasiswa input OTP salah 3x → lockout, pesan sisa waktu tampil.
- Setelah 5 menit, mahasiswa bisa coba lagi.
- Dosen buka-tutup-buka ulang sesi yang sama di hari yang sama → OTP berbeda dari sebelumnya.
- Verifikasi end-to-end lintas device: dosen buka sesi di device A, mahasiswa input OTP di device B.

## Ruang Lingkup

Fitur ini terbatas pada:
- Generate & tampilkan OTP di sisi dosen.
- Input & validasi OTP di sisi mahasiswa, terintegrasi dengan flow absensi GPS yang sudah ada.
- Lockout sederhana berbasis percobaan gagal.

Di luar ruang lingkup (tidak dibahas/dibangun di iterasi ini):
- Notifikasi push saat OTP di-generate.
- Audit log / riwayat percobaan OTP untuk keperluan reporting admin.
- Rotasi OTP otomatis berkala (time-based, seperti authenticator app).
