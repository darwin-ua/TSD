<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class UpdateCartOnLogin
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $uuid = $request->cookie('uuid') ?: Str::uuid(); // Получаем uuid из куки или создаем новый

            // Обновляем товары в корзине, присваивая user_id по uuid
//            \App\Models\Product::where('uuid', $uuid)
//                ->update(['user_id' => $user->id]);

            // Устанавливаем uuid в куки, если он был создан
            if (!$request->cookie('uuid')) {
                \Illuminate\Support\Facades\Cookie::queue('uuid', $uuid, 60 * 24 * 365); // Например, на год
            }
        }

        return $next($request);
    }
}
