<?php

namespace App\Http\Controllers\Api;

use App\Models\SesiKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use App\Models\KelasMatakuliahMahasiswa;
use App\Http\Resources\SesiKuliahResource;
use App\Http\Resources\SesiKuliahByIdResource;

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

    #[Group('Akses Mahasiswa')]
    /**
     * Melihat Sesi Absensi Aktif
     *
     * Mahasiswa dapat melihat sesi absensi yang sedang aktif.
     *
     * @return Response.
     */
    public function sesiAbsensiAktif(Request $request)
    {
        try {
            $mahasiswa = $request->user();

            $sesi = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                    ->whereHas('kelas.jadwal.sesiKuliah', function ($query) {
                        $query->where('status_absensi', 'buka');
                    })
                    ->with(['kelas.jadwal.sesiKuliah' => function ($query) {
                        $query->where('status_absensi', 'buka');
                    }, 'kelas.jadwal', 'kelas', 'kelas.matakuliah', 'kelas.dosen', 'kelas.jadwal.sesiKuliah'])
                    ->get();

            if ($sesi->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada sesi absensi aktif.'
                ], 404);
            }

            Log::info($sesi);

            return (new SesiKuliahResource(
                true,
                'Sesi absensi aktif ditemukan.',
                $sesi
            ))->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data sesi absensi aktif.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
