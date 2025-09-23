<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    protected $table = 'prodi';
    protected $fillable = ['kode_prodi', 'nama_prodi'];

    public function users() : HasMany
    {
        return $this->hasMany(User::class);
    }

    public function kelas() : HasMany
    {
        return $this->hasMany(Kelas::class);
    }
}
