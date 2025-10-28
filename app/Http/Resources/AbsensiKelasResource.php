<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $kelas = $this->resource->kelas;
        $matakuliah = $kelas->matakuliah->first();

        // Map semua sesi kuliah
        $sesiKuliah = $this->resource->sesiKuliah->map(function ($sesi) {
            $tanggalFormatted = null;
            if ($sesi->tanggal) {
                $carbon = Carbon::parse($sesi->tanggal);
                $carbon->locale('id');
                $tanggalFormatted = $carbon->isoFormat('dddd, D MMMM YYYY');
            }

            return [
                'id' => $sesi->id,
                'tanggal' => $sesi->tanggal,
                'tanggal_formatted' => $tanggalFormatted,
                'status_absensi' => $sesi->status_absensi,
                'waktu_buka' => $sesi->waktu_buka,
                'waktu_tutup' => $sesi->waktu_tutup,
                'jumlah_hadir' => $sesi->absensi->where('status', 'hadir')->count(),
                'jumlah_izin' => $sesi->absensi->where('status', 'izin')->count(),
                'jumlah_sakit' => $sesi->absensi->where('status', 'sakit')->count(),
                'jumlah_alfa' => $sesi->absensi->where('status', 'alfa')->count(),
                'total_absensi' => $sesi->absensi->count(),
            ];
        });

        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => [
                'jadwal_id' => $this->resource->id,
                'nama_kelas' => $matakuliah->nama_matkul . ' - ' . $kelas->nama_kelas,
                'hari' => $this->resource->hari,
                'jam' => [
                    'id' => $this->resource->jam->id ?? null,
                    'kode_jam' => $this->resource->jam->kode_jam ?? null,
                    'jam_mulai' => $this->resource->jam->jam_mulai ?? null,
                    'jam_selesai' => $this->resource->jam->jam_selesai ?? null,
                ],
                'ruangan' => [
                    'id' => $this->resource->ruangan->id ?? null,
                    'nama_ruang' => $this->resource->ruangan->nama_ruang ?? null,
                ],
                'tipe_pertemuan' => $this->resource->tipe_pertemuan ?? null,
                'matakuliah' => $matakuliah,
                'dosen' => [
                    'id' => $kelas->dosen->id ?? null,
                    'nidn' => $kelas->dosen->nidn ?? null,
                    'nama' => $kelas->dosen->nama ?? null,
                ],
            ],
            'sesi_kuliah' => $sesiKuliah,
            'statistik' => [
                'total_sesi' => $this->resource->sesiKuliah->count(),
                'sesi_aktif' => $this->resource->sesiKuliah->where('status_absensi', 'buka')->count(),
                'sesi_tutup' => $this->resource->sesiKuliah->where('status_absensi', 'tutup')->count(),
            ],
        ];
    }
}
