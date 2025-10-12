<?php

namespace App\Http\Controllers\Api;

use App\Models\Kelas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use App\Http\Resources\KelasResource;
use Dedoc\Scramble\Attributes\PathParameter;

class KelasController extends Controller
{
    #[Group('Akses Dosen')]
    /**
     * Kelas Berdasarkan Dosen.
     *
     * Mengambil data kelas(matakuliah) berdasarkan NIDN Dosen. dimana Dosen dapat melihat daftar kelas yang diampunya
     *
     * @param string $nidn
     * @return Response.
     */
    #[PathParameter('nidn', 'NIDN Dosen', example: '0114046501')]
    public function kelasByDosen($nidn)
    {
        try{
            $data = Kelas::whereHas('dosen', function ($query) use ($nidn) {
                $query->where('nidn', $nidn);
            })->with([
                'dosen',
                'matakuliah',
                'prodi',
                'jadwal',
                'jadwal.ruangan',
                'jadwal.jam',
                'tahunAkademik'
            ])->get();

            if($data->isEmpty()){
                return response()->json([
                    'status' => false,
                    'message' => 'Data kelas tidak ditemukan untuk NIDN Dosen: ' . $nidn
                ], 404);
            }

            return (new KelasResource(
                true,
                'Data kelas berdasarkan NIDN Dosen',
                $data,
            ))->response()
                ->setStatusCode(200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
