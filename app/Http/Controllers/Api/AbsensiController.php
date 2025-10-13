<?php

namespace App\Http\Controllers\Api;

use App\Models\SesiKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiBySesiResource;
use Dedoc\Scramble\Attributes\Group;
use App\Models\KelasMatakuliahMahasiswa;
use App\Http\Resources\SesiKuliahResource;
use App\Http\Resources\SesiKuliahByIdResource;
use App\Models\Absensi;

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

    #[Group('Akses Dosen')]
    /**
     * Menutup Sesi Absensi
     *
     * Dosen dapat menutup sesi absensi untuk kelas yang diampunya.
     *
     * @return Response.
     */
    public function tutupSesiAbsensi(Request $request, $sesiId)
    {
        $sesi = SesiKuliah::where('id', $sesiId)
            ->where('status_absensi', 'buka')
            ->first();

        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi absensi tidak ditemukan atau sudah ditutup.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $sesi->status_absensi = 'tutup';
            $sesi->waktu_tutup = Carbon::now();
            $sesi->save();
            DB::commit();
            return (new SesiKuliahByIdResource(
                true,
                'Sesi absensi berhasil ditutup.',
                $sesi
            ))->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menutup sesi absensi.',
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

    #[Group('Akses Mahasiswa')]
    /**
     * Melakukan Absensi
     *
     * Mahasiswa dapat melakukan absensi pada sesi yang sedang aktif.
     *
     * @return Response.
     */
    public function absensi(Request $request)
    {
        $validasi = $request->validate([
            'sesi_kuliah_id' => 'required|exists:sesi_kuliah,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $mahasiswa = $request->user();

        $sesi = SesiKuliah::where('id', $validasi['sesi_kuliah_id'])
            ->with('jadwal.ruangan')
            ->where('status_absensi', 'buka')
            ->first();

         if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi absensi sudah ditutup.'
            ], 404);
        }

        // cek latitude dan longitude mahasiswa apakah sesuai dengan ruangan kelas dan memiliki jarak maksimal 5 meter
        $ruangan = $sesi->jadwal->ruangan;
        if (!$ruangan || !$ruangan->latitude || !$ruangan->longitude) {
            return response()->json([
                'status' => false,
                'message' => 'Data lokasi ruangan tidak tersedia.'
            ], 400);
        }

        $jarak = $this->hitungJarak(
            $validasi['latitude'],
            $validasi['longitude'],
            $ruangan->latitude,
            $ruangan->longitude
        );

        if ($jarak > 5) {
            return response()->json([
                'status' => false,
                'message' => 'Anda berada di luar jangkauan ruangan kelas. Jarak Anda: ' . round($jarak, 1) . ' meter dari ruangan.'
            ], 403);
        }

        // Cek apakah mahasiswa terdaftar di kelas untuk sesi ini
        $kelasMatkulMhs = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('kelas.jadwal.sesiKuliah', function ($query) use ($sesi) {
                $query->where('id', $sesi->id);
            })
            ->first();

        if (!$kelasMatkulMhs) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak terdaftar di kelas untuk sesi ini.'
            ], 403);
        }

        // Cek apakah mahasiswa sudah melakukan absensi untuk sesi ini
        $absensi = $sesi->absensi()->where('sesi_kuliah_id', $validasi['sesi_kuliah_id'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();
        if ($absensi) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah melakukan absensi untuk sesi ini.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $simpanAbsensi = Absensi::create([
                'sesi_kuliah_id' => $sesi->id,
                'mahasiswa_id' => $mahasiswa->id,
                'waktu_absensi' => Carbon::now(),
                'status' => 'hadir',
                'latitude' => $validasi['latitude'] ?? null,
                'longitude' => $validasi['longitude'] ?? null,
            ]);

            DB::commit();

            return (new AbsensiBySesiResource(
                true,
                'Absensi berhasil dilakukan.',
                $simpanAbsensi
            ))->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal melakukan absensi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
