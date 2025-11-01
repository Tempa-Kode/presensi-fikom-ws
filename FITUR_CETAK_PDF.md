# Fitur Cetak PDF Daftar Hadir Mahasiswa

## Deskripsi

Fitur ini memungkinkan untuk mencetak daftar hadir peserta matakuliah dalam format PDF dengan format surat resmi universitas yang mencakup:

-   Header/kop surat universitas dengan logo
-   Informasi lengkap mata kuliah
-   Tabel absensi 16 pertemuan
-   Keterangan status kehadiran
-   Tanda tangan Ketua Program Studi

## Teknologi yang Digunakan

-   **Laravel DomPDF** (`barryvdh/laravel-dompdf`)
-   Blade Template Engine
-   CSS untuk styling PDF

## File yang Terlibat

### 1. Controller

**File**: `app/Http/Controllers/KelasController.php`

-   Method `cetakAbsensiPDF()`: Generate PDF dari data absensi

### 2. Route

**File**: `routes/web.php`

```php
Route::get('/{kelasId}/jadwal/{jadwalId}/absensi/pdf',
    [KelasController::class, 'cetakAbsensiPDF'])
    ->name('data.kelas.absensi.pdf');
```

### 3. View Template

-   **Web View**: `resources/views/kelas/absensi.blade.php` (tampilan browser)
-   **PDF View**: `resources/views/kelas/absensi-pdf.blade.php` (template PDF)

### 4. Konfigurasi

**File**: `config/dompdf.php`

## Cara Penggunaan

1. **Akses Halaman Daftar Hadir**

    - Buka menu Kelas
    - Pilih kelas yang ingin dilihat
    - Klik tombol "Daftar Hadir" pada jadwal tertentu

2. **Cetak PDF**

    - Klik tombol **"Cetak PDF"** (biru)
    - File PDF akan otomatis terdownload
    - Nama file: `Daftar_Hadir_{NamaKelas}_{Timestamp}.pdf`

3. **Alternatif: Print Browser**
    - Klik tombol **"Print Browser"** untuk print langsung dari browser

## Format PDF

### Header Surat

-   Logo Universitas (jika tersedia)
-   Nama Universitas: UNIVERSITAS KATOLIK SANTO THOMAS
-   Fakultas: FAKULTAS ILMU KOMPUTER
-   Alamat dan kontak lengkap

### Informasi Mata Kuliah

-   Mata Kuliah / SKS
-   Program Studi
-   Dosen
-   Semester / Kelas
-   Hari, Jam, Ruang
-   Tahun Ajaran

### Tabel Absensi

-   Kolom: No, NPM, Nama Mahasiswa, Pertemuan 1-16
-   Status: ✓ (Hadir), I (Izin), S (Sakit), A (Alfa)

### Footer

-   Tanda tangan Ketua Program Studi
-   Tanggal cetak otomatis
-   Catatan waktu cetak

## Konfigurasi Khusus

### Ukuran Kertas

-   **Format**: A4 Landscape (297mm x 210mm)
-   **Margin**: 15mm (atas/bawah), 10mm (kiri/kanan)

### Font

-   **Family**: Arial, sans-serif
-   **Size**:
    -   Body: 9pt
    -   Header: 11-14pt
    -   Tabel: 8pt
    -   Pertemuan: 7pt

## Customisasi

### Mengubah Logo

1. Upload logo baru ke `public/assets/images/`
2. Update path di template PDF:

```blade
<img src="{{ public_path('assets/images/logo-anda.png') }}" alt="Logo" class="logo">
```

### Mengubah Informasi Universitas

Edit file `resources/views/kelas/absensi-pdf.blade.php`:

```html
<h1>NAMA UNIVERSITAS ANDA</h1>
<h2>NAMA FAKULTAS ANDA</h2>
<p>Alamat lengkap...</p>
```

### Menambah/Mengurangi Jumlah Pertemuan

Ubah loop pertemuan di template:

```php
@for ($i = 1; $i <= 16; $i++) // Ganti 16 dengan jumlah yang diinginkan
```

### Mengubah Data Tanda Tangan

Pastikan model `Prodi` memiliki field:

-   `ketua_prodi`: Nama Ketua Program Studi
-   `kode_ketua`: NIDN/Kode Ketua Prodi

## Troubleshooting

### PDF Tidak Muncul Logo

-   Pastikan file logo ada di `public/assets/images/`
-   Cek format logo (PNG/JPG/SVG)
-   Gunakan absolute path: `public_path()`

### Layout PDF Berantakan

-   Cek CSS di template PDF
-   Pastikan tidak ada conflicting styles
-   Test dengan data yang berbeda-beda

### Error 500

-   Cek log Laravel: `storage/logs/laravel.log`
-   Pastikan package DomPDF terinstall dengan benar
-   Clear cache: `php artisan cache:clear`

### PDF Download Lambat

-   Optimalkan jumlah data yang diload
-   Compress gambar logo jika terlalu besar
-   Enable caching jika diperlukan

## Dependencies

```json
{
    "barryvdh/laravel-dompdf": "^3.1"
}
```

## Instalasi Package (Jika Belum)

```bash
composer require barryvdh/laravel-dompdf
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

## Lisensi & Credit

-   Laravel Framework
-   DomPDF by Barryvdh
-   Font: Arial (system font)

---

**Dibuat**: November 2025  
**Update Terakhir**: November 2025  
**Developer**: Tim Pengembangan Sistem Presensi FIKOM
