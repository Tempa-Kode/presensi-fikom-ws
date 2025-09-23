<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'sesi_kuliah_id',
        'mahasiswa_id',
        'waktu_absensi',
        'status',
        'latitude',
        'longitude',
    ];

    public function sesiKuliah() : BelongsTo
    {
        return $this->belongsTo(SesiKuliah::class);
    }

    public function mahasiswa() : BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }
}
