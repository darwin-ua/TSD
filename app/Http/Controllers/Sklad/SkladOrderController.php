<?php

namespace App\Http\Controllers\Sklad;

use Illuminate\Support\Facades\Session;


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

class SkladOrderController extends Controller
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


        return view('sklad.orders.index', compact('currentAdmin', 'admins'));
    }

    public function addition()
    {
        $admins = User::where('role_id', 1)->get();
        $currentAdmin = auth()->user();


        return view('sklad.orders.addition', compact('currentAdmin', 'admins'));
    }
    public function equipm()
    {
        $admins = User::where('role_id', 1)->get();
        $currentAdmin = auth()->user();


        return view('sklad.orders.equipm', compact('currentAdmin', 'admins'));
    }

    public function create()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();

        return view('sklad.orders.create',compact('currentAdmin','admins'));
    }

    public function statistic()
    {
        $currentAdmin = auth()->user();
        $admins = User::where('role_id', 1)->get();

        return view('sklad.orders.statistic', compact('currentAdmin','admins'));
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
    public function finishAcceptance(Request $request)
    {
        // 1) Валидация JSON
        $data = $request->validate([
            'Номер'                       => 'required|string',
            'Позиции'                     => 'required|array|min:1',
            'Позиции.*.НомерСтроки'       => 'required|integer|min:1',
            'Позиции.*.НовоеКоличество'   => 'nullable|numeric',
            'Позиции.*.СканДельта'        => 'nullable|numeric',
        ]);

        // Логируем то, что пришло от клиента
        \Log::info('FinishAcceptance: входящий запрос от фронта', [
            'payload' => $data,
        ]);

        // 2) Адрес сервиса FinishAcceptance
        $url = 'http://192.168.170.105/PROD_copy/hs/tsd/FinishAcceptance';

        try {
            // 3) Отправляем в 1С с Basic Auth (жёстко заданные креды)
            $user = \Illuminate\Support\Facades\Auth::user();
            $login = (string) $user->name;
            $password = (string) $user->parol_1c;
            Log::info('DEBUG login', [
                'len' => $user->name,
                'hex' => bin2hex($user->name),
            ]);

            $resp = \Illuminate\Support\Facades\Http::withBasicAuth($login, $password)
                ->acceptJson()
                ->timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json; charset=utf-8'
                ])
                ->post($url, $data);

            // 4) Возвращаем клиенту ответ 1С «как есть»
            return response($resp->body(), $resp->status())
                ->withHeaders([
                    'Content-Type' => $resp->header('Content-Type', 'application/json; charset=utf-8')
                ]);

        } catch (\Throwable $e) {
            \Log::error('FinishAcceptance error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok'    => false,
                'error' => 'Gateway error: '.$e->getMessage(),
            ], 502);
        }
    }
    public function fetchPickOrders(\Illuminate\Http\Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        $user = \Illuminate\Support\Facades\Auth::user();
        $login = (string) $user->name;
        $password = (string) $user->parol_1c;
        Log::info('DEBUG login', [
            'len' => $user->name,
            'hex' => bin2hex($user->name),
        ]);

        $resp = \Illuminate\Support\Facades\Http::withBasicAuth($login, $password)
            ->acceptJson()->timeout(60)
            ->post('http://192.168.170.105/PROD_copy/hs/tsd/AcceptGoodWarehouse', []);

        if ($request->boolean('debug')) {
            return response($resp->body(), $resp->status())
                ->withHeaders(['Content-Type' => 'application/json; charset=utf-8']);
        }

        if (!$resp->successful()) {
            \Log::error('1C HTTP error', ['status'=>$resp->status(),'body'=>mb_substr($resp->body(),0,1000)]);
            return response()->json(['ok'=>false,'msg'=>'Ошибка 1С: '.$resp->status()], 200);
        }

        $raw = $resp->body();
        $json = json_decode($raw, true);

        if (!is_array($json)) {
            $json = $this->sanitize1CJson($raw);
        }

        if (!is_array($json)) {
            \Log::error('Bad JSON from 1C (even after fix)', ['raw'=>mb_substr($raw,0,1000)]);
            return response()->json(['ok'=>false,'msg'=>'Некорректный JSON от 1С'], 200);
        }

        $orders = $json['documents'] ?? [];
        if ($orders instanceof \Illuminate\Support\Collection) $orders = $orders->toArray();
        elseif (is_string($orders)) $orders = json_decode($orders, true) ?: [];

        Log::info('RAW response from 1C:', ['raw' => $raw]);
        Log::info('Parsed JSON:', ['json' => $json]);

        session(['pick_orders' => $orders]);

        $count = is_array($orders) ? count($orders) : 0;
        $first = $count ? [
            'Ссылка' => $orders[0]['Ссылка'] ?? null,
            'Статус' => $orders[0]['Статус'] ?? null,
        ] : null;

        \Log::info('pick.fetch saved to session', ['count'=>$count,'first'=>$first]);

        return response()->json([
            'ok'       => true,
            'count'    => $count,
            'first'    => $first,
            'redirect' => route('sklad.orders.pick'),
        ], 200);
    }
    public function fetchAcceptOrders(Request $request)
    {
        $payload = [
            'Исполнитель' => $request->input('Исполнитель', 'Кучеренко Денис'),
            'Статус'      => $request->input('Статус', 'КПоступлению'),
        ];


        Log::info('accept.fetch -> payload to 1C', $payload);

        $user = \Illuminate\Support\Facades\Auth::user();
        $login = (string) $user->name;
        $password = (string) $user->parol_1c;
        Log::info('DEBUG login', [
            'len' => $user->name,
            'hex' => bin2hex($user->name),
        ]);

        $resp = \Illuminate\Support\Facades\Http::withBasicAuth($login, $password)
            ->timeout(60)
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json; charset=utf-8',
            ])
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('http://192.168.170.105/PROD_copy/hs/tsd/AcceptanceGoods');

        if ($request->boolean('debug')) {
            return response($resp->body(), $resp->status())
                ->withHeaders(['Content-Type' => 'application/json; charset=utf-8']);
        }

        if (!$resp->successful()) {
            Log::error('1C HTTP error (accept)', ['status'=>$resp->status(),'body'=>mb_substr($resp->body(),0,1000)]);
            return response()->json(['ok'=>false,'msg'=>'Ошибка 1С: '.$resp->status()], 200);
        }

        $raw  = $resp->body();
        Log::info('RAW (accept) response from 1C:', ['raw' => $raw]);

        $json = json_decode($raw, true);
        if (!is_array($json)) $json = $this->sanitize1CJson($raw);
        Log::info('Parsed (accept) JSON:', ['json' => $json]);

        // НОРМАЛИЗАЦИЯ: поддерживаем и {documents:[...]} и просто [...]
        $docs = [];
        if (is_array($json) && array_key_exists('documents', $json)) {
            $docs = is_array($json['documents']) ? $json['documents'] : (json_decode($json['documents'], true) ?: []);
        } elseif (is_array($json) && array_is_list($json)) {
            $docs = $json; // у тебя именно так
        } elseif (is_array($json) && !empty($json)) {
            $docs = [$json];
        }

        session(['accept_orders' => $docs]);

        $count = is_array($docs) ? count($docs) : 0;
        $first = $count ? [
            'Ссылка' => $docs[0]['Ссылка'] ?? null,
            'Статус' => $docs[0]['Статус'] ?? null,
        ] : null;

        Log::info('accept.fetch saved to session', ['count'=>$count,'first'=>$first]);

        return response()->json([
            'ok'       => true,
            'count'    => $count,
            'first'    => $first,
            'redirect' => route('sklad.orders.accept'),
        ], 200);
    }
    public function showAcceptOrders()
    {
        $orders = session('accept_orders', []);
        // раньше было: return response()->json($orders);
        return view('sklad.orders.accept', ['orders' => $orders]);
    }
    public function pickPage()
    {
        $orders = session('pick_orders', []);
        // на всякий случай добьём нормализацию
        if ($orders instanceof \Illuminate\Support\Collection) $orders = $orders->toArray();
        if (is_string($orders)) $orders = json_decode($orders, true) ?: [];

        return view('sklad.orders.pick', compact('orders'));
    }
    private function sanitize1CJson(string $raw): ?array
    {
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $raw);              // BOM
        $s = str_replace("\xC2\xA0", ' ', $s);                       // NBSP
        $s = preg_replace('/:\s*(?=,|\})/u', ': null', $s);          // "Ключ":,  -> null

        // убрать висячие запятые ",]" и ",}"
        $prev = null; $i = 0;
        while ($prev !== $s && $i < 5) {
            $prev = $s;
            $s = preg_replace('/,\s*([\]\}])/u', '$1', $s);
            $i++;
        }

        // ": }" -> ": null}"
        $s = preg_replace('/:\s*([\]\}])/', ': null$1', $s);

        $arr = json_decode($s, true);
        if (!is_array($arr) && preg_match('/^(.*[\}\]])/s', $s, $m)) {
            $arr = json_decode($m[1], true);
        }
        return is_array($arr) ? $arr : null;
    }

}

