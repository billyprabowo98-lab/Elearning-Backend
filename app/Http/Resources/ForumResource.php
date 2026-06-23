<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'judul'          => $this->judul,
            'konten'         => $this->konten,
            'status'         => $this->status,
            'jumlah_dilihat' => $this->jumlah_dilihat,
            'pembuat'        => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'nama' => $this->user->nama,
                'role' => $this->user->role,
            ]),
            'mapel'          => $this->whenLoaded('mapel', fn() => $this->mapel ? [
                'id'         => $this->mapel->id,
                'nama_mapel' => $this->mapel->nama_mapel,
            ] : null),
            'jumlah_komentar' => $this->whenLoaded('komentar',
                fn() => $this->komentar->count(),
                fn() => $this->komentar()->count() // fallback count query
            ),
            'komentar'       => $this->whenLoaded('komentarUtama',
                fn() => KomentarResource::collection($this->komentarUtama)
            ),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
