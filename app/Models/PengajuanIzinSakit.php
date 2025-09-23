<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanIzinSakit extends Model
{
    protected $table = 'pengajuan_izin_sakit';

    protected $fillable = [
        'sesi_kuliah_id',
        'mahasiswa_id',
        'status',
        'keterangan',
        'bukti_file_path',
        'status_validasi',
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
