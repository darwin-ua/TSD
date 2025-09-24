<?php

namespace App\Http\Controllers\Admin;

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


class AdminController extends Controller
{

    public function summaryData($clientName = null, $groupName = null)
    {
        if (!$clientName || !$groupName) {
            $currentUser = auth()->user();
            $clientName = $currentUser->name;
            $groupParts = explode(':', str_replace(' ', '', $currentUser->group));
            $groupName = $groupParts[1] ?? '';
        }

        $summary = [
            'overpay' => 0,
            'debt' => 0,
            'delivered' => 0,
            'unallocated' => 0,
        ];

        try {
            $summaryResponse = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
                ->acceptJson()
                ->timeout(10)
                ->post('http://185.112.41.230/darvin_test/hs/lk/summaryData', [
                    'client' => $clientName,
                    'organiz' => $groupName,
                ]);

            $raw = $summaryResponse->body();
            Log::info('Сырой ответ от summaryData:', ['body' => $raw]);

            if ($summaryResponse->successful()) {
                $decoded = json_decode($raw, true);

                if ($decoded === null) {
                    Log::error('Ошибка при первом json_decode: ' . json_last_error_msg());
                    return $summary;
                }

                // Если ответ вложенный — достаём поле 'body'
                if (isset($decoded['body']) && is_string($decoded['body'])) {
                    $inner = $decoded['body'];

                    // Удаляем неразрывные пробелы и всё подобное
                    $inner = str_replace(["\u{A0}", "\xC2\xA0", "\xc2\xa0", "\xa0", "\u00A0"], ' ', $inner);
                    $inner = preg_replace('/\x{00A0}/u', ' ', $inner);

                    Log::info('Очищенное тело перед decode body:', ['cleanBody' => $inner]);

                    $summaryData = json_decode($inner, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Ошибка декодирования JSON из поля body: ' . json_last_error_msg(), ['cleanBody' => $inner]);
                        return $summary;
                    }
                } else {
                    $summaryData = $decoded;
                }

                // Основная проверка и обработка
                if ($summaryData && $summaryData['status'] === 'ok') {
                    foreach (['overpay', 'debt', 'delivered', 'unallocated'] as $key) {
                        $rawValue = $summaryData[$key] ?? '0';
                        $cleaned = preg_replace('/[^\d,.-]/u', '', $rawValue);
                        $summary[$key] = (float) str_replace(',', '.', $cleaned);
                    }
                } else {
                    Log::warning('Некорректный ответ summaryData после парсинга: ' . json_encode($summaryData));
                }

            } else {
                Log::error('Ошибка получения summaryData: HTTP ' . $summaryResponse->status());
            }

        } catch (\Throwable $e) {
            Log::error('Исключение при запросе summaryData: ' . $e->getMessage());
        }

        return $summary;
    }

    private function fetchOrders($clientName, $groupName): array
    {
        $cacheKey = "orders_{$clientName}_{$groupName}";
        $refresh = request()->has('refresh');

        $orders = Cache::get($cacheKey);

        if ($refresh || !$orders) {
            try {
                $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
                    ->acceptJson()
                    ->timeout(30)
                    ->post('http://185.112.41.230/prod/hs/lk/DataOrders', [
                        'client' => $clientName,
                        'organiz' => $groupName,
                    ]);

                if ($response->successful()) {
                    $responseBody = preg_replace('/[\x{A0}\s]+/u', ' ', $response->body());
                    preg_match_all('/"(.*?)"/', $responseBody, $matches);
                    $rows = $matches[1];

                    $orders = [];
                    $currentOrder = [];
                    $orderKeys = [
                        'кнтНомерЗаказаLogiKal', 'Ссылка', 'Контрагент', 'МенеджерСоставившийРасчет',
                        'Номер', 'Организация', 'Ответственный', 'АдресДоставки',
                        'кнтСумма', 'ПлановаяДатаПроизводства', 'кнтСтатус', 'ДокументОснование', 'СтатусОплаты'
                    ];

                    foreach ($rows as $row) {
                        $keyValue = explode(':', trim($row), 2);
                        if (count($keyValue) === 2) {
                            $currentOrder[$keyValue[0]] = $keyValue[1];
                        }

                        if (count($currentOrder) === count($orderKeys)) {
                            $orders[] = $currentOrder;
                            $currentOrder = [];
                        }
                    }

                    Cache::put($cacheKey, $orders, now()->addMinutes(10));
                }
            } catch (\Throwable $e) {
                Log::error('Ошибка при fetchOrders: ' . $e->getMessage());
            }
        }

        return is_array($orders) ? $orders : [];
    }

    public function index()
    {
        $admins = User::where('role_id', 1)->get();
        $currentAdmin = auth()->user();

        $clientName = $currentAdmin->name;
        $groupName = $currentAdmin->group;
        $groupNameParts = explode(':', str_replace(' ', '', $groupName));
        $groupName = $groupNameParts[1] ?? '';

        $ordersCacheKey = "main_orders_{$clientName}_{$groupName}";
        $financeCacheKey = "main_financialStats_{$clientName}_{$groupName}";
        $refresh = request()->has('refresh');

        // Получаем данные из кэша
        $orders = Cache::get($ordersCacheKey);
        $financialStats = Cache::get($financeCacheKey);

        if ($refresh) {
            try {
                // === Загрузка заказов ===
                $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
                    ->acceptJson()
                    ->timeout(30)
                    ->post('http://185.112.41.230/darvin_test/hs/lk/DataOrders', [
                        'client' => $clientName,
                        'organiz' => $groupName,
                    ]);

                if ($response->successful()) {
                    $responseBody = preg_replace('/[\x{A0}\s]+/u', ' ', $response->body());
                    preg_match_all('/"(.*?)"/', $responseBody, $matches);
                    $rows = $matches[1];

                    $orders = [];
                    $currentOrder = [];
                    $orderKeys = [
                        'кнтНомерЗаказаLogiKal', 'Ссылка', 'Контрагент', 'МенеджерСоставившийРасчет',
                        'Номер', 'ЛК', 'Организация', 'Ответственный', 'АдресДоставки',
                        'кнтСумма', 'ПлановаяДатаПроизводства', 'кнтСтатус', 'ДокументОснование', 'СтатусОплаты'
                    ];

                    foreach ($rows as $row) {
                        $keyValue = explode(':', trim($row), 2);
                        if (count($keyValue) === 2) {
                            $currentOrder[$keyValue[0]] = $keyValue[1];
                        }

                        if (count($currentOrder) === count($orderKeys)) {
                            $orders[] = $currentOrder;
                            $currentOrder = [];
                        }
                    }

                    Cache::put($ordersCacheKey, $orders, now()->addMinutes(10));
                } else {
                    Log::error('Ошибка запроса заказов на главной: статус ' . $response->status());
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Ошибка соединения заказов на главной: ' . $e->getMessage());
            }

            // === Подтягиваем финансы из summaryData ===
            $summary = $this->summaryData($clientName, $groupName);

            $financialStats = [
                'balance' => $summary['overpay'],
                'debt' => $summary['debt'],
                'paid' => 0, // если появится значение — можно заменить
                'deliveryDebt' => $summary['delivered'],
                'unallocated' => $summary['unallocated'],
            ];

            Cache::put($financeCacheKey, $financialStats, now()->addMinutes(10));
        }
        // === Подтягиваем финансы из summaryData ===
        $summary = $this->summaryData($clientName, $groupName);

        $financialStats = [
            'balance' => $summary['overpay'],
            'debt' => $summary['debt'],
            'paid' => 0, // если появится значение — можно заменить
            'deliveryDebt' => $summary['delivered'],
            'unallocated' => $summary['unallocated'],
        ];

        Cache::put($financeCacheKey, $financialStats, now()->addMinutes(10));

        $ordersSummary = [
            'total' => 0,
            'cancelled' => 0,
            'in_work' => 0,
            'delivery' => 0,
        ];

// Получаем заказы
        $orders = $this->fetchOrders($clientName, $groupName);
        $ordersSummary['total'] = count($orders);

        foreach ($orders as $order) {
            $status = mb_strtolower(trim($order['кнтСтатус'] ?? ''));

            if (str_contains($status, 'відм')) {
                $ordersSummary['cancelled']++;
            } elseif (str_contains($status, 'достав')) {
                $ordersSummary['delivery']++;
            } elseif (str_contains($status, 'выпол')) {
                $ordersSummary['in_work']++;
            }
        }


        return view('admin.index', compact(
            'currentAdmin', 'admins',  'financialStats', 'ordersSummary'
        ));
    }

    public function showFtpGalleryRaw($orderId)
    {
        try {
            $files = Storage::disk('ftp')->files($orderId);
            $html = "<h2>Фото по заказу №{$orderId}</h2><div style='display: flex; flex-wrap: wrap;'>";

            foreach ($files as $file) {
                if (preg_match('/\.(jpe?g|png)$/i', $file)) {
                    $filename = basename($file);
                    $localPath = "ftp_cache/{$filename}";

                    if (!Storage::disk('public')->exists($localPath)) {
                        $content = Storage::disk('ftp')->get($file);
                        if (!empty($content)) {
                            Storage::disk('public')->put($localPath, $content);
                        }
                    }

                    $url = asset("storage/ftp_cache/{$filename}");
                    $html .= "<div style='margin:10px'><img src='{$url}' style='max-height:200px; border:1px solid #ccc;'></div>";
                }
            }

            $html .= "</div>";
            return response($html);

        } catch (\Throwable $e) {
            return response("<p style='color:red;'>Ошибка: " . $e->getMessage() . "</p>", 500);
        }
    }

    public function getFtpImage($orderId, $itemNumber)
    {
        $formats = ['jpg', 'jpeg', 'png'];
        $base = "{$orderId}/{$itemNumber}";

        foreach ($formats as $ext) {
            $ftpPath = "{$base}.{$ext}";

            if (Storage::disk('ftp')->exists($ftpPath)) {
                $contents = Storage::disk('ftp')->get($ftpPath);

                if (!empty($contents)) {
                    $localDir = "ftp_cache/{$orderId}";
                    $localPath = "{$localDir}/" . basename($ftpPath);

                    // Создаём папку, если нет
                    if (!Storage::disk('public')->exists($localDir)) {
                        Storage::disk('public')->makeDirectory($localDir);
                    }

                    Storage::disk('public')->put($localPath, $contents);

                    return response()->json([
                        'url' => asset("storage/{$localPath}")
                    ]);
                }
            }
        }

        return response()->json([
            'url' => asset('storage/files/no_image.png')
        ]);
    }





//    public function getFtpImage($orderId, $itemNumber)
//    {
//        $formats = ['jpg', 'jpeg', 'png', 'JPG'];
//        $base = "{$orderId}/{$itemNumber}";
//
//        foreach ($formats as $ext) {
//            $ftpPath = "{$base}.{$ext}";
//
//            if (Storage::disk('ftp')->exists($ftpPath)) {
//                \Log::info("✅ Файл найден на FTP: {$ftpPath}");
//
//                $contents = Storage::disk('ftp')->get($ftpPath);
//
//                if (!empty($contents)) {
//                    $localPath = "ftp_cache/" . basename($ftpPath);
//                    Storage::disk('public')->put($localPath, $contents);
//
//                    \Log::info("📥 Сохранено в public: {$localPath}");
//
//                    return response()->json([
//                        'url' => asset('storage/' . $localPath)
//                    ]);
//                } else {
//                    \Log::warning("⚠️ Файл пустой или не получен: {$ftpPath}");
//                }
//            } else {
//                \Log::warning("❌ Файл не найден на FTP: {$ftpPath}");
//            }
//        }
//
//        \Log::error("❌ Не удалось найти ни одного файла для {$base}");
//        return response()->json([
//            'url' => asset('storage/files/no_image.png')
//        ]);
//    }




// Контроллер
    public function getOrderDetails($number)
    {
        try {
            // Отправка запроса с базовой авторизацией и заголовками
            $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post('http://185.112.41.230/darvin_test/hs/lk/detailsOrder', [
                    'number' => $number,
                ]);

            // Логирование исходного ответа для диагностики
            \Log::info('Response from server: ' . $response->getBody());

            // Декодируем ответ в ассоциативный массив
            $responseData = json_decode($response->getBody(), true);

            // Проверяем, что данные успешно получены и не пусты
            if (is_array($responseData) && !empty($responseData)) {
                // Возвращаем исправленные данные
                return $responseData;
            } else {
                // Если данные пусты или в неверном формате, возвращаем ошибку
                return [
                    'error' => true,
                    'message' => 'Invalid response format or empty response',
                ];
            }
        } catch (\Exception $e) {
            // Обработка ошибок
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }


// Функция для исправления формата данных
    private function fixInvalidData($data)
    {
        $fixedData = [];

        // Пример исправления, если приходит массив с некорректными ключами или форматами
        foreach ($data as $item) {
            if (isset($item['Номенклатура']) && isset($item['Количество']) && isset($item['Цена'])) {
                // Преобразуем значения в правильные типы, если требуется
                $fixedData[] = [
                    'Номенклатура' => (string) $item['Номенклатура'],
                    'Количество' => (int) $item['Количество'],
                    'Цена' => (float) str_replace(',', '', $item['Цена']), // Убираем запятые, если это нужно
                ];
            }
        }

        return $fixedData;
    }




}

