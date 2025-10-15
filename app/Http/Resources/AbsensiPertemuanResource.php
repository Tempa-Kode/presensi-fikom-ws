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

    public function toArray(Request $request): array
    {
        // mengambil satu jadwal & satu sesi_kuliah yang sudah difilter di controller
        $jadwal = $this->jadwal->first();
        $sesi   = $jadwal?->sesiKuliah?->first();

        // mengambil satu matakuliah (kalau ada)
        $matkul = $this->matakuliah->first();

        // mengumpulkan semua entri absensi (sudah difilter per sesi di controller)
        $absensi = $this->mahasiswa->flatMap(fn ($mhs) => $mhs->absensi);

        $countBy = fn (string $status) => $absensi->where('status', $status)->count();

        return [
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => [
                'tanggal'     => $sesi?->tanggal,
                'waktu_buka'  => $sesi?->waktu_buka,
                'waktu_tutup' => $sesi?->waktu_tutup,
                'kelas'       => [
                    'id'          => $this->id,
                    'nama_kelas'  => $this->nama_kelas,
                    'matakuliah'  => $matkul ? [
                        'kode_matkul'     => $matkul->kode_matkul,
                        'nama'            => $matkul->nama_matkul,
                        'sks'             => $matkul->sks,
                        'tipe_pertemuan'  => $jadwal?->tipe_pertemuan,
                    ] : null,
                ],
                'absensi' => [
                    'hadir'           => $countBy('hadir'),
                    'izin'            => $countBy('izin'),
                    'sakit'           => $countBy('sakit'),
                    'alpha'           => $countBy('alfa'),
                    'total_mahasiswa' => $this->mahasiswa->count(),
                    'daftar'          => $this->mahasiswa->map(function ($mhs) {
                        $a = $mhs->absensi->first();
                        return [
                            'mahasiswa_id'  => $mhs->id,
                            'npm'           => $mhs->npm,
                            'nama'          => $mhs->nama,
                            'stambuk'       => $mhs->stambuk,
                            'absensi'     => $a ? [
                                'id'     => $a->id,
                                'waktu' => $a->waktu_absensi,
                                'status' => $a->status,
                            ] : null,
                        ];
                    })->values(),
                ],
            ],
            'meta' => [
                'total' => $this->mahasiswa->count(),
            ],
        ];
    }
}

