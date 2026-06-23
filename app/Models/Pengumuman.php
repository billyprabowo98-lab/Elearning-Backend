<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = [
        'judul',
        'isi',
        'user_id',
        'kelas_id',
    ];

    /** Pembuat pengumuman */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Kelas tujuan (null = semua kelas) */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /** Apakah pengumuman ini untuk semua kelas */
    public function isUntukSemua(): bool
    {
        return is_null($this->kelas_id);
    }
}
