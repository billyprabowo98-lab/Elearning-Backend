<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Daftar semua pengguna dengan pagination, search nama, filter role.
     *
     * Query params:
     *   ?search=budi        → search by nama (LIKE)
     *   ?role=guru          → filter by role
     *   ?per_page=10        → jumlah per halaman (default 10, max 100)
     *   ?page=1             → halaman
     */
    public function index(Request $request): UserCollection
    {
        $perPage = min((int) $request->query('per_page', 10), 100);

        $users = User::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('role'), function ($q) use ($request) {
                $q->where('role', $request->role);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString(); // pertahankan query string di link pagination

        return new UserCollection($users);
    }

    /**
     * POST /api/users
     * Buat pengguna baru.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'nama'     => $request->nama,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dibuat.',
            'data'    => new UserResource($user),
        ], 201);
    }

    /**
     * GET /api/users/{id}
     * Detail satu pengguna.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * PUT /api/users/{id}
     * Update data pengguna.
     * Field yang tidak dikirim tidak akan diubah (partial update didukung).
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $data = $request->only(['nama', 'username', 'email', 'role']);

        // Hanya update password jika dikirim dan tidak kosong
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil diperbarui.',
            'data'    => new UserResource($user->fresh()),
        ]);
    }

    /**
     * DELETE /api/users/{id}
     * Hapus pengguna.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        // Cegah admin menghapus akun dirinya sendiri
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun sendiri.',
            ], 422);
        }

        // Hapus semua token milik user sebelum dihapus
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }
}
