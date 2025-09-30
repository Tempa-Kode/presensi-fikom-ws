<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Kelas;
use App\Models\Prodi;
use App\Models\Matakuliah;
use App\Models\MatakuliahKelas;
use Illuminate\Http\Request;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    /**
     * Menampilkan seluruh daftar kelas
     */
    public function index()
    {
        $data = Kelas::with('dosen', 'prodi', 'matakuliah', 'tahunAkademik')->latest()->get();

        $semester = $data->flatMap(function($item) {
            if($item->matakuliah && $item->matakuliah->count() > 0) {
                return $item->matakuliah->pluck('semester');
            }
            return [];
        })->unique()->sort()->values();

        $prodi = Prodi::all();
        $tahunAkademik = TahunAkademik::all();

        return view('kelas.index', compact('data', 'semester', 'prodi', 'tahunAkademik'));
    }

    /**
     * Menampilkan form untuk membuat kelas baru
     */
    public function create()
    {
        $prodi = Prodi::all();
        $matkul = Matakuliah::all();
        $dosen = User::where('role', 'dosen')->get();
        $tahunAkademik = TahunAkademik::all();
        return view('kelas.create', compact(
            'prodi',
            'matkul',
            'dosen',
            'tahunAkademik'
        ));
    }

    /**
     * Menyimpan kelas baru ke database
     */
    public function store(Request $request)
    {
        $validasi = $request->validate([
            'tahun_akademik_id' => 'required',
            'prodi_id' => 'required',
            'matkul_id' => 'required',
            'dosen_id' => 'required',
            'nama_kelas' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $kelas = Kelas::create($validasi);
            MatakuliahKelas::create([
                'matkul_id' => $request->matkul_id,
                'kelas_id' => $kelas->id,
            ]);
            DB::commit();
            return redirect()->route('data.kelas')->with('success', 'Kelas berhasil ditambahkan');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Kelas gagal ditambahkan: ' . $th->getMessage())->withInput();
        }
    }

    /**
     * Menampilkan form untuk mengedit kelas yang sudah ada
     */
    public function edit($id)
    {
        $kelas = Kelas::with('dosen', 'prodi', 'matakuliah', 'tahunAkademik')->findOrFail($id);
        $prodi = Prodi::all();
        $matkul = Matakuliah::all();
        $dosen = User::where('role', 'dosen')->get();
        $tahunAkademik = TahunAkademik::all();
        return view('kelas.edit', compact(
            'kelas',
            'prodi',
            'matkul',
            'dosen',
            'tahunAkademik'
        ));
    }

    /**
     * Memperbarui data kelas yang sudah ada di database
     */
    public function update(Request $request, $id)
    {
        $validasi = $request->validate([
            'tahun_akademik_id' => 'required',
            'prodi_id' => 'required',
            'matkul_id' => 'required',
            'dosen_id' => 'required',
            'nama_kelas' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $kelas = Kelas::findOrFail($id);
            $kelas->update($validasi);
            MatakuliahKelas::where('kelas_id', $kelas->id)->update([
                'matkul_id' => $request->matkul_id,
            ]);
            DB::commit();
            return redirect()->route('data.kelas')->with('success', 'Kelas berhasil diperbarui');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Kelas gagal diperbarui: ' . $th->getMessage())->withInput();
        }
    }

    /**
     * Menghapus kelas dari database
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $kelas = Kelas::findOrFail($id);
            MatakuliahKelas::where('kelas_id', $kelas->id)->delete();
            $kelas->delete();
            DB::commit();
            return redirect()->route('data.kelas')->with('success', 'Kelas berhasil dihapus');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Kelas gagal dihapus: ' . $th->getMessage());
        }
    }
}
