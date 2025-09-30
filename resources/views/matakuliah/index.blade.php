@extends("template")
@section("title", "Data Matakuliah")
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
                        <h3 class="card-title">Data Matakuliah</h3>
                        <div class="card-options">
                            <a href="{{ route("data.matakuliah.create") }}"
                                class="btn btn-sm btn-primary text-white">Tambah
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
                                        @foreach($data->unique('semester')->sortBy('semester') as $item)
                                            <option value="{{ $item->semester }}">Semester {{ $item->semester }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="datatable table card-table table-vcenter" id="matakuliah-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th class="d-none d-sm-table-cell">Kode Matkul</th>
                                        <th class="d-none d-sm-table-cell">Matakuliah</th>
                                        <th class="d-none d-sm-table-cell">SKS</th>
                                        <th class="d-none d-sm-table-cell">Semester</th>
                                        <th class="d-none d-md-table-cell">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($data as $item)
                                        <tr data-semester="{{ $item->semester }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $item->kode_matkul }}</td>
                                            <td>{{ $item->nama_matkul }}</td>
                                            <td>{{ $item->sks }}</td>
                                            <td>{{ $item->semester }}</td>
                                            <td class="d-none d-md-table-cell">
                                                <div class="btn-group">
                                                    <a href="{{ route("data.matakuliah.edit", $item->id) }}"
                                                        class="btn btn-sm btn-primary">Edit</a>
                                                    <form action="{{ route("data.matakuliah.delete", $item->id) }}" method="POST"
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
        document.getElementById('filter-semester').addEventListener('change', function() {
            const selectedSemester = this.value;
            const table = document.getElementById('matakuliah-table');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            for (let i = 0; i < rows.length; i++) {
                const semester = rows[i].getAttribute('data-semester');

                if (selectedSemester === '' || semester === selectedSemester) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }

            // Update nomor urut setelah filter
            updateRowNumbers();
        });

        function updateRowNumbers() {
            const table = document.getElementById('matakuliah-table');
            const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');

            visibleRows.forEach((row, index) => {
                row.cells[0].textContent = index + 1;
            });
        }
    </script>
@endsection
