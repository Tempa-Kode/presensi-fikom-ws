<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $kelas = $this->resource->first()?->sesiKuliah;
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
                'jadwal' => [
                    'jadwal_id' => $kelas?->jadwal->id,
                    'tipe_pertemuan' => $kelas?->jadwal->tipe_pertemuan,
                ],
                'kelas' => [
                    'kelas_id' => $kelas?->jadwal->kelas->id,
                    'nama_kelas' =>  ($kelas?->jadwal->kelas->matakuliah->first()?->nama_matkul ?? '') . ' - ' . $kelas?->jadwal->kelas->nama_kelas,
                ],
                'dosen' => [
                    'dosen_id' => $kelas?->jadwal->kelas->dosen->id,
                    'nidn' => $kelas?->jadwal->kelas->dosen->nidn,
                    'nama' => $kelas?->jadwal->kelas->dosen->nama,
                ],
                'riwayat_absensi' => $this->resource->map(function ($item) {
                    $tanggal = Carbon::parse($item->sesiKuliah->tanggal);
                    return [
                        'sesi_kuliah_id' => $item->sesi_kuliah_id,
                        'tanggal' => $tanggal->locale('id')->translatedFormat('l, d F Y'),
                        'waktu_absensi' => $item->waktu_absensi,
                        'status' => $item->status,
                    ];
                })
            ]
        ];
    }
}
