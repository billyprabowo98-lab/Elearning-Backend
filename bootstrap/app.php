<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias untuk middleware role
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // ✅ HAPUS ->statefulApi() — tidak dibutuhkan untuk pure REST API
        // Sanctum v4 sudah tidak punya EnsureFrontendRequestsAreStateful
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {

                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validasi gagal.',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
                    ], 401);
                }

                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Endpoint tidak ditemukan.',
                    ], 404);
                }
            }
        });
    })->create();