<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;

class TahunAkademikController extends Controller
{
    /**
     * Menampilkan semua data tahun akademik.
     */
    public function index()
    {
        $data = TahunAkademik::latest()->get();
        return view('tahun_akademik.index', compact('data'));
    }

    /**
     * Menampilkan form untuk membuat data tahun akademik baru.
     */
    public function create()
    {
        return view('tahun_akademik.create');
    }

    /**
     * Menyimpan data tahun akademik baru ke database.
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'nama_tahun' => 'required|unique:tahun_akademik,nama_tahun',
        ]);

        DB::beginTransaction();
        try {
            TahunAkademik::create($validasi);
            DB::commit();
            return redirect()->route('data.tahun_akademik')->with('success', 'Data tahun akademik berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Menampilkan form untuk mengedit data tahun akademik yang ada.
     */
    public function edit($id)
    {
        $data = TahunAkademik::findOrFail($id);
        return view('tahun_akademik.edit', compact('data'));
    }

    /**
     * Memperbarui data tahun akademik yang ada di database.
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'nama_tahun' => 'required|unique:tahun_akademik,nama_tahun,' . $id,
        ]);

        DB::beginTransaction();
        try {
            $tahunAkademik = TahunAkademik::findOrFail($id);
            $tahunAkademik->update($validasi);
            DB::commit();
            return redirect()->route('data.tahun_akademik')->with('success', 'Data tahun akademik berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Menghapus data tahun akademik dari database.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $tahunAkademik = TahunAkademik::findOrFail($id);
            $tahunAkademik->delete();
            DB::commit();
            return redirect()->route('data.tahun_akademik')->with('success', 'Data tahun akademik berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()]);
        }
    }
}
