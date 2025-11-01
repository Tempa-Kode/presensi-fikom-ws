@extends("template")
@section("title", "Dashboard Admin")
@section("body")
    <div class="container">
        <!-- Header Dashboard -->
        <div class="row mt-4 mb-4">
            <div class="col-12">
                <h2 class="mb-1">
                    <i class="fe fe-home"></i> Dashboard Admin
                </h2>
                <p class="text-muted">Selamat datang di Sistem Presensi FIKOM -
                    {{ \Carbon\Carbon::now()->locale("id")->isoFormat("dddd, D MMMM YYYY") }}</p>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="row">
            <!-- Total Mahasiswa -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="text-uppercase text-muted mb-2">Total Mahasiswa</h6>
                                <span class="h2 mb-0">{{ number_format($totalMahasiswa) }}</span>
                            </div>
                            <div class="col-auto">
                                <span class="h2 fe fe-users text-primary"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Dosen -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="text-uppercase text-muted mb-2">Total Dosen</h6>
                                <span class="h2 mb-0">{{ number_format($totalDosen) }}</span>
                            </div>
                            <div class="col-auto">
                                <span class="h2 fe fe-user-check text-success"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Kelas -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="text-uppercase text-muted mb-2">Total Kelas</h6>
                                <span class="h2 mb-0">{{ number_format($totalKelas) }}</span>
                            </div>
                            <div class="col-auto">
                                <span class="h2 fe fe-book-open text-warning"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Mata Kuliah -->
            <div class="col-xl-3 col-lg-6 col-md-6 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="text-uppercase text-muted mb-2">Total Mata Kuliah</h6>
                                <span class="h2 mb-0">{{ number_format($totalMatakuliah) }}</span>
                            </div>
                            <div class="col-auto">
                                <span class="h2 fe fe-book text-info"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistik Absensi Hari Ini -->
        <div class="row">
            <div class="col-xl-8 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fe fe-bar-chart-2"></i> Statistik Kehadiran Minggu Ini</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="absensiChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fe fe-calendar"></i> Absensi Hari Ini</h5>
                    </div>
                    <div class="card-body text-center">
                        <h1 class="display-3 text-primary mb-2">{{ $absensiHariIni }}</h1>
                        <p class="text-muted mb-0">Total kehadiran tercatat</p>
                        <hr>
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-success">{{ $absensiStats["hadir"] ?? 0 }}</h4>
                                <small class="text-muted">Hadir</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-danger">{{ $absensiStats["alfa"] ?? 0 }}</h4>
                                <small class="text-muted">Alfa</small>
                            </div>
                        </div>
                        <div class="row text-center mt-3">
                            <div class="col-6">
                                <h4 class="text-warning">{{ $absensiStats["izin"] ?? 0 }}</h4>
                                <small class="text-muted">Izin</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info">{{ $absensiStats["sakit"] ?? 0 }}</h4>
                                <small class="text-muted">Sakit</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini & Kelas Terbaru -->
        <div class="row">
            <div class="col-xl-6 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fe fe-clock"></i> Jadwal Kuliah Hari Ini</h5>
                        <span
                            class="badge badge-primary">{{ ucfirst(\Carbon\Carbon::now()->locale("id")->dayName) }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Jam</th>
                                        <th>Mata Kuliah</th>
                                        <th>Dosen</th>
                                        <th>Ruang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($jadwalHariIni as $jadwal)
                                        <tr>
                                            <td>
                                                <small class="text-muted">{{ $jadwal->jam->jam_mulai ?? "-" }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $jadwal->kelas->matakuliah->first()->nama_matkul ?? "-" }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $jadwal->kelas->nama_kelas ?? "-" }}</small>
                                            </td>
                                            <td>{{ $jadwal->kelas->dosen->nama ?? "-" }}</td>
                                            <td>
                                                <span
                                                    class="badge badge-info">{{ $jadwal->ruangan->nama_ruang ?? "-" }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">
                                                <i class="fe fe-info"></i> Tidak ada jadwal hari ini
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fe fe-trending-up"></i> Tren Kehadiran 6 Bulan Terakhir</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        // Chart Statistik Absensi Minggu Ini
        const absensiCtx = document.getElementById('absensiChart').getContext('2d');
        new Chart(absensiCtx, {
            type: 'bar',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit', 'Alfa'],
                datasets: [{
                    label: 'Jumlah',
                    data: [
                        {{ $absensiStats["hadir"] ?? 0 }},
                        {{ $absensiStats["izin"] ?? 0 }},
                        {{ $absensiStats["sakit"] ?? 0 }},
                        {{ $absensiStats["alfa"] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgba(40, 199, 111, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgb(40, 199, 111)',
                        'rgb(255, 193, 7)',
                        'rgb(23, 162, 184)',
                        'rgb(220, 53, 69)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Chart Tren Kehadiran Bulanan
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($monthlyStats, "bulan")) !!},
                datasets: [{
                    label: 'Kehadiran',
                    data: {!! json_encode(array_column($monthlyStats, "total")) !!},
                    fill: true,
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderColor: 'rgb(99, 102, 241)',
                    tension: 0.4,
                    borderWidth: 2,
                    pointBackgroundColor: 'rgb(99, 102, 241)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(99, 102, 241)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>

    <style>
        .card {
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .list-group-item {
            transition: background-color 0.2s;
        }

        .list-group-item:hover {
            background-color: #f8f9fa;
        }
    </style>
@endsection
