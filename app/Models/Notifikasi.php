<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'tipe',
        'notifiable_type',
        'notifiable_id',
        'dibaca_at',
    ];

    protected function casts(): array
    {
        return [
            'dibaca_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Polymorphic: bisa ke ForumTopik atau Komentar */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sudahDibaca(): bool
    {
        return ! is_null($this->dibaca_at);
    }

    /** Static helper: kirim notifikasi ke user */
    public static function kirim(
        int    $userId,
        string $judul,
        string $pesan,
        string $tipe,
        Model  $notifiable,
    ): self {
        return self::create([
            'user_id'          => $userId,
            'judul'            => $judul,
            'pesan'            => $pesan,
            'tipe'             => $tipe,
            'notifiable_type'  => get_class($notifiable),
            'notifiable_id'    => $notifiable->id,
        ]);
    }
}
