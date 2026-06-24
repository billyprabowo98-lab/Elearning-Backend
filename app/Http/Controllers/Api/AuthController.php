<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Login dengan username/email dan password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $field = $request->loginField();
        $user  = User::where($field, $request->login)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Username/email atau password salah.'],
            ]);
        }

        // Hapus token lama agar tidak menumpuk (opsional, bisa dihapus untuk multi-device)
        $user->tokens()->delete();

        $token = $user->createToken(
            'auth_token',
            ['*'],                              // abilities: semua akses
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => new UserResource($user),
            ],
        ], 200);
    }

    /**
     * POST /api/logout
     * Hapus token yang sedang digunakan.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }

    /**
     * GET /api/profile
     * Ambil data user yang sedang login.
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($request->user()),
        ], 200);
    }

    /**
     * POST /api/register
     * Register a new student user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        // Auto-generate username from email
        $usernameBase = explode('@', $request->email)[0];
        $usernameBase = preg_replace('/[^A-Za-z0-9-_]/', '', $usernameBase);
        if (empty($usernameBase)) {
            $usernameBase = 'user';
        }

        $username = $usernameBase;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $usernameBase . $counter;
            $counter++;
        }

        $user = User::create([
            'nama'     => $request->name,
            'username' => $username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'siswa',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'user'       => [
                    'id'       => $user->id,
                    'nama'     => $user->nama,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'role'     => $user->role,
                ],
            ],
        ], 201);
    }
    /**
     * PUT /api/profile
     * Update data user yang sedang login.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'nama'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'nama.required'     => 'Nama wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan.',
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah digunakan.',
            'password.min'      => 'Kata sandi minimal 6 karakter.',
        ]);

        $data = [
            'nama'     => $request->nama,
            'username' => $request->username,
            'email'    => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => new UserResource($user),
        ], 200);
    }
}

