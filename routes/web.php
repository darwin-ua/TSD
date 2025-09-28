<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Sklad\SkladController;
use App\Http\Controllers\Sklad\SkladEventController;
use App\Http\Controllers\Sklad\SkladSheduleController;
use App\Http\Controllers\Sklad\SkladUsersController;
use App\Http\Controllers\Sklad\SkladOrderController;
use App\Http\Controllers\Sklad\SkladPaymentController;
use App\Http\Controllers\Sklad\SkladAlertController;
use App\Http\Controllers\UserDataController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\Sklad\SkladScanController;

Route::get('/test', [TestController::class, 'getUsers']);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::post('/sklad/acceptance/finish', [SkladOrderController::class, 'finishAcceptance'])
    ->name('sklad.acceptance.finish');

Route::get('/redis-test', function () {
    try {
        Redis::set('test_key', 'Redis is working!');
        $value = Redis::get('test_key');
        return $value; // Должно вернуть 'Redis is working!'
    } catch (\Exception $e) {
        return 'Redis connection failed: ' . $e->getMessage();
    }
});

Route::get('/send-email', function () {
    \Illuminate\Support\Facades\Mail::raw('This is a test email', function ($message) {
        $message->to('itsystems571@gmail.com')
            ->subject('Test Email from EVENTHES');
    });
    return 'Email sent successfully!';
});

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);

Route::get('/lang/{locale}', function ($locale) {
    App::setLocale($locale);
    session()->put('locale', $locale);
    return redirect()->back();
});

Auth::routes();

Route::middleware(['auth', 'update.cart'])->group(function () {
    Route::get('/restricted-access', function () {
        return view('restricted_access');
    })->name('restricted.access');
    Route::get('/sklad', [SkladController::class, 'index'])->name('sklad.index');
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/client', [HomeController::class, 'member'])->name('member');
    Route::get('/open/{id}', [HomeController::class, 'open'])->name('sklad.open');
    Route::get('/open/{events_id}/lesson/{lesson_id}', [HomeController::class, 'openLesson'])->name('sklad.openLesson');
    Route::get('/sklad/alerts/count', [SkladAlertController::class, 'count'])->name('sklad.alerts.count');
    Route::get('/sklad/get-orders-with-status', [SkladController::class, 'getOrdersWithStatus']);
    Route::get('/sklad/get-order-details/{number}', [SkladController::class, 'getOrderDetails']);
    Route::get('/sklad/update-order-status/{orderId}', [SkladController::class, 'updateOrderStatus']);
    Route::get('/sklad/summary', [SkladController::class, 'summaryData']);
    Route::get('/sklad/get-ftp-image/{orderId}/{itemNumber}', [SkladController::class, 'getFtpImage']);
    Route::get('/test-write', function () {
        Storage::disk('public')->put('ftp_cache/test.txt', 'hello world');
        return 'OK';
    });
    Route::get('/sklad/ftp-gallery/{orderId}', [SkladController::class, 'showFtpGalleryRaw']);
    Route::get('/sklad/payments/all', [SkladPaymentController::class, 'index'])->name('sklad.finances.index');
    Route::get('/sklad/payments/create', [SkladPaymentController::class, 'create'])->name('sklad.payments.create');
    Route::post('/sklad/payments', [SkladPaymentController::class, 'store'])->name('sklad.payments.store');
    Route::get('/sklad/users/all', [SkladUsersController::class, 'index'])->name('sklad.users.index');
    Route::get('/sklad/users/create', [SkladUsersController::class, 'create'])->name('sklad.users.create');
    Route::post('/sklad/users', [SkladUsersController::class, 'store'])->name('sklad.users.store');
    Route::get('/sklad/users/stats', [SkladUsersController::class, 'statistic'])->name('sklad.users.statistic');
    Route::delete('/sklad/users/destroy/{user}', [SkladUsersController::class, 'destroy'])->name('sklad.users.destroy');
    Route::get('/sklad/orders/gp', [SkladOrderController::class, 'index'])->name('sklad.orders.index');
    Route::get('/sklad/orders/dop', [SkladOrderController::class, 'addition'])->name('sklad.orders.addition');
    Route::get('/sklad/orders/komp', [SkladOrderController::class, 'equipm'])->name('sklad.orders.equipm');
    Route::post('/sklad/orders', [SkladOrderController::class, 'store'])->name('sklad.orders.store');
    Route::get('/sklad/orders/settings', [SkladOrderController::class, 'settings'])->name('sklad.orders.settings');
    Route::get('/sklad/orders/create', [SkladOrderController::class, 'create'])->name('sklad.orders.create');
    Route::get('/sklad/orders/stats', [SkladOrderController::class, 'statistic'])->name('sklad.orders.statistic');
    Route::post('/sklad/send-invoice', [SkladOrderController::class, 'sendInvoiceRequest'])->name('sklad.send-invoice');
    Route::post('/sklad/orders/pick/fetch', [SkladOrderController::class, 'fetchPickOrders'])
        ->name('sklad.orders.pick.fetch');
    Route::get('/sklad/orders/pick', [SkladOrderController::class, 'pickPage'])
        ->name('sklad.orders.pick');
    Route::post('/sklad/orders/accept/fetch', [SkladOrderController::class, 'fetchAcceptOrders'])->name('sklad.orders.accept.fetch');
    Route::get ('/sklad/orders/accept',       [SkladOrderController::class, 'showAcceptOrders'])->name('sklad.orders.accept');

    Route::post('/sklad/scan/send', [SkladScanController::class, 'sendTo1C'])
        ->name('sklad.scan.send');

    Route::post('/sklad/scan/store', [SkladScanController::class, 'store'])->name('sklad.scan.store');

    // активная ячейка
    Route::post('/sklad/scan/session/cell', [SkladScanController::class, 'setCell'])->name('sklad.scan.session.cell');
    Route::get('/sklad/scan/session',        [SkladScanController::class, 'getState'])->name('sklad.scan.session.state');
    Route::delete('/sklad/scan/session',     [SkladScanController::class, 'clearCell'])->name('sklad.scan.session.clear');

    // запись позиции документа (scan_position_document)
    Route::post('/sklad/scan/position', [SkladScanController::class, 'storePosition'])->name('sklad.scan.position.store');

    // Новый маршрут сканирования
    Route::post('/sklad/scan', [\App\Http\Controllers\Sklad\SkladScanController::class, 'scan'])->name('sklad.scan');

    // запись скана
    Route::post('/sklad/scan/store', [SkladScanController::class, 'store'])
        ->name('sklad.scan.store')
        ->middleware('auth'); // убери, если нужно и для неавторизованных

// просмотреть (опционально)
    Route::get('/sklad/scan', [SkladScanController::class, 'index'])
        ->name('sklad.scan.index')
        ->middleware('auth');
});
