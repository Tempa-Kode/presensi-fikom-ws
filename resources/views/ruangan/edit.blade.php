@extends("template")
@section("title", "Edit Ruangan")
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
                    <form action="{{ route("data.ruangan.update", $data->id) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method("PUT")
                        <div class="card-header">
                            <h3 class="card-title">Edit Data Ruangan</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <label for="nama_ruang" class="col-sm-2 col-form-label">Nama Ruangan <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="nama_ruang" name="nama_ruang"
                                        value="{{ old("nama_ruang", $data->nama_ruang) }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="latitude" class="col-sm-2 col-form-label">Latitude <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="number" class="form-control" id="latitude" name="latitude"
                                        value="{{ old("latitude", $data->latitude) }}" step="any">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="longitude" class="col-sm-2 col-form-label">Longitude <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="number" class="form-control" id="longitude" name="longitude"
                                        value="{{ old("longitude", $data->longitude) }}" step="any">
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
