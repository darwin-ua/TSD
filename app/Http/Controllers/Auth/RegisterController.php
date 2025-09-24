<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Doing;
use App\Models\Like;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\Request; // Убедитесь, что используется этот класс
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationConfirmation;
use App\Models\Product;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
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

        $this->middleware(function ($request, $next) {
            $locale = session('locale', config('app.locale'));
            App::setLocale($locale);
            return $next($request);
        });

        $this->middleware('guest');
    }


    public function showRegistrationForm()
    {
        $user = Auth::user();
        $sessionId = session()->getId();
        $uuid = request()->cookie('uuid') ?: \Illuminate\Support\Str::uuid();
        $cartCount = Product::where('uuid', $uuid)->count();
        $likeCount = Like::where('hash', $sessionId)->count();
        $cartDoingCount = Doing::where('uuid', $uuid)
            ->where('status', 0)
            ->when($user, function ($query) use ($user) {
                $query->orWhere('user_id', $user->id);
            })
            ->count();

        return view('auth.register', [
            'cartCount' => $cartCount,
            'cartDoingCount' => $cartDoingCount,
            'likeCount' => $likeCount
        ]);
    }
    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {

        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'captcha' => ['required', 'captcha'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => 2,
        ]);

        $userFolder = public_path('files/' . $user->id);
        if (!file_exists($userFolder)) {
            mkdir($userFolder, 0777, true);
        }

        Mail::raw("Your login credentials:\n\nEmail: {$data['email']}\nPassword: {$data['password']}", function ($message) use ($data) {
            $message->to($data['email'])->subject('Your Login Credentials');
        });

        return $user;
    }

    protected function redirectTo()
    {

        if (auth()->user()->role_id == 1 or auth()->user()->role_id == 3 or auth()->user()->role_id == 2 ) {
            return '/admin'; // Если роль равна 1, перенаправляем в админскую панель
        };
        if (auth()->user()->role_id == 2) {
            return '/sklad'; // Иначе перенаправляем на главную страницу
        }
    }
    protected function registered(Request $request, $user)
    {
        Auth::logout();

        return redirect('/login')->with('status', 'We sent you an activation code. Check your email and click on the link to verify.');
    }
}
