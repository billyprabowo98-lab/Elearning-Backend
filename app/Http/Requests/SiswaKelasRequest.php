<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiswaKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siswa_ids'   => ['required', 'array', 'min:1'],
            'siswa_ids.*' => ['required', 'integer', 'exists:users,id'],
            'tahun_ajaran' => [
                'required',
                'integer',
                'digits:4',
                'min:2000',
                'max:2099',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'siswa_ids.required'    => 'Daftar siswa wajib diisi.',
            'siswa_ids.array'       => 'Format siswa_ids harus berupa array.',
            'siswa_ids.min'         => 'Minimal 1 siswa harus dipilih.',
            'siswa_ids.*.exists'    => 'Salah satu siswa yang dipilih tidak ditemukan.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'tahun_ajaran.digits'   => 'Tahun ajaran harus 4 digit.',
        ];
    }
}
