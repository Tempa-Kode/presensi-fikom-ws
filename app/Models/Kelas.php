<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = [
        'matkul_id',
        'dosen_id',
        'prodi_id',
        'tahun_akademik',
        'nama_kelas',
    ];

    public function dosen() : BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function prodi() : BelongsTo
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function matakuliah() : BelongsToMany
    {
        return $this->belongsToMany(Matakuliah::class, 'matakuliah_kelas', 'kelas_id', 'matkul_id');
    }

    public function jadwal() : HasMany
    {
        return $this->hasMany(Jadwal::class);
    }

    public function tahunAkademik() : BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id');
    }
}
