<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiOtpAttempt extends Model
{
    protected $table = 'absensi_otp_attempts';

    protected $fillable = [
        'sesi_kuliah_id',
        'mahasiswa_id',
        'failed_count',
        'locked_until',
    ];

    protected $casts = [
        'locked_until' => 'datetime',
    ];

    public function sesiKuliah(): BelongsTo
    {
        return $this->belongsTo(SesiKuliah::class);
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }
}
