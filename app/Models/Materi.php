<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Materi extends Model
{
    protected $table = 'materi';

    protected $fillable = [
        'judul',
        'deskripsi',
        'file_path',
        'file_original_name',
        'file_size',
        'tipe',
        'link_video',
        'mapel_id',
        'guru_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /** Relasi ke mata pelajaran */
    public function mapel(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class, 'mapel_id');
    }

    /** Relasi ke guru yang membuat */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * URL publik untuk mengakses file.
     * Untuk video, kembalikan link_video langsung.
     */
    public function getFileUrlAttribute(): ?string
    {
        if ($this->tipe === 'video') {
            return $this->link_video;
        }

        return $this->file_path
            ? Storage::disk('public')->url($this->file_path)
            : null;
    }

    /**
     * Ukuran file dalam format human-readable (KB / MB).
     */
    public function getFileSizeReadableAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        if ($this->file_size >= 1_048_576) {
            return round($this->file_size / 1_048_576, 2) . ' MB';
        }

        return round($this->file_size / 1_024, 2) . ' KB';
    }
}
