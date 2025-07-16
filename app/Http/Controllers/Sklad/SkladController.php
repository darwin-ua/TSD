<?php

namespace App\Http\Controllers\Sklad;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Event;
use Carbon\Carbon;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;
use App\Models\Alert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;


class SkladController extends Controller
{

    public function index()
    {
        $admins = User::where('role_id', 1)->get();
        $currentAdmin = auth()->user();

        return view('sklad.index', compact(
            'currentAdmin', 'admins'
        ));
    }
    
}

