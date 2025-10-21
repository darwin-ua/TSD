@extends('layouts.app')
@section('content')
    @include('sklad.header_adm')
    <style>
        #oneCResults .list-group-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .pos-title {
            font-weight: 600;
            line-height: 1.25;
            word-break: break-word;
            font-size: 12px;
        }

        .pos-qty {
            margin-top: 2px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .qty-chip {
            display: inline-block;
            padding: 2px 8px;
            border: 2px solid #333;
            border-radius: 8px;
            background: #fffbe6;
            font-weight: 700;
            font-size: 13px;
            line-height: 1.1;
            white-space: nowrap;
        }

        .qty-chip.fact {
            background: #e7f1ff;
        }

        .hl-barcode {
            background-color: #fff3cd !important;
        }

        .small-muted {
            font-size: 13px;
            color: #6c757d;
        }
    </style>

    @if(!empty($activeCell))
        @php
            // Приоритет: ssylka → room → "№ number" → сам activeCell
            $displayCell = $cellName
                ?? ($cellRow->ssylka ?? null)
                ?? ($cellRow->room   ?? null)
                ?? (!empty($cellRow->number) ? '№ '.$cellRow->number : null)
                ?? $activeCell;
        @endphp
        <div class="alert alert-info">
            Ячейка: <b>{{ $displayCell }}</b>
        </div>
    @else
        <div class="alert alert-warning">
            Ячейка не выбрана. Відскануйте ячейку на екрані «Розміщення».
        </div>
    @endif
    <div class="content" style="min-height:100%; padding:10px;">
        <section class="content">
            <div class="container-fluid" id="freeScanContainer">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="{{ route('sklad.index') }}" class="btn btn-secondary">←</a>
                    <div class="text-center flex-grow-1">
                        <strong></strong>
                        <strong id="numberDoc"></strong>
                    </div>
                    <div style="width:88px"></div>
                </div>
                <div class="mb-3">
                    <input id="freeBarcodeInput" type="text" class="form-control form-control-lg"
                           placeholder="Сканируйте штрихкод..." autocomplete="off">

                </div>
                {{-- Результаты из 1С (рендерим сюда) --}}
                <div id="oneCResults" class="list-group mb-3 d-none"></div>
                {{-- Кнопка "Отправить" появляется только когда есть позиции --}}
                <div class="mb-3 d-none" id="sendWrap">
                    <button id="sendBtn" class="btn btn-primary btn-lg w-100" disabled>
                        Відправити
                    </button>
                </div>
                {{--                <div id="freeLog" class="list-group"></div>--}}
                <div class="mt-3">
                    <a href="{{ route('sklad.index') }}" class="btn btn-dark">Головна</a>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const activeCell = @json($activeCell);
            const cellRow = @json($cellRow);

            console.log("📦 Ячейка:", activeCell);
            console.log("📋 Запись из таблицы skladskie_yacheiki:", cellRow);
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('freeBarcodeInput');
            const logBox = document.getElementById('freeLog');
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const URL = @json(route('sklad.scan.free.store'));

            setTimeout(() => input?.focus(), 100);

            async function send(code) {
                try {
                    const resp = await fetch(URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            code: String(code).trim(),
                            quantity: 1
                        }),
                    });

                    const raw = await resp.text();
                    let data = {};
                    try {
                        data = raw ? JSON.parse(raw) : {};
                    } catch (e) {
                    }

                    if (!resp.ok || !data.ok) {
                        addRow('❌ ' + (data.msg || ('HTTP ' + resp.status)));
                        return;
                    }
                    addRow('✅ Принято: ' + code);
                } catch (e) {
                    addRow('❌ Помилка мережі/сервера');
                }
            }

            function addRow(text) {
                const a = document.createElement('div');
                a.className = 'list-group-item';
                a.textContent = '[' + (new Date()).toLocaleTimeString() + '] ' + text;
                logBox.prepend(a);
            }

            input?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    const v = input.value.trim();
                    input.value = '';
                    if (!v) return;
                    send(v);
                }
            });
        });

        console.log("📦 $activeCell =", @json($activeCell));
        console.log("📋 $cellRow =", @json($cellRow));
        console.log("🏷️ $cellName =", @json($cellName));
        console.log("🗄️ Сессия scan_state =", @json(session('scan_state')));

        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('freeBarcodeInput');
            const resultsBox = document.getElementById('oneCResults');

            // NEW: обёртка и кнопка «Відправити»
            const sendWrap = document.getElementById('sendWrap');
            const sendBtn = document.getElementById('sendBtn');

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const URL = @json(route('sklad.scan.free.store'));          // локальное сохранение
            const PING1C = @json(route('sklad.tsd.creating_blank'));       // прокси в 1С
            // const SUBMIT_URL = @json(route('sklad.tsd.finish_accommodation'));

            // авто-скан без Enter
            const AUTO_MIN_LEN = 8, AUTO_SILENCE_MS = 120;
            let autoTimer = null, justAutoCommitted = false;
            setTimeout(() => input?.focus(), 100);

            const makeCid = () => Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);

            // счётчики и кэш позиций
            const scanCountByBarcode = new Map(); // {barcode -> scans}
            const itemsByBarcode = new Map(); // {barcode -> { ...item, barcode, ts }}

            // Показывать/скрывать кнопку «Відправити»
            function ensureSendVisibility() {
                const anyItems = itemsByBarcode.size > 0;
                if (anyItems) {
                    resultsBox.classList.remove('d-none');
                    sendWrap?.classList.remove('d-none');
                    sendBtn?.removeAttribute('disabled');
                } else {
                    sendBtn?.setAttribute('disabled', 'disabled');
                    sendWrap?.classList.add('d-none');
                }
            }

            function renderOneCRow(item, count) {
                const li = document.createElement('div');
                li.className = 'list-group-item';

                const title = document.createElement('div');
                title.className = 'pos-title';
                title.textContent = item.nomen || '—';

                const qty = document.createElement('div');
                qty.className = 'pos-qty';
                const chip = document.createElement('span');
                chip.className = 'qty-chip fact';
                chip.textContent = `Сканы: ${count}`;
                qty.appendChild(chip);

                li.appendChild(title);
                li.appendChild(qty);
                return li;
            }

            // НЕ затираем список при ошибках, просто показываем строку-сообщение сверху
            function showInfoRow(text) {
                resultsBox.classList.remove('d-none');
                const row = document.createElement('div');
                row.className = 'list-group-item text-muted';
                row.textContent = text;
                resultsBox.prepend(row); // prepend, чтобы не потерять накопленные позиции
            }

            // Обновляем кэш позиций по результатам очередного скана
            function upsertItems(items, meta = {}) {
                if (!Array.isArray(items) || items.length === 0) return;

                const now = Date.now();
                for (const it of items) {
                    const bc = (it.barcode || meta.barcode || '').trim();
                    if (!bc) continue;

                    // сохраняем «последнюю версию» айтема + метку времени
                    const prev = itemsByBarcode.get(bc) || {};
                    itemsByBarcode.set(bc, {
                        ...prev,
                        ...it,
                        barcode: bc,
                        ts: now
                    });
                }
            }

            // Полный ререндер из кэша (сортировка: новые сверху)
            function renderAll() {
                resultsBox.innerHTML = '';
                resultsBox.classList.remove('d-none');

                const arr = Array.from(itemsByBarcode.values())
                    .sort((a, b) => (b.ts || 0) - (a.ts || 0));

                for (const it of arr) {
                    const cnt = scanCountByBarcode.get(it.barcode) || 0;
                    resultsBox.appendChild(renderOneCRow(it, cnt));
                }
                ensureSendVisibility();
            }

            async function call1C(code, cid) {
                try {
                    const r = await fetch(PING1C, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json', 'Content-Type': 'application/json',
                            'X-Scan-ID': cid
                        },
                        body: JSON.stringify({code: String(code).trim(), scan_id: cid}),
                    });
                    const raw = await r.text();
                    let data = {};
                    try {
                        data = raw ? JSON.parse(raw) : {};
                    } catch (_) {
                    }

                    // --- добавь это ---
                    // если 1С вернула объект с ключом reply — берём оттуда документ
                    const docPart = data?.reply && typeof data.reply === 'object' ? data.reply : data;
                    // вставляем номер документа в <strong id="numberDoc">
                    setDocNumber(docPart);
                    // --- конец вставки ---

                    const payload = data?.items ? data
                        : (data?.reply?.items ? data.reply
                            : null);

                    if (!r.ok || !data.ok || !payload) {
                        // не затираем список — просто сообщение
                        showInfoRow(`Не знайдено збігів (${code})`);
                        return;
                    }

                    upsertItems(payload.items || [], {barcode: payload.barcode || code});
                    renderAll();
                } catch (e) {
                    showInfoRow(`1С недоступна${code ? ' (' + code + ')' : ''}`);
                }
            }

            async function saveLocal(code, cid) {
                const resp = await fetch(URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-Scan-ID': cid
                    },
                    body: JSON.stringify({code: String(code).trim(), quantity: 1, scan_id: cid}),
                });
                const raw = await resp.text();
                let data = {};
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch (_) {
                }
                if (!resp.ok || !data.ok) throw new Error(data.msg || ('HTTP ' + resp.status));
                return data;
            }

            async function send(code) {
                const cid = makeCid();

                // учтём локальный счётчик «Сканы: N» для этого кода
                const bcKey = String(code).trim();
                scanCountByBarcode.set(bcKey, (scanCountByBarcode.get(bcKey) || 0) + 1);

                // параллельно: 1С + локально
                await Promise.allSettled([call1C(code, cid), saveLocal(code, cid)]);
                // renderAll вызывается внутри call1C при успехе; при ошибке список не чистится
            }

            // авто-триггер без Enter
            input?.addEventListener('input', () => {
                clearTimeout(autoTimer);
                const v = input.value.trim();
                if (!v) return;
                if (v.length >= AUTO_MIN_LEN) autoTimer = setTimeout(commit, AUTO_SILENCE_MS);
            });

            input?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    if (justAutoCommitted) return;
                    commit();
                }
            });

            async function commit() {
                const v = input.value.trim();
                if (!v) return;
                input.value = '';
                justAutoCommitted = true;
                setTimeout(() => justAutoCommitted = false, 200);
                await send(v);
            }

            // «Відправити» — отправка сводки по накопленным штрихкодам
            sendBtn?.addEventListener('click', async () => {
                if (sendBtn.disabled) return;

                const payload = Array.from(scanCountByBarcode.entries())
                    .map(([barcode, scans]) => ({barcode, scans}))
                    .filter(x => x.barcode && x.scans > 0);

                if (!payload.length) return;

                const lines = payload.map(x => `${x.barcode} — ${x.scans}`).join('\n');
                if (!confirm('Відправити в обробку:\n\n' + lines)) return;

                try {
                    sendBtn.disabled = true;
                    sendBtn.textContent = 'Відправляємо...';

                    // === 1) обычная логика отправки (остаётся как была) ===
                    /*
                    const resp = await fetch(SUBMIT_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ items: payload })
                    });
                    const raw = await resp.text();
                    const data = raw ? JSON.parse(raw) : {};
                    if (!resp.ok || data.ok === false) throw new Error(data.msg || ('HTTP ' + resp.status));
                    */
                    // === 2) ДОПОЛНЕНИЕ: после успешной отправки вызываем finishAcceptance ===
                    const number = (window.currentDocumentId || '').trim();
                    if (number) {
                        const FINISH_URL = @json(route('sklad.tsd.finish_acceptance'));
                        const resp2 = await fetch(FINISH_URL, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({number})
                        });
                        const raw2 = await resp2.text();
                        let data2 = {};
                        try {
                            data2 = raw2 ? JSON.parse(raw2) : {};
                        } catch (_) {
                        }

                        if (!resp2.ok || data2.ok === false) {
                            alert('⚠️ Документ не завершено: ' + (data2.msg || ('HTTP ' + resp2.status)));
                        } else {
                            alert('✅ Готово: ' + (data2.Документ || data2.Номер || number));
                        }
                    } else {
                        console.warn('[finish] номер документа не визначено');
                    }

                    // === 3) при желании можно вернуть на головну ===
                    // window.location.href = '/sklad';

                } catch (e) {
                    alert('Помилка відправки: ' + (e?.message || e));
                } finally {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Відправити';
                }
            });

        });

        document.addEventListener('DOMContentLoaded', () => {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const PING1C = @json(route('sklad.tsd.creating_blank'));

            // вытянем исполнителя из профиля
            const DATA_EXECUTOR = @json(optional(auth()->user())->data_executor
                         ?? optional(auth()->user())->name
                         ?? '');

            async function call1C(code, cid) {
                try {
                    const r = await fetch(PING1C, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Scan-ID': cid
                        },
                        body: JSON.stringify({
                            code: String(code).trim(),
                            scan_id: cid,
                            data_executor: DATA_EXECUTOR, // <-- добавили
                            // можно и room пробросить явно:
                            // room: 'ГП (ячейки)'
                        }),
                    });
                    // ... дальше без изменений
                } catch (e) { /* ... */
                }
            }
        });

        const FINISH_URL = @json(route('sklad.tsd.finish_acceptance'));

        async function finishDoc(number) {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const r = await fetch(FINISH_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    number: String(number).trim(),
                    // at: new Date().toISOString() // если нужно задать НаМомент
                })
            });

            const raw = await r.text();
            let data = {};
            try {
                data = raw ? JSON.parse(raw) : {};
            } catch (_) {
            }

            if (!r.ok || data.ok === false) {
                alert('Помилка: ' + (data.msg || ('HTTP ' + r.status)));
                return;
            }
            // тут можешь красиво показать статус, Проведен, СтатусДокумента, Группы и т.д.
            console.log('FinishAcceptance:', data);
            alert('Готово: ' + (data.Документ || data.Номер));
        }

        // Достаём номер из разных мест, если какое-то поле пустое
        function getDocNumber(payload) {
            if (!payload) return "";

            // 1) Пробуем document_number
            if (payload.document_number && String(payload.document_number).trim() !== "") {
                return String(payload.document_number).trim();
            }

            // 2) Пробуем вытянуть из document_ref
            const ref = String(payload.document_ref || "");
            let m = ref.match(/\b\d{2}-\d{8}\b/); // формат 00-00000358
            if (m) return m[0];

            // 3) Пробуем chosen_ref
            const cref = String(payload.doc_search?.chosen_ref || "");
            m = cref.match(/\b\d{2}-\d{8}\b/);
            if (m) return m[0];

            return "";
        }

        function setDocNumber(payload) {
            const num = getDocNumber(payload);
            const el = document.getElementById('numberDoc');
            if (!el) return;

            el.textContent = num || "—";
            window.currentDocumentId = num || "";
            // Если хочешь подсветить, когда номер появился впервые:
            if (num) {
                el.classList.add('text-success');
                // уберём подсветку чуть позже
                setTimeout(() => el.classList.remove('text-success'), 1500);
            }
            enableFinishIfNumber();
        }

        function enableFinishIfNumber() {
            const btn = document.getElementById('btnFinish');
            if (!btn) return;
            const num = (window.currentDocumentId || '').trim();
            if (num) btn.removeAttribute('disabled');
        }
    </script>

@endpush
