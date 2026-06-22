<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MateriResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'judul'              => $this->judul,
            'deskripsi'          => $this->deskripsi,
            'tipe'               => $this->tipe,
            'file_url'           => $this->file_url,           // accessor dari model
            'file_original_name' => $this->file_original_name,
            'file_size'          => $this->file_size,
            'file_size_readable' => $this->file_size_readable, // accessor dari model
            'link_video'         => $this->when($this->tipe === 'video', $this->link_video),
            'mapel'              => $this->whenLoaded('mapel', fn() => [
                'id'         => $this->mapel->id,
                'nama_mapel' => $this->mapel->nama_mapel,
                'kode_mapel' => $this->mapel->kode_mapel,
            ]),
            'guru'               => $this->whenLoaded('guru', fn() => [
                'id'   => $this->guru->id,
                'nama' => $this->guru->nama,
            ]),
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
