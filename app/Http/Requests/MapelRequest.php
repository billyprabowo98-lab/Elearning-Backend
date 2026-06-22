<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $mapelId  = $this->route('id');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nama_mapel'     => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
            ],
            'kode_mapel'     => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:20',
                "unique:mata_pelajaran,kode_mapel,{$mapelId}",
            ],
            'deskripsi'      => ['nullable', 'string'],
            'jam_per_minggu' => ['nullable', 'integer', 'min:1', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_mapel.required' => 'Nama mata pelajaran wajib diisi.',
            'kode_mapel.required' => 'Kode mata pelajaran wajib diisi.',
            'kode_mapel.unique'   => 'Kode mata pelajaran sudah digunakan.',
            'kode_mapel.max'      => 'Kode mata pelajaran maksimal 20 karakter.',
            'jam_per_minggu.min'  => 'Jam per minggu minimal 1.',
            'jam_per_minggu.max'  => 'Jam per minggu maksimal 40.',
        ];
    }
}
