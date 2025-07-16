<?php

namespace App\Http\Controllers\Auth;

use App\Models\User; // Добавляем импорт класса User
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first(); // Получаем пользователя по email

        if (!$user) {
            return back()->withErrors(['email' => 'Пользователь с этим email не найден']);
        }

        // Генерируем новый пароль
        $newPassword = Str::random(7); // Генерируем строку из 7 символов

        // Добавляем цифру к паролю
        $newPassword .= rand(0, 9);

        // Обновляем пароль пользователя
        $user->password = Hash::make($newPassword);
        $user->save();

        // Отправляем новый пароль на почту
        Mail::send('auth.emails.new_password', ['newPassword' => $newPassword], function ($message) use ($user) {
            $message->to($user->email)->subject('Новый пароль');
        });

        return back()->with('message', 'Новый пароль отправлен на вашу почту.');
    }
}

