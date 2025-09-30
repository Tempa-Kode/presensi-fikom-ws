@extends("template")
@section("title", "Tambah Data Tahun Akademik")
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
                    <form action="{{ route("data.tahun_akademik.store") }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method("POST")
                        <div class="card-header">
                            <h3 class="card-title">Tambah Data Tahun Akademik</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <label for="nama_tahun" class="col-sm-2 col-form-label">Nama Tahun <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="nama_tahun" name="nama_tahun"
                                        value="{{ old("nama_tahun") }}">
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
    </div>
@endsection
