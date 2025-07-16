<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Admin\AdminController;

use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminSheduleController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentController;

use App\Http\Controllers\Admin\AdminAlertController;

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

Route::get('/test', [TestController::class, 'getUsers']);

Route::get('/', function () {
    return redirect()->route('login');
});


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

//    Route::get('/member', [HomeController::class, 'member'])->name('sklad.member');
//

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



    // Новый маршрут сканирования
    Route::post('/sklad/scan', [\App\Http\Controllers\Sklad\SkladScanController::class, 'scan'])->name('sklad.scan');
});


//Route::middleware(['auth', 'update.cart'])->group(function () {
//    Route::get('/restricted-access', function () {
//        return view('restricted_access');
//    })->name('restricted.access');
//
//    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
//    Route::get('/home', [HomeController::class, 'index'])->name('home');
//
//
//    Route::get('/sklad', [HomeController::class, 'member'])->name('sklad');
//    Route::delete('/cart/remove/{eventId}', [HomeController::class, 'removeFromCart'])->name('cart.remove');
//    Route::delete('/doings/remove/{eventId}', [HomeController::class, 'removeFromDoings'])->name('doings.remove');
//
//
//    Route::post('/delete-user-data', [UserDataController::class, 'deleteUserData'])->name('deleteUserData');
//
//    Route::get('/client', [HomeController::class, 'member'])->name('member');
//
//    Route::get('/open/{id}', [HomeController::class, 'open'])->name('admin.open');
//    Route::get('/open/{events_id}/lesson/{lesson_id}', [HomeController::class, 'openLesson'])->name('admin.openLesson');
//
//    Route::get('/admin/alerts/count', [AdminAlertController::class, 'count'])->name('alerts.count');
//    Route::get('/admin/get-orders-with-status', [AdminController::class, 'getOrdersWithStatus']);
//    Route::get('/admin/get-order-details/{number}', [AdminController::class, 'getOrderDetails']);
//    Route::get('/admin/update-order-status/{orderId}', [AdminController::class, 'updateOrderStatus']);
//    Route::get('/admin/summary', [AdminController::class, 'summaryData']);
//    Route::get('/admin/get-ftp-image/{orderId}/{itemNumber}', [AdminController::class, 'getFtpImage']);
//
//    Route::get('/test-write', function () {
//        Storage::disk('public')->put('ftp_cache/test.txt', 'hello world');
//        return 'OK';
//    });
//
//    Route::get('/admin/ftp-gallery/{orderId}', [AdminController::class, 'showFtpGalleryRaw']);
//
//
//
//    Route::get('/admin/payments/all', [AdminPaymentController::class, 'index'])->name('admin.finances.index');
//    Route::get('/admin/payments/create', [AdminPaymentController::class, 'create'])->name('admin.payments.create');
//    Route::post('/admin/payments', [AdminPaymentController::class, 'store'])->name('admin.payments.store');
//
//
//
//    Route::get('/admin/users/all', [AdminUsersController::class, 'index'])->name('admin.users.index');
//    Route::get('/admin/users/create', [AdminUsersController::class, 'create'])->name('admin.users.create');
//    Route::post('/admin/users', [AdminUsersController::class, 'store'])->name('admin.users.store');
//    Route::get('/admin/users/stats', [AdminUsersController::class, 'statistic'])->name('admin.users.statistic');
//    Route::delete('/admin/users/destroy/{user}', [AdminUsersController::class, 'destroy'])->name('admin.users.destroy');
//
//    Route::get('/admin/shedules/all', [AdminSheduleController::class, 'index'])->name('admin.shedules.index');
//    Route::get('/admin/shedules/create', [AdminSheduleController::class, 'create'])->name('admin.shedules.create');
//    Route::delete('/admin/shedules/{shedule}', [AdminSheduleController::class, 'destroy'])->name('admin.shedules.destroy');
//    Route::post('/admin/shedules', [AdminSheduleController::class, 'store'])->name('admin.shedules.store');
//    Route::get('/admin/shedules/settings', [AdminSheduleController::class, 'settings'])->name('admin.shedules.settings');
//
//    Route::get('/admin/orders/all', [AdminOrderController::class, 'index'])->name('admin.orders.index');
//    Route::post('/admin/orders', [AdminOrderController::class, 'store'])->name('admin.orders.store');
//    Route::get('/admin/orders/settings', [AdminOrderController::class, 'settings'])->name('admin.orders.settings');
//    Route::get('/admin/orders/create', [AdminOrderController::class, 'create'])->name('admin.orders.create');
//    Route::get('/admin/orders/stats', [AdminOrderController::class, 'statistic'])->name('admin.orders.statistic');
//    Route::post('/admin/send-invoice', [AdminOrderController::class, 'sendInvoiceRequest'])->name('admin.send-invoice');
//
//
//    Route::get('/admin/events/all', [AdminEventController::class, 'index'])->name('admin.events.index');
//    Route::get('/admin/events/stats', [AdminEventController::class, 'statistic'])->name('admin.events.statistic');
//    Route::get('/admin/events/settings', [AdminEventController::class, 'settings'])->name('admin.events.settings');
//    Route::get('/admin/events/create', [AdminEventController::class, 'create'])->name('admin.events.create');
//    Route::post('/admin/events/st/{number}', [AdminEventController::class, 'searchTown'])->name('admin.events.searchTown')->withoutMiddleware(['auth']);
//    Route::get('/admin/events/lesson/{id}', [AdminEventController::class, 'lesson'])->name('admin.events.lesson');
//    Route::post('/admin/events/sl', [AdminEventController::class, 'lessonSaveData'])->name('admin.events.lessonSaveData');
//    Route::post('/admin/events', [AdminEventController::class, 'store'])->name('admin.events.store');
//    Route::get('/admin/events/{event}/edit', [AdminEventController::class, 'edit'])->name('admin.events.edit');
//    Route::put('/admin/events/{event}', [AdminEventController::class, 'update'])->name('admin.events.update');
//    Route::delete('/admin/events/{event}', [AdminEventController::class, 'destroy'])->name('admin.events.destroy');
//    Route::get('/admin/event/{id}', [AdminEventController::class, 'show'])->name('admin.events.show');
//    Route::get('/admin/events/{event}/edit', [AdminEventController::class, 'edit'])->name('admin.events.edit');
//    Route::put('/admin/events/{event}', [AdminEventController::class, 'update'])->name('admin.events.update');
//    Route::post('/admin/events/upload-video', [AdminEventController::class, 'uploadVideo'])->name('admin.events.uploadVideo');
//    Route::get('/admin/events/{id}/rl/{lesson}', [AdminEventController::class, 'redactLesson'])->name('admin.events.redactLesson');
//    Route::post('/admin/events/{id}/rl/{lesson}/update', [AdminEventController::class, 'redactLessonUpdate'])->name('admin.events.redactLessonUpdate');
//
//});
//
//
