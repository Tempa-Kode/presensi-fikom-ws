<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiKelasResource extends JsonResource
{
    public $status;
    public $message;
    public $resource;

    public function __construct($status, $message, $resource)
    {
        parent::__construct($resource);
        $this->status  = $status;
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => [
                'kelas_id' => $this->resource->id,
                'nama_kelas' => $this->resource->nama_kelas,
                'prodi' => $this->resource->prodi->nama_prodi ?? null,
                'tipe_pertemuan' => $this->resource->jadwal[0]->tipe_pertemuan ?? null,
                'matakuliah' => [
                    'id' => $this->resource->matakuliah[0]->id ?? null,
                    'kode_matkul' => $this->resource->matakuliah[0]->kode_matkul ?? null,
                    'nama_matkul' => $this->resource->matakuliah[0]->nama_matkul ?? null,
                    'sks' => $this->resource->matakuliah[0]->sks ?? null,
                    'semester' => $this->resource->matakuliah[0]->semester ?? null,
                    'dosen' => [
                        'id' => $this->resource->dosen->id ?? null,
                        'nidn' => $this->resource->dosen->nidn ?? null,
                        'nama' => $this->resource->dosen->nama ?? null,
                    ],
                ],
                'absensi' => collect($this->resource->jadwal)->flatMap(function ($jadwal) {
                    if (!$jadwal->sesiKuliah || $jadwal->sesiKuliah->isEmpty()) {
                        return collect([]);
                    }

                    return $jadwal->sesiKuliah->map(function ($sesi) {
                        // mengambil semua mahasiswa yang terdaftar di kelas
                        $semuaMahasiswa = $this->resource->mahasiswa;

                        // membuat map absensi berdasarkan mahasiswa_id untuk akses cepat
                        $absensiMap = $sesi->absensi ? $sesi->absensi->keyBy('mahasiswa_id') : collect();

                        // Generate daftar absensi untuk semua mahasiswa
                        $absensiMahasiswa = $semuaMahasiswa->map(function ($mahasiswa) use ($absensiMap) {
                            $absensi = $absensiMap->get($mahasiswa->id);

                            return [
                                'absensi_id' => $absensi ? $absensi->id : null,
                                'mahasiswa_id' => $mahasiswa->id,
                                'npm' => $mahasiswa->npm,
                                'nama' => $mahasiswa->nama,
                                'stambuk' => $mahasiswa->stambuk,
                                'status' => $absensi ? $absensi->status : null,
                                'waktu_absensi' => $absensi ? $absensi->waktu_absensi : null,
                            ];
                        });

                        return [
                            'sesi_id' => $sesi->id,
                            'tanggal' => $sesi->tanggal,
                            'daftar' => $absensiMahasiswa,
                        ];
                    });
                }),
            ],
        ];
    }
}
