<?php

namespace App\Http\Controllers;

use App\Models\Jam;
use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Ruangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JadwalController extends Controller
{
    /**
     * Menamilkan daftar jadwal
     */
    public function index()
    {
        $data = Jadwal::with([
            'kelas',
            'kelas.dosen',
            'kelas.prodi',
            'ruangan',
            'kelas.matakuliah',
            'jam'
        ])->get();

        return view('jadwal.index', compact('data'));
    }

    /**
     * Menampilkan form untuk membuat jadwal baru
     */
    public function create()
    {
        $kelas = Kelas::with('matakuliah')->latest()->get();
        $ruangan = Ruangan::latest()->get();
        $jam = Jam::orderBy('jam_mulai', 'asc')->get();
        return view('jadwal.create', compact('kelas', 'ruangan', 'jam'));
    }

    /**
     * Menyimpan jadwal baru
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'jam_id' => 'required|exists:jam,id',
            'hari' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu',
            'tipe_pertemuan' => 'required|in:teori,praktek'
        ]);

        DB::beginTransaction();
        try {
            Jadwal::create($validasi);
            DB::commit();
            return redirect()->route('data.jadwal')->with('success', 'Jadwal berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['error' => 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan form untuk mengedit jadwal yang ada
     */
    public function edit($id)
    {
        $jadwal = Jadwal::findOrFail($id);
        $kelas = Kelas::with('matakuliah')->latest()->get();
        $ruangan = Ruangan::latest()->get();
        $jam = Jam::orderBy('jam_mulai', 'asc')->get();
        return view('jadwal.edit', compact('jadwal', 'kelas', 'ruangan', 'jam'));
    }

    /**
     * Memperbarui jadwal yang ada
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'ruangan_id' => 'required|exists:ruangan,id',
            'jam_id' => 'required|exists:jam,id',
            'hari' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu',
            'tipe_pertemuan' => 'required|in:teori,praktek'
        ]);

        DB::beginTransaction();
        try {
            $jadwal = Jadwal::findOrFail($id);
            $jadwal->update($validasi);
            DB::commit();
            return redirect()->route('data.jadwal')->with('success', 'Jadwal berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['error' => 'Terjadi kesalahan saat memperbarui data: ' . $e->getMessage()]);
        }
    }

    /**
     * Menghapus data jadwal
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $jadwal = Jadwal::findOrFail($id);
            $jadwal->delete();
            DB::commit();
            return redirect()->route('data.jadwal')->with('success', 'Jadwal berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with(['error' => 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage()]);
        }
    }
}
