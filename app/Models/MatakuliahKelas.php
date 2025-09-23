<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MatakuliahKelas extends Model
{
    protected $table = 'matakuliah_kelas';

    protected $fillable = [
        'matkul_id',
        'kelas_id',
    ];
}
