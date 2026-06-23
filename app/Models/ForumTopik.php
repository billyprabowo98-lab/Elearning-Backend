<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ForumTopik extends Model
{
    protected $table = 'forum_topik';

    protected $fillable = [
        'judul',
        'konten',
        'user_id',
        'mapel_id',
        'status',
        'jumlah_dilihat',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_dilihat' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    /** Semua komentar di topik ini */
    public function komentar(): HasMany
    {
        return $this->hasMany(Komentar::class, 'forum_topik_id');
    }

    /** Hanya komentar level atas (bukan balasan) */
    public function komentarUtama(): HasMany
    {
        return $this->hasMany(Komentar::class, 'forum_topik_id')
                    ->whereNull('parent_id')
                    ->orderBy('created_at');
    }

    /** Notifikasi terkait topik ini */
    public function notifikasi(): MorphMany
    {
        return $this->morphMany(Notifikasi::class, 'notifiable');
    }

    /** Tambah view count */
    public function incrementView(): void
    {
        $this->increment('jumlah_dilihat');
    }
}
