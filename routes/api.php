<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\MapelController;
use App\Http\Controllers\Api\MateriController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\PengumumanController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TugasController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Sprint 1: Autentikasi (public) ────────────────────────────────────────
Route::get('/health', function () {
    return response()->json([
        'success'   => true,
        'message'   => 'E-Learning Backend API is running successfully',
        'timestamp' => now()->toIso8601String()
    ]);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);


// ── Protected routes ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Sprint 1: Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Sprint 2: Manajemen Pengguna — hanya admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('admin', UserController::class);
    });

    // Sprint 3: Kelas & Mata Pelajaran — admin dan guru
    Route::middleware('role:admin,guru')->group(function () {
        Route::post('kelas',                         [KelasController::class, 'store']);
        Route::put('kelas/{id}',                     [KelasController::class, 'update']);
        Route::delete('kelas/{id}',                  [KelasController::class, 'destroy']);
        Route::post('kelas/{id}/siswa',              [KelasController::class, 'tambahSiswa']);
        Route::delete('kelas/{id}/siswa/{id_siswa}', [KelasController::class, 'hapusSiswa']);

        Route::post('mapel',        [MapelController::class, 'store']);
        Route::put('mapel/{id}',    [MapelController::class, 'update']);
        Route::delete('mapel/{id}', [MapelController::class, 'destroy']);
    });

    // Sprint 4: Materi & Tugas & Kelas — GET untuk semua, CUD untuk guru/admin
    Route::middleware('role:admin,guru,siswa')->group(function () {
        Route::get('kelas',                  [KelasController::class, 'index']);
        Route::post('kelas/join',            [KelasController::class, 'joinKelas']);
        Route::get('mapel',                  [MapelController::class, 'index']);
        Route::get('materi',                 [MateriController::class, 'index']);
        Route::get('materi/{id}',            [MateriController::class, 'show']);
        Route::get('tugas',                  [TugasController::class, 'index']);
        Route::post('tugas/{id}/kumpul',     [TugasController::class, 'kumpulTugas']);
    });
    Route::middleware('role:admin,guru')->group(function () {
        Route::post('materi',        [MateriController::class, 'store']);
        Route::put('materi/{id}',    [MateriController::class, 'update']);
        Route::delete('materi/{id}', [MateriController::class, 'destroy']);

        Route::post('tugas',                 [TugasController::class, 'store']);
        Route::delete('tugas/{id}',          [TugasController::class, 'destroy']);
        Route::get('pengumpulan/{tugas_id}', [TugasController::class, 'listPengumpulan']);
        Route::put('pengumpulan/{id}/nilai', [TugasController::class, 'inputNilai']);
    });

    // Sprint 5: Forum Diskusi — semua role
    Route::middleware('role:admin,guru,siswa')->group(function () {
        Route::get('forum',                   [ForumController::class, 'index']);
        Route::post('forum',                  [ForumController::class, 'store']);
        Route::get('forum/{id}',              [ForumController::class, 'show']);
        Route::put('forum/{id}',              [ForumController::class, 'update']);
        Route::delete('forum/{id}',           [ForumController::class, 'destroy']);
        Route::post('forum/{id}/komentar',    [ForumController::class, 'simpanKomentar']);
        Route::put('komentar/{id}',           [ForumController::class, 'updateKomentar']);
        Route::delete('komentar/{id}',        [ForumController::class, 'hapusKomentar']);

        Route::get('notifikasi',              [NotifikasiController::class, 'index']);
        Route::put('notifikasi/baca-semua',   [NotifikasiController::class, 'bacaSemua']);
        Route::put('notifikasi/{id}/baca',    [NotifikasiController::class, 'tandaiBaca']);
        Route::delete('notifikasi/{id}',      [NotifikasiController::class, 'destroy']);
    });

    // Sprint 6: Pengumuman
    // GET → semua role (siswa difilter otomatis sesuai kelas)
    // POST, PUT, DELETE → admin dan guru
    Route::middleware('role:admin,guru,siswa')->group(function () {
        Route::get('pengumuman', [PengumumanController::class, 'index']);
    });
    Route::middleware('role:admin,guru')->group(function () {
        Route::post('pengumuman',        [PengumumanController::class, 'store']);
        Route::put('pengumuman/{id}',    [PengumumanController::class, 'update']);
        Route::delete('pengumuman/{id}', [PengumumanController::class, 'destroy']);
    });
});
