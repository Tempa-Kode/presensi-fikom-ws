<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiKuliah extends Model
{
    protected $table = 'sesi_kuliah';

    protected $fillable = [
        'jadwal_id',
        'tanggal',
        'status_absensi',
        'waktu_buka',
        'waktu_tutup',
    ];

    public function jadwal() : BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function absensi() : HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function pengajuanIzinSakit() : HasMany
    {
        return $this->hasMany(PengajuanIzinSakit::class);
    }
}
