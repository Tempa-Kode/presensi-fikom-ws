<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiwayatAbsensiResource extends JsonResource
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
            'data' => [
                'statistik' => [
                    'total_pertemuan' => $this->resource->count(),
                    'hadir' => $this->resource->where('status', 'hadir')->count(),
                    'izin' => $this->resource->where('status', 'izin')->count(),
                    'sakit' => $this->resource->where('status', 'sakit')->count(),
                    'alfa' => $this->resource->where('status', 'alfa')->count(),
                ],
                'riwayat_absensi' => $this->resource->map(function ($item) {
                    return [
                        'sesi_kuliah_id' => $item->sesi_kuliah_id,
                        'tanggal' => $item->sesiKuliah->tanggal,
                        'waktu_absensi' => $item->waktu_absensi,
                        'status' => $item->status,
                        'kelas' => [
                            'id' => $item->sesiKuliah->jadwal->kelas->id,
                            'nama_kelas' => $item->sesiKuliah->jadwal->kelas->nama_kelas,
                            'tipe_pertemuan' => $item->sesiKuliah->jadwal->tipe_pertemuan,
                        ],
                        'matakuliah' => [
                            'id' => $item->sesiKuliah->jadwal->kelas->matakuliah->first()->id,
                            'kode_matkul' => $item->sesiKuliah->jadwal->kelas->matakuliah->first()->kode_matkul,
                            'nama_matkul' => $item->sesiKuliah->jadwal->kelas->matakuliah->first()->nama_matkul,
                        ],
                    ];
                })
            ]
        ];
    }
}
