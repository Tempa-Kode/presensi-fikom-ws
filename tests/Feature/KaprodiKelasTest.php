<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Matakuliah;
use App\Models\MatakuliahKelas;
use App\Models\Prodi;
use App\Models\SesiKuliah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaprodiKelasTest extends TestCase
{
    use RefreshDatabase;

    private function buatKelasDenganMatakuliah(Prodi $prodi, int $semester): Kelas
    {
        $dosenPengampu = User::factory()->create();
        $kelas = Kelas::factory()->create([
            'prodi_id' => $prodi->id,
            'dosen_id' => $dosenPengampu->id,
        ]);
        $matakuliah = Matakuliah::factory()->create(['semester' => $semester]);
        MatakuliahKelas::create(['kelas_id' => $kelas->id, 'matkul_id' => $matakuliah->id]);

        return $kelas;
    }

    public function test_dosen_non_kaprodi_mendapat_403(): void
    {
        $dosen = User::factory()->create();

        $response = $this->actingAs($dosen)->getJson('/api/kaprodi/kelas');

        $response->assertStatus(403);
    }

    public function test_kaprodi_hanya_melihat_kelas_di_prodinya(): void
    {
        $kaprodi = User::factory()->create();
        $prodiSendiri = Prodi::factory()->create(['kaprodi_id' => $kaprodi->id]);
        $prodiLain = Prodi::factory()->create();

        $kelasSendiri = $this->buatKelasDenganMatakuliah($prodiSendiri, 3);
        $this->buatKelasDenganMatakuliah($prodiLain, 3);

        $response = $this->actingAs($kaprodi)->getJson('/api/kaprodi/kelas');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $kelasSendiri->id);
    }

    public function test_filter_semester_hanya_mengembalikan_kelas_yang_cocok(): void
    {
        $kaprodi = User::factory()->create();
        $prodi = Prodi::factory()->create(['kaprodi_id' => $kaprodi->id]);

        $kelasSemester3 = $this->buatKelasDenganMatakuliah($prodi, 3);
        $this->buatKelasDenganMatakuliah($prodi, 5);

        $response = $this->actingAs($kaprodi)->getJson('/api/kaprodi/kelas?semester=3');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $kelasSemester3->id);
    }

    public function test_rekap_absensi_menghitung_dari_seluruh_sesi(): void
    {
        $kaprodi = User::factory()->create();
        $prodi = Prodi::factory()->create(['kaprodi_id' => $kaprodi->id]);
        $kelas = $this->buatKelasDenganMatakuliah($prodi, 3);

        $jadwal = Jadwal::factory()->create(['kelas_id' => $kelas->id]);
        $sesi1 = SesiKuliah::factory()->create(['jadwal_id' => $jadwal->id, 'status_absensi' => 'tutup']);
        $sesi2 = SesiKuliah::factory()->create(['jadwal_id' => $jadwal->id, 'status_absensi' => 'tutup']);

        $mahasiswa1 = User::factory()->mahasiswa()->create();
        $mahasiswa2 = User::factory()->mahasiswa()->create();

        Absensi::create(['sesi_kuliah_id' => $sesi1->id, 'mahasiswa_id' => $mahasiswa1->id, 'status' => 'hadir', 'waktu_absensi' => now()]);
        Absensi::create(['sesi_kuliah_id' => $sesi1->id, 'mahasiswa_id' => $mahasiswa2->id, 'status' => 'alfa', 'waktu_absensi' => now()]);
        Absensi::create(['sesi_kuliah_id' => $sesi2->id, 'mahasiswa_id' => $mahasiswa1->id, 'status' => 'hadir', 'waktu_absensi' => now()]);
        Absensi::create(['sesi_kuliah_id' => $sesi2->id, 'mahasiswa_id' => $mahasiswa2->id, 'status' => 'izin', 'waktu_absensi' => now()]);

        $response = $this->actingAs($kaprodi)->getJson('/api/kaprodi/kelas');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.rekap_absensi.hadir', 2);
        $response->assertJsonPath('data.0.rekap_absensi.izin', 1);
        $response->assertJsonPath('data.0.rekap_absensi.alfa', 1);
        $response->assertJsonPath('data.0.rekap_absensi.total_sesi', 2);
    }
}
