<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Doing;
use App\Models\Like;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    //protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */


    public function __construct()
    {

        // Выполняем middleware для установки локали перед AuthenticateUsers
        $this->middleware(function ($request, $next) {
            $locale = session('locale', config('app.locale'));
            App::setLocale($locale);
            return $next($request);
        });

        // Применяем middleware AuthenticateUsers
        $this->middleware('guest')->except('logout');
    }

    protected function username()
    {
        return 'id_lk'; // Используем поле 'id_lk' вместо 'email'
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'id_lk' => 'required|string', // Поле 'id_lk' обязательно
            'password' => 'required|string', // Пароль обязателен
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        return Auth::attempt(
            ['id_lk' => $request->input('id_lk'), 'password' => $request->input('password')],
            $request->filled('remember')
        );
    }


    protected function redirectTo()
    {

            return '/sklad'; // Иначе перенаправляем на главную страницу

    }




}
