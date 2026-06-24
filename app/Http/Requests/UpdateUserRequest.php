<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Ambil ID dari route parameter untuk ignore unique sendiri
        $userId = $this->route('id') ?? $this->route('admin');

        return [
            'nama'     => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash', "unique:users,username,{$userId}"],
            'email'    => ['sometimes', 'required', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password' => ['sometimes', 'nullable', 'string', Password::min(6)->letters()->numbers()],
            'role'     => ['sometimes', 'required', 'in:admin,guru,siswa'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required'       => 'Nama wajib diisi.',
            'username.required'   => 'Username wajib diisi.',
            'username.unique'     => 'Username sudah digunakan.',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan underscore.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email sudah digunakan.',
            'role.in'             => 'Role tidak valid. Pilih: admin, guru, atau siswa.',
        ];
    }
}
