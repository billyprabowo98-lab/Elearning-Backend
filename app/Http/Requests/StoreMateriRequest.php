<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMateriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul'       => ['required', 'string', 'max:255'],
            'deskripsi'   => ['nullable', 'string'],
            'tipe'        => ['required', 'in:pdf,gambar,video'],
            'mapel_id'    => ['required', 'integer', 'exists:mata_pelajaran,id'],

            // File wajib jika tipe pdf atau gambar
            'file'        => [
                'required_if:tipe,pdf,gambar',
                'nullable',
                'file',
                'max:20480',            // maksimal 20 MB
                'mimes:pdf,jpg,jpeg,png,webp',
            ],

            // Link wajib jika tipe video
            'link_video'  => [
                'required_if:tipe,video',
                'nullable',
                'url',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'          => 'Judul materi wajib diisi.',
            'tipe.required'           => 'Tipe materi wajib diisi.',
            'tipe.in'                 => 'Tipe tidak valid. Pilih: pdf, gambar, atau video.',
            'mapel_id.required'       => 'Mata pelajaran wajib dipilih.',
            'mapel_id.exists'         => 'Mata pelajaran tidak ditemukan.',
            'file.required_if'        => 'File wajib diupload untuk tipe pdf atau gambar.',
            'file.file'               => 'Upload harus berupa file.',
            'file.max'                => 'Ukuran file maksimal 20 MB.',
            'file.mimes'              => 'Tipe file tidak valid. Diizinkan: PDF, JPG, PNG, WebP.',
            'link_video.required_if'  => 'Link video wajib diisi untuk tipe video.',
            'link_video.url'          => 'Format link video tidak valid.',
        ];
    }
}
