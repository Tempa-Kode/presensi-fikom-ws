<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JadwalKelasDosenResource extends JsonResource
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
            'data' => $this->resource->map(function ($jadwal) {
                $kelas = $jadwal->kelas;
                $matakuliah = $kelas->matakuliah->first();

                return [
                    'jadwal_id' => $jadwal->id,
                    'hari' => $jadwal->hari,
                    'jam' => [
                        'id' => $jadwal->jam->id,
                        'kode_jam' => $jadwal->jam->kode_jam,
                        'jam_mulai' => $jadwal->jam->jam_mulai,
                        'jam_selesai' => $jadwal->jam->jam_selesai,
                    ],
                    'ruangan' => $jadwal->ruangan ? [
                        'id' => $jadwal->ruangan->id,
                        'nama_ruangan' => "Ruang {$jadwal->ruangan->nama_ruang}",
                    ] : null,
                    'tipe_pertemuan' => $jadwal->tipe_pertemuan,
                    'kelas' => [
                        'id' => $kelas->id,
                        'nama_kelas' => $matakuliah
                            ? $matakuliah->nama_matkul . ' - ' . $kelas->nama_kelas
                            : $kelas->nama_kelas,
                        'kode_kelas' => $kelas->kode_kelas,
                        'prodi' => [
                            'id' => $kelas->prodi->id,
                            'nama_prodi' => $kelas->prodi->nama_prodi,
                        ],
                        'dosen' => [
                            'id' => $kelas->dosen->id,
                            'nidn' => $kelas->dosen->nidn,
                            'nama' => $kelas->dosen->nama,
                        ],
                        'matakuliah' => $kelas->matakuliah->map(function ($mk) {
                            return [
                                'id' => $mk->id,
                                'kode_matkul' => $mk->kode_matkul,
                                'nama_matkul' => $mk->nama_matkul,
                                'sks' => $mk->sks,
                                'semester' => $mk->semester,
                            ];
                        }),
                        'tahun_akademik' => $kelas->tahunAkademik ? [
                            'id' => $kelas->tahunAkademik->id,
                            'nama_tahun' => $kelas->tahunAkademik->nama_tahun,
                        ] : null,
                    ]
                ];
            }),
            'meta' => [
                'total' => $this->resource->count(),
            ]
        ];
    }
}
