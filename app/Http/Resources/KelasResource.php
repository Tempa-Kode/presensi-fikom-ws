<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelasResource extends JsonResource
{
    public $status;
    public $message;

    public function __construct($status, $message, $resource)
    {
        parent::__construct($resource);
        $this->status = $status;
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
                $matakuliah = $item->matakuliah->first();
                return [
                    'id' => $item->id,
                    'nama_kelas' => $matakuliah
                        ? $matakuliah->nama_matkul . ' - ' . $item->nama_kelas
                        : $item->nama_kelas,
                    'kode_kelas' => $item->kode_kelas,
                    'prodi' => [
                        'id' => $item->prodi->id,
                        'nama_prodi' => $item->prodi->nama_prodi,
                    ],
                    'dosen' => [
                        'id' => $item->dosen->id,
                        'nidn' => $item->dosen->nidn,
                        'nama' => $item->dosen->nama,
                    ],
                    'matakuliah' => $item->matakuliah->map(function ($mk) {
                        return [
                            'id' => $mk->id,
                            'kode_matkul' => $mk->kode_matkul,
                            'nama_matkul' => $mk->nama_matkul,
                            'sks' => $mk->sks,
                            'semester' => $mk->semester,
                        ];
                    }),
                    'jadwal' => $item->jadwal->map(function ($jadwal) {
                        return [
                            'id' => $jadwal->id,
                            'hari' => $jadwal->hari,
                            'jam' => $jadwal->jam->kode_jam,
                            'ruangan' => $jadwal->ruangan ? [
                                'id' => $jadwal->ruangan->id,
                                'nama_ruangan' => "Ruang {$jadwal->ruangan->nama_ruang}",
                            ] : null,
                            'tipe_pertemuan' => $jadwal->tipe_pertemuan,
                        ];
                    })
                ];
            }),
            'meta' => [
                'total' => $this->resource->count(),
            ]
        ];
    }
}
