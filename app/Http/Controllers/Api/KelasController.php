<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\PathParameter;

class KelasController extends Controller
{
    #[Group('Akses Dosen')]
    /**
     * Kelas Berdasarkan Dosen.
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

            return (new \App\Http\Resources\KelasResource($data, true, 'Data kelas berdasarkan NIDN Dosen'))
                ->response()
                ->setStatusCode(200);
            return response()->json([
                'status' => true,
                'message' => 'Data kelas berdasarkan NIDN Dosen',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
