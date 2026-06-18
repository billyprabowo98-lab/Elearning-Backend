<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sprint 1: Autentikasi
|--------------------------------------------------------------------------
*/

// ── Public routes (tanpa autentikasi) ──────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ── Protected routes (wajib token Sanctum) ────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/profile',  [AuthController::class, 'profile']);

    // Contoh penggunaan middleware role (aktifkan sesuai kebutuhan sprint berikutnya)
    // Route::middleware('role:admin')->group(function () {
    //     Route::get('/admin/dashboard', ...);
    // });

    // Route::middleware('role:guru,admin')->group(function () {
    //     Route::get('/nilai', ...);
    // });
});
