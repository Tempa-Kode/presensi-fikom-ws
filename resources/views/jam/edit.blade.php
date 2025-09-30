@extends("template")
@section("title", "Edit Jam {{ $data->kode_jam }}")
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
                    <form action="{{ route("data.jam.update", $data->id) }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @method("PUT")
                        <div class="card-header">
                            <h3 class="card-title">Edit Data Jam {{ $data->kode_jam }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <label for="kode_jam" class="col-sm-2 col-form-label">Kode Jam <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="kode_jam" name="kode_jam"
                                        value="{{ old("kode_jam", $data->kode_jam) }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="jam_mulai" class="col-sm-2 col-form-label">Jam Mulai <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="time" class="form-control" id="jam_mulai" name="jam_mulai"
                                        value="{{ old("jam_mulai", \Carbon\Carbon::parse($data->jam_mulai)->format('H:i')) }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="jam_selesai" class="col-sm-2 col-form-label">Jam Selesai <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-10">
                                    <input type="time" class="form-control" id="jam_selesai" name="jam_selesai"
                                        value="{{ old("jam_selesai", \Carbon\Carbon::parse($data->jam_selesai)->format('H:i')) }}">
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
