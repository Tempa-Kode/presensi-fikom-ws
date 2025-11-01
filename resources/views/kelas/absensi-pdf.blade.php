<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Hadir Peserta Matakuliah</title>
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
            /* font-weight: bold; */
            margin-bottom: 2px;
        }

        .kop-surat h2 {
            font-size: 15pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .kop-surat p {
            font-size: 9pt;
            /* margin: 1px 0; */
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

        .tabel-absensi {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8pt;
        }

        .tabel-absensi th,
        .tabel-absensi td {
            border: 1px solid #000;
            padding: 4px 3px;
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

        .tabel-absensi .pertemuan {
            width: 18px;
            font-size: 7pt;
        }

        .keterangan {
            margin-top: 15px;
            font-size: 8.5pt;
        }

        .keterangan .legend {
            display: inline-block;
            margin-right: 20px;
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
        DAFTAR HADIR PESERTA MATAKULIAH<br>
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
                <td width="150">Hari</td>
                <td>: {{ ucfirst($jadwal->hari ?? "-") }}</td>
            </tr>
            <tr>
                <td>Program Studi</td>
                <td>: {{ $kelas->prodi->nama_prodi ?? "-" }}</td>
                <td>Jam</td>
                <td>: {{ $jadwal->jam->kode_jam ?? "-" }}</td>
            </tr>
            <tr>
                <td>Dosen</td>
                <td>: {{ $kelas->dosen->nama ?? "-" }}</td>
                <td>Ruang</td>
                <td>: {{ $jadwal->ruangan->nama_ruang ?? "-" }}</td>
            </tr>
            <tr>
                <td>Semester / Kelas</td>
                <td>: {{ $kelas->nama_kelas }}</td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>

    <!-- TABEL ABSENSI -->
    <table class="tabel-absensi">
        <thead>
            <tr>
                <th rowspan="2" style="width: 30px;">No</th>
                <th rowspan="2" style="width: 80px;">NPM</th>
                <th rowspan="2" style="min-width: 150px;">Nama Mahasiswa</th>
                <th colspan="16">Pertemuan Ke-/Tanggal</th>
            </tr>
            <tr>
                @for ($i = 1; $i <= 16; $i++)
                    <th class="pertemuan">{{ $i }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @forelse ($kelas->mahasiswa as $index => $mahasiswa)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="npm">{{ $mahasiswa->npm }}</td>
                    <td class="nama">{{ strtoupper($mahasiswa->nama) }}</td>
                    @for ($i = 1; $i <= 16; $i++)
                        <td class="pertemuan">
                            @if (isset($absensiData[$mahasiswa->id][$i]))
                                @php
                                    $status = $absensiData[$mahasiswa->id][$i];
                                    switch ($status) {
                                        case "hadir":
                                            echo "H";
                                            break;
                                        case "izin":
                                            echo "I";
                                            break;
                                        case "sakit":
                                            echo "S";
                                            break;
                                        case "alfa":
                                            echo "A";
                                            break;
                                        default:
                                            echo "-";
                                    }
                                @endphp
                            @else
                                -
                            @endif
                        </td>
                    @endfor
                </tr>
            @empty
                <tr>
                    <td colspan="19">Tidak ada mahasiswa terdaftar</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- KETERANGAN -->
    <div class="keterangan">
        <strong>Keterangan:</strong><br>
        <div style="margin-top: 5px;">
            <span class="legend">H = Hadir</span>
            <span class="legend">I = Izin</span>
            <span class="legend">S = Sakit</span>
            <span class="legend">A = Alfa</span>
        </div>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-section">
        <p style="text-align: center; font-size: 12px">Medan, {{ \Carbon\Carbon::now()->locale("id")->isoFormat("D MMMM YYYY") }}</p>
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
