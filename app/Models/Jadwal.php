<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected $fillable = [
        'kelas_id',
        'ruangan_id',
        'jam_id',
        'hari',
        'tipe_pertemuan',
    ];

    public function kelas() : BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function ruangan() : BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'ruangan_id');
    }

    public function jam() : BelongsTo
    {
        return $this->belongsTo(Jam::class, 'jam_id');
    }

    public function sesiKuliah() : HasMany
    {
        return $this->hasMany(SesiKuliah::class);
    }
}
