<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $plainTextToken = $request->bearerToken();

        if (!$plainTextToken) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $token = PersonalAccessToken::with('user.role')
            ->where('token', hash('sha256', $plainTextToken))
            ->first();

        if (!$token || !$token->user || !$token->user->is_active) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
