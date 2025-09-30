<?php

namespace App\Http\Controllers;

use App\Models\Jam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JamController extends Controller
{
    /**
     * Menampilkan seluruh daftar jam
     */
    public function index()
    {
        $data = Jam::orderBy('jam_mulai', 'asc')->get();
        return view('jam.index', compact('data'));
    }

    /**
     * Menampilkan form untuk membuat data jam baru
     */
    public function create()
    {
        return view('jam.create');
    }

    /**
     * Menyimpan data jam baru ke database
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'kode_jam' => 'required|string|max:10|unique:jam,kode_jam',
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i',
        ]);

        DB::beginTransaction();
        try {
            Jam::create($validasi);
            DB::commit();
            return redirect()->route('data.jam')->with('success', 'Data jam berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Menampilkan form untuk mengedit data jam yang sudah ada
     */
    public function edit($id)
    {
        $data = Jam::findOrFail($id);
        return view('jam.edit', compact('data'));
    }

    /**
     * Memperbarui data jam yang sudah ada di database
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'kode_jam' => 'required|string|max:10|unique:jam,kode_jam,' . $id,
            'jam_mulai' => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i',
        ]);

        DB::beginTransaction();
        try {
            Jam::where('id', $id)->update($validasi);
            DB::commit();
            return redirect()->route('data.jam')->with('success', 'Data jam berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Menghapus data jam dari database
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            Jam::where('id', $id)->delete();
            DB::commit();
            return redirect()->route('data.jam')->with('success', 'Data jam berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()]);
        }
    }
}
