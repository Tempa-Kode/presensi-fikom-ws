<?php

namespace Tests\Feature;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginKaprodiFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_dosen_kaprodi_menyertakan_is_kaprodi_true(): void
    {
        $dosen = User::factory()->create(['password' => Hash::make('rahasia123')]);
        $prodi = Prodi::factory()->create(['kaprodi_id' => $dosen->id, 'nama_prodi' => 'Teknik Informatika']);

        $response = $this->postJson('/api/login', [
            'credential' => $dosen->nidn,
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_kaprodi', true);
        $response->assertJsonPath('data.prodi_diketuai.nama_prodi', 'Teknik Informatika');
    }

    public function test_login_dosen_biasa_menyertakan_is_kaprodi_false(): void
    {
        $dosen = User::factory()->create(['password' => Hash::make('rahasia123')]);

        $response = $this->postJson('/api/login', [
            'credential' => $dosen->nidn,
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_kaprodi', false);
        $response->assertJsonPath('data.prodi_diketuai', null);
    }
}
