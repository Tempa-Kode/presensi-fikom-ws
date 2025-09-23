<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruangan extends Model
{
    protected $table = 'ruangan';

    protected $fillable = ['nama_ruang', 'latitude', 'longitude'];

    public function jadwal() : HasMany
    {
        return $this->hasMany(Jadwal::class);
    }
}
