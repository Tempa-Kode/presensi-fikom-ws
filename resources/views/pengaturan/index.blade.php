@extends("template")
@section("title", "Pengaturan")
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
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pengaturan Tahun Akademik Aktif</h3>
                    </div>
                    <form action="{{ route("data.pengaturan.tahun_akademik.update") }}" method="POST">
                        @csrf
                        @method("PUT")
                        <div class="card-body">
                            @if ($tahunAkademik->isEmpty())
                                <div class="alert alert-warning">
                                    Belum ada data Tahun Akademik. Tambahkan Tahun Akademik terlebih dahulu.
                                </div>
                            @else
                                <div class="row mb-3">
                                    <label for="tahun_akademik_id" class="col-sm-3 col-form-label">
                                        Tahun Akademik Aktif <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-sm-9">
                                        <select class="form-control" name="tahun_akademik_id" id="tahun_akademik_id">
                                            <option value="" disabled {{ is_null($activeTahunAkademikId) ? 'selected' : '' }}>
                                                -- Belum ada yang aktif --
                                            </option>
                                            @foreach ($tahunAkademik as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ $item->id == $activeTahunAkademikId ? 'selected' : '' }}>
                                                    {{ $item->nama_tahun }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">
                                            Tahun Akademik yang dipilih akan menjadi default filter di seluruh CMS dan API.
                                        </small>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer text-right">
                            <div class="d-flex">
                                <a href="{{ url()->previous() }}" class="btn btn-link">Kembali</a>
                                <button type="submit" class="btn btn-primary ml-auto"
                                    {{ $tahunAkademik->isEmpty() ? 'disabled' : '' }}>
                                    Simpan Pengaturan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
