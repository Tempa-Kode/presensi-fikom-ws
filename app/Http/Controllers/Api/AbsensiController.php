<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\SesiKuliahByIdResource;
use App\Models\SesiKuliah;
use Dedoc\Scramble\Attributes\Group;

class AbsensiController extends Controller
{
    #[Group('Akses Dosen')]
    /**
     * Buat Sesi Absensi
     *
     * Dosen dapat membuat/membuka sesi absensi untuk kelas yang diampunya.
     *
     * @return Response.
     */
    public function buatSesiAbsensi(Request $request)
    {
        $validasi = $request->validate([
            'jadwal_id' => 'required|exists:jadwal,id',
        ]);

        $date = Carbon::now()->toDateString();

        $sesiExist = SesiKuliah::where('jadwal_id', $validasi['jadwal_id'])
            ->where('tanggal', $date)
            ->where('status_absensi', 'buka')
            ->first();
        if ($sesiExist) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi absensi untuk jadwal ini sudah dibuka'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $validasi['tanggal'] = $date;
            $validasi['status_absensi'] = 'buka';
            $validasi['waktu_buka'] = Carbon::now();

            $data = SesiKuliah::create($validasi);
            DB::commit();

            return (new SesiKuliahByIdResource(
                true,
                'Sesi absensi berhasil dibuat.',
                $data
            ))->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat sesi absensi.',
                'error' => $e->getMessage()
            ], 500);
        }

    }
}
