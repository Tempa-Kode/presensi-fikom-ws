<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsensiPertemuanResource extends JsonResource
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
                'tanggal' => $this->tanggal,
                'waktu_buka' => $this->waktu_buka,
                'waktu_tutup' => $this->waktu_tutup,
                'kelas' => [
                    'id' => $this->jadwal->kelas->id,
                    'nama_kelas' => $this->jadwal->kelas->nama_kelas,
                    'matakuliah' => [
                        'kode_matkul' => $this->jadwal->kelas->matakuliah[0]->kode_matkul,
                        'nama' => $this->jadwal->kelas->matakuliah[0]->nama_matkul,
                        'sks' => $this->jadwal->kelas->matakuliah[0]->sks,
                        'tipe_pertemuan' => $this->jadwal->tipe_pertemuan,
                    ],
                ],
                'absensi' => [
                    'hadir' => $this->absensi->where('status', 'hadir')->count(),
                    'izin' => $this->absensi->where('status', 'izin')->count(),
                    'sakit' => $this->absensi->where('status', 'sakit')->count(),
                    'alpha' => $this->absensi->where('status', 'alfa')->count(),
                    'total_mahasiswa' => $this->absensi->count(),
                    'daftar' => $this->absensi->map(function ($item) {
                        return [
                            'mahasiswa_id' => $item->mahasiswa->id,
                            'waktu_absensi' => $item->waktu_absensi,
                            'npm' => $item->mahasiswa->npm,
                            'nama' => $item->mahasiswa->nama,
                            'stambuk' => $item->mahasiswa->stambuk,
                            'status' => $item->status,
                        ];
                    })
                ]
            ],
            'meta' => [
                'total' => $this->absensi->count(),
            ],
        ];
    }
}
