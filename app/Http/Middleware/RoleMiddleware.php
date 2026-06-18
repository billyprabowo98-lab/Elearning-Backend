<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage di routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,guru')   // multiple roles (OR)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
            ], 401);
        }

        if (! empty($roles) && ! $request->user()->hasAnyRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.',
                'required_roles' => $roles,
                'your_role'      => $request->user()->role,
            ], 403);
        }

        return $next($request);
    }
}
