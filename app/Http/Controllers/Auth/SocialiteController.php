<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Socialite;
use Auth;
use App\Models\User;
use Exception;
use Log;

class SocialiteController extends Controller
{
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback()
    {
        try {
            $_SERVER['SERVER_PORT'] = '443'; // Указываем порт 443 для HTTPS
            $user = Socialite::driver('facebook')->user();
            Log::info('User info:', (array) $user);
        } catch (Exception $e) {
            Log::error('Facebook callback error:', ['error' => $e->getMessage()]);
            return redirect('/login');
        }

        $authUser = $this->findOrCreateUser($user);

        Auth::login($authUser, true);

        return redirect()->route('home');
    }

    public function findOrCreateUser($facebookUser)
    {
        $authUser = User::where('facebook_id', $facebookUser->id)->first();

        if ($authUser) {
            return $authUser;
        }

        return User::create([
            'name' => $facebookUser->name,
            'email' => $facebookUser->email,
            'facebook_id' => $facebookUser->id,
            'avatar' => $facebookUser->avatar,
        ]);
    }
}

