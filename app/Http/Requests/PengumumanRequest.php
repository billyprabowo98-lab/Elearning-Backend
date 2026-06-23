<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PengumumanRequest extends FormRequest
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
            'isi'      => [$isUpdate ? 'sometimes' : 'required', 'string', 'min:5'],
            // null = untuk semua kelas, isi id = spesifik satu kelas
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'judul.required'  => 'Judul pengumuman wajib diisi.',
            'judul.max'       => 'Judul maksimal 255 karakter.',
            'isi.required'    => 'Isi pengumuman wajib diisi.',
            'isi.min'         => 'Isi pengumuman minimal 5 karakter.',
            'kelas_id.exists' => 'Kelas tidak ditemukan.',
        ];
    }
}
