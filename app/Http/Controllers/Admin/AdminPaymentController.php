<?php
namespace App\Http\Controllers\Admin;

use App\Exports\PaymentsExport; // Импортируем новый класс
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Facades\Excel;  // Импорт фасада Excel
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class AdminPaymentController extends Controller
{
    public function exportPaymentsToExcel(Request $request)
    {
        // Получаем данные для экспорта из запроса или другого источника
        $payments = json_decode($request->input('payments'), true);

        // Экспортируем данные в Excel
        return Excel::download(new PaymentsExport($payments), 'payments.xlsx');
    }
    public function index()
    {
        $admins = User::where('role_id', 1)->get();
        $currentAdmin = auth()->user();

        $clientName = $currentAdmin->name;
        $groupName = $currentAdmin->group;
        $groupNameParts = explode(':', str_replace(' ', '', $groupName));
        $groupName = $groupNameParts[1] ?? '';

        $payments = [];
        $summary = $this->summaryData($clientName, $groupName);

        try {
            Log::info('📨 Отправка запроса на allTransactions', ['client' => $clientName, 'organiz' => $groupName]);
            $start = microtime(true);

            $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
                ->acceptJson()
                ->timeout(180)
                ->post('http://185.112.41.230/darvin_test/hs/lk/allTransactions', [
                    'client' => $clientName,
                    'organiz' => $groupName,
                ]);

            $duration = round(microtime(true) - $start, 2);
            Log::info("✅ Ответ от allTransactions за {$duration} сек. Статус: " . $response->status());

            if ($response->successful()) {
                $raw = $response->body();
                $clean = str_replace(["\u{A0}", "\xC2\xA0", ";"], [" ", " ", ","], $raw);

                $pairs = json_decode($clean, true, 512, JSON_THROW_ON_ERROR);

                if (!is_array($pairs)) {
                    throw new \Exception("Неверный формат JSON. Ожидался массив.");
                }

                foreach ($pairs as $line) {
                    if (!is_array($line)) continue;

                    $record = [];
                    foreach ($line as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                        }
                        $record[trim($key)] = trim((string)$value);
                    }

                    if (!empty($record)) {
                        $payments[] = $record;
                    }
                }

            } else {
                Log::error('❌ Ошибка получения платежей: статус ' . $response->status());
            }

        } catch (\JsonException $e) {
            Log::error('❌ Ошибка декодирования JSON: ' . $e->getMessage(), ['body' => $response->body()]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('❌ Ошибка соединения при получении платежей: ' . $e->getMessage());
        }

        return view('admin.payments.index', compact('currentAdmin', 'admins', 'payments', 'summary'));
    }


//    public function index()
//    {
//        $admins = User::where('role_id', 1)->get();
//        $currentAdmin = auth()->user();
//
//        $clientName = $currentAdmin->name;
//        $groupName = $currentAdmin->group;
//        $groupNameParts = explode(':', str_replace(' ', '', $groupName));
//        $groupName = $groupNameParts[1] ?? '';
//
//        $cacheKey = "transactions_{$clientName}_{$groupName}";
//        $refresh = request()->has('refresh');
//
//        $payments = [];
//
//        $summary = $this->summaryData($clientName, $groupName);
//
//        if (!$refresh && Cache::has($cacheKey)) {
//            $payments = Cache::get($cacheKey);
//        } else {
//            try {
//                $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
//                    ->acceptJson()
//                    ->timeout(180)
//                    ->post('http://185.112.41.230/darvin_test/hs/lk/allTransactions', [
//                        'client' => $clientName,
//                        'organiz' => $groupName,
//                    ]);
//
//                if ($response->successful()) {
//                    $raw = $response->body();
//                    $clean = str_replace(["\u{A0}", "\xC2\xA0", ";"], [" ", " ", ","], $raw);
//
//                    $pairs = json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
//
//                    if (!is_array($pairs)) {
//                        throw new \Exception("Неверный формат JSON. Ожидался массив.");
//                    }
//
//                    foreach ($pairs as $line) {
//                        // Проверим, что это массив с нужными ключами
//                        if (!is_array($line)) {
//                            continue;
//                        }
//
//                        $record = [];
//                        foreach ($line as $key => $value) {
//                            if (is_array($value)) {
//                                $value = json_encode($value, JSON_UNESCAPED_UNICODE); // если вдруг вложенный массив
//                            }
//                            $record[trim($key)] = trim((string)$value);
//                        }
//
//                        if (!empty($record)) {
//                            $payments[] = $record;
//                        }
//                    }
//
//                    Cache::put($cacheKey, $payments, now()->addMinutes(10));
//                }
//                else {
//                    Log::error('Ошибка получения платежей: статус ' . $response->status());
//                }
//            } catch (\JsonException $e) {
//                Log::error('Ошибка декодирования JSON: ' . $e->getMessage(), ['body' => $response->body()]);
//            } catch (\Illuminate\Http\Client\ConnectionException $e) {
//                Log::error('Ошибка соединения при получении платежей: ' . $e->getMessage());
//            }
//        }
//        return view('admin.payments.index', compact('currentAdmin', 'admins', 'payments', 'summary'));
//
////        return view('admin.payments.index', compact('currentAdmin', 'admins', 'payments', 'summary'));
//    }

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
                ->timeout(180)
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

}

