<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMateriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul'      => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi'  => ['nullable', 'string'],
            'mapel_id'   => ['sometimes', 'required', 'integer', 'exists:mata_pelajaran,id'],

            // File baru opsional saat update (hanya jika ingin ganti file)
            'file'       => [
                'nullable',
                'file',
                'max:20480',
                'mimes:pdf,jpg,jpeg,png,webp',
            ],

            'link_video' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'    => 'Judul materi wajib diisi.',
            'mapel_id.exists'   => 'Mata pelajaran tidak ditemukan.',
            'file.max'          => 'Ukuran file maksimal 20 MB.',
            'file.mimes'        => 'Tipe file tidak valid. Diizinkan: PDF, JPG, PNG, WebP.',
            'link_video.url'    => 'Format link video tidak valid.',
        ];
    }
}
