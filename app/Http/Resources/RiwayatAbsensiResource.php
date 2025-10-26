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
        // Akses data dari object yang dikirim controller
        $absensi = $this->resource->absensi;
        $jadwal = $this->resource->jadwal;

        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => [
                'statistik' => [
                    'total_pertemuan' => $absensi->count(),
                    'hadir' => $absensi->where('status', 'hadir')->count(),
                    'izin' => $absensi->where('status', 'izin')->count(),
                    'sakit' => $absensi->where('status', 'sakit')->count(),
                    'alfa' => $absensi->where('status', 'alfa')->count(),
                ],
                'jadwal' => [
                    'jadwal_id' => $jadwal->id,
                    'tipe_pertemuan' => $jadwal->tipe_pertemuan,
                ],
                'kelas' => [
                    'kelas_id' => $jadwal->kelas->id,
                    'nama_kelas' =>  ($jadwal->kelas->matakuliah->first()?->nama_matkul ?? '') . ' - ' . $jadwal->kelas->nama_kelas,
                ],
                'dosen' => [
                    'dosen_id' => $jadwal->kelas->dosen->id,
                    'nidn' => $jadwal->kelas->dosen->nidn,
                    'nama' => $jadwal->kelas->dosen->nama,
                ],
                'riwayat_absensi' => $absensi->map(function ($item) {
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
