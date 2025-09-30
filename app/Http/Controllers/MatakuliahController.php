<?php

namespace App\Http\Controllers;

use App\Models\Matakuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatakuliahController extends Controller
{
    /**
     * Menampilkan seluruh daftar matakuliah
     */
    public function index()
    {
        $data = Matakuliah::latest()->get();
        return view('matakuliah.index', compact('data'));
    }

    /**
     * Menampilkan form untuk menambah matakuliah baru
     */
    public function create()
    {
        return view('matakuliah.create');
    }

    /**
     * Menyimpan data matakuliah baru ke database
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'kode_matkul' => 'required|unique:matakuliah,kode_matkul',
            'nama_matkul' => 'required',
            'sks' => 'required|integer',
            'semester' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            Matakuliah::create($validasi);
            DB::commit();
            return redirect()->route('data.matakuliah')->with('success', 'Data matakuliah berhasil disimpan');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan data matakuliah: ' . $th->getMessage());
        }
    }

    /**
     * Menampilkan form untuk mengedit data matakuliah yang sudah ada
     */
    public function edit($id)
    {
        $data = Matakuliah::findOrFail($id);
        return view('matakuliah.edit', compact('data'));
    }

    /**
     * Menyimpan perubahan data matakuliah ke database
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'kode_matkul' => 'required|unique:matakuliah,kode_matkul,' . $id,
            'nama_matkul' => 'required',
            'sks' => 'required|integer',
            'semester' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            $matakuliah = Matakuliah::findOrFail($id);
            $matakuliah->update($validasi);
            DB::commit();
            return redirect()->route('data.matakuliah')->with('success', 'Data matakuliah berhasil diperbarui');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan saat memperbarui data matakuliah: ' . $th->getMessage());
        }
    }

    /**
     * Menghapus data matakuliah dari database
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $matakuliah = Matakuliah::findOrFail($id);
            $matakuliah->delete();
            DB::commit();
            return redirect()->route('data.matakuliah')->with('success', 'Data matakuliah berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus data matakuliah: ' . $th->getMessage());
        }
    }
}
