<?php


namespace App\Http\Controllers;

use App\Models\Doing;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\App;
use App\Models\Product;
use App\Models\Like;
use Illuminate\Support\Facades\Auth;

// Добавлен этот импорт

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function __construct()
    {
        $user = Auth::user();
        $this->middleware(function ($request, $next) {
            $locale = session('locale', config('app.locale'));
            App::setLocale($locale);
            return $next($request);
        });

        // Получаем количество товаров в корзине
        $sessionId = session()->getId();
        $uuid = request()->cookie('uuid') ?: \Illuminate\Support\Str::uuid();


        // Передаем переменные $cartCount и $likeCount во все представления
        view()->share([]);
    }

}

