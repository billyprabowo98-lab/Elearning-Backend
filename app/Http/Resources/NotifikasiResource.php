<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotifikasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'judul'      => $this->judul,
            'pesan'      => $this->pesan,
            'tipe'       => $this->tipe,
            'sudah_dibaca' => $this->sudahDibaca(),
            'dibaca_at'  => $this->dibaca_at?->toISOString(),
            'tautan'     => $this->resolveLink(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /** Generate link ke sumber notifikasi */
    private function resolveLink(): ?string
    {
        return match ($this->tipe) {
            'komentar_baru', 'balasan_komentar' => "/api/forum/{$this->notifiable_id}",
            'topik_baru'                         => "/api/forum/{$this->notifiable_id}",
            default                              => null,
        };
    }
}
