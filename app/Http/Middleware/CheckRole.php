<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userRole = $request->user()->role?->slug;

        if (!in_array($userRole, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this resource'
            ], 403);
        }

        return $next($request);
    }
}
