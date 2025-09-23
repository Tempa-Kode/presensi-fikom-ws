<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Matakuliah extends Model
{
    protected $table = 'matakuliah';

    protected $fillable = [
        'kode_matkul',
        'nama_matkul',
        'sks',
        'semester',
    ];

    public function kelas() : BelongsToMany
    {
        return $this->belongsToMany(Kelas::class, 'matakuliah_kelas', 'matkul_id', 'kelas_id');
    }
}
