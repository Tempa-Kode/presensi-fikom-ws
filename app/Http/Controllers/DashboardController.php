<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Kelas;
use App\Models\Jadwal;
use App\Models\Absensi;
use App\Models\SesiKuliah;
use App\Models\Matakuliah;
use App\Models\TahunAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistik Umum
        $totalMahasiswa = User::where('role', 'mahasiswa')->count();
        $totalDosen = User::where('role', 'dosen')->count();
        $totalKelas = Kelas::count();
        $totalMatakuliah = Matakuliah::count();

        // Statistik Absensi Hari Ini
        $today = Carbon::today();
        $sesiHariIni = SesiKuliah::whereDate('tanggal', $today)->get();
        $absensiHariIni = Absensi::whereIn('sesi_kuliah_id', $sesiHariIni->pluck('id'))->count();

        // Statistik Absensi per Status (Minggu ini)
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $sesiMingguIni = SesiKuliah::whereBetween('tanggal', [$weekStart, $weekEnd])->get();

        $absensiStats = Absensi::whereIn('sesi_kuliah_id', $sesiMingguIni->pluck('id'))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Kelas Terbaru
        $kelasTerbaru = Kelas::with('dosen', 'prodi', 'matakuliah', 'tahunAkademik')
            ->latest()
            ->take(5)
            ->get();

        // Jadwal Hari Ini
        $hariIni = strtolower(Carbon::now()->locale('id')->dayName);
        $jadwalHariIni = Jadwal::with(['kelas.matakuliah', 'kelas.dosen', 'ruangan', 'jam'])
            ->where('hari', $hariIni)
            ->orderBy('jam_id')
            ->take(10)
            ->get();

        // Statistik Kehadiran per Bulan (6 bulan terakhir)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $sesiMonth = SesiKuliah::whereYear('tanggal', $month->year)
                ->whereMonth('tanggal', $month->month)
                ->get();

            $hadir = Absensi::whereIn('sesi_kuliah_id', $sesiMonth->pluck('id'))
                ->where('status', 'hadir')
                ->count();

            $monthlyStats[] = [
                'bulan' => $month->locale('id')->format('M Y'),
                'total' => $hadir
            ];
        }

        // Mata Kuliah dengan Jumlah Kelas Terbanyak
        $matakuliahPopuler = Matakuliah::withCount('kelas')
            ->orderBy('kelas_count', 'desc')
            ->take(5)
            ->get();

        // Dosen dengan Kelas Terbanyak
        $dosenAktif = User::where('role', 'dosen')
            ->withCount('kelas')
            ->orderBy('kelas_count', 'desc')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalMahasiswa',
            'totalDosen',
            'totalKelas',
            'totalMatakuliah',
            'absensiHariIni',
            'absensiStats',
            'kelasTerbaru',
            'jadwalHariIni',
            'monthlyStats',
            'matakuliahPopuler',
            'dosenAktif'
        ));
    }
}
