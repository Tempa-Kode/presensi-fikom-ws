<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Kelas;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\AbsensiKelasResource;
use Dedoc\Scramble\Attributes\Group;
use App\Http\Resources\KelasResource;
use App\Models\KelasMatakuliahMahasiswa;
use App\Http\Resources\KelasByIdResource;
use App\Http\Resources\JadwalKelasResource;
use App\Http\Resources\JadwalKelasDosenResource;
use App\Models\Jadwal;
use Dedoc\Scramble\Attributes\PathParameter;

class KelasController extends Controller
{
    #[Group('Akses Dosen')]
    /**
     * Kelas Berdasarkan Dosen.
     *
     * Mengambil data jadwal kelas(matakuliah) berdasarkan NIDN Dosen. dimana Dosen dapat melihat daftar jadwal kelas yang diampunya
     *
     * @param string $nidn
     * @return Response.
     */
    #[PathParameter('nidn', 'NIDN Dosen', example: '0114046501')]
    public function kelasByDosen($nidn)
    {
        try{
            $activeId = Setting::activeTahunAkademikId();

            // Ambil jadwal berdasarkan kelas yang diampu dosen
            $jadwalData = Jadwal::whereHas('kelas.dosen', function ($query) use ($nidn) {
                $query->where('nidn', $nidn);
            })->when($activeId, function ($q) use ($activeId) {
                $q->whereHas('kelas', fn($q2) => $q2->where('tahun_akademik_id', $activeId));
            })->with([
                'kelas.dosen',
                'kelas.matakuliah',
                'kelas.prodi',
                'kelas.tahunAkademik',
                'kelas.mahasiswa',
                'ruangan',
                'jam',
                'sesiKuliah.absensi'
            ])->get();

            if($jadwalData->isEmpty()){
                return response()->json([
                    'status' => false,
                    'message' => 'Data jadwal kelas tidak ditemukan untuk NIDN Dosen: ' . $nidn
                ], 404);
            }

            return (new JadwalKelasDosenResource(
                true,
                'Data jadwal kelas berdasarkan NIDN Dosen',
                $jadwalData,
            ))->response()
                ->setStatusCode(200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Mahasiswa')]
    /**
     * Mendaftarkan diri ke kelas.
     *
     * Mahasiswa dapat mendaftarkan diri ke kelas(matakuliah) yang diinginkan dengan memasukkan Kode Kelas.
     *
     * @query string $kode_kelas
     * @return Response.
     */
    public function daftarKelas(Request $request)
    {
        $request->validate([
            'kode_kelas' => 'required|string|exists:kelas,kode_kelas',
            'npm' => 'required|exists:users,npm',
        ]);

        try {
            // Cek apakah kelas dengan kode_kelas ada
            $kelas = Kelas::where('kode_kelas', $request->kode_kelas)->first();
            if (!$kelas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kelas dengan kode: ' . $request->kode_kelas . ' tidak ditemukan.'
                ], 404);
            }

            // Cek apakah npm mahasiswa ada
            $mahasiswa = User::where('npm', $request->npm)->first();
            if (!$mahasiswa) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mahasiswa dengan NPM: ' . $request->npm . ' tidak ditemukan.'
                ], 404);
            }

            // Cek apakah mahasiswa sudah terdaftar di kelas tersebut
            $existingEnrollment = KelasMatakuliahMahasiswa::where('kelas_id', $kelas->id)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();
            if ($existingEnrollment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mahasiswa dengan NPM: ' . $request->npm . ' sudah terdaftar di kelas ' . $kelas->matakuliah->first()->nama_matkul . ' - ' .$kelas->nama_kelas . '.'
                ], 400);
            }

            KelasMatakuliahMahasiswa::create([
                'kelas_id' => $kelas->id,
                'mahasiswa_id' => $mahasiswa->id,
            ]);

            $data = [
                'id' => $kelas->id,
                'nama_kelas' => $kelas->matakuliah->first()
                    ? $kelas->matakuliah->first()->nama_matkul . ' - ' . $kelas->nama_kelas
                    : $kelas->nama_kelas,
                'dosen' => [
                    'id' => $kelas->dosen->id,
                    'nidn' => $kelas->dosen->nidn,
                    'nama' => $kelas->dosen->nama,
                ],
                'prodi' => [
                    'id' => $kelas->prodi->id,
                    'nama_prodi' => $kelas->prodi->nama_prodi,
                ],
            ];

            return (new KelasByIdResource(
                true,
                'Berhasil mendaftar ke kelas: ' . $request->kode_kelas,
                $data,
            ))->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Mahasiswa')]
    public function kelasTersedia(Request $request)
    {
        try {
            $mahasiswa = $request->user();
            $activeId = Setting::activeTahunAkademikId();

            if (!$activeId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tahun Akademik aktif belum diatur.',
                ], 422);
            }

            $kelasTerdaftar = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                ->pluck('kelas_id')
                ->toArray();

            $kelas = Kelas::where('tahun_akademik_id', $activeId)
                ->with(['matakuliah', 'dosen', 'prodi', 'tahunAkademik', 'jadwal.ruangan', 'jadwal.jam'])
                ->latest()
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Daftar kelas tahun akademik aktif',
                'data' => $kelas->map(fn($item) => $this->formatKelasTersedia($item, $kelasTerdaftar))->values(),
                'meta' => [
                    'total' => $kelas->count(),
                    'tahun_akademik_aktif_id' => $activeId,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Group('Akses Mahasiswa')]
    public function daftarKelasById(Request $request, $kelasId)
    {
        try {
            $mahasiswa = $request->user();
            $activeId = Setting::activeTahunAkademikId();

            if (!$activeId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tahun Akademik aktif belum diatur.',
                ], 422);
            }

            $kelas = Kelas::where('id', $kelasId)
                ->where('tahun_akademik_id', $activeId)
                ->with(['matakuliah', 'dosen', 'prodi', 'tahunAkademik', 'jadwal.ruangan', 'jadwal.jam'])
                ->first();

            if (!$kelas) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kelas tidak ditemukan pada Tahun Akademik aktif.',
                ], 404);
            }

            $existingEnrollment = KelasMatakuliahMahasiswa::where('kelas_id', $kelas->id)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();

            if ($existingEnrollment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda sudah terdaftar di kelas ini.',
                ], 400);
            }

            KelasMatakuliahMahasiswa::create([
                'kelas_id' => $kelas->id,
                'mahasiswa_id' => $mahasiswa->id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendaftar ke kelas.',
                'data' => $this->formatKelasTersedia($kelas, [$kelas->id]),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Group('Akses Mahasiswa')]
    /**
     * Menampilkan daftar kelas yang diambil/didaftarkan.
     *
     * Mahasiswa dapat melihat daftar kelas yang telah diambil/didaftarkan. berdasarkan NPM mahasiswa yang sedang login.
     *
     * @return Response.
     */
    public function kelasByMahasiswa(Request $request)
    {
        try {
            $mahasiswa = $request->user();
            $activeId = Setting::activeTahunAkademikId();

            // Ambil jadwal berdasarkan kelas yang diambil mahasiswa
            $jadwalData = Jadwal::whereHas('kelas.mahasiswa', function ($query) use ($mahasiswa) {
                $query->where('mahasiswa_id', $mahasiswa->id);
            })->when($activeId, function ($q) use ($activeId) {
                $q->whereHas('kelas', fn($q2) => $q2->where('tahun_akademik_id', $activeId));
            })->with([
                'kelas.dosen',
                'kelas.matakuliah',
                'kelas.prodi',
                'kelas.tahunAkademik',
                'ruangan',
                'jam',
                'sesiKuliah.absensi'
            ])->get();

            return (new JadwalKelasResource(
                true,
                'Daftar jadwal kelas/matakuliah yang diambil',
                $jadwalData,
            ))->response()
                ->setStatusCode(200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Dosen')]
    /**
     * Absensi By Jadwal ID
     *
     * Dosen dapat melihat daftar absensi mahasiswa berdasarkan kelas / jadwal yang dipilih.
     *
     * @return Response.
     */
    public function absensiByKelas($jadwalId)
    {
        $jadwal = Jadwal::where('id', $jadwalId)->with('kelas', 'kelas.matakuliah', 'sesiKuliah')->first();

        if (!$jadwal) {
            return response()->json([
                'status' => false,
                'message' => 'Jadwal dengan ID: ' . $jadwalId . ' tidak ditemukan.'
            ], 404);
        }

        Log::info("Mengambil data absensi untuk Jadwal ID: $jadwalId " . $jadwal);

        return (new AbsensiKelasResource(
            true,
            'Data absensi berdasarkan kelas ID: ' . $jadwalId,
            $jadwal,
        ))->response()
            ->setStatusCode(200);
    }

    #[Group('Akses Mahasiswa')]
    /**
     * Jadwal Kelas By Mahasiswa NPM.
     *
     * Mahasiswa dapat melihat daftar jadwal kelas yang diambilnya berdasarkan urutan hari dan jam.
     *
     * @return Response.
     */
    public function jadwalKelasByMahasiswaNpm(Request $request)
    {
        try {
            $mahasiswa = $request->user();
            $activeId = Setting::activeTahunAkademikId();

            // Ambil jadwal berdasarkan kelas yang diambil mahasiswa
            $jadwalData = Jadwal::whereHas('kelas.mahasiswa', function ($query) use ($mahasiswa) {
                $query->where('mahasiswa_id', $mahasiswa->id);
            })->when($activeId, function ($q) use ($activeId) {
                $q->whereHas('kelas', fn($q2) => $q2->where('tahun_akademik_id', $activeId));
            })->with([
                'kelas.dosen',
                'kelas.matakuliah',
                'kelas.prodi',
                'kelas.tahunAkademik',
                'ruangan',
                'jam',
                'sesiKuliah.absensi'
            ])->orderBy('hari', 'asc')->orderBy('jam_id', 'asc')->get();

            return (new JadwalKelasResource(
                true,
                'Daftar jadwal kelas/matakuliah yang diambil',
                $jadwalData,
            ))->response()
                ->setStatusCode(200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatKelasTersedia(Kelas $kelas, array $kelasTerdaftar): array
    {
        $matakuliah = $kelas->matakuliah->first();

        return [
            'id' => $kelas->id,
            'nama_kelas' => $matakuliah
                ? $matakuliah->nama_matkul . ' - ' . $kelas->nama_kelas
                : $kelas->nama_kelas,
            'sudah_terdaftar' => in_array($kelas->id, $kelasTerdaftar),
            'prodi' => [
                'id' => $kelas->prodi->id,
                'nama_prodi' => $kelas->prodi->nama_prodi,
            ],
            'dosen' => [
                'id' => $kelas->dosen->id,
                'nidn' => $kelas->dosen->nidn,
                'nama' => $kelas->dosen->nama,
            ],
            'matakuliah' => $kelas->matakuliah->map(function ($mk) {
                return [
                    'id' => $mk->id,
                    'kode_matkul' => $mk->kode_matkul,
                    'nama_matkul' => $mk->nama_matkul,
                    'sks' => $mk->sks,
                    'semester' => $mk->semester,
                ];
            })->values(),
            'tahun_akademik' => $kelas->tahunAkademik ? [
                'id' => $kelas->tahunAkademik->id,
                'nama_tahun' => $kelas->tahunAkademik->nama_tahun,
            ] : null,
            'jadwal' => $kelas->jadwal->map(function ($jadwal) {
                return [
                    'id' => $jadwal->id,
                    'hari' => $jadwal->hari,
                    'tipe_pertemuan' => $jadwal->tipe_pertemuan,
                    'ruangan' => $jadwal->ruangan ? [
                        'id' => $jadwal->ruangan->id,
                        'nama_ruangan' => 'Ruang ' . $jadwal->ruangan->nama_ruang,
                    ] : null,
                    'jam' => $jadwal->jam ? [
                        'id' => $jadwal->jam->id,
                        'kode_jam' => $jadwal->jam->kode_jam,
                        'jam_mulai' => $jadwal->jam->jam_mulai,
                        'jam_selesai' => $jadwal->jam->jam_selesai,
                    ] : null,
                ];
            })->values(),
        ];
    }
}
