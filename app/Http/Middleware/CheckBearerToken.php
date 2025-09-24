<?php
namespace App\Http\Middleware;

use Closure;

class CheckBearerToken
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');
        $validToken = 'Bearer ' . env('API_BEARER_TOKEN');

        if ($token !== $validToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}

