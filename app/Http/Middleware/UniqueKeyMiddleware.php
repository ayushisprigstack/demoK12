<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UniqueKeyMiddleware
{
    public function handle($request, Closure $next) {
        $accessToken = $request->header('Authorization') ?: $request->query('access_token');        
        if (!$accessToken) {           
            return response()->json(['error' => 'Access token not provided.'], 401);
        }
             $key = '0ga7v0ipnufgfpC';
        if ($key != $accessToken) {
            return response()->json(['error' => 'Invalid access token.'], 401);
        }             
        return $next($request);
    }

}
