@extends("template")
@section("title", "Data Jadwal Kuliah")
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
                        <h3 class="card-title">Data Jadwal Kuliah</h3>
                        <div class="card-options">
                            <a href="{{ route("data.jadwal.create") }}"
                                class="btn btn-sm btn-primary text-white">Tambah
                                Data</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="datatable table card-table table-vcenter">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Prodi</th>
                                    <th class="d-none d-sm-table-cell">Hari</th>
                                    <th class="d-none d-sm-table-cell">Jam</th>
                                    <th class="d-none d-sm-table-cell">Matakuliah</th>
                                    <th class="d-none d-sm-table-cell">Dosen</th>
                                    <th class="d-none d-sm-table-cell">Ruangan</th>
                                    <th class="d-none d-sm-table-cell">Pertemuan</th>
                                    <th class="d-none d-md-table-cell">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($data as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->kelas->prodi->nama_prodi }}</td>
                                        <td class="text-capitalize">{{ $item->hari }}</td>
                                        <td class="d-none d-md-table-cell text-uppercase">{{ $item->jam->kode_jam }}</td>
                                        <td class="d-none d-md-table-cell">{{ $item->kelas->matakuliah->first()->nama_matkul }} - {{ $item->kelas->nama_kelas }}</td>
                                        <td class="d-none d-md-table-cell">{{ $item->kelas->dosen->nama }}</td>
                                        <td class="d-none d-md-table-cell">{{ $item->ruangan->nama_ruang }}</td>
                                        <td class="d-none d-md-table-cell text-capitalize">{{ $item->tipe_pertemuan }}</td>
                                        <td class="d-none d-md-table-cell">
                                            <div class="btn-group">
                                                <a href="{{ route("data.jadwal.edit", $item->id) }}"
                                                    class="btn btn-sm btn-primary">Edit</a>
                                                <form action="{{ route("data.jadwal.delete", $item->id) }}" method="POST"
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
@endsection
