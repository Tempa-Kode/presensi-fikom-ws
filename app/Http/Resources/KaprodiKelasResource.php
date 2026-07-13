<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KaprodiKelasResource extends JsonResource
{
    public $status;
    public $message;

    public function __construct($status, $message, $resource)
    {
        parent::__construct($resource);
        $this->status = $status;
        $this->message = $message;
    }

    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->resource->map(function ($jadwal) {
                $kelas = $jadwal->kelas;
                $matakuliah = $kelas->matakuliah->first();
                $semuaAbsensi = $jadwal->sesiKuliah->flatMap(fn($sesi) => $sesi->absensi);

                return [
                    'jadwal_id' => $jadwal->id,
                    'kelas_id' => $kelas->id,
                    'nama_kelas' => $matakuliah
                        ? $matakuliah->nama_matkul . ' - ' . $kelas->nama_kelas
                        : $kelas->nama_kelas,
                    'kode_kelas' => $kelas->kode_kelas,
                    'dosen' => [
                        'id' => $kelas->dosen->id,
                        'nama' => $kelas->dosen->nama,
                    ],
                    'matakuliah' => $kelas->matakuliah->map(fn($mk) => [
                        'id' => $mk->id,
                        'nama_matkul' => $mk->nama_matkul,
                        'semester' => $mk->semester,
                        'sks' => $mk->sks,
                    ]),
                    'rekap_absensi' => [
                        'total_sesi' => $jadwal->sesiKuliah->count(),
                        'hadir' => $semuaAbsensi->where('status', 'hadir')->count(),
                        'izin' => $semuaAbsensi->where('status', 'izin')->count(),
                        'sakit' => $semuaAbsensi->where('status', 'sakit')->count(),
                        'alfa' => $semuaAbsensi->where('status', 'alfa')->count(),
                    ],
                ];
            }),
            'meta' => [
                'total_kelas' => $this->resource->count(),
                'prodi' => $this->resource->first()?->kelas->prodi->nama_prodi,
            ]
        ];
    }
}
