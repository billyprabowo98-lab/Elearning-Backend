<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tugas;
use App\Models\PengumpulanTugas;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TugasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Tugas::with(['kelas', 'guru']);

        if ($user->role === 'guru') {
            $query->where('guru_id', $user->id);
        } elseif ($user->role === 'siswa') {
            // Get student classes
            $kelasIds = \DB::table('siswa_kelas')
                ->where('siswa_id', $user->id)
                ->pluck('kelas_id');
            $query->whereIn('kelas_id', $kelasIds);
            
            // Eager load only current student's submission
            $query->with(['pengumpulan' => function ($q) use ($user) {
                $q->where('siswa_id', $user->id);
            }]);
        }

        $tugas = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tugas
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'deadline' => 'required',
            'kelas_id' => 'required|exists:kelas,id',
            'file_tugas' => 'nullable|file|max:20480',
        ]);

        $data = [
            'judul' => $request->judul,
            'deskripsi' => $request->deskripsi,
            'deadline' => $request->deadline,
            'kelas_id' => $request->kelas_id,
            'guru_id' => $user->id,
        ];

        if ($request->hasFile('file_tugas')) {
            $file = $request->file('file_tugas');
            $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('tugas', $filename, 'public');
            
            $data['attachment_path'] = $path;
            $data['attachment_name'] = $file->getClientOriginalName();
        }

        $tugas = Tugas::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dibuat.',
            'data' => $tugas
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $tugas = Tugas::find($id);

        if (!$tugas) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan.'
            ], 404);
        }

        $tugas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dihapus.'
        ]);
    }

    public function listPengumpulan(int $tugas_id): JsonResponse
    {
        $tugas = Tugas::find($tugas_id);

        if (!$tugas) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan.'
            ], 404);
        }

        // Get all students enrolled in the class of this assignment
        $siswaInKelas = \DB::table('siswa_kelas')
            ->where('kelas_id', $tugas->kelas_id)
            ->pluck('siswa_id');

        // Get actual submissions
        $submissions = PengumpulanTugas::with('siswa:id,nama,email')
            ->where('tugas_id', $tugas_id)
            ->get();

        // Map them so we include dummy/empty submissions for students who haven't submitted yet
        $allSubmissions = [];
        $submittedSiswaIds = $submissions->pluck('siswa_id')->toArray();

        // 1. Add students who submitted
        foreach ($submissions as $sub) {
            $allSubmissions[] = $sub;
        }

        // 2. Add students who haven't submitted
        $studentsNotSubmitted = \App\Models\User::whereIn('id', $siswaInKelas)
            ->whereNotIn('id', $submittedSiswaIds)
            ->get();

        foreach ($studentsNotSubmitted as $siswa) {
            $allSubmissions[] = [
                'id' => null,
                'tugas_id' => $tugas_id,
                'siswa_id' => $siswa->id,
                'file_path' => null,
                'file_name' => null,
                'nilai' => null,
                'catatan_guru' => null,
                'dikumpul_pada' => null,
                'siswa' => [
                    'id' => $siswa->id,
                    'nama' => $siswa->nama,
                    'email' => $siswa->email,
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $allSubmissions
        ]);
    }

    public function inputNilai(Request $request, int $id): JsonResponse
    {
        // If $id is null or 0, or submission doesn't exist, we might need to create it (for empty grading),
        // but typically the student must submit first, or teacher grades an existing submission.
        // Let's find by id.
        $submission = PengumpulanTugas::find($id);

        if (!$submission) {
            // Check if we are passing siswa_id and tugas_id to create a graded record manually
            if ($request->filled('siswa_id') && $request->filled('tugas_id')) {
                $submission = PengumpulanTugas::create([
                    'tugas_id' => $request->tugas_id,
                    'siswa_id' => $request->siswa_id,
                    'file_path' => '',
                    'file_name' => 'Dinilai langsung oleh Guru',
                    'nilai' => $request->nilai,
                    'catatan_guru' => $request->catatan_guru,
                    'dikumpul_pada' => now(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengumpulan tugas tidak ditemukan.'
                ], 404);
            }
        } else {
            $request->validate([
                'nilai' => 'required|integer|min:0|max:100',
                'catatan_guru' => 'nullable|string',
            ]);

            $submission->update([
                'nilai' => $request->nilai,
                'catatan_guru' => $request->catatan_guru,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nilai berhasil disimpan.',
            'data' => $submission
        ]);
    }

    /**
     * POST /api/tugas/{id}/kumpul
     * Siswa mengumpulkan tugas.
     */
    public function kumpulTugas(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'siswa') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya siswa yang dapat mengumpulkan tugas.'
            ], 403);
        }

        $tugas = Tugas::find($id);
        if (!$tugas) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas tidak ditemukan.'
            ], 404);
        }

        // Validasi file
        $request->validate([
            'file_jawaban' => 'required|file|max:20480', // max 20MB
        ]);

        // Cek jika sudah pernah mengumpulkan
        $submission = PengumpulanTugas::where('tugas_id', $id)
            ->where('siswa_id', $user->id)
            ->first();

        if ($submission) {
            if ($submission->file_path) {
                \Storage::disk('public')->delete($submission->file_path);
            }
        } else {
            $submission = new PengumpulanTugas([
                'tugas_id' => $id,
                'siswa_id' => $user->id,
            ]);
        }

        if ($request->hasFile('file_jawaban')) {
            $file = $request->file('file_jawaban');
            $filename = \Illuminate\Support\Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('pengumpulan', $filename, 'public');
            
            $submission->file_path = $path;
            $submission->file_name = $file->getClientOriginalName();
            $submission->dikumpul_pada = now();
            $submission->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil dikumpulkan.',
            'data' => $submission
        ]);
    }
}
