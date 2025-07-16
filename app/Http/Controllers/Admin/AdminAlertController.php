<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminAlertController extends Controller
{
    public function count()
    {
        $user = Auth::user();
        $alertCount = Alert::where('user_id', $user->id)
            ->where('status', 0)
            ->count(); // Используйте метод count() после where()
        return response()->json(['count' => $alertCount ]);
    }

}
