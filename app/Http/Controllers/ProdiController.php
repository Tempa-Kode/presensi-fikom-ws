<?php

namespace App\Http\Controllers;

use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdiController extends Controller
{
    /**
     * Menampilkan seluruh data prodi
     */
    public function index()
    {
        $data = Prodi::latest()->get();
        return view('prodi.index', compact('data'));
    }

    /**
     * menampilkan form untuk membuat prodi baru
     */
    public function create()
    {
        return view('prodi.create');
    }

    /**
     * menyimpan data prodi baru
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'kode_prodi' => 'required|unique:prodi,kode_prodi',
            'nama_prodi' => 'required|unique:prodi,kode_prodi',
        ]);

        DB::beginTransaction();
        try {
            Prodi::create($validasi);
            DB::commit();
            return redirect()->route('data.prodi')->with('success', 'Data prodi berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data prodi: ' . $th->getMessage());
        }
    }

    /**
     * Menampilkan form untuk mengedit data prodi yang dipilih.
     */
    public function edit($id)
    {
        $data = Prodi::findOrFail($id);
        return view('prodi.edit', compact('data'));
    }

    /**
     * Menyimpan pembaharuan data prodi
     */
    public function update(Request $request, string $id)
    {
        $validasi = $request->validate([
            'kode_prodi' => 'required|unique:prodi,kode_prodi,' . $id,
            'nama_prodi' => 'required|unique:prodi,nama_prodi,' . $id,
        ]);

        DB::beginTransaction();
        try {
            Prodi::findOrFail($id)->update($validasi);
            DB::commit();
            return redirect()->route('data.prodi')->with('success', 'Data prodi berhasil diperbarui');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui data prodi: ' . $th->getMessage());
        }
    }

    /**
     * Menghapus data prodi yang dipilih
     */
    public function destroy($id)
    {
        try {
            Prodi::findOrFail($id)->delete();
            return redirect()->route('data.prodi')->with('success', 'Data prodi berhasil dihapus');
        } catch (\Throwable $th) {
            return redirect()->route('data.prodi')->with('error', 'Terjadi kesalahan saat menghapus data prodi: ' . $th->getMessage());
        }
    }
}
