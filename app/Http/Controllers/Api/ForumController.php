<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForumRequest;
use App\Http\Requests\KomentarRequest;
use App\Http\Resources\ForumResource;
use App\Http\Resources\KomentarResource;
use App\Models\ForumTopik;
use App\Models\Komentar;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    /**
     * GET /api/forum
     * Daftar semua topik forum.
     *
     * Query params:
     *   ?search=python      → search judul
     *   ?mapel_id=1         → filter mapel
     *   ?status=aktif       → filter status
     *   ?per_page=10
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 10), 100);

        $topik = ForumTopik::with(['user:id,nama,role', 'mapel:id,nama_mapel'])
            ->withCount('komentar')
            ->when($request->filled('search'), fn($q) =>
                $q->where('judul', 'like', '%' . $request->search . '%')
            )
            ->when($request->filled('mapel_id'), fn($q) =>
                $q->where('mapel_id', $request->mapel_id)
            )
            ->when($request->filled('status'), fn($q) =>
                $q->where('status', $request->status)
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => ForumResource::collection($topik),
            'meta'    => [
                'current_page' => $topik->currentPage(),
                'per_page'     => $topik->perPage(),
                'total'        => $topik->total(),
                'last_page'    => $topik->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/forum
     * Buat topik baru. Kirim notifikasi ke semua guru jika pembuat adalah siswa.
     */
    public function store(ForumRequest $request): JsonResponse
    {
        $user  = $request->user();
        $topik = ForumTopik::create([
            'judul'    => $request->judul,
            'konten'   => $request->konten,
            'mapel_id' => $request->mapel_id,
            'user_id'  => $user->id,
            'status'   => 'aktif',
        ]);

        // Notifikasi: jika siswa buat topik → beritahu semua guru mapel terkait
        if ($user->role === 'siswa' && $request->filled('mapel_id')) {
            $guruIds = User::where('role', 'guru')->pluck('id');
            foreach ($guruIds as $guruId) {
                Notifikasi::kirim(
                    userId: $guruId,
                    judul: 'Topik Baru di Forum',
                    pesan: "{$user->nama} membuat topik baru: \"{$topik->judul}\"",
                    tipe: 'topik_baru',
                    notifiable: $topik,
                );
            }
        }

        $topik->load(['user:id,nama,role', 'mapel:id,nama_mapel']);

        return response()->json([
            'success' => true,
            'message' => 'Topik berhasil dibuat.',
            'data'    => new ForumResource($topik),
        ], 201);
    }

    /**
     * GET /api/forum/{id}
     * Detail topik beserta komentar bertingkat.
     */
    public function show(int $id): JsonResponse
    {
        $topik = ForumTopik::with([
            'user:id,nama,role',
            'mapel:id,nama_mapel',
            'komentarUtama.user:id,nama,role',
            'komentarUtama.balasan.user:id,nama,role',
        ])->find($id);

        if (! $topik) {
            return response()->json([
                'success' => false,
                'message' => 'Topik tidak ditemukan.',
            ], 404);
        }

        // Tambah jumlah view setiap kali dibuka
        $topik->incrementView();

        return response()->json([
            'success' => true,
            'data'    => new ForumResource($topik),
        ]);
    }

    /**
     * PUT /api/forum/{id}
     * Update topik. Hanya pemilik atau admin.
     */
    public function update(ForumRequest $request, int $id): JsonResponse
    {
        $topik = ForumTopik::find($id);

        if (! $topik) {
            return response()->json([
                'success' => false,
                'message' => 'Topik tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        if ($topik->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit topik ini.',
            ], 403);
        }

        $topik->update($request->validated());
        $topik->load(['user:id,nama,role', 'mapel:id,nama_mapel']);

        return response()->json([
            'success' => true,
            'message' => 'Topik berhasil diperbarui.',
            'data'    => new ForumResource($topik),
        ]);
    }

    /**
     * DELETE /api/forum/{id}
     * Hapus topik. Hanya pemilik atau admin.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $topik = ForumTopik::find($id);

        if (! $topik) {
            return response()->json([
                'success' => false,
                'message' => 'Topik tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        if ($topik->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus topik ini.',
            ], 403);
        }

        $topik->delete(); // komentar & notifikasi terhapus via cascadeOnDelete

        return response()->json([
            'success' => true,
            'message' => 'Topik berhasil dihapus.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Komentar
    // ──────────────────────────────────────────────────────────────────────

    /**
     * POST /api/forum/{id}/komentar
     * Tambah komentar atau balasan pada topik.
     * Body: { "konten": "...", "parent_id": null } → komentar utama
     * Body: { "konten": "...", "parent_id": 5 }    → balasan komentar #5
     */
    public function simpanKomentar(KomentarRequest $request, int $id): JsonResponse
    {
        $topik = ForumTopik::find($id);

        if (! $topik) {
            return response()->json([
                'success' => false,
                'message' => 'Topik tidak ditemukan.',
            ], 404);
        }

        if ($topik->status === 'ditutup') {
            return response()->json([
                'success' => false,
                'message' => 'Topik sudah ditutup. Komentar tidak bisa ditambahkan.',
            ], 422);
        }

        // Validasi: parent_id harus milik topik yang sama
        if ($request->filled('parent_id')) {
            $parentKomentar = Komentar::where('id', $request->parent_id)
                ->where('forum_topik_id', $id)
                ->first();

            if (! $parentKomentar) {
                return response()->json([
                    'success' => false,
                    'message' => 'Komentar induk tidak ditemukan di topik ini.',
                ], 422);
            }
        }

        $user     = $request->user();
        $komentar = Komentar::create([
            'konten'         => $request->konten,
            'forum_topik_id' => $id,
            'user_id'        => $user->id,
            'parent_id'      => $request->parent_id,
        ]);

        // ── Notifikasi ──────────────────────────────────────────────────
        if ($request->filled('parent_id')) {
            // Balasan → notifikasi ke penulis komentar induk
            $parentKomentar = Komentar::find($request->parent_id);
            if ($parentKomentar && $parentKomentar->user_id !== $user->id) {
                Notifikasi::kirim(
                    userId: $parentKomentar->user_id,
                    judul: 'Balasan Komentar Anda',
                    pesan: "{$user->nama} membalas komentar Anda di topik \"{$topik->judul}\"",
                    tipe: 'balasan_komentar',
                    notifiable: $komentar,
                );
            }
        } else {
            // Komentar baru → notifikasi ke pemilik topik
            if ($topik->user_id !== $user->id) {
                Notifikasi::kirim(
                    userId: $topik->user_id,
                    judul: 'Komentar Baru di Topik Anda',
                    pesan: "{$user->nama} mengomentari topik \"{$topik->judul}\"",
                    tipe: 'komentar_baru',
                    notifiable: $komentar,
                );
            }
        }

        $komentar->load(['user:id,nama,role', 'balasan.user:id,nama,role']);

        return response()->json([
            'success' => true,
            'message' => $request->filled('parent_id') ? 'Balasan berhasil dikirim.' : 'Komentar berhasil ditambahkan.',
            'data'    => new KomentarResource($komentar),
        ], 201);
    }

    /**
     * PUT /api/komentar/{id}
     * Edit komentar. Hanya pemilik komentar.
     */
    public function updateKomentar(KomentarRequest $request, int $id): JsonResponse
    {
        $komentar = Komentar::find($id);

        if (! $komentar) {
            return response()->json([
                'success' => false,
                'message' => 'Komentar tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        if ($komentar->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit komentar ini.',
            ], 403);
        }

        $komentar->update(['konten' => $request->konten]);
        $komentar->load(['user:id,nama,role', 'balasan.user:id,nama,role']);

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil diperbarui.',
            'data'    => new KomentarResource($komentar),
        ]);
    }

    /**
     * DELETE /api/komentar/{id}
     * Hapus komentar. Hanya pemilik atau admin.
     */
    public function hapusKomentar(Request $request, int $id): JsonResponse
    {
        $komentar = Komentar::find($id);

        if (! $komentar) {
            return response()->json([
                'success' => false,
                'message' => 'Komentar tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        if ($komentar->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus komentar ini.',
            ], 403);
        }

        $komentar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Komentar berhasil dihapus.',
        ]);
    }
}
