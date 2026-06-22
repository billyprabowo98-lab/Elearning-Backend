<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMateriRequest;
use App\Http\Requests\UpdateMateriRequest;
use App\Http\Resources\MateriResource;
use App\Models\Materi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MateriController extends Controller
{
    /**
     * GET /api/materi
     * Daftar materi dengan pagination, filter, dan search.
     *
     * Query params:
     *   ?search=pythagoras   → search judul
     *   ?tipe=pdf            → filter tipe (pdf|gambar|video)
     *   ?mapel_id=1          → filter mata pelajaran
     *   ?per_page=10
     *
     * Hak akses:
     *   - Guru: melihat semua materi
     *   - Siswa: melihat semua materi (read-only)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 10), 100);
        $user    = $request->user();

        $materi = Materi::with(['mapel', 'guru'])
            ->when($request->filled('search'), fn($q) =>
                $q->where('judul', 'like', '%' . $request->search . '%')
            )
            ->when($request->filled('tipe'), fn($q) =>
                $q->where('tipe', $request->tipe)
            )
            ->when($request->filled('mapel_id'), fn($q) =>
                $q->where('mapel_id', $request->mapel_id)
            )
            // Guru hanya melihat materi miliknya sendiri
            ->when($user->role === 'guru', fn($q) =>
                $q->where('guru_id', $user->id)
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => MateriResource::collection($materi),
            'meta'    => [
                'current_page' => $materi->currentPage(),
                'per_page'     => $materi->perPage(),
                'total'        => $materi->total(),
                'last_page'    => $materi->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/materi
     * Buat materi baru dengan upload file.
     * Hanya guru.
     */
    public function store(StoreMateriRequest $request): JsonResponse
    {
        $data = [
            'judul'     => $request->judul,
            'deskripsi' => $request->deskripsi,
            'tipe'      => $request->tipe,
            'mapel_id'  => $request->mapel_id,
            'guru_id'   => $request->user()->id,
        ];

        if ($request->tipe === 'video') {
            $data['link_video'] = $request->link_video;
        } else {
            // Proses upload file (pdf / gambar)
            $fileData = $this->uploadFile($request);
            $data     = array_merge($data, $fileData);
        }

        $materi = Materi::create($data);
        $materi->load(['mapel', 'guru']);

        return response()->json([
            'success' => true,
            'message' => 'Materi berhasil dibuat.',
            'data'    => new MateriResource($materi),
        ], 201);
    }

    /**
     * GET /api/materi/{id}
     * Detail satu materi.
     * Guru dan siswa bisa akses.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user   = $request->user();
        $materi = Materi::with(['mapel', 'guru'])->find($id);

        if (! $materi) {
            return response()->json([
                'success' => false,
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        // Guru hanya bisa lihat materi miliknya
        if ($user->role === 'guru' && $materi->guru_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke materi ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => new MateriResource($materi),
        ]);
    }

    /**
     * PUT /api/materi/{id}
     * Update materi. File lama dihapus jika ada file baru.
     * Hanya guru pemilik materi.
     */
    public function update(UpdateMateriRequest $request, int $id): JsonResponse
    {
        $materi = Materi::find($id);

        if (! $materi) {
            return response()->json([
                'success' => false,
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        // Guru hanya bisa edit materi miliknya
        if ($request->user()->role === 'guru' && $materi->guru_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit materi ini.',
            ], 403);
        }

        $data = $request->only(['judul', 'deskripsi', 'mapel_id']);

        // Jika ada file baru → hapus file lama, simpan file baru
        if ($request->hasFile('file')) {
            $this->deleteFile($materi);
            $data = array_merge($data, $this->uploadFile($request));
        }

        // Update link video jika dikirim
        if ($request->filled('link_video')) {
            $data['link_video'] = $request->link_video;
        }

        $materi->update($data);
        $materi->load(['mapel', 'guru']);

        return response()->json([
            'success' => true,
            'message' => 'Materi berhasil diperbarui.',
            'data'    => new MateriResource($materi->fresh(['mapel', 'guru'])),
        ]);
    }

    /**
     * DELETE /api/materi/{id}
     * Hapus materi beserta file-nya dari storage.
     * Hanya guru pemilik materi.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $materi = Materi::find($id);

        if (! $materi) {
            return response()->json([
                'success' => false,
                'message' => 'Materi tidak ditemukan.',
            ], 404);
        }

        if ($request->user()->role === 'guru' && $materi->guru_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus materi ini.',
            ], 403);
        }

        // Hapus file dari storage sebelum delete record
        $this->deleteFile($materi);
        $materi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Materi berhasil dihapus.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Upload file ke storage/app/public/materi/{tipe}/
     * dan kembalikan array data file.
     */
    private function uploadFile(StoreMateriRequest|UpdateMateriRequest $request): array
    {
        $file    = $request->file('file');
        $tipe    = $request->tipe ?? 'file'; // fallback saat update
        $folder  = "materi/{$tipe}";

        // Generate nama file unik agar tidak tabrakan
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs($folder, $filename, 'public');

        return [
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_size'          => $file->getSize(),
        ];
    }

    /**
     * Hapus file dari storage jika ada.
     */
    private function deleteFile(Materi $materi): void
    {
        if ($materi->file_path && Storage::disk('public')->exists($materi->file_path)) {
            Storage::disk('public')->delete($materi->file_path);
        }
    }
}
