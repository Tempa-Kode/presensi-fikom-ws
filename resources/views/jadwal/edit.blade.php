@extends("template")
@section("title", "Edit Jadwal Kuliah")
@push("styles")
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush
@section("body")
    <div class="container">
        <div class="row">
            <div class="col-12">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @elseif (session("error"))
                    <div class="alert alert-danger">
                        {{ session("error") }}
                    </div>
                @endif
                <div class="card">
                    <form action="{{ route("data.jadwal.update", $jadwal->id) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method("put")
                        <div class="card-header">
                            <h3 class="card-title">Tambah Data Jadwal Kuliah</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <label for="jam_id" class="col-sm-2 col-form-label">Jam <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-control js-example-basic-single" id="jam_id" name="jam_id">
                                        <option value="">Pilih Jam</option>
                                        @foreach ($jam as $j)
                                            <option value="{{ $j->id }}" {{ old("jam_id", $jadwal->jam_id) == $j->id ? "selected" : "" }}>
                                                {{ $j->kode_jam }} - {{ $j->jam_mulai }} s.d {{ $j->jam_selesai }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="kelas_id" class="col-sm-2 col-form-label">Matakuliah/Kelas <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-control js-example-basic-single" id="kelas_id" name="kelas_id">
                                        <option value="">Pilih Kelas</option>
                                        @foreach ($kelas as $k)
                                            <option value="{{ $k->id }}" {{ old("kelas_id", $jadwal->kelas_id) == $k->id ? "selected" : "" }}>
                                                {{ $k->matakuliah->first()->nama_matkul }} ({{ $k->nama_kelas }}) - {{ $k->prodi->nama_prodi }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="ruangan_id" class="col-sm-2 col-form-label">Ruangan <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-control js-example-basic-single" id="ruangan_id" name="ruangan_id">
                                        <option value="">Pilih Ruangan</option>
                                        @foreach ($ruangan as $r)
                                            <option value="{{ $r->id }}" {{ old("ruangan_id", $jadwal->ruangan_id) == $r->id ? "selected" : "" }}>
                                                {{ $r->nama_ruang }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="hari" class="col-sm-2 col-form-label">Hari <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-control js-example-basic-single" id="hari" name="hari">
                                        <option value="">Pilih Hari</option>
                                        <option value="senin" {{ old("hari", $jadwal->hari) == "senin" ? "selected" : "" }}>Senin</option>
                                        <option value="selasa" {{ old("hari", $jadwal->hari) == "selasa" ? "selected" : "" }}>Selasa</option>
                                        <option value="rabu" {{ old("hari", $jadwal->hari) == "rabu" ? "selected" : "" }}>Rabu</option>
                                        <option value="kamis" {{ old("hari", $jadwal->hari) == "kamis" ? "selected" : "" }}>Kamis</option>
                                        <option value="jumat" {{ old("hari", $jadwal->hari) == "jumat" ? "selected" : "" }}>Jumat</option>
                                        <option value="sabtu" {{ old("hari", $jadwal->hari) == "sabtu" ? "selected" : "" }}>Sabtu</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="tipe_pertemuan" class="col-sm-2 col-form-label">Tipe Pertemuan <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="form-control" id="tipe_pertemuan" name="tipe_pertemuan">
                                        <option value="">Pilih Tipe Pertemuan</option>
                                        <option value="teori" {{ old("tipe_pertemuan", $jadwal->tipe_pertemuan) == "teori" ? "selected" : "" }}>Teori</option>
                                        <option value="praktek" {{ old("tipe_pertemuan", $jadwal->tipe_pertemuan) == "praktek" ? "selected" : "" }}>Praktek</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <div class="d-flex">
                                    <a href="{{ url()->previous() }}" class="btn btn-link">Batal</a>
                                    <button type="submit" class="btn btn-primary ml-auto">Edit Data</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@push("scripts")
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Pilih...',
                language: {
                    noResults: function() {
                        return "Tidak ada hasil ditemukan";
                    },
                    searching: function() {
                        return "Mencari...";
                    },
                }
            });
        });
    </script>
@endpush
