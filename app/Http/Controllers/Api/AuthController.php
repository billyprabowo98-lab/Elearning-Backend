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
}
