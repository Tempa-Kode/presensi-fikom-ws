<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Prodi;
use App\Models\Matakuliah;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TahunAkademik;
use App\Models\MatakuliahKelas;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

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
            return collect();
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
            $validasi['kode_kelas'] = Str::random(6);
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

    /**
     * Menampilkan detail kelas
     */
    public function detail($id)
    {
        $kelas = Kelas::with(
            'dosen',
            'prodi',
            'matakuliah',
            'tahunAkademik',
            'mahasiswa',
            'jadwal',
            'jadwal.ruangan',
            'jadwal.jam'
        )->findOrFail($id);

        return view('kelas.show', compact('kelas'));
    }

    /**
     * Mengeluarkan mahasiswa dari kelas
     */
    public function keluarkan($kelasId, $mahasiswaId)
    {
        DB::beginTransaction();
        try {
            $kelas = Kelas::findOrFail($kelasId);
            $kelas->mahasiswa()->detach($mahasiswaId);
            DB::commit();
            return redirect()->back()->with('success', "Mahasiswa berhasil dikeluarkan dari kelas");
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal mengeluarkan mahasiswa: ' . $th->getMessage());
        }
    }

    /**
     * Menampilkan tabel absensi kelas per jadwal
     */
    public function absensi($kelasId, $jadwalId)
    {
        $kelas = Kelas::with([
            'dosen',
            'prodi',
            'matakuliah',
            'tahunAkademik',
            'mahasiswa' => function($query) {
                $query->orderBy('npm', 'asc');
            }
        ])->where('id', $kelasId)->first();

        // Ambil jadwal spesifik dengan sesi kuliah dan absensi
        $jadwal = Jadwal::with([
            'ruangan',
            'jam',
            'sesiKuliah' => function($query) {
                $query->orderBy('tanggal', 'asc');
            },
            'sesiKuliah.absensi'
        ])->where('id', $jadwalId)
          ->where('kelas_id', $kelasId)
          ->first();

        // Ambil sesi kuliah untuk jadwal ini (maksimal 16 pertemuan)
        $sesiKuliah = $jadwal->sesiKuliah->sortBy('tanggal')->take(16);
        // dd($sesiKuliah);

        // Buat array absensi untuk setiap mahasiswa di setiap pertemuan
        $absensiData = [];
        foreach ($kelas->mahasiswa as $mahasiswa) {
            $absensiData[$mahasiswa->id] = [];
            foreach ($sesiKuliah as $index => $sesi) {
                $absensi = $sesi->absensi->where('mahasiswa_id', $mahasiswa->id)->first();
                $absensiData[$mahasiswa->id][$index + 1] = $absensi ? $absensi->status : null;
            }
        }

        return view('kelas.absensi', compact('kelas', 'jadwal', 'sesiKuliah', 'absensiData'));
    }

    /**
     * Generate PDF untuk daftar hadir
     */
    public function cetakAbsensiPDF($kelasId, $jadwalId)
    {
        $kelas = Kelas::with([
            'dosen',
            'prodi',
            'matakuliah',
            'tahunAkademik',
            'mahasiswa' => function($query) {
                $query->orderBy('npm', 'asc');
            }
        ])->where('id', $kelasId)->first();

        // Ambil jadwal spesifik dengan sesi kuliah dan absensi
        $jadwal = Jadwal::with([
            'ruangan',
            'jam',
            'sesiKuliah' => function($query) {
                $query->orderBy('tanggal', 'asc');
            },
            'sesiKuliah.absensi'
        ])->where('id', $jadwalId)
          ->where('kelas_id', $kelasId)
          ->first();

        // Ambil sesi kuliah untuk jadwal ini (maksimal 16 pertemuan)
        $sesiKuliah = $jadwal->sesiKuliah->sortBy('tanggal')->take(16);

        // Buat array absensi untuk setiap mahasiswa di setiap pertemuan
        $absensiData = [];
        foreach ($kelas->mahasiswa as $mahasiswa) {
            $absensiData[$mahasiswa->id] = [];
            foreach ($sesiKuliah as $index => $sesi) {
                $absensi = $sesi->absensi->where('mahasiswa_id', $mahasiswa->id)->first();
                $absensiData[$mahasiswa->id][$index + 1] = $absensi ? $absensi->status : null;
            }
        }

        // Generate PDF
        $pdf = Pdf::loadView('kelas.absensi-pdf', compact('kelas', 'jadwal', 'sesiKuliah', 'absensiData'));
        $pdf->setPaper('A4', 'portrait');


        // Nama file PDF - bersihkan karakter yang tidak valid
        $namaKelas = preg_replace('/[^A-Za-z0-9_\-]/', '_', $kelas->nama_kelas);
        $fileName = 'Daftar_Hadir_' . $namaKelas . '_' . date('YmdHis') . '.pdf';

        return $pdf->stream($fileName);
    }
}
