<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nama_mapel'     => $this->nama_mapel,
            'kode_mapel'     => $this->kode_mapel,
            'deskripsi'      => $this->deskripsi,
            'jam_per_minggu' => $this->jam_per_minggu,
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
