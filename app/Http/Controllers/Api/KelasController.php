<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Kelas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\KelasByIdResource;
use Dedoc\Scramble\Attributes\Group;
use App\Http\Resources\KelasResource;
use App\Models\KelasMatakuliahMahasiswa;
use Dedoc\Scramble\Attributes\PathParameter;

class KelasController extends Controller
{
    #[Group('Akses Dosen')]
    /**
     * Kelas Berdasarkan Dosen.
     *
     * Mengambil data kelas(matakuliah) berdasarkan NIDN Dosen. dimana Dosen dapat melihat daftar kelas yang diampunya
     *
     * @param string $nidn
     * @return Response.
     */
    #[PathParameter('nidn', 'NIDN Dosen', example: '0114046501')]
    public function kelasByDosen($nidn)
    {
        try{
            $data = Kelas::whereHas('dosen', function ($query) use ($nidn) {
                $query->where('nidn', $nidn);
            })->with([
                'dosen',
                'matakuliah',
                'prodi',
                'jadwal',
                'jadwal.ruangan',
                'jadwal.jam',
                'tahunAkademik'
            ])->get();

            if($data->isEmpty()){
                return response()->json([
                    'status' => false,
                    'message' => 'Data kelas tidak ditemukan untuk NIDN Dosen: ' . $nidn
                ], 404);
            }

            return (new KelasResource(
                true,
                'Data kelas berdasarkan NIDN Dosen',
                $data,
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
    /**
     * Menampilkan daftar kelas yang diambil/didaftarkan.
     *
     * Mahasiswa dapat melihat daftar kelas yang telah diambil/didaftarkan.
     *
     * @return Response.
     */
    public function kelasByMahasiswa(Request $request)
    {
        try {
            $mahasiswa = $request->user();

            $kelas = KelasMatakuliahMahasiswa::where('mahasiswa_id', $mahasiswa->id)
                ->with('kelas.matakuliah', 'kelas.dosen', 'kelas.prodi')
                ->get();

            $data = Kelas::whereHas('mahasiswa', function ($query) use ($mahasiswa) {
                $query->where('mahasiswa_id', $mahasiswa->id);
            })->with([
                'dosen',
                'matakuliah',
                'prodi',
                'jadwal',
                'jadwal.ruangan',
                'jadwal.jam',
                'tahunAkademik'
            ])->get();

            return (new KelasResource(
                true,
                'Daftar kelas/matakuliah yang diambil',
                $data,
            ))->response()
                ->setStatusCode(200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
