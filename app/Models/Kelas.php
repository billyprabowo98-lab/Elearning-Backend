<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'jurusan',
        'guru_id',
        'tahun_ajaran',
    ];

    protected function casts(): array
    {
        return [
            'tahun_ajaran' => 'integer',
        ];
    }

    /** Wali kelas (guru) */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /** Daftar siswa di kelas ini */
    public function siswa(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'siswa_kelas',
            'kelas_id',
            'siswa_id'
        )->withPivot('tahun_ajaran')->withTimestamps();
    }
}
