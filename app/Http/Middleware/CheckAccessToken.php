<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAccessToken
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if ($token !== config('auth.api_token')) {
            return response('Unauthorized.', 401);
        }

        return $next($request);
    }
}
