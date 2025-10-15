<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SesiKuliahResource extends JsonResource
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
            'data' => $this->resource->map(function ($item) {
                $matakuliah = $item->kelas->matakuliah->first();
                $jadwal = $item->kelas->jadwal->first();
                $sesiKuliah = $jadwal->sesiKuliah->first();

                return [
                    'matakuliah' => [
                        'id' => $matakuliah->id,
                        'nama_matakuliah' => $matakuliah->nama_matkul . '-' . $item->kelas->nama_kelas,
                        'kode_matakuliah' => $matakuliah->kode_matkul,
                        'dosen' => [
                            'id' => $item->kelas->dosen->id,
                            'nama_dosen' => $item->kelas->dosen->nama,
                            'nidn' => $item->kelas->dosen->nidn,
                        ],
                        'semester' => $matakuliah->semester,
                        'sks' => $matakuliah->sks,
                        'kelas' => [
                            'id' => $item->kelas->id,
                            'nama_kelas' => $item->kelas->nama_kelas,
                        ],
                    ],
                    'jadwal' => [
                        'id' => $jadwal->id,
                        'tipe_pertemuan' => $jadwal->tipe_pertemuan,
                        'sesi' => [
                            'id' => $sesiKuliah->id,
                            'tanggal' => $sesiKuliah->tanggal,
                            'status_absensi' => $sesiKuliah->status_absensi,
                            'waktu_buka' => $sesiKuliah->waktu_buka,
                            'waktu_tutup' => $sesiKuliah->waktu_tutup,
                        ]
                    ]
                ];
            }),
        ];
    }
}
