<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelasResource extends JsonResource
{
    public $status;
    public $message;

    public function __construct($resource, $status = null, $message = null)
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
        if ($this->resource instanceof \Illuminate\Support\Collection) {
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
                                    'nama_ruangan' => $jadwal->ruangan->nama_ruangan,
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

        // Jika ini adalah single item
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => [
                'id' => $this->id,
                'nama_kelas' => $this->matakuliah->nama_matkul . ' - ' . $this->nama_kelas,
                'kode_kelas' => $this->kode_kelas,
                'prodi' => [
                    'id' => $this->prodi->id,
                    'nama_prodi' => $this->prodi->nama_prodi,
                ],
                'dosen' => [
                    'id' => $this->dosen->id,
                    'nidn' => $this->dosen->nidn,
                    'nama' => $this->dosen->nama,
                ],
                'matakuliah' => [
                    'id' => $this->matakuliah->id,
                    'kode_mk' => $this->matakuliah->kode_mk,
                    'nama_mk' => $this->matakuliah->nama_mk,
                ],
                'jadwal' => [
                    'id' => $this->jadwal->id,
                    'hari' => $this->jadwal->hari,
                    'ruangan' => [
                        'id' => $this->jadwal->ruangan->id,
                        'nama_ruangan' => $this->jadwal->ruangan->nama_ruangan,
                    ],
                    'tipe_pertemuan' => $this->jadwal->tipe_pertemuan,
                ]
            ]
        ];
    }
}
