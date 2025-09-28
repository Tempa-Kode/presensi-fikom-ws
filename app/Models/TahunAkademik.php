<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAkademik extends Model
{
    protected $table = 'tahun_akademik';
    protected $fillable = ['nama_tahun'];

    public function kelas() : HasMany
    {
        return $this->hasMany(Kelas::class, 'tahun_akademik_id');
    }
}
