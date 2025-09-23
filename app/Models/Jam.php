<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jam extends Model
{
    protected $table = 'jam';
    protected $fillable = ['kode_jam', 'jam_mulai', 'jam_selesai'];

    public function jadwal() : HasMany
    {
        return $this->hasMany(Jadwal::class);
    }
}
