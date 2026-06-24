<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KelasRequest;
use App\Http\Requests\SiswaKelasRequest;
use App\Http\Resources\KelasResource;
use App\Models\Kelas;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    /**
     * GET /api/kelas
     * Daftar kelas dengan pagination, search, filter tahun ajaran.
     *
     * Query params:
     *   ?search=X IPA      → search nama_kelas
     *   ?tahun_ajaran=2024 → filter tahun
     *   ?tingkat=X         → filter tingkat
     *   ?per_page=10
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 10), 100);
        $user = $request->user();

        $query = Kelas::with(['guru', 'siswa']);

        if ($user->role === 'siswa') {
            $kelasIds = \DB::table('siswa_kelas')
                ->where('siswa_id', $user->id)
                ->pluck('kelas_id');
            $query->whereIn('id', $kelasIds);
        } elseif ($user->role === 'guru') {
            $query->where('guru_id', $user->id);
        }

        $kelas = $query->when($request->filled('search'), fn($q) =>
                $q->where('nama_kelas', 'like', '%' . $request->search . '%')
            )
            ->when($request->filled('tahun_ajaran'), fn($q) =>
                $q->where('tahun_ajaran', $request->tahun_ajaran)
            )
            ->when($request->filled('tingkat'), fn($q) =>
                $q->where('tingkat', $request->tingkat)
            )
            ->orderBy('tahun_ajaran', 'desc')
            ->orderBy('nama_kelas')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data'    => KelasResource::collection($kelas),
            'meta'    => [
                'current_page' => $kelas->currentPage(),
                'per_page'     => $kelas->perPage(),
                'total'        => $kelas->total(),
                'last_page'    => $kelas->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/kelas
     */
    public function store(KelasRequest $request): JsonResponse
    {
        // Cek apakah guru yang dipilih memang ber-role guru
        if ($request->filled('guru_id')) {
            $guru = User::find($request->guru_id);
            if (! $guru || $guru->role !== 'guru') {
                return response()->json([
                    'success' => false,
                    'message' => 'Wali kelas harus memiliki role guru.',
                ], 422);
            }
        }

        // Auto-create mapel if not exists
        if ($request->filled('jurusan')) {
            $this->ensureMapelExists($request->jurusan);
        }

        $kelas = Kelas::create($request->validated());
        $kelas->load(['guru', 'siswa']);

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dibuat.',
            'data'    => new KelasResource($kelas),
        ], 201);
    }

    /**
     * PUT /api/kelas/{id}
     */
    public function update(KelasRequest $request, int $id): JsonResponse
    {
        $kelas = Kelas::find($id);

        if (! $kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ], 404);
        }

        if ($request->filled('guru_id')) {
            $guru = User::find($request->guru_id);
            if (! $guru || $guru->role !== 'guru') {
                return response()->json([
                    'success' => false,
                    'message' => 'Wali kelas harus memiliki role guru.',
                ], 422);
            }
        }

        // Auto-create mapel if not exists
        if ($request->filled('jurusan')) {
            $this->ensureMapelExists($request->jurusan);
        }

        $kelas->update($request->validated());
        $kelas->load(['guru', 'siswa']);

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil diperbarui.',
            'data'    => new KelasResource($kelas),
        ]);
    }

    /**
     * DELETE /api/kelas/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $kelas = Kelas::find($id);

        if (! $kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ], 404);
        }

        $kelas->delete(); // siswa_kelas terhapus otomatis (cascadeOnDelete)

        return response()->json([
            'success' => true,
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/kelas/{id}/siswa
     * Tambah satu atau beberapa siswa ke kelas.
     * Body: { "siswa_ids": [1, 2, 3], "tahun_ajaran": 2024 }
     */
    public function tambahSiswa(SiswaKelasRequest $request, int $id): JsonResponse
    {
        $kelas = Kelas::find($id);

        if (! $kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ], 404);
        }

        $siswaIds    = $request->siswa_ids;
        $tahunAjaran = $request->tahun_ajaran;

        // Pastikan semua ID adalah siswa (role = siswa)
        $bukanSiswa = User::whereIn('id', $siswaIds)
            ->where('role', '!=', 'siswa')
            ->pluck('nama')
            ->toArray();

        if (! empty($bukanSiswa)) {
            return response()->json([
                'success' => false,
                'message' => 'Beberapa pengguna bukan siswa: ' . implode(', ', $bukanSiswa),
            ], 422);
        }

        // Cek siswa yang sudah terdaftar di kelas lain pada tahun ajaran yang sama
        $sudahTerdaftar = \DB::table('siswa_kelas')
            ->whereIn('siswa_id', $siswaIds)
            ->where('tahun_ajaran', $tahunAjaran)
            ->pluck('siswa_id')
            ->toArray();

        if (! empty($sudahTerdaftar)) {
            $namaSiswa = User::whereIn('id', $sudahTerdaftar)->pluck('nama')->toArray();
            return response()->json([
                'success' => false,
                'message' => 'Beberapa siswa sudah terdaftar di kelas lain pada tahun ajaran ini: '
                           . implode(', ', $namaSiswa),
            ], 422);
        }

        // Attach siswa ke kelas (dengan pivot tahun_ajaran)
        $pivotData = collect($siswaIds)->mapWithKeys(fn($siswaId) => [
            $siswaId => ['tahun_ajaran' => $tahunAjaran],
        ])->toArray();

        $kelas->siswa()->attach($pivotData);
        $kelas->load(['guru', 'siswa']);

        return response()->json([
            'success' => true,
            'message' => count($siswaIds) . ' siswa berhasil ditambahkan ke kelas.',
            'data'    => new KelasResource($kelas),
        ], 201);
    }

    /**
     * DELETE /api/kelas/{id}/siswa/{id_siswa}
     * Hapus satu siswa dari kelas.
     */
    public function hapusSiswa(int $id, int $idSiswa): JsonResponse
    {
        $kelas = Kelas::find($id);

        if (! $kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan.',
            ], 404);
        }

        $terdaftar = $kelas->siswa()->where('siswa_id', $idSiswa)->exists();

        if (! $terdaftar) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa tidak terdaftar di kelas ini.',
            ], 404);
        }

        $kelas->siswa()->detach($idSiswa);

        return response()->json([
            'success' => true,
            'message' => 'Siswa berhasil dikeluarkan dari kelas.',
        ]);
    }

    /**
     * POST /api/kelas/join
     * Siswa bergabung ke kelas menggunakan ID kelas (Class Code).
     */
    public function joinKelas(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'siswa') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya siswa yang dapat bergabung ke kelas.'
            ], 403);
        }

        $request->validate([
            'kelas_id' => 'required|integer',
        ]);

        $kelasId = $request->kelas_id;
        $kelas = Kelas::find($kelasId);

        if (!$kelas) {
            return response()->json([
                'success' => false,
                'message' => 'Kelas tidak ditemukan. Periksa kembali kode kelas.'
            ], 404);
        }

        // Cek apakah sudah bergabung
        $alreadyJoined = \DB::table('siswa_kelas')
            ->where('kelas_id', $kelasId)
            ->where('siswa_id', $user->id)
            ->exists();

        if ($alreadyJoined) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah terdaftar di kelas ini.'
            ], 422);
        }

        // Hubungkan siswa ke kelas
        \DB::table('siswa_kelas')->insert([
            'kelas_id' => $kelasId,
            'siswa_id' => $user->id,
            'tahun_ajaran' => $kelas->tahun_ajaran ?: now()->year,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil bergabung ke kelas.',
            'data' => $kelas
        ]);
    }

    /**
     * Pastikan mapel dengan nama/kode jurusan terdaftar di mata_pelajaran.
     */
    private function ensureMapelExists(string $jurusan): void
    {
        $jurusanTrim = trim($jurusan);
        if (empty($jurusanTrim)) return;

        $exists = \DB::table('mata_pelajaran')
            ->whereRaw('LOWER(nama_mapel) = ?', [strtolower($jurusanTrim)])
            ->orWhereRaw('LOWER(kode_mapel) = ?', [strtolower($jurusanTrim)])
            ->exists();

        if (!$exists) {
            // Generate clean alphanumeric base code
            $baseKode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $jurusanTrim), 0, 5));
            if (empty($baseKode)) {
                $baseKode = 'MP';
            }

            // Loop to guarantee uniqueness of kode_mapel in the database
            $kode = $baseKode;
            $counter = 1;
            while (\DB::table('mata_pelajaran')->where('kode_mapel', $kode)->exists()) {
                $kode = substr($baseKode, 0, 4) . $counter;
                $counter++;
            }

            \DB::table('mata_pelajaran')->insert([
                'nama_mapel' => $jurusanTrim,
                'kode_mapel' => $kode,
                'jam_per_minggu' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
