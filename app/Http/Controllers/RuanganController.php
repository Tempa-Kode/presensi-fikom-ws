<?php

namespace App\Http\Controllers;

use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RuanganController extends Controller
{
    /**
     * Menampilkan daftar ruangan
     */
    public function index()
    {
        $data = Ruangan::latest()->get();
        return view('ruangan.index', compact('data'));
    }

    /**
     * menampilkan form untuk membuat resource baru
     */
    public function create()
    {
        return view('ruangan.create');
    }

    /**
     * Menyimpan resource baru yang dibuat
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'nama_ruang' => 'required|string|max:255',
            'latitude' => 'required|string|max:255',
            'longitude' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            Ruangan::create($validasi);
            DB::commit();
            return redirect()->route('data.ruangan')->with('success', 'Data ruangan berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }

    /**
     * Menampilkan form untuk mengedit resource yang ada
     */
    public function edit($id)
    {
        $data = Ruangan::findOrFail($id);
        return view('ruangan.edit', compact('data'));
    }

    /**
     * Menyimpan perubahan pada resource yang ada
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'nama_ruang' => 'required|string|max:255',
            'latitude' => 'required|string|max:255',
            'longitude' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $ruangan = Ruangan::findOrFail($id);
            $ruangan->update($validasi);
            DB::commit();
            return redirect()->route('data.ruangan')->with('success', 'Data ruangan berhasil diupdate');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }

    /**
     * Menghapus resource yang ada
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $ruangan = Ruangan::findOrFail($id);
            $ruangan->delete();
            DB::commit();
            return redirect()->route('data.ruangan')->with('success', 'Data ruangan berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }
}
