<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PengumumanRequest;
use App\Http\Resources\PengumumanResource;
use App\Models\Pengumuman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengumumanController extends Controller
{
    /**
     * GET /api/pengumuman
     *
     * - Admin/Guru : lihat semua pengumuman + filter opsional
     * - Siswa      : hanya pengumuman untuk kelasnya ATAU untuk semua kelas
     *
     * Query params:
     *   ?search=upacara     → search judul
     *   ?kelas_id=1         → filter kelas (admin/guru)
     *   ?per_page=10
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 10), 100);
        $user    = $request->user();

        $query = Pengumuman::with(['user:id,nama,role', 'kelas:id,nama_kelas,tingkat'])
            ->when($request->filled('search'), fn($q) =>
                $q->where('judul', 'like', '%' . $request->search . '%')
            );

        if ($user->role === 'siswa') {
            // Ambil kelas_id siswa dari tahun ajaran terbaru
            $kelasId = DB::table('siswa_kelas')
                ->where('siswa_id', $user->id)
                ->orderBy('tahun_ajaran', 'desc')
                ->value('kelas_id');

            // Siswa hanya melihat: pengumuman untuk kelasnya ATAU untuk semua kelas (kelas_id null)
            $query->where(function ($q) use ($kelasId) {
                $q->whereNull('kelas_id');
                if ($kelasId) {
                    $q->orWhere('kelas_id', $kelasId);
                }
            });
        } else {
            // Admin/Guru: bisa filter per kelas
            $query->when($request->filled('kelas_id'), fn($q) =>
                $q->where('kelas_id', $request->kelas_id)
            );
        }

        $pengumuman = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => PengumumanResource::collection($pengumuman),
            'meta'    => [
                'current_page' => $pengumuman->currentPage(),
                'per_page'     => $pengumuman->perPage(),
                'total'        => $pengumuman->total(),
                'last_page'    => $pengumuman->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/pengumuman
     * Buat pengumuman baru. Hanya admin dan guru.
     * kelas_id null = untuk semua kelas.
     */
    public function store(PengumumanRequest $request): JsonResponse
    {
        $pengumuman = Pengumuman::create([
            'judul'    => $request->judul,
            'isi'      => $request->isi,
            'user_id'  => $request->user()->id,
            'kelas_id' => $request->kelas_id, // null jika tidak dikirim
        ]);

        $pengumuman->load(['user:id,nama,role', 'kelas:id,nama_kelas,tingkat']);

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dibuat.',
            'data'    => new PengumumanResource($pengumuman),
        ], 201);
    }

    /**
     * PUT /api/pengumuman/{id}
     * Update pengumuman. Hanya pemilik atau admin.
     */
    public function update(PengumumanRequest $request, int $id): JsonResponse
    {
        $pengumuman = Pengumuman::find($id);

        if (! $pengumuman) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        if ($pengumuman->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit pengumuman ini.',
            ], 403);
        }

        $data = $request->validated();

        // Izinkan reset kelas_id ke null secara eksplisit
        if ($request->exists('kelas_id')) {
            $data['kelas_id'] = $request->kelas_id;
        }

        $pengumuman->update($data);
        $pengumuman->load(['user:id,nama,role', 'kelas:id,nama_kelas,tingkat']);

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil diperbarui.',
            'data'    => new PengumumanResource($pengumuman),
        ]);
    }

    /**
     * DELETE /api/pengumuman/{id}
     * Hapus pengumuman. Hanya pemilik atau admin.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $pengumuman = Pengumuman::find($id);

        if (! $pengumuman) {
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        if ($pengumuman->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus pengumuman ini.',
            ], 403);
        }

        $pengumuman->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil dihapus.',
        ]);
    }
}
