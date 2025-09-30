@extends("template")
@section("title", "Edit Data Kelas")
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
                    <form action="{{ route("data.kelas.update", $kelas->id) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method("PUT")
                        <div class="card-header">
                            <h3 class="card-title">Edit Data Kelas</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <label for="tahun_akademik_id" class="col-sm-2 col-form-label">Tahun Akademik <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="js-example-basic-single form-control" name="tahun_akademik_id">
                                        <option value="" disabled selected>Pilih Tahun Akademik</option>
                                        @foreach ($tahunAkademik as $item)
                                            <option value="{{ $item->id }}" {{ $item->id == old("tahun_akademik_id", $kelas->tahunAkademik->id) ? "selected" : "" }}>{{ $item->nama_tahun }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="prodi_id" class="col-sm-2 col-form-label">Prodi <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="js-example-basic-single form-control" name="prodi_id">
                                        <option value="" disabled selected>Pilih Prodi</option>
                                        @foreach ($prodi as $item)
                                            <option value="{{ $item->id }}" {{ $item->id == old("prodi_id", $kelas->prodi->id) ? "selected" : "" }}>{{ $item->nama_prodi }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="matkul_id" class="col-sm-2 col-form-label">Matakuliah <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="js-example-basic-single form-control" name="matkul_id">
                                        <option value="" disabled selected>Pilih Matakuliah</option>
                                        @foreach ($matkul as $item)
                                            <option value="{{ $item->id }}" {{ $item->id == old("matkul_id", $kelas->matakuliah->first()->id) ? "selected" : "" }}>{{ $item->nama_matkul }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="dosen_id" class="col-sm-2 col-form-label">Dosen <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <select class="js-example-basic-single form-control" name="dosen_id">
                                        <option value="" disabled selected>Pilih Dosen</option>
                                        @foreach ($dosen as $item)
                                            <option value="{{ $item->id }}" {{ $item->id == old("dosen_id", $kelas->dosen->id) ? "selected" : "" }}>{{ $item->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="nama_kelas" class="col-sm-2 col-form-label">Nama Kelas <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="nama_kelas" name="nama_kelas"
                                        value="{{ old("nama_kelas", $kelas->nama_kelas) }}">
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
                // allowClear: true,
                // closeOnSelect: false,
                language: {
                    noResults: function() {
                        return "Tidak ada hasil ditemukan";
                    },
                    searching: function() {
                        return "Mencari...";
                    },
                    // removeAllItems: function() {
                    //     return "Hapus semua item";
                    // }
                }
            });
        });
    </script>
@endpush
