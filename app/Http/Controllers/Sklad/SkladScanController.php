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
use Illuminate\Support\Str;


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
        Log::info('scan.position.store: incoming', $this->ctx($request, [
            'payload' => $request->all()
        ]));

        // Валидатор
        $validator = Validator::make($request->all(), [
            'document_id'     => 'required|string|max:50',
            'warehouse_id'    => 'nullable|integer',
            'position_name'   => 'nullable|integer',
            'number_position' => 'nullable|integer',
            'quantity'        => 'nullable|integer',
            'code'            => 'required|string|max:' . self::POS_CODE_MAX,
            'amount'          => 'nullable|integer',
            'status'          => 'nullable|integer',

            // чисто для логов:
            'doc_link'        => 'nullable|string|max:255',
            'nom'             => 'nullable|string|max:255',
            'line_no'         => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            Log::warning('scan.position.store: validation failed', $this->ctx($request, [
                'errors' => $validator->errors()->toArray()
            ]));
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        // активная ячейка
        $state = $request->session()->get('active_cell') ?: Cache::get($this->cellCacheKey());
        Log::info('scan.position.store: active_cell state', $this->ctx($request, ['state' => $state]));

        if (!$state || empty($state['cell'])) {
            Log::warning('scan.position.store: no active cell', $this->ctx($request));
            return response()->json(['ok' => false, 'msg' => 'Активная ячейка не выбрана'], 422);
        }

        // безопасное усечение кода
        $safeCode = mb_substr((string)$request->input('code'), 0, self::POS_CODE_MAX);

        // ===== Нормализация номера документа =====
        $docIdRaw = trim((string)$request->input('document_id'));
        if ($docIdRaw === '') {
            return response()->json(['ok' => false, 'msg' => 'Порожній document_id'], 422);
        }

        if (ctype_digit($docIdRaw)) {
            // если просто число — делаем формат "00-00000270"
            $documentId = '00-' . str_pad($docIdRaw, 8, '0', STR_PAD_LEFT);
        } else {
            $documentId = mb_substr($docIdRaw, 0, 50);
        }

        // ===== Определяем warehouse_id =====
        $warehouseId = $request->input('warehouse_id');
        if ($warehouseId === null && is_array($state) && !empty($state['warehouse_id'])) {
            $warehouseId = (int)$state['warehouse_id'];
        }

        try {
            $row = ScanPositionDocument::create([
                'user_register'   => Auth::user()->name ?? 'system',
                'document_id'     => $documentId,
                'warehouse_id'    => $warehouseId,
                'position_name'   => $request->input('position_name'),
                'number_position' => $request->input('number_position'),
                'quantity'        => $request->input('quantity', 1),
                'cell'            => $state['cell'],
                'code'            => $safeCode,
                'amount'          => $request->input('amount'),
                'status'          => $request->input('status', 1),
            ]);

            // подробный лог
            Log::info('scan.position.store: SAVED', $this->ctx($request, [
                'row_id'       => $row->id,
                'document_id'  => $row->document_id,
                'warehouse_id' => $row->warehouse_id,
                'cell'         => $row->cell,
                'code'         => $row->code,
                'nom'          => $request->input('nom'),
                'line_no'      => $request->input('line_no'),
                'doc_link'     => $request->input('doc_link'),
            ]));

            return response()->json(['ok' => true, 'id' => $row->id]);
        } catch (QueryException $qe) {
            Log::error('scan.position.store: DB error', $this->ctx($request, [
                'message' => $qe->getMessage(),
                'sqlState'=> $qe->errorInfo[0] ?? null,
                'sqlCode' => $qe->errorInfo[1] ?? null,
            ]));
            return response()->json(['ok' => false, 'msg' => 'DB error: '.$qe->getMessage()], 500);
        } catch (Throwable $e) {
            Log::error('scan.position.store: fatal error', $this->ctx($request, [
                'message' => $e->getMessage()
            ]));
            return response()->json(['ok' => false, 'msg' => 'Server error'], 500);
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
