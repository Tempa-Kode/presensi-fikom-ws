@extends("template")
@section("title", "Edit Data Prodi")
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
                <form action="{{ route('data.prodi.update', $data->id) }}" method="post" class="card" enctype="multipart/form-data">
                    @csrf
                    @method("PUT")
                    <div class="card-header">
                        <h3 class="card-title">Edit Data Prodi</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="kode_prodi" class="col-sm-2 col-form-label">Kode Prodi <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="kode_prodi" name="kode_prodi"
                                    value="{{ old("kode_prodi", $data->kode_prodi) }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="nama_prodi" class="col-sm-2 col-form-label">Nama Prodi <span
                                    class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="nama_prodi" name="nama_prodi"
                                    value="{{ old("nama_prodi", $data->nama_prodi) }}">
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
@endsection
