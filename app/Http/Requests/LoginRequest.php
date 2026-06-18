<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string'],   // bisa email atau username
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required'    => 'Username atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 6 karakter.',
        ];
    }

    /**
     * Deteksi apakah input login berupa email atau username.
     */
    public function loginField(): string
    {
        return filter_var($this->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';
    }
}
