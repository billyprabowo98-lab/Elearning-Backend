<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\MapelController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Sprint 1: Autentikasi (public) ────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ── Protected routes ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Sprint 1: Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Sprint 2: Manajemen Pengguna — hanya admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('admin', UserController::class);
    });

    // Sprint 3: Kelas & Mata Pelajaran — admin dan guru
    Route::middleware('role:admin,guru')->group(function () {

        // Kelas
        Route::get('kelas',                              [KelasController::class, 'index']);
        Route::post('kelas',                             [KelasController::class, 'store']);
        Route::put('kelas/{id}',                         [KelasController::class, 'update']);
        Route::delete('kelas/{id}',                      [KelasController::class, 'destroy']);

        // Siswa Kelas
        Route::post('kelas/{id}/siswa',                  [KelasController::class, 'tambahSiswa']);
        Route::delete('kelas/{id}/siswa/{id_siswa}',     [KelasController::class, 'hapusSiswa']);

        // Mata Pelajaran
        Route::get('mapel',                              [MapelController::class, 'index']);
        Route::post('mapel',                             [MapelController::class, 'store']);
        Route::put('mapel/{id}',                         [MapelController::class, 'update']);
        Route::delete('mapel/{id}',                      [MapelController::class, 'destroy']);
    });
});
