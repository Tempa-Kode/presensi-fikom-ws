<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
    /**
     * Menampilkan halaman pengaturan sistem
     */
    public function index()
    {
        $tahunAkademik = TahunAkademik::all();
        $activeTahunAkademikId = Setting::activeTahunAkademikId();

        return view('pengaturan.index', compact('tahunAkademik', 'activeTahunAkademikId'));
    }

    /**
     * Menyimpan pengaturan Tahun Akademik aktif
     */
    public function updateTahunAkademik(Request $request)
    {
        $request->validate([
            'tahun_akademik_id' => 'required|exists:tahun_akademik,id',
        ]);

        DB::beginTransaction();
        try {
            Setting::setValue('tahun_akademik_aktif_id', $request->tahun_akademik_id);
            DB::commit();
            return redirect()->route('data.pengaturan')->with('success', 'Tahun Akademik aktif berhasil diperbarui');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memperbarui pengaturan: ' . $th->getMessage());
        }
    }
}
