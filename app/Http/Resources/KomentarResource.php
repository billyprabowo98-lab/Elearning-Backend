<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KomentarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'konten'     => $this->konten,
            'parent_id'  => $this->parent_id,
            'penulis'    => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'nama' => $this->user->nama,
                'role' => $this->user->role,
            ]),
            'balasan'    => $this->whenLoaded('balasan',
                fn() => KomentarResource::collection($this->balasan)
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
