<?php

use App\Http\Controllers\Api\AuthController;
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
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/profile',  [AuthController::class, 'profile']);

    // Sprint 2: Manajemen Pengguna — hanya admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
