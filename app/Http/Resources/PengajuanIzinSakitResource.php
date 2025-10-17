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
        $data = $this->resource;
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->resource->map(function ($data) {
                return [
                    'pengajuan_id' => $data->id,
                    'kelas_id' => $data->kelas_id,
                    'status' => $data->status,
                    'keterangan' => $data->keterangan,
                    'bukti_file_path' => $data->bukti_file_path ? url($data->bukti_file_path) : null,
                    'status_validasi' => $data->status_validasi,
                    'mahasiswa' => [
                        'id' => $data->mahasiswa->id,
                        'npm' => $data->mahasiswa->npm,
                        'nama' => $data->mahasiswa->nama,
                        'stambuk' => $data->mahasiswa->stambuk,
                    ],
                    'sesi_kuliah' => [
                        'id' => $data->sesiKuliah->id,
                        'tanggal' => $data->sesiKuliah->tanggal,
                    ],
                ];
            })
        ];
    }
}
