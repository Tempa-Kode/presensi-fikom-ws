<?php

namespace App\Http\Controllers\Api;

use App\Models\Jadwal;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\KaprodiKelasResource;

class KaprodiController extends Controller
{
    public function daftarKelas(Request $request)
    {
        $dosen = $request->user();
        $prodiDiketuai = $dosen->prodiDiketuai;

        if (!$prodiDiketuai) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak terdaftar sebagai kaprodi.'
            ], 403);
        }

        $activeId = Setting::activeTahunAkademikId();

        $jadwalQuery = Jadwal::whereHas('kelas', function ($q) use ($prodiDiketuai) {
            $q->where('prodi_id', $prodiDiketuai->id);
        })
            ->when($activeId, function ($q) use ($activeId) {
                $q->whereHas('kelas', fn($q2) => $q2->where('tahun_akademik_id', $activeId));
            })
            ->with(['kelas.dosen', 'kelas.matakuliah', 'kelas.prodi', 'ruangan', 'jam', 'sesiKuliah.absensi']);

        if ($request->filled('semester')) {
            $jadwalQuery->whereHas('kelas.matakuliah', function ($q) use ($request) {
                $q->where('semester', $request->semester);
            });
        }

        $jadwal = $jadwalQuery->get();

        return (new KaprodiKelasResource(
            true,
            'Daftar kelas program studi',
            $jadwal
        ))->response()->setStatusCode(200);
    }
}
