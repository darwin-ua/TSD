<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class SetUuidCookie
{
    public function handle($request, Closure $next)
    {
        if (!$request->hasCookie('uuid')) {
            $uuid = Str::uuid()->toString();
            cookie()->queue('uuid', $uuid, 60*24*365); // Cookie действует один год
        }

        return $next($request);
    }
}
