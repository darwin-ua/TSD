<?php

namespace App\Http\Controllers\Sklad;

use App\Http\Controllers\Controller;
use App\Models\ScanCode;
use App\Models\ScanPositionDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;



class SkladScanController extends Controller
{
    /** длина штрихкода в scan_position_document (синхронизируй с БД) */
    private const POS_CODE_MAX = 11; // если увеличишь колонку, поменяй здесь

    /** общий ключ кеша активной ячейки */
    protected function cellCacheKey(): string
    {
        return 'scan:active_cell:user:' . (Auth::id() ?? 'guest');
    }

    /** Удобный контекст для логирования */
    protected function ctx(Request $r, array $extra = []): array
    {
        return array_merge([
            'ip'      => $r->ip(),
            'userId'  => Auth::id(),
            'user'    => Auth::user()->name ?? null,
            'route'   => $r->path(),
        ], $extra);
    }

    public function searchBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string|max:64',
        ]);

        $barcode = trim((string)$request->input('barcode'));

        // URL лучше вынести в конфиг/ ENV, но оставлю дефолт для быстрого старта
        $url = config('services.tsd.search_barcode_url', 'http://192.168.170.105/PROD_copy/hs/tsd/SearchBarcode');

        // Хелпер контекста для логов
        $ctx = function (array $extra = []) use ($request, $barcode, $url) {
            return array_merge([
                'ip'     => $request->ip(),
                'userId' => Auth::id(),
                'user'   => optional(Auth::user())->name,
                'route'  => $request->path(),
                'url'    => $url,
                'barcode'=> $barcode,
            ], $extra);
        };

        $t0 = microtime(true);
        Log::info('scan.searchBarcode: start', $ctx());

        try {
            // Готовим HTTP-клиент (логин/пароль лучше в .env)
            $login    = env('TSD_LOGIN',     'КучеренкоД');
            $password = env('TSD_PASSWORD',  'NitraPa$$@0@!');

            Log::info('scan.searchBarcode: request', $ctx([
                'payload' => ['barcode' => $barcode],
                'auth_user' => $login, // пароль в лог НЕ пишем
            ]));

            $resp = Http::withBasicAuth($login, $password)
                ->acceptJson()
                ->asJson()
                ->timeout(8)
                ->post($url, ['barcode' => $barcode]);

            $ms = (int) round((microtime(true) - $t0) * 1000);

            Log::info('scan.searchBarcode: response', $ctx([
                'status'       => $resp->status(),
                'ok'           => $resp->ok(),
                'duration_ms'  => $ms,
                'headers'      => $resp->headers(),
                'raw'          => $resp->body(), // если страшно — закомментируй
            ]));

            if (!$resp->ok()) {
                // Ошибка уровня HTTP от 1С
                return response()->json([
                    'ok'  => false,
                    'msg' => '1C HTTP ' . $resp->status(),
                    'raw' => $resp->body(),
                ], $resp->status());
            }

            // Парсим JSON безопасно
            $data = $resp->json();
            if (!is_array($data)) {
                Log::warning('scan.searchBarcode: invalid JSON, fallback to empty array', $ctx([
                    'raw' => $resp->body(),
                ]));
                $data = [];
            }

            // Преобразование items
            $items = [];
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $it) {
                    $items[] = [
                        'barcode'             => $it['barcode'] ?? $it['Штрихкод'] ?? $barcode,
                        'nomen'               => $it['nomen'] ?? $it['Номенклатура'] ?? null,
                        'characteristic'      => $it['characteristic'] ?? $it['Характеристика'] ?? null,
                        'package'             => $it['package'] ?? $it['Упаковка'] ?? null,
                        'nomen_guid'          => $it['nomen_guid'] ?? null,
                        'characteristic_guid' => $it['characteristic_guid'] ?? null,
                        'package_guid'        => $it['package_guid'] ?? null,
                    ];
                }
            }

            Log::info('scan.searchBarcode: parsed', $ctx([
                'items_count' => count($items),
            ]));

            return response()->json([
                'ok'    => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            $ms = (int) round((microtime(true) - $t0) * 1000);
            Log::error('scan.searchBarcode: error', $ctx([
                'duration_ms' => $ms,
                'error'       => $e->getMessage(),
                // при желании можно урезать трейс, чтобы не раздувать лог
                'trace'       => substr($e->getTraceAsString(), 0, 4000),
            ]));

            return response()->json([
                'ok'  => false,
                'msg' => 'Помилка сервера: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Сохранить запись о сканировании (scan_code)
     */
    public function store(Request $request)
    {
        Log::info('scan.store: incoming', $this->ctx($request, ['payload' => $request->all()]));

        // ВАЖНО: колонка scan_code.code должна вмещать эту длину (например VARCHAR(64))
        $validator = Validator::make($request->all(), [
            'code'         => 'required|string|max:64',
            'document_id'  => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'cell'         => 'nullable|string|max:191',
            'amount'       => 'nullable|numeric',
            'status'       => 'nullable|integer',
            'order_date'   => 'nullable|date',
        ]);

        if ($validator->fails()) {
            Log::warning('scan.store: validation failed', $this->ctx($request, ['errors' => $validator->errors()->toArray()]));
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $scan = ScanCode::create([
                'user_register' => Auth::user()->name ?? 'system',
                'document_id'   => $request->input('document_id', 0), // если NOT NULL в БД — 0 как заглушка
                'warehouse_id'  => $request->input('warehouse_id', 0),
                'user_id'       => Auth::id(),
                'cell'          => $request->input('cell', ''),
                'code'          => mb_substr((string)$request->input('code'), 0, 64),
                'order_date'    => now(),
                'amount'        => $request->input('amount', 1),
                'status'        => $request->input('status', 1),
            ]);

            Log::info('scan.store: insert ok', $this->ctx($request, ['scan_id' => $scan->id]));
            return response()->json(['ok' => true, 'id' => $scan->id]);
        } catch (QueryException $qe) {
            Log::error('scan.store: DB error', $this->ctx($request, [
                'message' => $qe->getMessage(),
                'sqlState'=> $qe->errorInfo[0] ?? null,
                'sqlCode' => $qe->errorInfo[1] ?? null,
            ]));
            return response()->json(['ok' => false, 'msg' => 'DB error: '.$qe->getMessage()], 500);
        } catch (Throwable $e) {
            Log::error('scan.store: fatal error', $this->ctx($request, ['message' => $e->getMessage()]));
            return response()->json(['ok' => false, 'msg' => 'Server error'], 500);
        }
    }

    /**
     * Установить активную ячейку (сессия + кеш)
     * body: { cell: "ГП-01-02", warehouse_id?: 1 }
     */
    public function setCell(Request $request)
    {
        Log::info('scan.session.cell: incoming', $this->ctx($request, ['payload' => $request->all()]));

        $validator = Validator::make($request->all(), [
            'cell'         => 'required|string|max:191',
            'warehouse_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            Log::warning('scan.session.cell: validation failed', $this->ctx($request, ['errors' => $validator->errors()->toArray()]));
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $state = [
            'cell'         => $request->input('cell'),
            'warehouse_id' => $request->input('warehouse_id'),
            'user_id'      => Auth::id(),
            'user_name'    => Auth::user()->name ?? null,
            'set_at'       => now()->toDateTimeString(),
        ];

        $request->session()->put('active_cell', $state);
        Cache::put($this->cellCacheKey(), $state, now()->addHours(6));

        Log::info('scan.session.cell: saved', $this->ctx($request, ['state' => $state]));
        return response()->json(['ok' => true, 'state' => $state]);
    }

    /** Получить активную ячейку */
    public function getState(Request $request)
    {
        $state = $request->session()->get('active_cell');
        if (!$state) {
            $state = Cache::get($this->cellCacheKey());
            if ($state) {
                $request->session()->put('active_cell', $state);
            }
        }
        Log::info('scan.session.state: fetched', $this->ctx($request, ['state' => $state]));
        return response()->json(['ok' => true, 'state' => $state]);
    }

    /** Очистить активную ячейку */
    public function clearCell(Request $request)
    {
        $request->session()->forget('active_cell');
        Cache::forget($this->cellCacheKey());
        Log::info('scan.session.clear: cleared', $this->ctx($request));
        return response()->json(['ok' => true]);
    }

    /**
     * Записать позицию документа (scan_position_document)
     */
    public function storePosition(Request $request)
    {
        Log::info('scan.position.store: incoming', $this->ctx($request, ['payload' => $request->all()]));

        $validator = Validator::make($request->all(), [
            'document_id'     => 'required|string|max:50',
            'warehouse_id'    => 'nullable|integer',
            'code'            => 'required|string|max:' . self::POS_CODE_MAX,
            'quantity'        => 'nullable|integer',
            'status'          => 'nullable|integer',

            'number_position' => 'nullable|integer',
            'lines'           => 'nullable|array',
            'lines.*'         => 'integer',

            'doc_link'        => 'nullable|string|max:255',
            'nom'             => 'nullable|string|max:255',
            'line_no'         => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $state = $request->session()->get('active_cell') ?: Cache::get($this->cellCacheKey());
        if (!$state || empty($state['cell'])) {
            return response()->json(['ok' => false, 'msg' => 'Активная ячейка не выбрана'], 422);
        }

        // нормализация
        $safeCode = mb_substr((string)$request->input('code'), 0, self::POS_CODE_MAX);

        $docIdRaw = trim((string)$request->input('document_id'));
        if ($docIdRaw === '') {
            return response()->json(['ok' => false, 'msg' => 'Порожній document_id'], 422);
        }
        $documentId = ctype_digit($docIdRaw)
            ? '00-' . str_pad($docIdRaw, 8, '0', STR_PAD_LEFT)
            : mb_substr($docIdRaw, 0, 50);

        $warehouseId = $request->input('warehouse_id');
        if ($warehouseId === null && is_array($state) && !empty($state['warehouse_id'])) {
            $warehouseId = (int)$state['warehouse_id'];
        }

        // id ячейки
        $linkId = DB::table('skladskie_yacheiki')
            ->where('number', $state['cell'])
            ->value('id');

        // ---- 1) собрать позиции из запроса
        $lines = $request->input('lines');
        if (!is_array($lines) || !count($lines)) {
            $single = $request->input('number_position');
            if ($single !== null) $lines = [(int)$single];
        }
        $lines = array_values(array_unique(array_map('intval', (array)$lines)));

        Log::info('scan.position.store: incoming lines (raw)', $this->ctx($request, [
            'lines' => $lines,
            'document_id' => $documentId,
            'code' => $safeCode,
        ]));

        // ---- 2) если пришла 0/1 позиция — расширим из session('pick_orders')
        if (count($lines) <= 1) {
            $docs = (array) session('pick_orders', []);
            $expanded = [];

            foreach ($docs as $doc) {
                $link = (string)($doc['Ссылка'] ?? $doc->Ссылка ?? '');
                $docNo = $documentId; // уже '00-00000334'
                $sameDoc =
                    (isset($doc['document_id']) && (string)$doc['document_id'] === $docNo)
                    || ($link && mb_strpos($link, $docNo) !== false);

                if (!$sameDoc) continue;

                $rows = $doc['ТоварыРазмещение'] ?? ($doc->ТоварыРазмещение ?? []);
                if (!is_array($rows)) $rows = [];

                foreach ($rows as $r) {
                    $bc = mb_strtolower((string)($r['Штрихкод'] ?? $r->Штрихкод ?? ''));
                    if ($bc === '') continue;

                    if ($bc === mb_strtolower($safeCode) || mb_strpos($bc, mb_strtolower($safeCode)) !== false) {
                        $ln = (int)($r['НомерСтроки'] ?? $r->НомерСтроки ?? 0);
                        if ($ln > 0) $expanded[] = $ln;
                    }
                }
                break;
            }

            if (count($expanded)) {
                $before = $lines;
                $lines = array_values(array_unique(array_merge($lines, $expanded)));
                Log::info('scan.position.store: expanded lines from session', $this->ctx($request, [
                    'before' => $before,
                    'expanded' => $expanded,
                    'final' => $lines,
                ]));
            }
        }

        if (!count($lines)) {
            return response()->json(['ok' => false, 'msg' => 'Не переданы позиции (lines/number_position), і не удалось расширить из сессии'], 422);
        }

        $ids = [];
        try {
            DB::beginTransaction();

            foreach ($lines as $lineNo) {
                $deltaQty = (int) $request->input('quantity', 1);
                if ($deltaQty === 0) $deltaQty = 1;

                $existing = ScanPositionDocument::query()
                    ->where('document_id', $documentId)
                    ->where('cell', $state['cell'])
                    ->where('code', $safeCode)
                    ->where('number_position', $lineNo)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    $before = (int) $existing->quantity;
                    $existing->quantity = max(0, $before) + $deltaQty;

                    if ($request->filled('status')) {
                        $existing->status = (int) $request->input('status');
                    }
                    if ($request->filled('amount')) {
                        $existing->amount = $request->input('amount');
                    }
                    if ($warehouseId !== null && $existing->warehouse_id !== $warehouseId) {
                        $existing->warehouse_id = $warehouseId;
                    }

                    $existing->save();

                    Log::info('scan.position.store: incremented existing line', $this->ctx($request, [
                        'line'   => $lineNo,
                        'before' => $before,
                        'delta'  => $deltaQty,
                        'after'  => $existing->quantity,
                        'id'     => $existing->id,
                    ]));

                    $ids[] = $existing->id;
                    continue;
                }

                $row = ScanPositionDocument::create([
                    'user_register'   => Auth::user()->name ?? 'system',
                    'document_id'     => $documentId,
                    'warehouse_id'    => $warehouseId,
                    'id_ssylka'       => $linkId,
                    'number_position' => $lineNo,
                    'quantity'        => $deltaQty,
                    'cell'            => $state['cell'],
                    'code'            => $safeCode,
                    'amount'          => $request->input('amount'),
                    'status'          => $request->input('status', 1),
                ]);

                $ids[] = $row->id;

                Log::info('scan.position.store: created new line', $this->ctx($request, [
                    'line' => $lineNo,
                    'id'   => $row->id,
                    'qty'  => $deltaQty,
                ]));
            }

            DB::commit();
        } catch (QueryException $qe) {
            DB::rollBack();
            return response()->json(['ok' => false, 'msg' => 'DB error: '.$qe->getMessage()], 500);
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'msg' => 'Server error'], 500);
        }

        Log::info('scan.position.store: SAVED_BATCH', $this->ctx($request, [
            'count'         => count($ids),
            'ids'           => $ids,
            'document_id'   => $documentId,
            'cell'          => $state['cell'],
            'code'          => $safeCode,
            'id_ssylka'     => $linkId,
        ]));

        return response()->json(['ok' => true, 'count' => count($ids), 'ids' => $ids]);
    }

    public function sendTo1C(Request $request)
    {
        Log::info('scan.send1c: incoming', $this->ctx($request, ['payload' => $request->all()]));

        // 0) Валидация входа
        $request->validate([
            'document_id'      => 'required|string|max:50',
            'mode'             => 'nullable|in:delta,absolute', // delta = СканДельта (по умолчанию), absolute = НовоеКоличество
            'only_active_cell' => 'nullable|boolean',
            'fill_placed'      => 'nullable|boolean',
        ]);

        // 1) Нормализация параметров
        $docIdRaw   = trim((string)$request->input('document_id'));
        $documentId = ctype_digit($docIdRaw)
            ? '00-' . str_pad($docIdRaw, 8, '0', STR_PAD_LEFT)
            : mb_substr($docIdRaw, 0, 50);

        $mode            = $request->input('mode', 'delta');       // 'delta' | 'absolute'
        $onlyActiveCell  = (bool)$request->boolean('only_active_cell', true);
        $fillPlaced      = (bool)$request->boolean('fill_placed', true);

        // 2) Активная ячейка
        $state      = $request->session()->get('active_cell') ?: Cache::get($this->cellCacheKey());
        $activeCell = $state['cell'] ?? null;

        // 3) Сбор дельт из БД
        $q = DB::table('scan_position_document')
            ->select([
                'number_position',
                DB::raw('SUM(quantity)    AS qty_total'),
                DB::raw('GROUP_CONCAT(id) AS ids')
            ])
            ->where('document_id', $documentId)
            ->where('status', 1);

        if ($onlyActiveCell && $activeCell) {
            $q->where('cell', $activeCell);
        }

        $rows = $q->groupBy('number_position')
            ->orderBy('number_position')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['ok' => false, 'msg' => 'Нет данных для отправки по этому документу'], 422);
        }

        // 4) Подтянем ПЛАН и стартовый ФАКТ из session('pick_orders') для расчёта статуса
        $planMap      = []; // line => Количество (план)
        $factStartMap = []; // line => Факт/Отобрано на момент открытия (если есть), иначе 0

        $docs = (array) session('pick_orders', []);
        foreach ($docs as $doc) {
            $link    = (string)($doc['Ссылка'] ?? $doc->Ссылка ?? '');
            $sameDoc =
                (isset($doc['document_id']) && (string)$doc['document_id'] === $documentId)
                || ($link && mb_strpos($link, $documentId) !== false);

            if (!$sameDoc) continue;

            $lines = $doc['ТоварыРазмещение'] ?? ($doc->ТоварыРазмещение ?? []);
            if (!is_array($lines)) $lines = [];

            foreach ($lines as $r) {
                $ln = (int)($r['НомерСтроки'] ?? $r->НомерСтроки ?? 0);
                if ($ln <= 0) continue;

                $pl = (int)($r['Количество'] ?? $r->Количество ?? 0);
                $fc = (int)($r['Факт'] ?? $r->Факт ?? ($r['Отобрано'] ?? $r->Отобрано ?? 0));

                $planMap[$ln]      = $pl;
                $factStartMap[$ln] = $fc;
            }
            break; // нашли нужный документ
        }

        // 5) Сбор позиций для 1С + список отправляемых ID + карта дельт
        $positions   = [];
        $sentScanIds = [];
        $deltaMap    = []; // line => delta

        foreach ($rows as $r) {
            $line  = (int)$r->number_position;
            $delta = (int)$r->qty_total;

            $ids = array_filter(array_map('intval', explode(',', (string)$r->ids)));
            $sentScanIds = array_merge($sentScanIds, $ids);

            $deltaMap[$line] = ($deltaMap[$line] ?? 0) + $delta;

            if ($mode === 'absolute') {
                // НовоеКоличество = план (если знаем) + дельта
                $newQty = ($planMap[$line] ?? 0) + $delta;
                $positions[] = [
                    'НомерСтроки'     => $line,
                    'НовоеКоличество' => $newQty,
                ];
            } else {
                // delta (СканДельта) по умолчанию
                $positions[] = [
                    'НомерСтроки' => $line,
                    'СканДельта'  => $delta,
                ];
            }
        }

        // 6) Рассчитать "СтатусДокумента"
        // Итоговый факт = стартовый факт + дельта (только по строкам, что отправляем).
        // Без ошибок — если для всех строк, где есть план, итоговый факт == плану.
        $allOk = true;
        foreach ($planMap as $line => $planQty) {
            $factStart = $factStartMap[$line] ?? 0;
            $delta     = $deltaMap[$line] ?? 0; // если в этой отправке строки нет — дельта 0
            $factFinal = $factStart + $delta;

            if ($factFinal !== $planQty) {
                $allOk = false;
                break;
            }
        }
        $docStatusStr = $allOk ? 'Выполнено без ошибок' : 'Выполнено с ошибками';

        // 7) Формируем payload в 1С
        $payload = [
            'Номер'              => $documentId,
            'Позиции'            => $positions,
            'ЗаполнитьРазмещено' => $fillPlaced,
            'СтатусДокумента'    => $docStatusStr,
        ];

        Log::info('scan.send1c: payload', $this->ctx($request, ['payload' => $payload]));

        // 8) Адрес 1С
        $url = 'http://192.168.170.105/PROD_copy/hs/tsd/FinishAccommodation';

        // 9) Отправка в 1С (жёстко прошитая Basic Auth)
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 20,
                'verify'  => false,
            ]);

            $resp = $client->post($url, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'auth' => ['КучеренкоД', 'NitraPa$$@0@!'], // <<< авторизация как просил
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $body = (string)$resp->getBody();
            $code = $resp->getStatusCode();

            Log::info('scan.send1c: 1C response', $this->ctx($request, ['status' => $code, 'body' => $body]));

            if ($code < 200 || $code >= 300) {
                return response()->json(['ok' => false, 'msg' => '1C HTTP '.$code, 'body' => $body], 502);
            }

            // 10) Успех: пометить отправленные сканы статусом = 2
            if (!empty($sentScanIds)) {
                DB::table('scan_position_document')
                    ->whereIn('id', $sentScanIds)
                    ->update(['status' => 2, 'updated_at' => now()]);
            }

            return response()->json([
                'ok'             => true,
                'sent_positions' => count($positions),
                'sent_scans'     => count($sentScanIds),
                'one_c_reply'    => $body ? json_decode($body, true) : null,
                'doc_status'     => $docStatusStr,
            ]);

        } catch (\Throwable $e) {
            Log::error('scan.send1c: error', $this->ctx($request, ['err' => $e->getMessage()]));
            return response()->json(['ok' => false, 'msg' => 'Ошибка отправки в 1С: '.$e->getMessage()], 500);
        }
    }
    public function freeScanPage(Request $request)
    {
        $cell = $request->query('cell');

        if (!$cell) {
            $state = $request->session()->get('active_cell');
            $cell  = is_array($state) ? ($state['cell'] ?? null) : $state;
        }

        if ($cell) {
            // старый ключ на совместимость
            session(['scan_state.cell' => $cell]);
        }

        $cellRow = null;
        if ($cell) {
            $cellRow = \DB::table('skladskie_yacheiki')
                ->where('number', $cell)
                ->orWhere('ssylka', $cell)
                ->orWhere('room',   $cell)
                ->first();
        }

        // Красивое имя для вывода
        $cellName = null;
        if ($cellRow) {
            $cellName = $cellRow->ssylka ?: ($cellRow->room ?: null);
            if (!$cellName && !empty($cellRow->number)) {
                $cellName = '№ ' . $cellRow->number;
            }
        }

        \Log::info('FREE_SCAN: входящий cell', [
            'query'   => $request->query('cell'),
            'session' => session('scan_state.cell'),
            'active'  => $request->session()->get('active_cell'),
            'resolved_cellName' => $cellName,
        ]);

        return view('sklad.free_scan', [
            'activeCell' => $cell,     // что реально активно (может быть номером)
            'cellRow'    => $cellRow,  // строка БД
            'cellName'   => $cellName, // красивое имя для UI
        ]);
    }

// Пример: SkladScanController.php
    public function saveActiveCell(Request $request)
    {
        $cell = trim((string) $request->input('cell', ''));
        $warehouseId = $request->input('warehouse_id'); // если надо
        session(['scan_state.cell' => $cell, 'scan_state.warehouse_id' => $warehouseId]);
        return response()->json(['ok' => true, 'state' => session('scan_state')], 200);
    }

// ...

    public function creatingBlankDocument(Request $request)
    {
        // валидируем вход
        $request->validate([
            'code'    => 'required|string|max:64',
            'scan_id' => 'nullable|string|max:64',
        ]);

        $cid   = (string)($request->input('scan_id') ?: $request->header('X-Scan-ID') ?: Str::uuid());
        $code  = (string)$request->input('code');

        Log::info('scan.1c.creatingBlank: start', $this->ctx($request, [
            'cid'   => $cid,
            'code'  => $code,
        ]));

        $state      = $request->session()->get('active_cell') ?: Cache::get($this->cellCacheKey());
        $activeCell = is_array($state) ? ($state['cell'] ?? null) : null;

        $url = 'http://192.168.170.105/PROD_copy/hs/tsd/CreatingBlankDocument';

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10, 'verify' => false]);

            $payload = [
                'barcode'    => $code,
                'ActiveCell' => $activeCell,
                'scan_id'    => $cid,
            ];

            Log::info('scan.1c.creatingBlank: request', $this->ctx($request, [
                'cid'     => $cid,
                'url'     => $url,
                'payload' => $payload,
            ]));

            $resp = $client->post($url, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'auth' => ['КучеренкоД', 'NitraPa$$@0@!'],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $status = $resp->getStatusCode();
            $body   = (string)$resp->getBody();
            $bodyShort = mb_substr($body, 0, 2000);

            Log::info('scan.1c.creatingBlank: response', $this->ctx($request, [
                'cid'    => $cid,
                'status' => $status,
                'body'   => $bodyShort,
            ]));

            if ($status < 200 || $status >= 300) {
                return response()->json(['ok' => false, 'cid' => $cid, 'msg' => '1C HTTP '.$status], 502);
            }

            return response()->json([
                'ok'    => true,
                'cid'   => $cid,
                'reply' => $body ? json_decode($body, true) : null,
                'echo'  => ['barcode' => $code, 'active_cell' => $activeCell],
            ]);
        } catch (\Throwable $e) {
            Log::error('scan.1c.creatingBlank: error', $this->ctx($request, [
                'cid' => $cid,
                'err' => $e->getMessage(),
            ]));
            return response()->json(['ok' => false, 'cid' => $cid, 'msg' => 'Ошибка вызова 1С: '.$e->getMessage()], 500);
        }
    }


//    public function freeScanPage(\Illuminate\Http\Request $request)
//    {
//        // Здесь предполагаем, что ты уже где-то сохраняешь active state (ячейку) в сессии
//        // Например, у тебя есть эндпоинт STATE_FETCH_URL, который это делает.
//        // Для Blade страницы просто отдадим то, что знаем:
//        $state = $request->session()->get('scan_state'); // или откуда ты его берёшь
//        $cell  = $state['cell'] ?? null;
//
//        // Можно дополнительно красиво получить label/room, если есть свой сервис/роут
//        return view('sklad.free_scan', [
//            'cell' => $cell,
//            // опционально: 'label' => ..., 'room' => ...,
//        ]);
//    }

    /**
     * Принимает одиночный скан "без документа".
     * Требования минимальные: есть активная ячейка и штрихкод.
     */
    public function freeScanStore(Request $request)
    {
        $data = $request->validate([
            'code'          => 'required|string|max:255',
            'quantity'      => 'nullable|integer|min:1',
            'warehouse_id'  => 'nullable|integer',
            'scan_id'       => 'nullable|string|max:64',
        ]);

        $cid = (string)($data['scan_id'] ?? $request->header('X-Scan-ID') ?? Str::uuid());
        Log::info('scan.free: start', $this->ctx($request, ['cid' => $cid, 'code' => $data['code']]));

        $state = $request->session()->get('active_cell')
            ?: $request->session()->get('scan_state')
                ?: Cache::get($this->cellCacheKey());
        $cell  = is_array($state) ? ($state['cell'] ?? null) : null;

        if (!$cell) {
            Log::warning('scan.free: no active cell', $this->ctx($request, ['cid' => $cid]));
            return response()->json(['ok' => false, 'msg' => 'Активная ячейка не выбрана', 'cid' => $cid], 422);
        }

        $qty      = max(1, (int)($data['quantity'] ?? 1));
        $safeCode = mb_substr($data['code'], 0, 50);

        try {
            $payload = [
                'user_register' => Auth::user()->name ?? 'system',
                'document_id'   => 0,
                'warehouse_id'  => $data['warehouse_id'] ?? null,
                'user_id'       => Auth::id(),
                'cell'          => $cell,
                'code'          => $safeCode,
                'order_date'    => now(),
                'amount'        => $qty,
                'status'        => 1,
            ];

            Log::info('scan.free: db.insert', $this->ctx($request, ['cid' => $cid, 'payload' => $payload]));
            $scan = \App\Models\ScanCode::create($payload);

            Log::info('scan.free: db.ok', $this->ctx($request, ['cid' => $cid, 'id' => $scan->id]));
            return response()->json(['ok' => true, 'saved' => 1, 'id' => $scan->id, 'cid' => $cid]);
        } catch (\Throwable $e) {
            Log::error('scan.free: db.fail', $this->ctx($request, ['cid' => $cid, 'err' => $e->getMessage()]));
            return response()->json(['ok' => false, 'cid' => $cid, 'msg' => 'Помилка збереження: '.$e->getMessage()], 500);
        }
    }

    /** Список логов (как было) */
    public function index()
    {
        return view('sklad.scans.index', [
            'scans' => ScanCode::latest()->paginate(25),
        ]);
    }
}
