<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'judul'    => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'konten'   => [$isUpdate ? 'sometimes' : 'required', 'string', 'min:10'],
            'mapel_id' => ['nullable', 'integer', 'exists:mata_pelajaran,id'],
            'status'   => ['sometimes', 'in:aktif,ditutup'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'   => 'Judul topik wajib diisi.',
            'judul.max'        => 'Judul maksimal 255 karakter.',
            'konten.required'  => 'Konten topik wajib diisi.',
            'konten.min'       => 'Konten minimal 10 karakter.',
            'mapel_id.exists'  => 'Mata pelajaran tidak ditemukan.',
            'status.in'        => 'Status tidak valid. Pilih: aktif atau ditutup.',
        ];
    }
}
