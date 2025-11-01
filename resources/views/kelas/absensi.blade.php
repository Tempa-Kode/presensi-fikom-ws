@extends("template")
@section("title", "Daftar Hadir Peserta Matakuliah")
@section("body")
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card mt-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">DAFTAR HADIR PESERTA MATAKULIAH</h3>
                    </div>
                    <div class="card-body">
                        <!-- Header Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td width="150"><strong>Mata Kuliah / SKS</strong></td>
                                        <td>: {{ $kelas->matakuliah->first()->kode_matkul ?? "-" }}
                                            {{ $kelas->matakuliah->first()->nama_matkul ?? "-" }} /
                                            {{ $kelas->matakuliah->first()->sks ?? "-" }} SKS</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Dosen</strong></td>
                                        <td>: {{ $kelas->dosen->nidn ?? "-" }} - {{ $kelas->dosen->nama ?? "-" }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Semester / Kelas</strong></td>
                                        <td>: {{ $kelas->matakuliah->first()->semester ?? "-" }} / {{ $kelas->nama_kelas }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td width="150"><strong>Tahun Ajaran</strong></td>
                                        <td>: {{ $kelas->tahunAkademik->nama_tahun ?? "-" }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Program Studi</strong></td>
                                        <td>: {{ $kelas->prodi->nama_prodi ?? "-" }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tipe Pertemuan</strong></td>
                                        <td>: <span
                                                class="badge badge-info text-uppercase">{{ $jadwal->tipe_pertemuan ?? "-" }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Hari</strong></td>
                                        <td>: {{ ucfirst($jadwal->hari ?? "-") }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jam</strong></td>
                                        <td>: {{ $jadwal->jam->kode_jam ?? "-" }} ({{ $jadwal->jam->jam_mulai ?? "-" }} -
                                            {{ $jadwal->jam->jam_selesai ?? "-" }})</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ruang</strong></td>
                                        <td>: {{ $jadwal->ruangan->nama_ruang ?? "-" }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Attendance Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th rowspan="2" class="text-center align-middle" style="width: 50px;">No</th>
                                        <th rowspan="2" class="text-center align-middle" style="width: 120px;">NPM</th>
                                        <th rowspan="2" class="text-center align-middle" style="min-width: 200px;">Nama
                                            Mahasiswa</th>
                                        <th colspan="16" class="text-center">Pertemuan Ke-/Tanggal</th>
                                    </tr>
                                    <tr>
                                        @for ($i = 1; $i <= 16; $i++)
                                            <th class="text-center" style="width: 40px;">{{ $i }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($kelas->mahasiswa as $index => $mahasiswa)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td>{{ $mahasiswa->npm }}</td>
                                            <td>{{ strtoupper($mahasiswa->nama) }}</td>
                                            @for ($i = 1; $i <= 16; $i++)
                                                <td class="text-center" style="vertical-align: middle;">
                                                    @if (isset($absensiData[$mahasiswa->id][$i]))
                                                        @php
                                                            $status = $absensiData[$mahasiswa->id][$i];
                                                            $badgeClass = "";
                                                            $statusText = "";
                                                            switch ($status) {
                                                                case "hadir":
                                                                    $badgeClass = "success";
                                                                    $statusText = "✓";
                                                                    break;
                                                                case "izin":
                                                                    $badgeClass = "warning";
                                                                    $statusText = "I";
                                                                    break;
                                                                case "sakit":
                                                                    $badgeClass = "info";
                                                                    $statusText = "S";
                                                                    break;
                                                                case "alfa":
                                                                    $badgeClass = "danger";
                                                                    $statusText = "A";
                                                                    break;
                                                            }
                                                        @endphp
                                                        <span class="badge badge-{{ $badgeClass }}"
                                                            title="{{ ucfirst($status) }}">{{ $statusText }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endfor
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="19" class="text-center">Tidak ada mahasiswa terdaftar</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Legend -->
                        <div class="mt-4">
                            <h6><strong>Keterangan:</strong></h6>
                            <div class="row">
                                <div class="col-md-3">
                                    <span class="badge badge-success">✓</span> = Hadir
                                </div>
                                <div class="col-md-3">
                                    <span class="badge badge-warning">I</span> = Izin
                                </div>
                                <div class="col-md-3">
                                    <span class="badge badge-info">S</span> = Sakit
                                </div>
                                <div class="col-md-3">
                                    <span class="badge badge-danger">A</span> = Alfa
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4">
                            <a href="{{ route("data.kelas.detail", $kelas->id) }}" class="btn btn-secondary">
                                <i class="fe fe-arrow-left"></i> Kembali
                            </a>
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fe fe-printer"></i> Cetak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Styles -->
    <style>
        @media print {

            .btn,
            .card-header,
            nav,
            footer {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .table {
                font-size: 10px !important;
            }

            .table th,
            .table td {
                padding: 3px !important;
            }

            body {
                font-size: 12px !important;
            }
        }
    </style>
@endsection
