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
use App\Support\TsdOneC;



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

    public function finishAcceptance(Request $request)
    {
        // ===== 1) Валидация =====
        $data = $request->validate([
            'number' => ['required','string'],
            'at'     => ['nullable','date'], // опционально
        ]);

        // ===== 2) Жёстко зашитые параметры 1С =====
        $endpoint = TsdOneC::url('FinishAcceptance', Auth::user());
        $login    = TsdOneC::login(Auth::user());
        $password = TsdOneC::password(Auth::user());
        $timeout  = 15;

        // Формируем тело так, как ждёт 1С
        $payload = [
            'Номер' => (string) $data['number'],
        ];
        if (!empty($data['at'])) {
            // 1С съедает ISO-8601; можно и сырую дату отдать
            $payload['НаМомент'] = date('c', strtotime($data['at']));
        }

        // ===== 3) Вызов 1С =====
        try {
            $resp = Http::withBasicAuth($login, $password)
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json; charset=utf-8',
                    'X-Scan-ID'    => $request->header('X-Scan-ID', (string) Str::uuid()),
                ])
                ->timeout($timeout)
                ->post($endpoint, $payload);

            $status = $resp->status();
            $raw    = $resp->body();

            // Пробуем JSON — 1С у тебя возвращает JSON
            $json = null;
            try { $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); } catch (\Throwable $e) {}

            if ($json !== null) {
                return response()->json($json, $status, [], JSON_UNESCAPED_UNICODE);
            }
            return response($raw, $status)->header('Content-Type', 'text/plain; charset=utf-8');

        } catch (\Throwable $e) {
            return response()->json([
                'ok'  => false,
                'msg' => '1С недоступна: '.$e->getMessage(),
            ], 502, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function searchBarcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string|max:64',
        ]);

        $barcode = trim((string)$request->input('barcode'));

        // URL лучше вынести в конфиг/ ENV, но оставлю дефолт для быстрого старта
        $url = TsdOneC::url('SearchBarcode', Auth::user());

        // Хелпер контекста для логов
        $ctx = function (array $extra = []) use ($request, $barcode, $url) {
            return array_merge([
                'ip'     => $request->ip(),
                'userId' => Auth::id(),
                'user'   => optional(Auth::user())->name,
                'route'  => $request->path(),
                'url'    => $url,
                'tsd'    => TsdOneC::diagnostics(Auth::user()),
                'barcode'=> $barcode,
            ], $extra);
        };

        $t0 = microtime(true);
        Log::info('scan.searchBarcode: start', $ctx());

        try {
            // Готовим HTTP-клиент (логин/пароль лучше в .env)
            $login    = TsdOneC::login(Auth::user());
            $password = TsdOneC::password(Auth::user());

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
        Log::info('scan.pos.store: incoming', $this->ctx($request, ['payload' => $request->all()]));

        $request->validate([
            'document_id'     => 'required|string|max:50',
            'code'            => 'required|string|max:50',
            'quantity'        => 'required|integer|min:1',
            'number_position' => 'required|integer|min:1',
            'warehouse_id'    => 'nullable|integer',
            'doc_link'        => 'nullable|string',
            'active_cell'     => 'nullable|string|max:128',   // <<< NEW
        ]);

        $documentId     = trim((string)$request->input('document_id'));
        $code           = trim((string)$request->input('code'));
        $qty            = (int)$request->input('quantity', 1);
        $numberPosition = (int)$request->input('number_position');
        $warehouseId    = $request->input('warehouse_id');

        $activeCell = $request->input('active_cell');
        if (!$activeCell) {
            $state      = $request->session()->get('active_cell') ?: Cache::get($this->cellCacheKey());
            $activeCell = $state['cell'] ?? null;
        }
        Log::info('scan.pos.store: active_cell', $this->ctx($request, ['active_cell' => $activeCell]));

        $id = DB::table('scan_position_document')->insertGetId([
            'document_id'     => $documentId,
            'code'            => $code,
            'quantity'        => $qty,
            'number_position' => $numberPosition,
            'status'          => 1,
            'cell'            => $activeCell,    // <<< NEW
            'warehouse_id'    => $warehouseId,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    public function sendTo1C(Request $request)
    {
        Log::info('scan.send1c: incoming', $this->ctx($request, ['payload' => $request->all()]));

        $request->validate([
            'document_id'      => 'required|string|max:50',
            'mode'             => 'nullable|in:delta,absolute',
            'only_active_cell' => 'nullable|boolean',
            'fill_placed'      => 'nullable|boolean',
        ]);

        $docIdRaw   = trim((string)$request->input('document_id'));
        $documentId = ctype_digit($docIdRaw)
            ? '00-' . str_pad($docIdRaw, 8, '0', STR_PAD_LEFT)
            : mb_substr($docIdRaw, 0, 50);

        $mode            = $request->input('mode', 'delta');
        $onlyActiveCell  = (bool)$request->boolean('only_active_cell', true);
        $fillPlaced      = (bool)$request->boolean('fill_placed', true);

        // ==== ЯЧЕЙКА: number → ssylka ====
        $state            = $request->session()->get('active_cell') ?: Cache::get($this->cellCacheKey());
        $activeCellNumber = $state['cell'] ?? null;
        $cellRef          = null;

        if (!empty($activeCellNumber)) {
            $cellRow = DB::table('skladskie_yacheiki')->where('number', $activeCellNumber)->first();
            $cellRef = $cellRow->ssylka ?? null;
        }

        Log::info('scan.send1c: active_cell', $this->ctx($request, [
            'active_cell_number' => $activeCellNumber,
            'active_cell_ref'    => $cellRef,
        ]));

        // 3) Дельты из БД
        $q = DB::table('scan_position_document')
            ->select([
                'number_position',
                DB::raw('SUM(quantity)    AS qty_total'),
                DB::raw('GROUP_CONCAT(id) AS ids')
            ])
            ->where('document_id', $documentId)
            ->where('status', 1);

        if ($onlyActiveCell && $activeCellNumber) {
            $q->where('cell', $activeCellNumber);
        }

        $rows = $q->groupBy('number_position')
            ->orderBy('number_position')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json(['ok' => false, 'msg' => 'Нет данных для отправки по этому документу'], 422);
        }

        // 4) План/факт
        $planMap      = [];
        $factStartMap = [];
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
            break;
        }

        // 5) Позиции
        $positions   = [];
        $sentScanIds = [];
        $deltaMap    = [];

        foreach ($rows as $r) {
            $line  = (int)$r->number_position;
            $delta = (int)$r->qty_total;

            $ids = array_filter(array_map('intval', explode(',', (string)$r->ids)));
            $sentScanIds = array_merge($sentScanIds, $ids);

            $deltaMap[$line] = ($deltaMap[$line] ?? 0) + $delta;

            if ($mode === 'absolute') {
                $newQty = ($planMap[$line] ?? 0) + $delta;
                $p = [
                    'НомерСтроки'     => $line,
                    'НовоеКоличество' => $newQty,
                ];
            } else {
                $p = [
                    'НомерСтроки' => $line,
                    'СканДельта'  => $delta,
                ];
            }

            if (!empty($cellRef)) {
                $p['Ячейка'] = $cellRef; // ← слать ssylka
            }

            $positions[] = $p;
        }

        // 6) СтатусДокумента
        $allOk = true;
        foreach ($planMap as $line => $planQty) {
            $factStart = $factStartMap[$line] ?? 0;
            $delta     = $deltaMap[$line] ?? 0;
            $factFinal = $factStart + $delta;
            if ($factFinal !== $planQty) { $allOk = false; break; }
        }
        $docStatusStr = $allOk ? 'Выполнено без ошибок' : 'Выполнено с ошибками';

        // 7) Полный справочник ячеек
        $warehouseStorage = DB::table('skladskie_yacheiki')
            ->select(['id', 'ssylka', 'number', 'room', 'versiya_dannykh'])
            ->orderBy('id')
            ->get()
            ->map(fn($row) => [
                'id'              => (int)$row->id,
                'ssylka'          => (string)$row->ssylka,
                'number'          => (string)$row->number,
                'room'            => (string)$row->room,
                'versiya_dannykh' => (string)$row->versiya_dannykh,
            ])
            ->toArray();

        // Payload
        $payload = [
            'Номер'              => $documentId,
            'Позиции'            => $positions,
            'ЗаполнитьРазмещено' => $fillPlaced,
            'СтатусДокумента'    => $docStatusStr,
            'warehouse_storage'  => $warehouseStorage,
        ];
        Log::info('scan.send1c: payload', $this->ctx($request, ['payload' => $payload]));

        $url = TsdOneC::url('FinishAccommodation', Auth::user());

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 20, 'verify' => false]);
            $resp = $client->post($url, [
                'headers' => ['Accept'=>'application/json','Content-Type'=>'application/json; charset=utf-8'],
                'auth'    => [TsdOneC::login(Auth::user()), TsdOneC::password(Auth::user())],
                'body'    => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $body = (string)$resp->getBody();
            $code = $resp->getStatusCode();
            Log::info('scan.send1c: 1C response', $this->ctx($request, ['status'=>$code,'body'=>$body]));

            if ($code < 200 || $code >= 300) {
                return response()->json(['ok'=>false,'msg'=>'1C HTTP '.$code,'body'=>$body], 502);
            }

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
            return response()->json(['ok'=>false,'msg'=>'Ошибка отправки в 1С: '.$e->getMessage()], 500);
        }
    }

    public function addExternalPosition(Request $request)
    {
        Log::info('scan.addExternal: incoming', $this->ctx($request, ['payload' => $request->all()]));

        $request->validate([
            'document_id'    => 'required|string|max:50',
            'barcode'        => 'nullable|string|max:64',
            'nomen'          => 'required|string|max:255',
            'characteristic' => 'nullable|string|max:255',
            'fill_placed'    => 'nullable|boolean',
            'warehouse_id'   => 'nullable|integer',
            'active_cell'    => 'nullable|string|max:128',
        ]);

        // Документ
        $docIdRaw   = trim((string)$request->input('document_id'));
        $documentId = ctype_digit($docIdRaw)
            ? '00-' . str_pad($docIdRaw, 8, '0', STR_PAD_LEFT)
            : mb_substr($docIdRaw, 0, 50);

        $barcode     = trim((string)$request->input('barcode', ''));
        $nomen       = trim((string)$request->input('nomen'));
        $char        = trim((string)$request->input('characteristic', ''));
        $fillPlaced  = (bool)$request->boolean('fill_placed', true);
        $warehouseId = $request->input('warehouse_id');

        // ==== ЯЧЕЙКА: number → ssylka ====
        $activeCellNumber = $request->input('active_cell');
        if (!$activeCellNumber) {
            $state            = $request->session()->get('active_cell') ?: Cache::get($this->cellCacheKey());
            $activeCellNumber = $state['cell'] ?? null;
        }

        $cellRef = null;
        if (!empty($activeCellNumber)) {
            $cellRow = DB::table('skladskie_yacheiki')->where('number', $activeCellNumber)->first();
            $cellRef = $cellRow->ssylka ?? null;
        }

        Log::info('scan.addExternal: cell mapping', $this->ctx($request, [
            'active_cell_number' => $activeCellNumber,
            'mapped_ssylka'      => $cellRef,
        ]));

        // Позиция
        $position = [
            'Номенклатура' => $nomen,
            'СканДельта'   => 1,
        ];
        if ($char !== '')    $position['Характеристика'] = $char;
        if ($barcode !== '') $position['Штрихкод']       = $barcode;
        if (!empty($cellRef)) $position['Ячейка']        = $cellRef; // ← сюда идёт ssylka

        $payload = [
            'Номер'              => $documentId,
            'Позиции'            => [ $position ],
            'ЗаполнитьРазмещено' => $fillPlaced,
            'ДобавитьЕслиНет'    => true,
            'ПроводитьДокумент'  => false,
        ];
        Log::info('scan.addExternal: payload', $this->ctx($request, ['payload' => $payload]));

        $url = TsdOneC::url('FinishAccommodation', Auth::user());

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 20, 'verify' => false]);
            $resp = $client->post($url, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'auth' => [TsdOneC::login(Auth::user()), TsdOneC::password(Auth::user())],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $code = $resp->getStatusCode();
            $body = (string)$resp->getBody();
            Log::info('scan.addExternal: 1C response', $this->ctx($request, ['status'=>$code,'body'=>$body]));

            if ($code < 200 || $code >= 300) {
                return response()->json(['ok'=>false,'msg'=>'1C HTTP '.$code,'body'=>$body], 502);
            }

            return response()->json([
                'ok'           => true,
                'one_c_reply'  => json_decode($body, true),
                'document_id'  => $documentId,
            ]);

        } catch (\Throwable $e) {
            Log::error('scan.addExternal: error', $this->ctx($request, ['err'=>$e->getMessage()]));
            return response()->json(['ok'=>false, 'msg'=>'Ошибка отправки в 1С: '.$e->getMessage()], 500);
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
    public function creatingBlankDocument(\Illuminate\Http\Request $request)
    {
        // ===== 0) Корреляция и тайминг =====
        $t0  = microtime(true);
        $cid = (string)($request->input('scan_id')
            ?: $request->header('X-Scan-ID')
                ?: \Illuminate\Support\Str::uuid());

        // ===== 1) Вход и валидация =====
        $validated = $request->validate([
            'code'          => 'required|string|max:64',
            'scan_id'       => 'nullable|string|max:64',
            'data_executor' => 'nullable|string|max:100',
            'document_no'   => 'nullable|string|max:50',
            'room'          => 'nullable|string|max:100',
            'warehouse'     => 'nullable|string|max:100',
            'cell_name'     => 'nullable|string|max:150',
        ]);

        $code = (string)$validated['code'];

        // Базовый контекст для логов
        $ctxBase = [
            'cid'         => $cid,
            'ip'          => $request->ip(),
            'route'       => $request->path(),
            'ua'          => substr((string)$request->userAgent(), 0, 256),
            'reqHeaders'  => array_intersect_key($request->headers->all(), array_flip([
                'x-scan-id','accept','content-type','referer'
            ])),
            'reqQuery'    => $request->query(),
        ];

        \Log::info('scan.1c.creatingBlank:start', $ctxBase + [
                'payload_raw' => $request->all(),
            ]);

        // ===== 2) Активная ячейка из сессии/кэша =====
        $state      = $request->session()->get('active_cell') ?: \Illuminate\Support\Facades\Cache::get($this->cellCacheKey());
        $activeCell = is_array($state) ? ($state['cell'] ?? null) : null;

        // ===== 3) Достаём строку ячейки из БД (учитываем number/ssylka/link) =====
        $cellRow            = null;
        $cellNameFor1C      = null;
        $roomFromCell       = null;
        $warehouseFromCell  = null;
        $cellRefGuid        = null;
        $receptionAreaName  = null; // ВАЖНО: зона приёмки из вашей таблицы

        if (!empty($activeCell)) {
            $cellRow = \DB::table('skladskie_yacheiki')
                ->where('number', $activeCell)
                ->orWhere('ssylka', $activeCell)
                ->orWhere('link',   $activeCell)
                ->first();
        }

        if ($cellRow) {
            // ваши поля: id | ssylka | sklad | link | number | room | reception_area | versiya_dannykh
            $cellNameFor1C      = $cellRow->ssylka ?: ($cellRow->name ?? $cellRow->number);
            $roomFromCell       = $cellRow->room ?? null;
            $warehouseFromCell  = $cellRow->sklad ?? ($cellRow->warehouse ?? null);
            $cellRefGuid        = $cellRow->link ?? null;
            $receptionAreaName  = $cellRow->reception_area ?? null; // например: "Приемка Допы Дарв"
        }

        \Log::debug('scan.1c.creatingBlank:cell.resolve', $ctxBase + [
                'activeCell_in'   => $activeCell,
                'cellRow_found'   => (bool)$cellRow,
                'cellRow_keys'    => $cellRow ? array_keys((array)$cellRow) : [],
                'cell_name_1c'    => $cellNameFor1C,
                'room_from_cell'  => $roomFromCell,
                'wh_from_cell'    => $warehouseFromCell,
                'cell_ref'        => $cellRefGuid,
                'session_state'   => $state,
            ]);

        // ===== 4) Исполнитель =====
        $executor       = trim((string)($validated['data_executor'] ?? ''));
        $executorSource = 'request.data_executor';

        if ($executor === '') {
            $u = \Illuminate\Support\Facades\Auth::user();
            if ($u) {
                $executor       = trim((string)($u->data_executor ?? ''));
                $executorSource = 'users.data_executor';
                if ($executor === '') {
                    $executor       = $u->user_register ?: $u->name ?: $u->login ?: $u->email ?: 'Кучеренко Денис';
                    $executorSource = 'fallback('.$executorSource.')';
                }
            } else {
                $executor       = 'Кучеренко Денис';
                $executorSource = 'fallback.no_user';
            }
        }

        \Log::debug('scan.1c.creatingBlank:executor.resolve', $ctxBase + [
                'executor'       => $executor,
                'executorSource' => $executorSource,
            ]);

        // ===== 5) Прочие входные =====
        $roomInput      = trim((string)($validated['room'] ?? ''));
        $warehouseInput = trim((string)($validated['warehouse'] ?? ''));
        $cellNameInput  = trim((string)($validated['cell_name'] ?? ''));
        $documentNo     = isset($validated['document_no']) ? (string)$validated['document_no'] : null;

        // ===== 6) Формируем payload для 1С =====
        $payload = [
            'barcode'     => $code,
            'scan_id'     => $cid,
            'Исполнитель' => $executor,
        ];

        // Имя/GUID/ActiveCell: приоритет GUID -> имя -> ActiveCell
        if (!empty($cellRefGuid)) {
            $payload['cell_ref'] = (string)$cellRefGuid;
        }
        if (!empty($cellNameFor1C)) {
            $payload['Ячейка']    = (string)$cellNameFor1C;
            $payload['cell_name'] = (string)$cellNameFor1C;
        } elseif ($cellNameInput !== '') {
            $payload['cell_name'] = $cellNameInput;
        } elseif (!empty($activeCell)) {
            $payload['ActiveCell'] = $activeCell;
        }

        // Помещение
        if (!empty($roomFromCell)) {
            $payload['Помещение'] = (string)$roomFromCell;
        }
        if ($roomInput !== '' && $roomInput !== 'ГП (ячейки)') {
            $payload['Помещение'] = (string)$roomInput;
        }

        // Склад
        if (!empty($warehouseFromCell)) {
            $payload['Склад'] = (string)$warehouseFromCell;
        } elseif ($warehouseInput !== '') {
            $payload['Склад'] = (string)$warehouseInput;
        } else {
            $payload['Склад'] = 'Дарвин - склад ГП';
        }

        // ЗонаПриемки из таблицы (если есть)
        if (!empty($receptionAreaName)) {
            $payload['ЗонаПриемки'] = (string)$receptionAreaName; // пример: "Приемка Допы Дарв"
        }

        if (!empty($documentNo)) {
            $payload['document_no'] = $documentNo;
        }

        // ===== 7) Вызов 1С =====
        $url      = TsdOneC::url('CreatingBlankDocument', Auth::user());
        $login    = TsdOneC::login(Auth::user());
        $password = TsdOneC::password(Auth::user());

        $client = new \GuzzleHttp\Client([
            'timeout'     => 20,
            'verify'      => false,
            'http_errors' => false,
        ]);

        \Log::info('scan.1c.creatingBlank:request', $ctxBase + [
                'cid'       => $cid,
                'url'       => $url,
                'executor'  => ['value' => $executor, 'source' => $executorSource],
                'payload'   => $payload,
                'hasRef'    => array_key_exists('cell_ref', $payload),
                'hasActive' => array_key_exists('ActiveCell', $payload),
                'hasName'   => array_key_exists('Ячейка', $payload) || array_key_exists('cell_name', $payload),
            ]);

        $tCall0 = microtime(true);
        try {
            $resp = $client->post($url, [
                'headers' => [
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json; charset=utf-8',
                    'X-Correlation-ID' => $cid,
                ],
                'auth' => [$login, $password],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $tCall1 = microtime(true);

            $status    = $resp->getStatusCode();
            $headers   = $resp->getHeaders();
            $body      = (string)$resp->getBody();
            $bodyLen   = strlen($body);
            $bodyShort = mb_substr($body, 0, 6000);

            $json = null;
            $jsonKeys = [];
            try {
                $json = $body ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : null;
                if (is_array($json)) {
                    $jsonKeys = array_slice(array_keys($json), 0, 20);
                }
            } catch (\Throwable $e) {}

            \Log::info('scan.1c.creatingBlank:response', $ctxBase + [
                    'cid'        => $cid,
                    'status'     => $status,
                    'elapsed_ms' => (int)round(($tCall1 - $tCall0) * 1000),
                    'hdrs'       => array_intersect_key($headers, array_flip(['Content-Type','Content-Length','Date','Server'])),
                    'body_len'   => $bodyLen,
                    'body_head'  => $bodyShort,
                    'json_keys'  => $jsonKeys,
                ]);

            if ($status >= 500 && $status < 600) {
                \Log::warning('scan.1c.creatingBlank:server500', $ctxBase + [
                        'cid'      => $cid,
                        'message'  => is_array($json) && isset($json['message']) ? $json['message'] : null,
                        'payload'  => $payload,
                        'cell_diag'=> [
                            'activeCell_in'  => $activeCell,
                            'cell_name_1c'   => $cellNameFor1C,
                            'cell_ref'       => $cellRefGuid,
                            'room_from_cell' => $roomFromCell,
                            'wh_from_cell'   => $warehouseFromCell,
                        ],
                    ]);
            }

            if ($status < 200 || $status >= 300) {
                $msg = '1C HTTP '.$status;
                if (is_array($json) && isset($json['message'])) {
                    $msg .= ': '.$json['message'];
                } elseif (!empty($bodyShort)) {
                    $msg .= '; body: '.$bodyShort;
                }
                $out = [
                    'ok'   => false,
                    'cid'  => $cid,
                    'msg'  => $msg,
                    'echo' => [
                        'barcode'            => $code,
                        'active_cell'        => $activeCell,
                        'executor'           => $executor,
                        'executorSource'     => $executorSource,
                        'cell_name_sent'     => $payload['cell_name'] ?? ($payload['Ячейка'] ?? null),
                        'room_sent'          => $payload['Помещение'] ?? null,
                        'warehouse_sent'     => $payload['Склад'] ?? null,
                        'cell_name_resolved' => $cellNameFor1C,
                        'room_resolved'      => $roomFromCell,
                        'warehouse_resolved' => $warehouseFromCell,
                        'cell_ref'           => $cellRefGuid,
                    ],
                ];
                $out['elapsed_ms_total'] = (int)round((microtime(true) - $t0) * 1000);
                return response()->json($out, 502);
            }

            $out = [
                'ok'    => true,
                'cid'   => $cid,
                'reply' => $json,
                'echo'  => [
                    'barcode'            => $code,
                    'active_cell'        => $activeCell,
                    'executor'           => $executor,
                    'executorSource'     => $executorSource,
                    'cell_name_sent'     => $payload['cell_name'] ?? ($payload['Ячейка'] ?? null),
                    'room_sent'          => $payload['Помещение'] ?? null,
                    'warehouse_sent'     => $payload['Склад'] ?? null,
                    'cell_name_resolved' => $cellNameFor1C,
                    'room_resolved'      => $roomFromCell,
                    'warehouse_resolved' => $warehouseFromCell,
                    'cell_ref'           => $cellRefGuid,
                ],
                'elapsed_ms_total' => (int)round((microtime(true) - $t0) * 1000),
            ];

            if (is_array($json)) {
                $out['diag'] = [
                    'found_count' => $json['found_count'] ?? null,
                    'doc_search'  => $json['doc_search'] ?? null,
                    'ts'          => $json['ts'] ?? null,
                ];
            }

            return response()->json($out, 200);

        } catch (\Throwable $e) {
            $elapsed = (int)round((microtime(true) - $tCall0) * 1000);
            \Log::error('scan.1c.creatingBlank:exception', $ctxBase + [
                    'cid'        => $cid,
                    'elapsed_ms' => $elapsed,
                    'err'        => $e->getMessage(),
                    'trace'      => substr($e->getTraceAsString(), 0, 4000),
                    'payload'    => $payload,
                ]);

            return response()->json([
                'ok'    => false,
                'cid'   => $cid,
                'msg'   => 'Ошибка вызова 1С: '.$e->getMessage(),
                'echo'  => [
                    'barcode'            => $code,
                    'active_cell'        => $activeCell,
                    'executor'           => $executor,
                    'executorSource'     => $executorSource,
                    'cell_name_sent'     => $payload['cell_name'] ?? ($payload['Ячейка'] ?? null),
                    'room_sent'          => $payload['Помещение'] ?? null,
                    'warehouse_sent'     => $payload['Склад'] ?? null,
                    'cell_name_resolved' => $cellNameFor1C,
                    'room_resolved'      => $roomFromCell,
                    'warehouse_resolved' => $warehouseFromCell,
                    'cell_ref'           => $cellRefGuid,
                ],
                'elapsed_ms_total' => (int)round((microtime(true) - $t0) * 1000),
            ], 500);
        }
    }

    public function addLineByNumber(\Illuminate\Http\Request $request)
    {
        // ===== 0) Корреляция и тайминг =====
        $t0  = microtime(true);
        $cid = (string)($request->input('scan_id')
            ?: $request->header('X-Scan-ID')
                ?: \Illuminate\Support\Str::uuid());

        // ===== 1) Вход и валидация =====
        $validated = $request->validate([
            'document_no' => 'required|string|max:50',   // номер документа
            'code'        => 'required|string|max:64',   // штрихкод
            'cell_ref'    => 'nullable|string|max:150',  // GUID ячейки
            'cell_name'   => 'nullable|string|max:150',  // имя ячейки
            'quantity'    => 'nullable|integer|min:1',   // (новое) количество
            'scan_id'     => 'nullable|string|max:64',
        ]);

        $documentNo = trim((string)$validated['document_no']);
        // Нормализация штрихкода: обрезаем пробелы и управляющие символы
        $barcodeRaw = (string)$validated['code'];
        $barcode    = preg_replace('/[\s\x{0009}\x{000A}\x{000D}]+/u', '', trim($barcodeRaw)) ?: '';

        $quantity   = max(1, (int)($validated['quantity'] ?? 1));

        // ===== 2) Контекст логов =====
        $ctxBase = [
            'cid'        => $cid,
            'ip'         => $request->ip(),
            'route'      => $request->path(),
            'ua'         => substr((string)$request->userAgent(), 0, 256),
            'reqHeaders' => array_intersect_key($request->headers->all(), array_flip([
                'x-scan-id','accept','content-type','referer'
            ])),
            'reqQuery'   => $request->query(),
        ];

        \Log::info('scan.1c.addLineByNumber:start', $ctxBase + [
                'payload_raw' => $request->all(),
                'validated'   => [
                    'document_no' => $documentNo,
                    'code_len'    => strlen($barcode),
                    'has_cell_ref'=> array_key_exists('cell_ref', $validated),
                    'has_cell_name'=>array_key_exists('cell_name', $validated),
                    'quantity'    => $quantity,
                ],
            ]);

        // ===== 3) Определяем активную ячейку (сессия/кэш + справочник БД) =====
        $state      = $request->session()->get('active_cell') ?: \Illuminate\Support\Facades\Cache::get($this->cellCacheKey());
        $activeCell = is_array($state) ? ($state['cell'] ?? null) : null;

        $cellRow          = null;
        $cellNameFor1C    = null;
        $cellRefGuid      = null;

        if (!empty($activeCell)) {
            $cellRow = \DB::table('skladskie_yacheiki')
                ->where('number', $activeCell)
                ->orWhere('ssylka', $activeCell)
                ->orWhere('link',   $activeCell)
                ->first();
        }

        if ($cellRow) {
            // ваши поля: id | ssylka | sklad | link | number | room | reception_area | ...
            $cellNameFor1C = $cellRow->ssylka ?: ($cellRow->name ?? $cellRow->number);
            $cellRefGuid   = $cellRow->link ?? null;
        }

        // Приоритет входа: явный запрос перекрывает активную ячейку
        $cellRefReq  = trim((string)($validated['cell_ref']  ?? ''));
        $cellNameReq = trim((string)($validated['cell_name'] ?? ''));
        if ($cellRefReq !== '')  { $cellRefGuid   = $cellRefReq; }
        if ($cellNameReq !== '') { $cellNameFor1C = $cellNameReq; }

        \Log::debug('scan.1c.addLineByNumber:cell.resolve', $ctxBase + [
                'activeCell_in'   => $activeCell,
                'cellRow_found'   => (bool)$cellRow,
                'cellRow_keys'    => $cellRow ? array_keys((array)$cellRow) : [],
                'cell_name_1c'    => $cellNameFor1C,
                'cell_ref_guid'   => $cellRefGuid,
                'session_state'   => $state,
            ]);

        // ===== 4) Формируем payload для 1С =====
        $payload = [
            'document_no' => $documentNo,
            'barcode'     => $barcode,
            'scan_id'     => $cid,
            'quantity'    => $quantity, // (новое) передаём количество в 1С
        ];
        if (!empty($cellRefGuid))   { $payload['cell_ref']  = (string)$cellRefGuid; }
        if (!empty($cellNameFor1C)) { $payload['cell_name'] = (string)$cellNameFor1C; }

        // ===== 5) Вызов 1С AddLine =====
        $url      = TsdOneC::url('AddLine', Auth::user());
        $login    = TsdOneC::login(Auth::user());
        $password = TsdOneC::password(Auth::user());

        $client = new \GuzzleHttp\Client([
            'timeout'     => 20,
            'verify'      => false,
            'http_errors' => false,
        ]);

        \Log::info('scan.1c.addLineByNumber:request', $ctxBase + [
                'cid'       => $cid,
                'url'       => $url,
                'payload'   => $payload,
                'diag_cell' => [
                    'from_active'  => $activeCell,
                    'resolved_name'=> $cellNameFor1C,
                    'resolved_ref' => $cellRefGuid,
                ],
            ]);

        $tCall0 = microtime(true);
        try {
            $resp = $client->post($url, [
                'headers' => [
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json; charset=utf-8',
                    'X-Correlation-ID' => $cid,
                ],
                'auth' => [$login, $password],
                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            $tCall1  = microtime(true);
            $status  = $resp->getStatusCode();
            $headers = $resp->getHeaders();
            $body    = (string)$resp->getBody();

            $json    = null;
            try { $json = $body ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : null; } catch (\Throwable $e) {}

            \Log::info('scan.1c.addLineByNumber:response', $ctxBase + [
                    'cid'        => $cid,
                    'status'     => $status,
                    'elapsed_ms' => (int)round(($tCall1 - $tCall0) * 1000),
                    'hdrs'       => array_intersect_key($headers, array_flip(['Content-Type','Content-Length','Date','Server'])),
                    'body_len'   => strlen($body),
                    'body_head'  => mb_substr($body, 0, 6000),
                    'json_keys'  => is_array($json) ? array_slice(array_keys($json), 0, 20) : [],
                ]);

            // Диагностика 5xx
            if ($status >= 500 && $status < 600) {
                \Log::warning('scan.1c.addLineByNumber:server500', $ctxBase + [
                        'cid'       => $cid,
                        'payload'   => $payload,
                        'cell_diag' => [
                            'activeCell_in' => $activeCell,
                            'cell_name_1c'  => $cellNameFor1C,
                            'cell_ref'      => $cellRefGuid,
                        ],
                        'body_head' => mb_substr($body, 0, 1000),
                    ]);
            }

            // ===== 6) Обработка результата =====
            if ($status === 404) {
                return response()->json([
                    'ok'   => false,
                    'cid'  => $cid,
                    'msg'  => 'Документ с номером не найден в 1С',
                    'echo' => ['document_no' => $documentNo, 'barcode' => $barcode, 'quantity' => $quantity],
                ], 404);
            }

            if ($status < 200 || $status >= 300) {
                return response()->json([
                    'ok'   => false,
                    'cid'  => $cid,
                    'msg'  => '1C HTTP '.$status,
                    'echo' => ['document_no' => $documentNo, 'barcode' => $barcode, 'quantity' => $quantity],
                    'body' => mb_substr($body, 0, 6000),
                ], 502);
            }

            // если 1С вернула JSON с ok:true
            if (is_array($json) && ($json['ok'] ?? false)) {
                $out = [
                    'ok'          => true,
                    'cid'         => $cid,
                    'document_no' => $json['document_no'] ?? $documentNo,
                    'doc_ref'     => $json['doc_ref']     ?? null,
                    'barcode'     => $json['barcode']     ?? $barcode,
                    'cell_name'   => $json['cell_name']   ?? ($payload['cell_name'] ?? null),
                    'cell_ref'    => $json['cell_ref']    ?? ($payload['cell_ref']  ?? null),
                    'quantity'    => $json['quantity']    ?? $quantity,
                    'reply'       => $json,
                    'elapsed_ms'  => (int)round((microtime(true) - $t0) * 1000),
                ];
                \Log::info('scan.1c.addLineByNumber:ok', $ctxBase + $out);
                return response()->json($out, 200);
            }

            // если 1С вернула пустое тело или "OK"
            $plain = trim($body);
            if ($plain === '' || strcasecmp($plain, 'OK') === 0) {
                $out = [
                    'ok'          => true,
                    'cid'         => $cid,
                    'document_no' => $documentNo,
                    'barcode'     => $barcode,
                    'cell_name'   => $payload['cell_name'] ?? null,
                    'cell_ref'    => $payload['cell_ref']  ?? null,
                    'quantity'    => $quantity,
                    'reply'       => ['ok' => true, 'mode' => 'empty-200'],
                    'elapsed_ms'  => (int)round((microtime(true) - $t0) * 1000),
                ];
                \Log::info('scan.1c.addLineByNumber:ok-empty', $ctxBase + $out);
                return response()->json($out, 200);
            }

            // всё остальное — ошибка бизнес-логики 1С
            \Log::warning('scan.1c.addLineByNumber:business-error', $ctxBase + [
                    'cid'      => $cid,
                    'document' => $documentNo,
                    'barcode'  => $barcode,
                    'quantity' => $quantity,
                    'body'     => mb_substr($plain !== '' ? $plain : json_encode($json, JSON_UNESCAPED_UNICODE), 0, 6000),
                ]);
            return response()->json([
                'ok'   => false,
                'cid'  => $cid,
                'msg'  => 'Ошибка 1С',
                'body' => $plain !== '' ? $plain : $json,
            ], 422);

        } catch (\Throwable $e) {
            \Log::error('scan.1c.addLineByNumber:exception', $ctxBase + [
                    'cid'     => $cid,
                    'err'     => $e->getMessage(),
                    'trace'   => substr($e->getTraceAsString(), 0, 4000),
                    'payload' => $payload,
                ]);
            return response()->json([
                'ok'   => false,
                'cid'  => $cid,
                'msg'  => 'Ошибка вызова 1С: '.$e->getMessage(),
            ], 500);
        }
    }

//    public function addLineByNumber(\Illuminate\Http\Request $request)
//    {
//        // ===== 0) Корреляция и тайминг =====
//        $t0  = microtime(true);
//        $cid = (string)($request->input('scan_id')
//            ?: $request->header('X-Scan-ID')
//                ?: \Illuminate\Support\Str::uuid());
//
//        // ===== 1) Вход и валидация =====
//        $validated = $request->validate([
//            'document_no' => 'required|string|max:50',   // номер документа
//            'code'        => 'required|string|max:64',   // штрихкод
//            'cell_ref'    => 'nullable|string|max:150',  // GUID ячейки
//            'cell_name'   => 'nullable|string|max:150',  // имя ячейки
//            'scan_id'     => 'nullable|string|max:64',
//        ]);
//
//        $documentNo = trim((string)$validated['document_no']);
//        $barcode    = (string)$validated['code'];
//
//        // ===== 2) Контекст логов =====
//        $ctxBase = [
//            'cid'        => $cid,
//            'ip'         => $request->ip(),
//            'route'      => $request->path(),
//            'ua'         => substr((string)$request->userAgent(), 0, 256),
//            'reqHeaders' => array_intersect_key($request->headers->all(), array_flip([
//                'x-scan-id','accept','content-type','referer'
//            ])),
//            'reqQuery'   => $request->query(),
//        ];
//
//        \Log::info('scan.1c.addLineByNumber:start', $ctxBase + ['payload_raw' => $request->all()]);
//
//        // ===== 3) Определяем ячейку =====
//        $state      = $request->session()->get('active_cell') ?: \Illuminate\Support\Facades\Cache::get($this->cellCacheKey());
//        $activeCell = is_array($state) ? ($state['cell'] ?? null) : null;
//
//        $cellRow           = null;
//        $cellNameFor1C     = null;
//        $cellRefGuid       = null;
//
//        if (!empty($activeCell)) {
//            $cellRow = \DB::table('skladskie_yacheiki')
//                ->where('number', $activeCell)
//                ->orWhere('ssylka', $activeCell)
//                ->orWhere('link',   $activeCell)
//                ->first();
//        }
//
//        if ($cellRow) {
//            $cellNameFor1C = $cellRow->ssylka ?: ($cellRow->name ?? $cellRow->number);
//            $cellRefGuid   = $cellRow->link ?? null;
//        }
//
//        $cellRefReq  = trim((string)($validated['cell_ref']  ?? ''));
//        $cellNameReq = trim((string)($validated['cell_name'] ?? ''));
//        if ($cellRefReq !== '')  { $cellRefGuid   = $cellRefReq; }
//        if ($cellNameReq !== '') { $cellNameFor1C = $cellNameReq; }
//
//        \Log::debug('scan.1c.addLineByNumber:cell.resolve', $ctxBase + [
//                'activeCell_in' => $activeCell,
//                'cellRow_found' => (bool)$cellRow,
//                'cell_ref'      => $cellRefGuid,
//                'cell_name_1c'  => $cellNameFor1C,
//                'session_state' => $state,
//            ]);
//
//        // ===== 4) Формируем payload для 1С =====
//        $payload = [
//            'document_no' => $documentNo,
//            'barcode'     => $barcode,
//            'scan_id'     => $cid,
//        ];
//        if (!empty($cellRefGuid))   { $payload['cell_ref']  = (string)$cellRefGuid; }
//        if (!empty($cellNameFor1C)) { $payload['cell_name'] = (string)$cellNameFor1C; }
//
//        // ===== 5) Вызов 1С AddLine =====
//        $url      = 'http://192.168.170.105/PROD_copy/hs/tsd/AddLine';
//        $login    = 'КучеренкоД';
//        $password = 'NitraPa$$@0@!';
//
//        $client = new \GuzzleHttp\Client([
//            'timeout'     => 20,
//            'verify'      => false,
//            'http_errors' => false,
//        ]);
//
//        \Log::info('scan.1c.addLineByNumber:request', $ctxBase + [
//                'cid'     => $cid,
//                'url'     => $url,
//                'payload' => $payload,
//            ]);
//
//        $tCall0 = microtime(true);
//        try {
//            $resp = $client->post($url, [
//                'headers' => [
//                    'Accept'           => 'application/json',
//                    'Content-Type'     => 'application/json; charset=utf-8',
//                    'X-Correlation-ID' => $cid,
//                ],
//                'auth' => [$login, $password],
//                'body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
//            ]);
//            $tCall1  = microtime(true);
//            $status  = $resp->getStatusCode();
//            $body    = (string)$resp->getBody();
//            $json    = null;
//            try { $json = $body ? json_decode($body, true, 512, JSON_THROW_ON_ERROR) : null; } catch (\Throwable $e) {}
//
//            \Log::info('scan.1c.addLineByNumber:response', $ctxBase + [
//                    'cid'        => $cid,
//                    'status'     => $status,
//                    'elapsed_ms' => (int)round(($tCall1 - $tCall0) * 1000),
//                    'body_head'  => mb_substr($body, 0, 6000),
//                    'json_keys'  => is_array($json) ? array_slice(array_keys($json), 0, 20) : [],
//                ]);
//
//            // ===== 6) Обработка результата =====
//            if ($status === 404) {
//                return response()->json([
//                    'ok'  => false,
//                    'cid' => $cid,
//                    'msg' => 'Документ с номером не найден в 1С',
//                    'echo'=> ['document_no' => $documentNo, 'barcode' => $barcode],
//                ], 404);
//            }
//
//            if ($status < 200 || $status >= 300) {
//                return response()->json([
//                    'ok'   => false,
//                    'cid'  => $cid,
//                    'msg'  => '1C HTTP '.$status,
//                    'echo' => ['document_no' => $documentNo, 'barcode' => $barcode],
//                    'body' => mb_substr($body, 0, 6000),
//                ], 502);
//            }
//
//            // если 1С вернула JSON с ok:true
//            if (is_array($json) && ($json['ok'] ?? false)) {
//                return response()->json([
//                    'ok'          => true,
//                    'cid'         => $cid,
//                    'document_no' => $json['document_no'] ?? $documentNo,
//                    'doc_ref'     => $json['doc_ref']     ?? null,
//                    'barcode'     => $json['barcode']     ?? $barcode,
//                    'cell_name'   => $json['cell_name']   ?? ($payload['cell_name'] ?? null),
//                    'cell_ref'    => $json['cell_ref']    ?? ($payload['cell_ref']  ?? null),
//                    'reply'       => $json,
//                    'elapsed_ms'  => (int)round((microtime(true) - $t0) * 1000),
//                ], 200);
//            }
//
//            // если 1С вернула пустое тело или "OK"
//            $plain = trim($body);
//            if ($plain === '' || strcasecmp($plain, 'OK') === 0) {
//                return response()->json([
//                    'ok'          => true,
//                    'cid'         => $cid,
//                    'document_no' => $documentNo,
//                    'barcode'     => $barcode,
//                    'cell_name'   => $payload['cell_name'] ?? null,
//                    'cell_ref'    => $payload['cell_ref']  ?? null,
//                    'reply'       => ['ok' => true, 'mode' => 'empty-200'],
//                    'elapsed_ms'  => (int)round((microtime(true) - $t0) * 1000),
//                ], 200);
//            }
//
//            // всё остальное — ошибка
//            return response()->json([
//                'ok'   => false,
//                'cid'  => $cid,
//                'msg'  => 'Ошибка 1С',
//                'body' => $plain !== '' ? $plain : $json,
//            ], 422);
//
//        } catch (\Throwable $e) {
//            \Log::error('scan.1c.addLineByNumber:exception', $ctxBase + [
//                    'cid'     => $cid,
//                    'err'     => $e->getMessage(),
//                    'trace'   => substr($e->getTraceAsString(), 0, 4000),
//                    'payload' => $payload,
//                ]);
//            return response()->json([
//                'ok'   => false,
//                'cid'  => $cid,
//                'msg'  => 'Ошибка вызова 1С: '.$e->getMessage(),
//            ], 500);
//        }
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
