<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Pastikan hanya pengguna dengan role admin yang dapat mengakses rute ini.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json([
                'message' => 'Access denied. Only administrators can perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
