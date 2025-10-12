<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'prodi_id',
        'npm',
        'nidn',
        'email',
        'nama',
        'password',
        'role',
        'stambuk',
        'foto',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function prodi() : BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function kelas() : HasMany
    {
        return $this->hasMany(Kelas::class, 'dosen_id');
    }

    public function absensi() : HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function pengajuanIzinSakit() : HasMany
    {
        return $this->hasMany(PengajuanIzinSakit::class);
    }

    public function kelasMatakuliahMahasiswa() : HasMany
    {
        return $this->hasMany(KelasMatakuliahMahasiswa::class, 'mahasiswa_id');
    }
}
