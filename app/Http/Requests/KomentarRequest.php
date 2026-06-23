<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KomentarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'konten'    => [$isUpdate ? 'sometimes' : 'required', 'string', 'min:2'],
            // parent_id opsional: jika diisi → ini balasan dari komentar tersebut
            'parent_id' => ['nullable', 'integer', 'exists:komentar,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'konten.required'  => 'Isi komentar wajib diisi.',
            'konten.min'       => 'Komentar minimal 2 karakter.',
            'parent_id.exists' => 'Komentar yang ingin dibalas tidak ditemukan.',
        ];
    }
}
