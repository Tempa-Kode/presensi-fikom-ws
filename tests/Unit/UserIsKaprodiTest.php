<?php

namespace Tests\Unit;

use App\Models\Prodi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsKaprodiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_yang_menjadi_kaprodi_prodi_mengembalikan_true(): void
    {
        $dosen = User::factory()->create();
        Prodi::factory()->create(['kaprodi_id' => $dosen->id]);

        $this->assertTrue($dosen->isKaprodi());
    }

    public function test_dosen_biasa_mengembalikan_false(): void
    {
        $dosen = User::factory()->create();

        $this->assertFalse($dosen->isKaprodi());
    }

    public function test_prodi_diketuai_mengembalikan_prodi_yang_benar(): void
    {
        $dosen = User::factory()->create();
        $prodi = Prodi::factory()->create(['kaprodi_id' => $dosen->id]);

        $this->assertTrue($dosen->prodiDiketuai->is($prodi));
    }
}
