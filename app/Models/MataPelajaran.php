<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';

    protected $fillable = [
        'nama_mapel',
        'kode_mapel',
        'deskripsi',
        'jam_per_minggu',
    ];

    protected function casts(): array
    {
        return [
            'jam_per_minggu' => 'integer',
        ];
    }
}
