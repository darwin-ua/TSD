<?php


namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EventController;
use App\Models\Event;
use App\Models\Shedule;
use App\Models\User;
use App\Models\PortfolioFoto;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Like;
use App\Models\Doing;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;

class WelcomeController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $uuid = request()->cookie('uuid') ?: \Illuminate\Support\Str::uuid();
        $currentLocale = session('locale', config('app.locale'));
        App::setLocale($currentLocale);

        $goods = Event::where('status', 1)
            ->where(function ($query) {
                $query->orWhere('category', 4)
                    ->where('amount', '>', 0);
            })
            ->inRandomOrder()
            ->take(12)
            ->get();

        foreach ($goods as $eventgoods) {
                $firstImageGoods = PortfolioFoto::where('event_id', $eventgoods->id)->first();
                if ($firstImageGoods) {
                    $eventgoods->firstImage = asset('files/' . $eventgoods->user_id . '/' . $eventgoods->id . '/' . $firstImageGoods->title);
                } else {
                    $eventgoods->firstImage = null; // или путь к изображению по умолчанию
                }
        }


        $eventsDuble = Event::where('status', 1)
            ->where(function ($query) {
                $query->orWhere('category', 2);
            })
            ->inRandomOrder()
            ->take(12)
            ->get();

        foreach ($eventsDuble as $event) {
            $firstImage = PortfolioFoto::where('event_id', $event->id)->first();
            if ($firstImage) {
                $event->firstImage = asset('files/' . $event->user_id . '/' . $event->id . '/' . $firstImage->title);
            } else {
                $event->firstImage = null; // или путь к изображению по умолчанию
            }
        }

        $goodsNew = Event::where('status', 1)
            ->where(function ($query) {
                $query->where('category', 1)
                    ->orWhere('category', 2)
                    ->orWhere('category', 4)
                    ->orWhere(function ($query) {
                        $query->where('category', 4)
                            ->where('amount', '>', 0);
                    });
            })
            ->orderBy('created_at', 'desc') // Сортировка по дате создания в порядке убывания
            ->take(3) // Ограничение до 3 записей
            ->get();

        foreach ($goodsNew as $event) {
            $firstImage = PortfolioFoto::where('event_id', $event->id)->first();
            if ($firstImage) {
                $event->firstImage = asset('files/' . $event->user_id . '/' . $event->id . '/' . $firstImage->title);
            } else {
                $event->firstImage = null; // или путь к изображению по умолчанию
            }
        }

        // Получение самых просматриваемых событий
        $mostViewedEvents = Statistic::select('event_id', DB::raw('SUM(count) as total_views'))
            ->groupBy('event_id')
            ->orderBy('total_views', 'desc')
            ->take(3) // Ограничение до 3 записей
            ->get();

        // Преобразование результатов в коллекцию событий
        $eventsMost = Event::whereIn('id', $mostViewedEvents->pluck('event_id'))->get();

        foreach ($eventsMost as $event) {
            $firstImage = PortfolioFoto::where('event_id', $event->id)->first();
            if ($firstImage) {
                $event->firstImage = asset('files/' . $event->user_id . '/' . $event->id . '/' . $firstImage->title);
            } else {
                $event->firstImage = null; // или путь к изображению по умолчанию
            }
        }

        $eventsall = Event::latest()->where('status', 1)->get();

        foreach ($eventsall as $event) {
            $schedule = Shedule::where('event_id', $event->id)->first();
            if ($schedule) {
                $event->reserv = $schedule->reserv;
            } else {
                $event->reserv = null;
            }
        }

        // Получаем количество товаров в корзине для текущей сессии
        $sessionId = session()->getId();

        $cartCount = Product::where('uuid', $uuid)
            ->where('status', 0)
            ->when($user, function ($query) use ($user) {
                // Если пользователь авторизован, добавляем товары с его user_id
                $query->orWhere('user_id', $user->id);
            })
            ->count();

        $cartDoingCount = Doing::where('uuid', $uuid)
            ->where('status', 0)
            ->when($user, function ($query) use ($user) {
                $query->orWhere('user_id', $user->id);
            })
            ->count();

        // Получаем количество лайков для текущей сессии
        $likeCount = Like::where('hash', $sessionId)->count();

        $goodstop = Statistic::select('event_id')
            ->groupBy('event_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(3)
            ->pluck('event_id');

        if ($user && $user->role_id == 1) {
            return view('welcome', [
                'showTestRecord' => true,
                'currentLocale' => $currentLocale,
                'goods' => $goods,
                'event' => $event,
                'eventsDuble' =>$eventsDuble ,
                'eventsall' => $eventsall,
                'cartCount' => $cartCount,
                'cartDoingCount' => $cartDoingCount,
                'likeCount' => $likeCount,
                'goodstop' => $goodstop,
                'goodsNew' => $goodsNew,
                'eventsMost'=> $eventsMost,
            ]);
        } else {
            $currentLocale = app()->getLocale();
            return view('welcome', [
                'showTestRecord' => false,
                'eventsDuble' =>$eventsDuble ,
                'currentLocale' => $currentLocale,
                'goods' => $goods,
                'event' => $event,
                'eventsall' => $eventsall,
                'cartCount' => $cartCount,
                'cartDoingCount' => $cartDoingCount,
                'likeCount' => $likeCount,
                'goodsNew' => $goodsNew,
                'eventsMost'=> $eventsMost,
            ]);
        }
    }


    public function Shedule($id)
    {
        $schedule = Shedule::find($id);
        if ($schedule) {
            return $schedule->reserv;
        } else {
            return "Запись с ID $id не найдена в таблице schedules.";
        }

    }

    public function filter(Request $request)
    {

        $searchTerm = $request->input('what');
        $selectedCategory = $request->input('category');

        $user = User::where('id', 1)->where('role_id', 1)->first();
        $phone = $user->phone;

        $eventsQuery = Event::where('title', 'like', '%' . $searchTerm . '%')
            ->where('events.status', 1);

        if (!empty($selectedCategory)) {
            $eventsQuery->where('category', $selectedCategory);
        }

        $events = $eventsQuery->join('shedules', 'events.id', '=', 'shedules.event_id')
            ->orderBy('events.id')
            ->limit(3)
            ->get();

    }

    public function search(Request $request)
    {
        $user = Auth::user();
        $regions = Region::take(100)->get();

        $cities = [];
        if ($regions->isNotEmpty()) {
            $firstRegion = $regions->first();
            $cities = $firstRegion->towns;
        }

        $currentLocale = session('locale', config('app.locale'));
        App::setLocale($currentLocale);

        $searchTerm = $request->input('what');
        $rng1 = $request->input('rng');
        $rng2 = $request->input('rng2');
        $selectedCategory = $request->input('cat');
        $user_id = $request->input('salesman');

        $eventsQuery = Event::where('title', 'like', '%' . $searchTerm . '%')
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('category', '!=', 4)
                    ->orWhereNotNull('amount');
            });


        if ($rng1 !== null && $rng2 !== null) {
            $eventsQuery->whereBetween('amount', [$rng1, $rng2]);
        }

        if ($selectedCategory !== null && $selectedCategory !== '') {
            $eventsQuery->where('category', $selectedCategory);
        }

        if ($user_id !== null) {
            $eventsQuery->where('user_id', $user_id);
        }

        $events = $eventsQuery->orderBy('events.id')->paginate(10);

        $events->appends([
            'what' => $searchTerm,
            'rng' => $rng1,
            'rng2' => $rng2,
            'cat' => $selectedCategory,
            'salesman' => $user_id
        ]);

        foreach ($events as $event) {
            $firstImage = PortfolioFoto::where('event_id', $event->id)->first();
            if ($firstImage) {
                $event->first_image_path = asset('files/' . $event->user_id . '/' . $event->id . '/' . $firstImage->title);
            } else {
                $event->first_image_path = null;
            }
        }
        $uuid = request()->cookie('uuid') ?: \Illuminate\Support\Str::uuid();
        // Получаем количество товаров в корзине для текущей сессии
        $sessionId = session()->getId();
        //$cartCount = Product::where('session_id', $sessionId)->count();
        $cartCount = Product::where('uuid', $uuid)
            ->where('status', 0)
            ->when($user, function ($query) use ($user) {
                // Если пользователь авторизован, добавляем товары с его user_id
                $query->orWhere('user_id', $user->id);
            })
            ->count();
        $cartDoingCount = Doing::where('uuid', $uuid)
            ->where('status', 0)
            ->when($user, function ($query) use ($user) {
                $query->orWhere('user_id', $user->id);
            })
            ->count();
        $likeCount = Like::where('hash', $sessionId)->count();

        return view('events.yesearch', [
            'events' => $events,
            'currentLocale' => $currentLocale,
            'searchTerm' => $searchTerm,
            'cartDoingCount' => $cartDoingCount,
            'cartCount' => $cartCount,
            'rng1' => $rng1,
            'user_id' => $user_id,
            'regions' => $regions,
            'cities' => $cities,
            'salesman' => $user_id,
            'rng2' => $rng2,
            'likeCount' => $likeCount
        ]);
    }


}

