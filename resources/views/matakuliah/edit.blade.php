@extends("template")
@section("title", "Edit Data Matakuliah")
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
                    <form action="{{ route("data.matakuliah.update", $data->id) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method("PUT")
                        <div class="card-header">
                            <h3 class="card-title">Edit Data Matakuliah {{ $data->nama_matkul }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <label for="kode_matkul" class="col-sm-2 col-form-label">Kode Matakuliah <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="kode_matkul" name="kode_matkul"
                                        value="{{ old("kode_matkul", $data->kode_matkul) }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="nama_matkul" class="col-sm-2 col-form-label">Nama Matakuliah <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="nama_matkul" name="nama_matkul"
                                        value="{{ old("nama_matkul", $data->nama_matkul) }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="sks" class="col-sm-2 col-form-label">SKS <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="sks" name="sks"
                                        value="{{ old("sks", $data->sks) }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="semester" class="col-sm-2 col-form-label">Semester <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="semester" name="semester"
                                        value="{{ old("semester", $data->semester) }}">
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
