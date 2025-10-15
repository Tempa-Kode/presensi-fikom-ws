@extends("template")
@section("title", "Data Kelas")
@section("body")
    <div class="container">
        <div class="row">
            <div class="col-12">
                @if (session("success"))
                    <div class="alert alert-success" role="alert">
                        {{ session("success") }}
                    </div>
                @elseif (session("error"))
                    <div class="alert alert-danger" role="alert">
                        {{ session("error") }}
                    </div>
                @endif
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Data Kelas</h3>
                        <div class="card-options">
                            <a href="{{ route("data.kelas.create") }}" class="btn btn-sm btn-primary text-white">Tambah
                                Data</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="filter-semester" class="form-label">Filter Semester:</label>
                                    <select id="filter-semester" class="form-control">
                                        <option value="">Semua Semester</option>
                                        @foreach ($semester as $item)
                                            <option value="{{ $item }}">Semester {{ $item }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter-prodi" class="form-label">Filter Prodi:</label>
                                    <select id="filter-prodi" class="form-control">
                                        <option value="">Semua Prodi</option>
                                        @foreach ($prodi as $item)
                                            <option value="{{ $item->id }}">{{ $item->nama_prodi }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filter-tahun-akademik" class="form-label">Filter Tahun Akademik:</label>
                                    <select id="filter-tahun-akademik" class="form-control">
                                        <option value="">Semua Tahun Akademik</option>
                                        @foreach ($tahunAkademik as $item)
                                            <option value="{{ $item->id }}">{{ $item->nama_tahun }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <table class="datatable table card-table table-vcenter" id="kelas-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th class="d-none d-sm-table-cell">Prodi</th>
                                    <th class="d-none d-sm-table-cell">Kode Kelas</th>
                                    <th class="d-none d-sm-table-cell">Nama Matkul / Kelas</th>
                                    <th class="d-none d-sm-table-cell">Dosen</th>
                                    <th class="d-none d-sm-table-cell">TA</th>
                                    <th class="d-none d-md-table-cell">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $item)
                                    @php
                                        $semester =
                                            $item->matakuliah && $item->matakuliah->count() > 0
                                                ? $item->matakuliah->first()->semester
                                                : "";
                                    @endphp
                                    <tr data-semester="{{ $semester }}" data-prodi="{{ $item->prodi_id }}"
                                        data-tahun-akademik="{{ $item->tahun_akademik_id }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->prodi->nama_prodi }}</td>
                                        <td>{{ $item->kode_kelas }}</td>
                                        <td>
                                            @if ($item->matakuliah && $item->matakuliah->count() > 0)
                                                {{ $item->matakuliah->first()->nama_matkul }} - ({{ $item->nama_kelas }})
                                            @else
                                                Data matakuliah tidak tersedia
                                            @endif
                                        </td>
                                        <td>{{ $item->dosen->nama }}</td>
                                        <td>{{ $item->tahunAkademik->nama_tahun }}</td>
                                        <td class="d-none d-md-table-cell">
                                            <div class="btn-group">
                                                <a href="{{ route("data.kelas.edit", $item->id) }}"
                                                    class="btn btn-sm btn-primary">Edit</a>
                                                <a href="{{ route("data.kelas.detail", $item->id) }}"
                                                    class="btn btn-sm btn-warning">Detail</a>
                                                <form action="{{ route("data.kelas.delete", $item->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    @method("DELETE")
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-right">
                        <div class="d-flex">
                            <a href="{{ url()->previous() }}" class="btn btn-link">Kembali</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function untuk filter table
        function filterTable() {
            const semesterFilter = document.getElementById('filter-semester').value;
            const prodiFilter = document.getElementById('filter-prodi').value;
            const tahunAkademikFilter = document.getElementById('filter-tahun-akademik').value;

            const table = document.getElementById('kelas-table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const semester = rows[i].getAttribute('data-semester');
                const prodi = rows[i].getAttribute('data-prodi');
                const tahunAkademik = rows[i].getAttribute('data-tahun-akademik');

                let showRow = true;

                // Filter semester
                if (semesterFilter !== '' && semester !== semesterFilter) {
                    showRow = false;
                }

                // Filter prodi
                if (prodiFilter !== '' && prodi !== prodiFilter) {
                    showRow = false;
                }

                // Filter tahun akademik
                if (tahunAkademikFilter !== '' && tahunAkademik !== tahunAkademikFilter) {
                    showRow = false;
                }

                // Tampilkan atau sembunyikan baris
                if (showRow) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }

            // Update nomor urut setelah filter
            updateRowNumbers();
        }

        // Function untuk update nomor urut
        function updateRowNumbers() {
            const table = document.getElementById('kelas-table');
            const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');

            visibleRows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
        }

        // Event listeners untuk semua filter
        document.getElementById('filter-semester').addEventListener('change', filterTable);
        document.getElementById('filter-prodi').addEventListener('change', filterTable);
        document.getElementById('filter-tahun-akademik').addEventListener('change', filterTable);
    </script>
@endsection
