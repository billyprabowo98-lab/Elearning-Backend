<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kelasId = $this->route('id');
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nama_kelas'   => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
            ],
            'tingkat'      => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:50',
            ],
            'jurusan'      => ['nullable', 'string', 'max:100'],
            'guru_id'      => ['nullable', 'exists:users,id'],
            'tahun_ajaran' => [
                $isUpdate ? 'sometimes' : 'required',
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
            'nama_kelas.required'   => 'Nama kelas wajib diisi.',
            'tingkat.required'      => 'Tingkat wajib diisi.',
            'guru_id.exists'        => 'Guru yang dipilih tidak ditemukan.',
            'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
            'tahun_ajaran.digits'   => 'Tahun ajaran harus 4 digit.',
            'tahun_ajaran.min'      => 'Tahun ajaran minimal 2000.',
            'tahun_ajaran.max'      => 'Tahun ajaran maksimal 2099.',
        ];
    }
}
