<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanIzinSakitResource extends JsonResource
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
        // Ambil info sesi dari pengajuan pertama (karena semua pengajuan dalam 1 sesi)
        $sesiInfo = null;
        if ($this->resource->isNotEmpty() && $this->resource->first()->sesiKuliah) {
            $sesi = $this->resource->first()->sesiKuliah;
            $tanggalFormatted = null;
            if ($sesi->tanggal) {
                $carbon = \Carbon\Carbon::parse($sesi->tanggal);
                $carbon->locale('id');
                $tanggalFormatted = $carbon->isoFormat('dddd, D MMMM YYYY');
            }

            $sesiInfo = [
                'id' => $sesi->id,
                'tanggal' => $sesi->tanggal,
                'tanggal_formatted' => $tanggalFormatted,
                'status_absensi' => $sesi->status_absensi,
                'waktu_buka' => $sesi->waktu_buka,
                'waktu_tutup' => $sesi->waktu_tutup,
            ];
        }

        return [
            'status' => $this->status,
            'message' => $this->message,
            'sesi_kuliah' => $sesiInfo,
            'total_pengajuan' => $this->resource->count(),
            'pengajuan' => $this->resource->map(function ($data) {
                return [
                    'id' => $data->id,
                    'status' => $data->status,
                    'keterangan' => $data->keterangan,
                    'bukti_file_path' => $data->bukti_file_path ? url($data->bukti_file_path) : null,
                    'status_validasi' => $data->status_validasi,
                    'created_at' => $data->created_at,
                    'mahasiswa' => [
                        'id' => $data->mahasiswa->id,
                        'npm' => $data->mahasiswa->npm,
                        'nama' => $data->mahasiswa->nama,
                        'stambuk' => $data->mahasiswa->stambuk ?? null,
                    ],
                ];
            })
        ];
    }
}
