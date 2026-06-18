<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi Pertemuan Kuliah</title>
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 9pt;
            line-height: 1.3;
            padding: 10mm 5mm;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .kop-surat .logo {
            width: 90px;
            height: 90px;
            float: left;
            margin-right: 15px;
        }

        .kop-surat .header-text {
            text-align: center;
        }

        .kop-surat h1 {
            font-size: 20pt;
            margin-bottom: 2px;
        }

        .kop-surat h2 {
            font-size: 15pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .kop-surat p {
            font-size: 9pt;
            font-weight: bold;
        }

        .clear {
            clear: both;
        }

        .judul {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            margin: 15px 0 10px 0;
        }

        .info-matkul {
            margin-bottom: 10px;
        }

        .info-matkul table {
            width: 100%;
            font-size: 8.5pt;
        }

        .info-matkul table td {
            padding: 2px 5px;
            vertical-align: top;
        }

        .info-matkul table td:first-child {
            width: 150px;
            font-weight: bold;
        }

        .info-sesi {
            margin-bottom: 15px;
            border: 1px solid #ccc;
            padding: 8px;
            background-color: #f9f9f9;
        }

        .info-sesi table {
            width: 100%;
            font-size: 8.5pt;
        }

        .info-sesi table td {
            padding: 2px 5px;
        }

        .tabel-absensi {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8pt;
        }

        .tabel-absensi th,
        .tabel-absensi td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center;
        }

        .tabel-absensi th {
            background-color: #e0e0e0;
            font-weight: bold;
        }

        .tabel-absensi td.nama {
            text-align: left;
            padding-left: 5px;
        }

        .tabel-absensi td.npm {
            text-align: left;
            padding-left: 5px;
        }

        .tabel-absensi td.keterangan {
            text-align: left;
            padding-left: 5px;
        }

        .status-hadir {
            color: green;
            font-weight: bold;
        }

        .status-izin {
            color: blue;
            font-weight: bold;
        }

        .status-sakit {
            color: orange;
            font-weight: bold;
        }

        .status-alfa {
            color: red;
            font-weight: bold;
        }

        .ttd-section {
            margin-top: 30px;
            width: 100%;
        }

        .ttd-container {
            float: left;
            width: 48%;
            text-align: center;
        }

        .ttd-container.right {
            float: right;
        }

        .ttd-container p {
            margin: 5px 0;
        }

        .ttd-space {
            height: 80px;
        }

        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <!-- KOP SURAT -->
    <div class="kop-surat">
        @if (file_exists(public_path("assets/images/logo-fikom.svg")))
            <img src="{{ public_path("assets/images/logo-fikom.svg") }}" alt="Logo" class="logo">
        @endif
        <div class="header-text">
            <h1>UNIVERSITAS KATOLIK SANTO THOMAS</h1>
            <h2>FAKULTAS ILMU KOMPUTER</h2>
            <p>Jalan Setia Budi No. 479-F Tanjung Sari - Medan 20132</p>
            <p>Telp: (061) 821016 (4 Lines), Fax: (061) 8213269, Hp : 081264935370</p>
        </div>
    </div>
    <div class="clear"></div>

    <!-- JUDUL -->
    <div class="judul">
        LAPORAN PRESENSI PERTEMUAN KULIAH<br>
        Tahun Ajaran {{ $kelas->tahunAkademik->nama_tahun ?? "-" }}<br>
        Program Studi {{ ucwords(strtolower($kelas->prodi->nama_prodi ?? "-")) }}
    </div>

    <!-- INFO MATA KULIAH -->
    <div class="info-matkul">
        <table>
            <tr>
                <td>Mata Kuliah / SKS</td>
                <td>: {{ $kelas->matakuliah->first()->kode_matkul ?? "-" }} /
                    {{ $kelas->matakuliah->first()->nama_matkul ?? "-" }} /
                    {{ $kelas->matakuliah->first()->sks ?? "-" }} SKS</td>
                <td width="150">Hari / Jam Kuliah</td>
                <td>: {{ ucfirst($jadwal->hari ?? "-") }} / {{ $jadwal->jam->kode_jam ?? "-" }}</td>
            </tr>
            <tr>
                <td>Program Studi</td>
                <td>: {{ $kelas->prodi->nama_prodi ?? "-" }}</td>
                <td>Ruang</td>
                <td>: {{ $jadwal->ruangan->nama_ruang ?? "-" }}</td>
            </tr>
            <tr>
                <td>Dosen Pengampu</td>
                <td>: {{ $kelas->dosen->nama ?? "-" }}</td>
                <td>Semester / Kelas</td>
                <td>: {{ $kelas->nama_kelas }}</td>
            </tr>
        </table>
    </div>

    <!-- INFO SESI PERTEMUAN -->
    <div class="info-sesi">
        <table style="width: 100%;">
            <tr>
                <td width="120"><strong>Pertemuan Ke-</strong></td>
                <td>: {{ $pertemuanKe }}</td>
                <td width="150"><strong>Waktu Buka Absensi</strong></td>
                <td>: {{ $sesi->waktu_buka ? \Carbon\Carbon::parse($sesi->waktu_buka)->format('H:i') : '-' }} WIB</td>
            </tr>
            <tr>
                <td><strong>Tanggal</strong></td>
                <td>: {{ \Carbon\Carbon::parse($sesi->tanggal)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</td>
                <td><strong>Waktu Tutup Absensi</strong></td>
                <td>: {{ $sesi->waktu_tutup ? \Carbon\Carbon::parse($sesi->waktu_tutup)->format('H:i') : '-' }} WIB</td>
            </tr>
            <tr>
                <td><strong>Status Sesi</strong></td>
                <td>: <span style="text-transform: uppercase; font-weight: bold;">{{ $sesi->status_absensi }}</span></td>
                <td><strong>Statistik Kehadiran</strong></td>
                <td>: 
                    Hadir: {{ $stats['hadir'] }} | 
                    Sakit: {{ $stats['sakit'] }} | 
                    Izin: {{ $stats['izin'] }} | 
                    Alfa: {{ $stats['alfa'] }} | 
                    Belum Absen: {{ $stats['belum_absen'] }}
                    (Total: {{ $stats['total'] }})
                </td>
            </tr>
        </table>
    </div>

    <!-- TABEL ABSENSI -->
    <table class="tabel-absensi">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 100px;">NPM</th>
                <th style="min-width: 200px;">Nama Mahasiswa</th>
                <th style="width: 100px;">Waktu Presensi</th>
                <th style="width: 100px;">Status Kehadiran</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kelas->mahasiswa as $index => $mahasiswa)
                @php
                    $absensi = $absensiMap->get($mahasiswa->id);
                    $pengajuan = $pengajuanMap->get($mahasiswa->id);
                    $status = $absensi ? $absensi->status : null;
                    $waktuAbsen = $absensi && $absensi->waktu_absensi ? \Carbon\Carbon::parse($absensi->waktu_absensi)->format('H:i:s') : '-';
                    $keterangan = $pengajuan ? $pengajuan->keterangan : '';
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="npm">{{ $mahasiswa->npm }}</td>
                    <td class="nama">{{ strtoupper($mahasiswa->nama) }}</td>
                    <td>{{ $waktuAbsen }}</td>
                    <td>
                        @if ($status === 'hadir')
                            <span class="status-hadir">HADIR</span>
                        @elseif ($status === 'izin')
                            <span class="status-izin">IZIN</span>
                        @elseif ($status === 'sakit')
                            <span class="status-sakit">SAKIT</span>
                        @elseif ($status === 'alfa')
                            <span class="status-alfa">ALFA</span>
                        @else
                            <span>-</span>
                        @endif
                    </td>
                    <td class="keterangan">{{ $keterangan ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Tidak ada mahasiswa terdaftar</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TANDA TANGAN -->
    <div class="ttd-section">
        <p style="text-align: center; font-size: 12px; margin-bottom: 5px;">Medan, {{ \Carbon\Carbon::now()->locale("id")->isoFormat("D MMMM YYYY") }}</p>
        <div class="ttd-container">
            <p>Ketua Program Studi,</p>
            <div class="ttd-space"></div>
            <p class="ttd-nama">{{ strtoupper($kelas->prodi->kaprodi->nama ?? "___________________") }}</p>
        </div>

        <div class="ttd-container right">
            <p>Dosen Ybs,</p>
            <div class="ttd-space"></div>
            <p class="ttd-nama">{{ strtoupper($kelas->dosen->nama ?? "___________________") }}</p>
        </div>
    </div>
    <div class="clear"></div>

</body>

</html>
