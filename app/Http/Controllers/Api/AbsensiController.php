<?php

namespace App\Http\Controllers\Api;

use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Absensi;
use App\Models\SesiKuliah;
use App\Models\AbsensiOtpAttempt;
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
use Barryvdh\DomPDF\Facade\Pdf;

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
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
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
            $validasi['otp_code'] = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            // cek jika latitude dan longitude tidak disediakan, ambil dari data ruangan kelas
            if (empty($validasi['latitude']) || empty($validasi['longitude'])) {
                $jadwal = Jadwal::where('id', $validasi['jadwal_id'])
                    ->with('ruangan')
                    ->first();
                if ($jadwal && $jadwal->ruangan) {
                    $validasi['latitude'] = $jadwal->ruangan->latitude;
                    $validasi['longitude'] = $jadwal->ruangan->longitude;
                }
            }

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
     * Semua mahasiswa yang belum melakukan absensi akan otomatis ditandai sebagai alfa.
     *
     * @return Response.
     */
    public function tutupSesiAbsensi(Request $request, $sesiId)
    {
        $sesi = SesiKuliah::where('id', $sesiId)
            ->where('status_absensi', 'buka')
            ->with('jadwal.kelas')
            ->first();

        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi absensi tidak ditemukan atau sudah ditutup.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Dapatkan semua mahasiswa yang terdaftar di kelas
            $kelasId = $sesi->jadwal->kelas_id;
            $mahasiswaTerdaftar = KelasMatakuliahMahasiswa::where('kelas_id', $kelasId)
                ->pluck('mahasiswa_id')
                ->toArray();

            // Dapatkan mahasiswa yang sudah melakukan absensi
            $mahasiswaSudahAbsen = Absensi::where('sesi_kuliah_id', $sesiId)
                ->pluck('mahasiswa_id')
                ->toArray();

            // Mahasiswa yang belum absen
            $mahasiswaBelumAbsen = array_diff($mahasiswaTerdaftar, $mahasiswaSudahAbsen);

            // Tandai mahasiswa yang belum absen sebagai alfa
            $waktuSekarang = Carbon::now();
            $dataAlfa = [];
            foreach ($mahasiswaBelumAbsen as $mahasiswaId) {
                $dataAlfa[] = [
                    'sesi_kuliah_id' => $sesiId,
                    'mahasiswa_id' => $mahasiswaId,
                    'waktu_absensi' => $waktuSekarang,
                    'status' => 'alfa',
                    'created_at' => $waktuSekarang,
                    'updated_at' => $waktuSekarang,
                ];
            }

            if (!empty($dataAlfa)) {
                Absensi::insert($dataAlfa);
            }

            // Tutup sesi absensi
            $sesi->status_absensi = 'tutup';
            $sesi->waktu_tutup = $waktuSekarang;
            $sesi->save();

            DB::commit();

            $message = 'Sesi absensi berhasil ditutup.';
            if (count($mahasiswaBelumAbsen) > 0) {
                $message .= ' ' . count($mahasiswaBelumAbsen) . ' mahasiswa ditandai alfa.';
            }

            return (new SesiKuliahByIdResource(
                true,
                $message,
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
                    ->with([
                        'kelas.matakuliah',
                        'kelas.dosen',
                        'kelas.jadwal' => function ($query) {
                            $query->whereHas('sesiKuliah', function ($q) {
                                $q->where('status_absensi', 'buka');
                            });
                        },
                        'kelas.jadwal.sesiKuliah' => function ($query) {
                            $query->where('status_absensi', 'buka');
                        }
                    ])
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
            'otp_code' => 'required|digits:4',
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

        // Cek/siapkan record attempt tracking untuk mahasiswa ini pada sesi ini
        $attempt = AbsensiOtpAttempt::firstOrCreate(
            ['sesi_kuliah_id' => $sesi->id, 'mahasiswa_id' => $mahasiswa->id],
            ['failed_count' => 0]
        );

        // Cek apakah sedang lockout
        if ($attempt->locked_until && Carbon::now()->lt($attempt->locked_until)) {
            $sisaDetik = Carbon::now()->diffInSeconds($attempt->locked_until);
            return response()->json([
                'status' => false,
                'message' => 'Terlalu banyak percobaan salah. Coba lagi dalam ' . ceil($sisaDetik / 60) . ' menit.'
            ], 429);
        }

        // Validasi OTP
        if ($validasi['otp_code'] !== $sesi->otp_code) {
            $attempt->failed_count += 1;
            if ($attempt->failed_count >= 3) {
                $attempt->locked_until = Carbon::now()->addMinutes(5);
                $attempt->failed_count = 0;
            }
            $attempt->save();

            return response()->json([
                'status' => false,
                'message' => 'Kode OTP salah.'
            ], 422);
        }

        // cek latitude dan longitude mahasiswa apakah sesuai dengan ruangan kelas dan memiliki jarak maksimal 5 meter
        $jarak = $this->hitungJarak(
            $validasi['latitude'],
            $validasi['longitude'],
            $sesi->latitude,
            $sesi->longitude
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

            $attempt->delete();

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
    public function daftarAbsensiBySesi($sesiId)
    {
        // Ambil sesi kuliah terlebih dahulu
        $sesi = SesiKuliah::where('id', $sesiId)
            ->with(['jadwal.kelas'])
            ->first();

        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi kuliah tidak ditemukan.'
            ], 404);
        }

        // Ambil kelas dari sesi
        $kelasId = $sesi->jadwal->kelas_id;

        $kelas = Kelas::query()
            ->where('id', $kelasId)
            ->with([
                'matakuliah',
                'dosen',
                'jadwal' => fn($q) =>
                    $q->where('id', $sesi->jadwal_id)
                    ->with(['sesiKuliah' => fn($qq) => $qq->where('id', $sesiId), 'ruangan', 'jam']),
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
     * Melihat pengajuan izin/sakit mahasiswa pada sesi tertentu
     *
     * Dosen dapat melihat pengajuan izin/sakit mahasiswa pada sesi kuliah tertentu.
     *
     * @return Response.
     */
    public function pengajuanIzinSakitBySesi($sesiId)
    {
        $sesi = SesiKuliah::where('id', $sesiId)
            ->with(['jadwal.kelas.matakuliah', 'jadwal.kelas.dosen', 'jadwal.ruangan', 'jadwal.jam'])
            ->first();

        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi kuliah tidak ditemukan.'
            ], 404);
        }

        $pengajuan = PengajuanIzinSakit::where('sesi_kuliah_id', $sesiId)
            ->with(['mahasiswa', 'sesiKuliah'])
            ->latest()
            ->get();

        if ($pengajuan->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak ada pengajuan izin/sakit pada sesi ini.'
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
            $absensi = $pengajuan->absensi;
            $absensi->update([
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

    #[Group('Akses Dosen')]
    /**
     * Export Absensi Sesi ke PDF
     *
     * Dosen dapat mengunduh laporan absensi untuk sesi pertemuan tertentu dalam format PDF.
     *
     * @return Response.
     */
    public function exportAbsensiSesi($sesiId)
    {
        // 1. Fetch SesiKuliah with all required relationships
        $sesi = SesiKuliah::with([
            'jadwal.kelas.dosen',
            'jadwal.kelas.prodi.kaprodi',
            'jadwal.kelas.matakuliah',
            'jadwal.kelas.tahunAkademik',
            'jadwal.kelas.mahasiswa' => function ($q) {
                $q->orderBy('npm', 'asc');
            },
            'jadwal.ruangan',
            'jadwal.jam',
            'absensi',
            'pengajuanIzinSakit'
        ])->where('id', $sesiId)->first();

        if (!$sesi) {
            return response()->json([
                'status' => false,
                'message' => 'Sesi kuliah tidak ditemukan.'
            ], 404);
        }

        $jadwal = $sesi->jadwal;
        $kelas = $jadwal->kelas;

        // 2. Determine "Pertemuan Ke-" by ordering all sessions of this schedule
        $allSesi = SesiKuliah::where('jadwal_id', $sesi->jadwal_id)
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();

        $pertemuanKe = array_search($sesi->id, $allSesi) !== false ? array_search($sesi->id, $allSesi) + 1 : 1;

        // 3. Map absensi & pengajuan status for easy retrieval in view
        $absensiMap = $sesi->absensi->keyBy('mahasiswa_id');
        $pengajuanMap = $sesi->pengajuanIzinSakit->keyBy('mahasiswa_id');

        // 4. Calculate attendance statistics
        $stats = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alfa' => 0,
            'belum_absen' => 0,
            'total' => $kelas->mahasiswa->count(),
        ];

        foreach ($kelas->mahasiswa as $mahasiswa) {
            $absensi = $absensiMap->get($mahasiswa->id);
            if ($absensi) {
                $status = $absensi->status;
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            } else {
                $stats['belum_absen']++;
            }
        }

        // 5. Generate PDF using Barryvdh\DomPDF\Facade\Pdf
        $pdf = Pdf::loadView('kelas.absensi-sesi-pdf', compact('sesi', 'jadwal', 'kelas', 'pertemuanKe', 'absensiMap', 'pengajuanMap', 'stats'));
        $pdf->setPaper('A4', 'portrait');

        // 6. Name and return/stream the PDF file
        $namaKelas = preg_replace('/[^A-Za-z0-9_\-]/', '_', $kelas->nama_kelas);
        $fileName = 'Laporan_Presensi_Pertemuan_' . $pertemuanKe . '_' . $namaKelas . '_' . date('YmdHis') . '.pdf';

        return $pdf->stream($fileName);
    }
}
