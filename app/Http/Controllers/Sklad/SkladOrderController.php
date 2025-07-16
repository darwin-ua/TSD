<?php

namespace App\Http\Controllers\Sklad;

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


}

