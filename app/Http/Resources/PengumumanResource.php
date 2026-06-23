<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengumumanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'judul'        => $this->judul,
            'isi'          => $this->isi,
            'untuk_semua'  => $this->isUntukSemua(),
            'pembuat'      => $this->whenLoaded('user', fn() => [
                'id'   => $this->user->id,
                'nama' => $this->user->nama,
                'role' => $this->user->role,
            ]),
            'kelas'        => $this->whenLoaded('kelas', fn() => $this->kelas ? [
                'id'         => $this->kelas->id,
                'nama_kelas' => $this->kelas->nama_kelas,
                'tingkat'    => $this->kelas->tingkat,
            ] : null),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
