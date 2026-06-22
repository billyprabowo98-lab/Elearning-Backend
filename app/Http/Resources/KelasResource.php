<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'nama_kelas'   => $this->nama_kelas,
            'tingkat'      => $this->tingkat,
            'jurusan'      => $this->jurusan,
            'tahun_ajaran' => $this->tahun_ajaran,
            'wali_kelas'   => $this->whenLoaded('guru', fn() => [
                'id'   => $this->guru->id,
                'nama' => $this->guru->nama,
            ]),
            'jumlah_siswa' => $this->whenLoaded('siswa', fn() => $this->siswa->count()),
            'siswa'        => $this->whenLoaded('siswa', fn() =>
                $this->siswa->map(fn($s) => [
                    'id'           => $s->id,
                    'nama'         => $s->nama,
                    'username'     => $s->username,
                    'tahun_ajaran' => $s->pivot->tahun_ajaran,
                ])
            ),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
