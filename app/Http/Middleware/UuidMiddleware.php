<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UuidMiddleware
{
    public function handle($request, Closure $next)
    {
        // Проверяем, есть ли в сессии параметр Sdfg
        if (!$request->session()->has('uuid')) {
            $randomNumber = rand();
            $request->session()->put('uuid', $randomNumber);
        }

        return $next($request);
    }
}
