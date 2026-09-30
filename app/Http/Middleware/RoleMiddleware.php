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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! auth('api')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        $user = auth('api')->user();
        if (! in_array($user->role, $roles)) {
            return response()->json(['message' => 'Akses ditolak. Anda tidak memiliki izin.'], 403);
        }

        return $next($request);
    }
}
