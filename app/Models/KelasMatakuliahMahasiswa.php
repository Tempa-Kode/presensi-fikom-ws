<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelasMatakuliahMahasiswa extends Model
{
    protected $table = 'kelas_matakuliah_mahasiswa';

    protected $fillable = [
        'kelas_id',
        'mahasiswa_id',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(User::class, 'mahasiswa_id');
    }
}
