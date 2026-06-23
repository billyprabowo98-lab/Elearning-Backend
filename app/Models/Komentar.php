<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Komentar extends Model
{
    protected $table = 'komentar';

    protected $fillable = [
        'konten',
        'forum_topik_id',
        'user_id',
        'parent_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function topik(): BelongsTo
    {
        return $this->belongsTo(ForumTopik::class, 'forum_topik_id');
    }

    /** Komentar induk (jika ini adalah balasan) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Komentar::class, 'parent_id');
    }

    /** Balasan komentar ini (1 level nested) */
    public function balasan(): HasMany
    {
        return $this->hasMany(Komentar::class, 'parent_id')
                    ->with('user:id,nama,role')
                    ->orderBy('created_at');
    }

    /** Apakah ini komentar utama (bukan balasan) */
    public function isUtama(): bool
    {
        return is_null($this->parent_id);
    }

    /** Notifikasi terkait komentar ini */
    public function notifikasi(): MorphMany
    {
        return $this->morphMany(Notifikasi::class, 'notifiable');
    }
}
