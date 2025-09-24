<?php

namespace App\Http\Controllers;

use App\Models\Doing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Models\User;
use App\Models\Like;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\Event;
use App\Models\Order;
use App\Models\UserData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PortfolioFoto;
use App\Models\Product;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function processReferral(Request $request)
    {
        $user = auth()->user();
        $randomCode = Str::random(6);
        $user->update(['code_part' => $randomCode]);

        return response()->json(['message' => 'Success']);
    }

    public function handleLikeNo(Request $request)
    {
        $eventId = $request->input('event_id');
        $sessionHash = $request->session()->get('uuid');
        $userId = auth()->check() ? auth()->id() : null;

        $like = Like::where('event_id', $eventId)
            ->where(function ($query) use ($userId, $sessionHash) {
                $query->where('user_id', $userId)
                    ->orWhere('hash', $sessionHash);
            })
            ->first();

        if ($like) {
            $like->delete(); // Удаляем запись
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Like not found.']);
        }
    }

    public function removeFromDoings(Request $request, $eventId)
    {
        $user = Auth::user();

        // Удаляем товар из таблицы Doings на основе event_id и user_id (если пользователь авторизован)
        Doing::where('event_id', $eventId)
            ->when($user, function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->delete();

        // Также можно удалить товар, если пользователь не авторизован, используя uuid
        if (!$user) {
            $uuid = $request->cookie('uuid');
            Doing::where('event_id', $eventId)
                ->where('uuid', $uuid)
                ->delete();
        }

        return redirect()->back()->with('success', 'Товар удален из таблицы Doings.');
    }

    public function removeFromCart(Request $request, $eventId)
    {
        $user = Auth::user();

        // Удаляем товар из таблицы Products на основе event_id и user_id (если пользователь авторизован)
        Product::where('event_id', $eventId)
            ->when($user, function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->delete();

        // Также можно удалить товар, если пользователь не авторизован, используя session_id или uuid
        if (!$user) {
            $uuid = $request->cookie('uuid');
            Product::where('event_id', $eventId)
                ->where('uuid', $uuid)
                ->delete();
        }

        return redirect()->back()->with('success', 'Товар удален из корзины.');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function member(Request $request)
    {
        $user = Auth::user();
        $use = User::where('email', $user->email)->first();

        //$orders = Order::where('email', $user->email)->get();
        $uuidValue = $request->session()->get('uuid');
        $uuid = $request->cookie('uuid'); // Получаем UUID из cookie

        if ($user) {
            // Ищем заказы по email пользователя
            $orders = Order::where('email', $user->email)->get();

            // Проверяем, пустая ли коллекция заказов
            if ($orders->isEmpty()) {
                // Если по email не найдены заказы, ищем по token
                $uuid = $request->cookie('uuid'); // Убедитесь, что uuid получен раньше
                $orders = Order::where('token', $uuid)->get();
            }
        } else {
            // Если пользователь не авторизован, ищем по token
            $uuid = $request->cookie('uuid');
            $orders = Order::where('token', $uuid)->get();
        }

        Like::updateOrCreate(
            ['hash' => $uuidValue],
            ['user_id' => $user->id]
        );

        $likes = Like::where('user_id', $user->id)->get();
        $events = [];

        foreach ($likes as $like) {
            $event = Event::find($like->event_id);
            if ($event) {
                // Получаем первое изображение для события
                $firstImage = PortfolioFoto::where('event_id', $event->id)->first();
                if ($firstImage) {
                    $event->firstImage = asset('files/' . $event->user_id . '/' . $event->id . '/' . $firstImage->title);
                } else {
                    $event->firstImage = null; // или путь к изображению по умолчанию
                }
                $events[] = $event;
            }
        }

        $eventsCount = count($events);

        $ordersData = $user->ordersWithStatus(3)->get();
        $totalOrders = $orders->count();
        $sessionId = session()->getId();
        $likeCount = Like::where('hash', $sessionId)->count();

        $ordersdata = [];

        foreach ($ordersData as $orddata) {
            $eventDat = Event::where('id', $orddata->order_id)->first();
            if ($eventDat) {
                // Получаем первое изображение для события
                $firstImage = PortfolioFoto::where('event_id', $eventDat->id)->first();
                if ($firstImage) {
                    $eventDat->firstImage = asset('files/' . $eventDat->user_id . '/' . $eventDat->id . '/' . $firstImage->title);
                } else {
                    $eventDat->firstImage = null; // или путь к изображению по умолчанию
                }
                $ordersdata[] = $eventDat;
            }
        }

        $userDataRecords = UserData::where('user_id', $user->id)->get();
        $currentLocale = session('locale', config('app.locale'));

        //$cartCount = Product::where('uuid', $uuid)->count();
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

        $cartItemsWithEvents = Product::with('event')
            ->where('uuid', $uuid) // Фильтруем по uuid
            ->when($user, function ($query) use ($user) {
                // Если пользователь авторизован, добавляем товары с его user_id
                $query->orWhere('user_id', $user->id);
            })
            ->get();

        $totalItems = $cartItemsWithEvents->count();

        App::setLocale($currentLocale);

        return view('sklad.index', [
            'showTestRecord' => true,
            'cartItemsWithEvents' => $cartItemsWithEvents,
            'eventsCount' => $eventsCount,
            'totalItems' => $totalItems,
            'cartCount' => $cartCount,
            'totalOrders' => $totalOrders,
            'likeCount' => $likeCount,
            'cartDoingCount' => $cartDoingCount,
            'use' => $use,
            'ordersdata' => $ordersdata,
            'userDataRecords' => $userDataRecords,
            'currentLocale' => $currentLocale,
            'events' => $events,
            'sessionId' => $sessionId,
            'orders' => $orders
        ]);

    }

    public function open($id)
    {
        $user = Auth::user();
        $orders = $user->orders;

        $online = Event::where('id', $id)->first();

        $userDataRecords = UserData::where('user_id', $user->id)->get();
        $currentLocale = session('locale', config('app.locale'));
        App::setLocale($currentLocale);

        $lessonRecords = Lesson::where('events_id', $id)->where('lesson_chapter', 0)->get();

        if (!$lessonRecords) {
            return redirect()->back()->withErrors('Lesson not found.');
        }

        return view('open', [
            'showTestRecord' => true,
            'online' => $online->online,
            'userDataRecords' => $userDataRecords,
            'currentLocale' => $currentLocale,
            'orders' => $orders,
            'lessonRecords' => $lessonRecords
        ]);
    }

    public function openLesson($id, $lesson_id)
    {
        $user = Auth::user();
        $orders = $user->orders()->get();

        $userDataRecords = UserData::where('user_id', $user->id)->get();
        $currentLocale = session('locale', config('app.locale'));
        App::setLocale($currentLocale);

        $specificLesson = Lesson::where('events_id', $id)->first();

        if (!$specificLesson) {
            return redirect()->back()->withErrors('Specific lesson not found.');
        }

        $lessonRecords = Lesson::where('events_id', $id)->get();

        if (!$lessonRecords) {
            return redirect()->back()->withErrors('Lesson not found.');
        }

        $lessonRecords = Lesson::where('events_id', $id)->get();
        $lessonFiles = LessonFile::where('events_id', $id)
            ->where('lesson_chapter', $specificLesson->lesson_chapter)
            ->get();

        $lessonsWithFiles = DB::table('lesson')
            ->leftJoin('lesson_files', function ($join) use ($id) {
                $join->on('lesson.events_id', '=', 'lesson_files.events_id')
                    ->on('lesson.lesson_chapter', '=', 'lesson_files.lesson_chapter')
                    ->where('lesson.events_id', '=', $id);
            })
            ->select('lesson.*', 'lesson_files.text as lessonFileText')
            ->get();

        return view('open_lesson', [
            'showTestRecord' => true,
            'specificLesson' => $specificLesson,
            'lessonsWithFiles' => $lessonsWithFiles,
            'lessonFiles' => $lessonFiles,
            'currentLocale' => $currentLocale,
            'orders' => $orders,
            'lessonRecords' => $lessonRecords
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        $currentLocale = session('locale', config('app.locale'));
        App::setLocale($currentLocale);

        if ($user->role_id == 1 or $user->role_id == 3) {
            return redirect('/admin')->with([
                'showTestRecord' => true,
                'currentLocale' => $currentLocale,
            ]);
        }

        if ($user->role_id == 2) {
            return redirect('/admin')->with([
                'showTestRecord' => true,
                'currentLocale' => $currentLocale,
            ]);
        }

        return redirect('/admin')->with([
            'showTestRecord' => true,
            'currentLocale' => $currentLocale,
        ]);

    }
}
