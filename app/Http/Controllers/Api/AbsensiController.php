<?php

namespace App\Http\Controllers\Api;

use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Absensi;
use App\Models\SesiKuliah;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PengajuanIzinSakit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use App\Models\KelasMatakuliahMahasiswa;
use App\Http\Resources\SesiKuliahResource;
use App\Http\Resources\AbsensiBySesiResource;
use App\Http\Resources\RiwayatAbsensiResource;
use App\Http\Resources\SesiKuliahByIdResource;
use App\Http\Resources\AbsensiPertemuanResource;
use App\Http\Resources\ValidasiPengajuanResource;
use App\Http\Resources\PengajuanIzinSakitResource;

class AbsensiController extends Controller
{
    #[Group('Akses Dosen')]
    /**
     * Buat Sesi Absensi
     *
     * Dosen dapat membuat/membuka sesi absensi untuk kelas yang diampunya.
     *
     * @return Response.
     */
    public function buatSesiAbsensi(Request $request)
    {
        $validasi = $request->validate([
            'jadwal_id' => 'required|exists:jadwal,id',
        ]);

        $date = Carbon::now()->toDateString();

        $sesiExist = SesiKuliah::where('jadwal_id', $validasi['jadwal_id'])
            ->where('tanggal', $date)
            ->where('status_absensi', 'buka')
            ->first();
        if ($sesiExist) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi absensi untuk jadwal ini sudah dibuka'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $validasi['tanggal'] = $date;
            $validasi['status_absensi'] = 'buka';
            $validasi['waktu_buka'] = Carbon::now();

            $data = SesiKuliah::create($validasi);
            DB::commit();

            return (new SesiKuliahByIdResource(
                true,
                'Sesi absensi berhasil dibuat.',
                $data
            ))->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membuat sesi absensi.',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    #[Group('Akses Dosen')]
    /**
     * Menutup Sesi Absensi
     *
     * Dosen dapat menutup sesi absensi untuk kelas yang diampunya.
     *
     * @return Response.
     */
    public function tutupSesiAbsensi(Request $request, $sesiId)
    {
        $sesi = SesiKuliah::where('id', $sesiId)
            ->where('status_absensi', 'buka')
            ->first();

        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi absensi tidak ditemukan atau sudah ditutup.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $sesi->status_absensi = 'tutup';
            $sesi->waktu_tutup = Carbon::now();
            $sesi->save();
            DB::commit();
            return (new SesiKuliahByIdResource(
                true,
                'Sesi absensi berhasil ditutup.',
                $sesi
            ))->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menutup sesi absensi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    #[Group('Akses Mahasiswa')]
    /**
     * Melihat Sesi Absensi Aktif
     *
     * Mahasiswa dapat melihat sesi absensi yang sedang aktif.
     *
     * @return Response.
     */
    public function sesiAbsensiAktif(Request $request)
    {
        try {
            $mahasiswa = $request->user();

            $sesi = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                    ->whereHas('kelas.jadwal.sesiKuliah', function ($query) {
                        $query->where('status_absensi', 'buka');
                    })
                    ->with(['kelas.jadwal.sesiKuliah' => function ($query) {
                        $query->where('status_absensi', 'buka');
                    }, 'kelas.jadwal', 'kelas', 'kelas.matakuliah', 'kelas.dosen', 'kelas.jadwal.sesiKuliah'])
                    ->get();

            if ($sesi->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada sesi absensi aktif.'
                ], 404);
            }

            Log::info($sesi);

            return (new SesiKuliahResource(
                true,
                'Sesi absensi aktif ditemukan.',
                $sesi
            ))->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data sesi absensi aktif.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Mahasiswa')]
    /**
     * Melakukan Absensi
     *
     * Mahasiswa dapat melakukan absensi pada sesi yang sedang aktif.
     *
     * @return Response.
     */
    public function absensi(Request $request)
    {
        $validasi = $request->validate([
            'sesi_kuliah_id' => 'required|exists:sesi_kuliah,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $mahasiswa = $request->user();

        $sesi = SesiKuliah::where('id', $validasi['sesi_kuliah_id'])
            ->with('jadwal.ruangan')
            ->where('status_absensi', 'buka')
            ->first();

         if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi absensi sudah ditutup.'
            ], 404);
        }

        // cek latitude dan longitude mahasiswa apakah sesuai dengan ruangan kelas dan memiliki jarak maksimal 5 meter
        $ruangan = $sesi->jadwal->ruangan;
        if (!$ruangan || !$ruangan->latitude || !$ruangan->longitude) {
            return response()->json([
                'status' => false,
                'message' => 'Data lokasi ruangan tidak tersedia.'
            ], 400);
        }

        $jarak = $this->hitungJarak(
            $validasi['latitude'],
            $validasi['longitude'],
            $ruangan->latitude,
            $ruangan->longitude
        );

        if ($jarak > 5) {
            return response()->json([
                'status' => false,
                'message' => 'Anda berada di luar jangkauan ruangan kelas. Jarak Anda: ' . round($jarak, 1) . ' meter dari ruangan.'
            ], 403);
        }

        // Cek apakah mahasiswa terdaftar di kelas untuk sesi ini
        $kelasMatkulMhs = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('kelas.jadwal.sesiKuliah', function ($query) use ($sesi) {
                $query->where('id', $sesi->id);
            })
            ->first();

        if (!$kelasMatkulMhs) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak terdaftar di kelas untuk sesi ini.'
            ], 403);
        }

        // Cek apakah mahasiswa sudah melakukan absensi untuk sesi ini
        $absensi = $sesi->absensi()->where('sesi_kuliah_id', $validasi['sesi_kuliah_id'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();
        if ($absensi) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah melakukan absensi untuk sesi ini.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $simpanAbsensi = Absensi::create([
                'sesi_kuliah_id' => $sesi->id,
                'mahasiswa_id' => $mahasiswa->id,
                'waktu_absensi' => Carbon::now(),
                'status' => 'hadir',
                'latitude' => $validasi['latitude'] ?? null,
                'longitude' => $validasi['longitude'] ?? null,
            ]);

            DB::commit();

            return (new AbsensiBySesiResource(
                true,
                'Absensi berhasil dilakukan.',
                $simpanAbsensi
            ))->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal melakukan absensi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Dosen')]
    /**
     * Daftar Absensi by Sesi
     *
     * Dosen dapat melihat daftar absensi mahasiswa untuk sesi tertentu.
     *
     * @return Response.
     */
    public function daftarAbsensiBySesi($kelasId, $sesiId)
    {
        $kelas = Kelas::query()
            ->where('id', $kelasId)
            ->whereHas('jadwal.sesiKuliah', fn($q) => $q->where('id', $sesiId))
            ->with([
                'matakuliah',
                'jadwal' => fn($q) =>
                    $q->whereHas('sesiKuliah', fn($qq) => $qq->where('id', $sesiId))
                    ->with(['sesiKuliah' => fn($qq) => $qq->where('id', $sesiId)]),
                'mahasiswa' => fn($q) =>
                    $q->with(['absensi' => fn($aq) => $aq->where('sesi_kuliah_id', $sesiId)]),
            ])
            ->firstOrFail();

        return (new AbsensiPertemuanResource(
            true,
            'Daftar absensi untuk sesi ditemukan.',
            $kelas
        ))->response()
            ->setStatusCode(200);
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    #[Group('Akses Mahasiswa')]
    /**
     * Riwayat Absensi by Jadwal
     *
     * Mahasiswa dapat melihat absensi yang telah dilakukan berdasarkan pertemuan/jadwal.
     *
     * @return Response.
     */
    public function riwayatAbsensi(Request $request, $jadwalId)
    {
        $mahasiswa = $request->user();

        // Ambil data jadwal terlebih dahulu untuk memastikan data kelas dan dosen tersedia
        $jadwal = \App\Models\Jadwal::with(['kelas.matakuliah', 'kelas.dosen', 'ruangan'])
            ->find($jadwalId);

        if (!$jadwal) {
            return response()->json([
                'status' => false,
                'message' => 'Jadwal tidak ditemukan.'
            ], 404);
        }

        $absensi = Absensi::where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('sesiKuliah.jadwal', fn($q) => $q->where('id', $jadwalId))
            ->with([
                'sesiKuliah.jadwal.kelas.matakuliah',
                'sesiKuliah.jadwal.kelas.dosen',
                'sesiKuliah.jadwal.ruangan'
            ])
            ->join('sesi_kuliah', 'absensi.sesi_kuliah_id', '=', 'sesi_kuliah.id')
            ->orderBy('sesi_kuliah.tanggal', 'desc')
            ->orderBy('absensi.waktu_absensi', 'desc')
            ->select('absensi.*')
            ->get();

        // Ambil semua pengajuan izin/sakit untuk mahasiswa ini pada jadwal ini
        $pengajuanIds = $absensi->pluck('sesi_kuliah_id');
        $pengajuanMap = PengajuanIzinSakit::where('mahasiswa_id', $mahasiswa->id)
            ->whereIn('sesi_kuliah_id', $pengajuanIds)
            ->get()
            ->keyBy('sesi_kuliah_id');

        // Attach pengajuan ke setiap absensi
        $absensi->each(function ($item) use ($pengajuanMap) {
            $item->pengajuan = $pengajuanMap->get($item->sesi_kuliah_id);
        });

        Log::info("Absensi: ", $absensi->toArray());

        // Buat object gabungan untuk resource
        $data = (object)[
            'absensi' => $absensi,
            'jadwal' => $jadwal
        ];

        return (new RiwayatAbsensiResource(
            true,
            'Riwayat absensi ditemukan.',
            $data
        ))->response()
            ->setStatusCode(200);
    }

    #[Group('Akses Dosen')]
    /**
     * Edit Status Absensi
     *
     * Dosen dapat mengedit status absensi mahasiswa untuk sesi tertentu.
     *
     * @return Response.
     */
    public function editStatusAbsensi(Request $request, $sesiId, $mahasiswaId)
    {
        $validasi = $request->validate([
            'status' => 'required|in:hadir,izin,alfa,sakit',
        ]);

        $sesi = SesiKuliah::where('id', $sesiId)->first();
        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi kuliah tidak ditemukan.'
            ], 404);
        }

        $absensi = Absensi::where('sesi_kuliah_id', $sesiId)
            ->where('mahasiswa_id', $mahasiswaId)
            ->first();

        DB::beginTransaction();
        try {
            $data = null;
            if (!$absensi) {
                $data = Absensi::create([
                    'sesi_kuliah_id' => $sesiId,
                    'mahasiswa_id' => $mahasiswaId,
                    'waktu_absensi' => Carbon::now(),
                    'status' => $validasi['status'],
                ]);
            } else {
                if ($absensi->status === $validasi['status']) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Status absensi sudah sesuai.'
                    ], 400);
                } else {
                    $absensi->status = $validasi['status'];
                    $absensi->save();
                    $data = $absensi;
                }
            }

            DB::commit();

            return (new AbsensiBySesiResource(
                true,
                'Status absensi berhasil diubah.',
                $data
            ))->response()
                ->setStatusCode(200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengubah status absensi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Mahasiswa')]
    /**
     * Mengajukan Izin/Sakit
     *
     * Mahasiswa dapat mengajukan izin atau sakit untuk sesi tertentu.
     *
     * @return Response.
     */
    public function ajukanIzinSakit(Request $request, $kelasId, $sesiId )
    {
        $validasi = $request->validate([
            'status' => 'required|in:izin,sakit',
            'keterangan' => 'required',
            'bukti_file' => 'nullable|file',
        ]);
        $mahasiswa = $request->user();

        $sesi = SesiKuliah::where('id', $sesiId)->first();
        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi kuliah tidak ditemukan.'
            ], 404);
        }

        $absensiExist = Absensi::where('sesi_kuliah_id', $sesiId)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();
        if ($absensiExist) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah melakukan absensi untuk sesi ini.',
                'absensi' => $absensiExist
            ], 400);
        }

        // mengecek apakah sudah ada pengajuan izin/sakit untuk sesi ini
        $pengajuanExist = PengajuanIzinSakit::where('sesi_kuliah_id', $sesiId)
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();
        if ($pengajuanExist) {
            return response()->json([
                'status' => false,
                'message' => 'Anda sudah mengajukan izin/sakit untuk sesi ini.',
                'data' => $pengajuanExist,
            ], 400);
        }

        DB::beginTransaction();
        try {
            $validasi['sesi_kuliah_id'] = $sesiId;
            $validasi['kelas_id'] = $kelasId;
            $validasi['mahasiswa_id'] = $mahasiswa->id;
            $validasi['status_validasi'] = 'pending';

            // handler upload file jika ada, dan simpan ke folder public/uploads/bukti_absensi
            if ($request->hasFile('bukti_file')) {
                $file = $request->file('bukti_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/bukti_file'), $filename);
                $validasi['bukti_file_path'] = 'uploads/bukti_file/' . $filename;
            }

            $data = PengajuanIzinSakit::create($validasi);

            Absensi::create([
                'sesi_kuliah_id' => $sesiId,
                'mahasiswa_id' => $mahasiswa->id,
                'waktu_absensi' => Carbon::now(),
                'status' => $validasi['status'],
            ]);

            DB::commit();

            return (new AbsensiBySesiResource(
                true,
                "Pengajuan {$validasi['status']} berhasil dikirim.",
                $data
            ))->response()
                ->setStatusCode(201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengajukan izin/sakit.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Dosen')]
    /**
     * Melihat pengajuan izin/sakit mahasiswa pada setiap kelas
     *
     * Dosen dapat melihat pengajuan izin/sakit mahasiswa pada setiap kelas yang diampunya.
     *
     * @return Response.
     */
    public function pengajuanIzinSakitByKelas($kelasId)
    {
        $kelas = Kelas::where('id', $kelasId)->first();
        if (!$kelas) {
            return response()->json([
                'status' => false,
                'message' => 'Kelas tidak ditemukan.'
            ], 404);
        }

        $pengajuan = $kelas->pengajuanIzinSakit()->with('mahasiswa', 'sesiKuliah')->latest()->get();
        if ($pengajuan->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada pengajuan izin/sakit pada kelas ini.'
            ], 404);
        }

        try {
            return (new PengajuanIzinSakitResource(
                true,
                'Daftar pengajuan izin/sakit ditemukan.',
                $pengajuan
            ))->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil pengajuan izin/sakit.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Group('Akses Dosen')]
    /**
     * Validasi Pengajuan Izin/Sakit
     *
     * Dosen dapat melakukan validasi terhadap pengajuan izin/sakit mahasiswa pada setiap kelas yang diampunya.
     *
     * @return Response.
     */
    public function validasiPengajuanIzinSakit(Request $request, $pengajuanId)
    {
        $validasi = $request->validate([
            'status_validasi' => 'required|in:terima,tolak',
        ]);

        $pengajuan = PengajuanIzinSakit::find($pengajuanId);
        if (!$pengajuan) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan izin/sakit tidak ditemukan.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $pengajuan->update($validasi);
            $absensi = Absensi::create([
                'sesi_kuliah_id' => $pengajuan->sesi_kuliah_id,
                'mahasiswa_id' => $pengajuan->mahasiswa_id,
                'waktu_absensi' => Carbon::now(),
                'status' => $validasi['status_validasi'] === 'terima' ? $pengajuan->status : 'alfa',
            ]);

            DB::commit();
            return (new ValidasiPengajuanResource(
                true,
                'Pengajuan izin/sakit berhasil divalidasi.',
                (object)[
                    'mahasiswa' => $pengajuan->mahasiswa,
                    'pengajuan' => $pengajuan,
                    'absensi' => $absensi
                ]
            ))->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal memvalidasi pengajuan izin/sakit.',
                'error' => $e->getMessage()
            ], 500);
        }

    }
}
