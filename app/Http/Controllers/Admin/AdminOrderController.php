<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Models\Event;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AdminOrderController extends Controller
{

    public function sendInvoiceRequest(Request $request)
    {
        $currentAdmin = auth()->user();
        $groupNameParts = explode(':', str_replace(' ', '', $currentAdmin->group));
        $groupName = $groupNameParts[1] ?? '';

        $selectedOrders = json_decode($request->input('selected_orders'), true);

        Log::info('📤 Декодировано selected_orders:', $selectedOrders);

        $номерЗаказа = is_array($selectedOrders) && count($selectedOrders) > 0
            ? ($selectedOrders[0]['order'] ?? null)
            : null;


        $строки = [];

        if (is_array($selectedOrders)) {
            foreach ($selectedOrders as $order) {
                $sum = (float)str_replace([' ', ','], ['', '.'], $order['cost']);
                $baseDocument = $order['baseDocument'] ?? null;
               // $baseDocument = $order['baseDocument'] ?? '0000-000043'; // временно захардкодить для теста

                Log::info('🧾 Обработка строки заказа:', [
                    'order' => $order['order'] ?? null,
                    'baseDocument' => $order['baseDocument'] ?? '❌ отсутствует'
                ]);

                $строки[] = [
                    'СуммаПродукции' => $sum,
                    'СуммаУслуг' => 0,
                    'СуммаМатериалов' => 0,
                    'ДокументОснование' => $baseDocument,
                ];
            }
        }

        $data = [
            'Организация' => $groupName,
            'Дилер' => $currentAdmin->name,
            'ДилерАЙДИ' => (string)Auth::user()->id_lk,
            'НаименованиеПлательщика' => $request->input('payerName'),
            'ЕДРПОУПлательщика' => $request->input('edrpou'),
            'ЭлектроннаяПочтаПлательщика' => $request->input('email'),
            'ТелефонПлательщика' => $request->input('phone'),
            'Сумма' => (float)$request->input('sum'),
            'ЕдиницаИзмерения' => $request->input('unit'),
            'Комментарий' => 'Создано из ЛК Дилера',
            'ВыделятьМонтажОтдельнойСтрокойВСчете' => $request->input('separateInstall') === 'Так',
            'НуженДоговор' => $request->input('needContract') === 'Так',
            'МетодПолученияСчета' => 'Самовывоз',
            'МетодПолученияОтгрузочныхДокументов' => 'Самовывоз',
            'Бюджет' => $request->input('budgetOrg') === 'Так',
            'ПлатникПДВ' => $request->input('vatPayer') === 'Так',
            'ПИБКонтактноеЛицо' => $request->input('contactPerson'),
            'НомерЗаказа' => $номерЗаказа,
            'ЗаявкиРасчет' => [
                'Строка' => $строки
            ]
        ];

        try {
            Log::debug('=== Отправка заявки в 1С ===', [
                'user_id' => $currentAdmin->id,
                'user_name' => $currentAdmin->name,
                'id_lk' => $currentAdmin->id_lk,
                'request_data' => $request->all(),
                'payload' => $data
            ]);

            Log::info('📦 Отправка заявки в 1С: номер заказа', [
                'номерЗаказа' => $номерЗаказа,
            ]);

            $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
                ->acceptJson()
                ->timeout(180)
                ->post('http://185.112.41.230/darvin_test/hs/lk/creatingInvoice', $data);

            Log::info('📬 Ответ от 1С', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Заявка успешно отправлена',
                    'body' => $response->json()
                ]);
            } else {
                return response()->json([
                    'status' => $response->status(),
                    'message' => 'Ошибка 1С: ' . $response->status(),
                    'body' => $response->body()
                ], $response->status());
            }

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Ошибка HTTP: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Ошибка соединения с 1С: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function exportProductsToExcel(Request $request)
    {
        $products = json_decode($request->input('products'), true);
        return Excel::download(new ProductsExport($products), 'orders.xlsx');
    }

    public function index()
    {
        $admins = User::where('role_id', 1)->get();
        $currentAdmin = auth()->user();

        $clientName = $currentAdmin->name;
        $groupName = $currentAdmin->group;
        $groupNameParts = explode(':', str_replace(' ', '', $groupName));
        $groupName = $groupNameParts[1] ?? '';

        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        $dateFrom = request('date_from');
        $dateTo = request('date_to');

// ✅ Если даты не переданы — взять последние два месяца от текущей даты
        if (empty($dateFrom) || empty($dateTo)) {
            $dateFrom = now()->subMonths(2)->format('Y-m-d');
            $dateTo = now()->format('Y-m-d');
        }


        $orders = [];
//dd($clientName,$groupName,$dateFrom,$dateTo);
        try {
            Log::info('🕒 Отправка запроса к 1С', ['client' => $clientName, 'organiz' => $groupName]);
            $start = microtime(true);

            $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
                ->acceptJson()
                ->timeout(180)
                ->post('http://185.112.41.230/darvin_test/hs/lk/DataOrders', [
                    'client' => $clientName,
                    'organiz' => $groupName,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]);

            $duration = round(microtime(true) - $start, 2);
            Log::info("✅ Ответ от 1С получен за {$duration} сек. Статус: " . $response->status());

            if ($response->successful()) {
                $responseBody = $response->body();
                $responseBody = preg_replace('/\s+/', ' ', $responseBody);
                $responseBody = preg_replace('/[\x{A0}\s]+/u', ' ', $responseBody);

                preg_match_all('/"(.*?)"/', $responseBody, $matches);
                $rows = $matches[1];

                $currentOrder = [];
                $orderKeys = [
                    'кнтНомерЗаказаLogiKal', 'Ссылка', 'Контрагент', 'МенеджерСоставившийРасчет',
                    'Номер', 'Организация', 'Ответственный', 'АдресДоставки',
                    'кнтСумма', 'ПлановаяДатаПроизводства', 'кнтСтатус', 'ДокументОснование', 'СтатусОплаты', 'МенеджерДоставка','НомерДокДоп'
                ];

                foreach ($rows as $row) {
                    $row = trim($row);
                    if (!empty($row)) {
                        $keyValue = explode(':', $row, 2);
                        if (count($keyValue) === 2) {
                            $key = $keyValue[0];
                            $value = $keyValue[1];

                            if ($key === 'ДокументОснование') {
                                if (preg_match('/\b\d{4}-\d{6}\b/', $value, $docMatch)) {
                                    $value = $docMatch[0];
                                }
                            }

                            // Если начинается новый заказ — сохраняем предыдущий
                            if ($key === 'кнтНомерЗаказаLogiKal' && !empty($currentOrder)) {
                                foreach ($orderKeys as $k) {
                                    if (!array_key_exists($k, $currentOrder)) {
                                        $currentOrder[$k] = null;
                                    }
                                }
                                $orders[] = $currentOrder;
                                $currentOrder = [];
                            }

                            $currentOrder[$key] = $value;
                        }
                    }
                }

// Добавим последний заказ
                if (!empty($currentOrder)) {
                    foreach ($orderKeys as $k) {
                        if (!array_key_exists($k, $currentOrder)) {
                            $currentOrder[$k] = null;
                        }
                    }
                    $orders[] = $currentOrder;
                }


            } else {
                Log::error('❌ Ошибка запроса на сервер: статус ' . $response->status());
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('❌ Ошибка соединения с сервером 1С: ' . $e->getMessage());
        }

        return view('admin.orders.index', compact('currentAdmin', 'admins', 'orders'));
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
//        $orders = [];
//
//        try {
//            Log::info('🕒 Отправка запроса к 1С', ['client' => $clientName, 'organiz' => $groupName]);
//            $start = microtime(true);
//
//            $response = Http::withBasicAuth('КучеренкоД', 'NitraPa$$@0@!')
//                ->acceptJson()
//                ->timeout(180)
//                ->post('http://185.112.41.230/darvin_test/hs/lk/DataOrders', [
//                    'client' => $clientName,
//                    'organiz' => $groupName,
//                ]);
//
//            $duration = round(microtime(true) - $start, 2);
//            Log::info("✅ Ответ от 1С получен за {$duration} сек. Статус: " . $response->status());
//
//            if ($response->successful()) {
//                $responseBody = $response->body();
//                $responseBody = preg_replace('/\s+/', ' ', $responseBody);
//                $responseBody = preg_replace('/[\x{A0}\s]+/u', ' ', $responseBody);
//
//                preg_match_all('/"(.*?)"/', $responseBody, $matches);
//                $rows = $matches[1];
//
//                $currentOrder = [];
//                $orderKeys = [
//                    'кнтНомерЗаказаLogiKal', 'Ссылка', 'Контрагент', 'МенеджерСоставившийРасчет',
//                    'Номер', 'Организация', 'Ответственный', 'АдресДоставки',
//                    'кнтСумма', 'ПлановаяДатаПроизводства', 'кнтСтатус', 'ДокументОснование', 'СтатусОплаты'
//                ];
//
//                foreach ($rows as $row) {
//                    $row = trim($row);
//                    if (!empty($row)) {
//                        $keyValue = explode(':', $row, 2);
//                        if (count($keyValue) === 2) {
//                            $key = $keyValue[0];
//                            $value = $keyValue[1];
//
//                            if ($key === 'ДокументОснование') {
//                                if (preg_match('/\b\d{4}-\d{6}\b/', $value, $docMatch)) {
//                                    $value = $docMatch[0];
//                                }
//                            }
//
//                            $currentOrder[$key] = $value;
//                        }
//
//                        if (count($currentOrder) === count($orderKeys)) {
//                            $orders[] = $currentOrder;
//                            $currentOrder = [];
//                        }
//                    }
//                }
//
//            } else {
//                Log::error('❌ Ошибка запроса на сервер: статус ' . $response->status());
//            }
//        } catch (\Illuminate\Http\Client\ConnectionException $e) {
//            Log::error('❌ Ошибка соединения с сервером 1С: ' . $e->getMessage());
//        }
//
//        return view('admin.orders.index', compact('currentAdmin', 'admins', 'orders'));
//    }

    public function create()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();

        return view('admin.orders.create',compact('currentAdmin','admins'));
    }

    public function statistic()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();

        return view('admin.orders.statistic', compact('currentAdmin','admins'));
    }

        public function store(Request $request)
    {
        $user = Auth::user();
        // Получаем данные из запроса
        $date = $request->input('date');
        $quantity = $request->input('quantity');

        // Обрабатываем данные (например, сохраняем их в базу данных)

        return response()->json(['message' => 'Данные успешно получены и обработаны'], 200);
    }


}

