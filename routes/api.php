<?php

use Illuminate\Support\Facades\Route;

// Tempat route API kamu nanti
Route::get('/hello', function () {
    return response()->json([
        'message' => 'Hello World dari Laravel (Backend)!'
    ]);
});