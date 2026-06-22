<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MapelRequest;
use App\Http\Resources\MapelResource;
use App\Models\MataPelajaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    /**
     * GET /api/mapel
     * Daftar mata pelajaran dengan pagination dan search.
     *
     * Query params:
     *   ?search=matematika  → search nama_mapel atau kode_mapel
     *   ?per_page=10
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 10), 100);

        $mapel = MataPelajaran::query()
            ->when($request->filled('search'), fn($q) =>
                $q->where(function ($sub) use ($request) {
                    $sub->where('nama_mapel', 'like', '%' . $request->search . '%')
                        ->orWhere('kode_mapel', 'like', '%' . $request->search . '%');
                })
            )
            ->orderBy('nama_mapel')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => MapelResource::collection($mapel),
            'meta'    => [
                'current_page' => $mapel->currentPage(),
                'per_page'     => $mapel->perPage(),
                'total'        => $mapel->total(),
                'last_page'    => $mapel->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/mapel
     */
    public function store(MapelRequest $request): JsonResponse
    {
        $mapel = MataPelajaran::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil dibuat.',
            'data'    => new MapelResource($mapel),
        ], 201);
    }

    /**
     * PUT /api/mapel/{id}
     */
    public function update(MapelRequest $request, int $id): JsonResponse
    {
        $mapel = MataPelajaran::find($id);

        if (! $mapel) {
            return response()->json([
                'success' => false,
                'message' => 'Mata pelajaran tidak ditemukan.',
            ], 404);
        }

        $mapel->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil diperbarui.',
            'data'    => new MapelResource($mapel->fresh()),
        ]);
    }

    /**
     * DELETE /api/mapel/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $mapel = MataPelajaran::find($id);

        if (! $mapel) {
            return response()->json([
                'success' => false,
                'message' => 'Mata pelajaran tidak ditemukan.',
            ], 404);
        }

        $mapel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mata pelajaran berhasil dihapus.',
        ]);
    }
}
