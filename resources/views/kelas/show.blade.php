@extends("template")
@section("title", "Detail Data Kelas")
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
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Detail Data Kelas</h3>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Matakuliah/Kelas: {{ $kelas->matakuliah->first()->nama_matkul }} -
                            {{ $kelas->nama_kelas }}</h5>
                        <p class="card-text"><strong>Dosen:</strong> {{ $kelas->dosen->nidn }} - {{ $kelas->dosen->nama }}
                        </p>
                        <p class="card-text"><strong>Program Studi:</strong> {{ $kelas->prodi->nama_prodi }}</p>
                        <p class="card-text"><strong>Semester:</strong> {{ $kelas->matakuliah->first()->semester }}</p>
                        <p class="card-text"><strong>Tahun Akademik:</strong> {{ $kelas->tahunAkademik->nama_tahun }}</p>
                        <p class="card-text"><strong>Kode Kelas:</strong> <span
                                class="badge bg-primary">{{ $kelas->kode_kelas }}</span></p>
                    </div>
                </div>

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab"
                            aria-controls="home" aria-selected="true">Daftar Mahasiswa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab"
                            aria-controls="profile" aria-selected="false">Pertemuan</a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                        <div class="card mt-4">
                            <div class="card-body">
                                @if ($kelas->mahasiswa->isEmpty())
                                    <p class="card-text">Tidak ada mahasiswa yang terdaftar di kelas ini.</p>
                                @else
                                    <table class="datatable table card-table table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Prodi</th>
                                                <th class="d-none d-sm-table-cell">NPM</th>
                                                <th class="d-none d-sm-table-cell">Nama</th>
                                                <th class="d-none d-sm-table-cell">Stambuk</th>
                                                <th class="d-none d-md-table-cell">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($kelas->mahasiswa as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->prodi->nama_prodi }}</td>
                                                    <td class="d-none d-sm-table-cell">{{ $item->npm }}</td>
                                                    <td class="d-none d-sm-table-cell">{{ $item->nama }}</td>
                                                    <td class="d-none d-sm-table-cell">{{ $item->stambuk ?? "-" }}</td>
                                                    <td class="d-none d-md-table-cell">
                                                        <div class="btn-group">
                                                            <form
                                                                action="{{ route("data.kelas.keluarkan", ["kelasId" => $kelas->id, "mahasiswaId" => $item->id]) }}"
                                                                method="POST" class="d-inline">
                                                                @csrf
                                                                @method("DELETE")
                                                                <button type="submit" class="btn btn-sm btn-danger"
                                                                    onsubmit="return confirm('Apakah Anda yakin ingin mengeluarkan mahasiswa ini?')">Keluarkan</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                        {{-- @dd($kelas) --}}
                        <div class="row">
                            @forelse ($kelas->jadwal as $item)
                                <div class="card">
                                    <div class="card-status bg-yellow"></div>
                                    <div class="card-header">
                                        <h3 class="card-title text-capitalize">Pertemuan {{ $item->tipe_pertemuan }}</h3>
                                        <div class="card-options">
                                            <a href="#" class="card-options-collapse" data-toggle="card-collapse"><i
                                                    class="fe fe-chevron-up"></i></a>
                                            <a href="#" class="card-options-remove" data-toggle="card-remove"><i
                                                    class="fe fe-x"></i></a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><strong>Hari:</strong> {{ ucfirst($item->hari) }}</p>
                                        <p class="card-text"><strong>Ruangan:</strong> {{ $item->ruangan->nama_ruang }}</p>
                                        <p class="card-text"><strong>Jam:</strong> {{ $item->jam->kode_jam }} /
                                            {{ $item->jam->jam_mulai }}
                                            - {{ $item->jam->jam_selesai }}</p>
                                        <a href="{{ route("data.kelas.absensi", [$kelas->id, $item->id]) }}"
                                            class="btn btn-success">
                                            <i class="fe fe-list"></i> Lihat Absensi {{ ucfirst($item->tipe_pertemuan) }}
                                        </a>
                                    </div>
                                </div>
                            @empty
                                <p class="card-text">Tidak ada jadwal pertemuan untuk kelas ini.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
