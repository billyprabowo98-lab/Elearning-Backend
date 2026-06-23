<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotifikasiResource;
use App\Models\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /**
     * GET /api/notifikasi
     * Daftar notifikasi milik user yang login.
     *
     * Query params:
     *   ?belum_dibaca=1  → hanya yang belum dibaca
     *   ?per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $notifikasi = Notifikasi::where('user_id', $request->user()->id)
            ->when($request->boolean('belum_dibaca'), fn($q) =>
                $q->whereNull('dibaca_at')
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success'          => true,
            'jumlah_belum_dibaca' => Notifikasi::where('user_id', $request->user()->id)
                                        ->whereNull('dibaca_at')
                                        ->count(),
            'data'             => NotifikasiResource::collection($notifikasi),
            'meta'             => [
                'current_page' => $notifikasi->currentPage(),
                'per_page'     => $notifikasi->perPage(),
                'total'        => $notifikasi->total(),
                'last_page'    => $notifikasi->lastPage(),
            ],
        ]);
    }

    /**
     * PUT /api/notifikasi/{id}/baca
     * Tandai satu notifikasi sebagai sudah dibaca.
     */
    public function tandaiBaca(Request $request, int $id): JsonResponse
    {
        $notifikasi = Notifikasi::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        if (! $notifikasi->sudahDibaca()) {
            $notifikasi->update(['dibaca_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'data'    => new NotifikasiResource($notifikasi->fresh()),
        ]);
    }

    /**
     * PUT /api/notifikasi/baca-semua
     * Tandai semua notifikasi milik user sebagai sudah dibaca.
     */
    public function bacaSemua(Request $request): JsonResponse
    {
        $jumlah = Notifikasi::where('user_id', $request->user()->id)
            ->whereNull('dibaca_at')
            ->update(['dibaca_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "{$jumlah} notifikasi ditandai sudah dibaca.",
        ]);
    }

    /**
     * DELETE /api/notifikasi/{id}
     * Hapus satu notifikasi milik user.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notifikasi = Notifikasi::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        $notifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dihapus.',
        ]);
    }
}
