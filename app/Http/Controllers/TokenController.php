<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str; // Импортируем класс для генерации строки

class TokenController extends Controller
{
    public function getToken(Request $request)
    {
        // Генерация уникального токена
        $token = Str::random(60);

        // Сохранение токена в сессии
        session(['api_token' => $token]);

        // Возвращаем токен клиенту
        return response()->json(['token' => $token]);
    }
}

