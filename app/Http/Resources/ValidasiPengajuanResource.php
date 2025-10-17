<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidasiPengajuanResource extends JsonResource
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
        $data = $this->resource;
        return [
            'status'  => $this->status,
            'message' => $this->message,
            'data'    => [
                'mahasiswa' => [
                    'id'            => $data->mahasiswa->id,
                    'nama'          => $data->mahasiswa->nama,
                    'npm'           => $data->mahasiswa->npm,
                    'stambuk'        => $data->mahasiswa->stambuk,
                ],
                'pengajuan' => [
                    'id'            => $data->pengajuan->id,
                    'status'        => $data->pengajuan->status,
                    'keterangan'    => $data->pengajuan->keterangan,
                    'status_validasi' => $data->pengajuan->status_validasi,
                ],
                'absensi' => [
                    'id'            => $data->absensi->id,
                    'status'        => $data->absensi->status,
                    'waktu_absensi' => $data->absensi->waktu_absensi,
                ],
            ],
        ];
    }
}
