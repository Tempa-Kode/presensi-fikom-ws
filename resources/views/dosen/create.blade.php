@extends("template")
@section("title", "Tambah Data Dosen")
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
                @endif
                <form action="{{ route("data.dosen.store") }}" method="post" class="card" enctype="multipart/form-data">
                    @csrf
                    @method("POST")
                    <div class="card-header">
                        <h3 class="card-title">Tambah Data Dosen</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="nidn" class="col-sm-2 col-form-label">NIDN <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control" id="nidn" name="nidn"
                                    value="{{ old("nidn") }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="nama" class="col-sm-2 col-form-label">Nama <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="nama" name="nama"
                                    value="{{ old("nama") }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="password" class="col-sm-2 col-form-label">Password <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="password" name="password">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="konfirmasi_password" class="col-sm-2 col-form-label">Konfirmasi Password <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="konfirmasi_password"
                                    name="konfirmasi_password">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="foto" class="col-sm-2 col-form-label">Foto</label>
                            <div class="col-sm-10">
                                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <div class="d-flex">
                                <a href="{{ url()->previous() }}" class="btn btn-link">Batal</a>
                                <button type="submit" class="btn btn-primary ml-auto">Simpan Data</button>
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </div>
@endsection
