
@extends('layouts.app')
@section('content')


    @include('sklad.header_adm')
    <style>
        #oneCResults .list-group-item{ display:flex; flex-direction:column; gap:6px; }
        .pos-title{ font-weight:600; line-height:1.25; word-break:break-word; }
        .pos-qty{ margin-top:2px; display:flex; gap:8px; flex-wrap:wrap; }
        .qty-chip{
            display:inline-block; padding:2px 8px; border:2px solid #333;
            border-radius:8px; background:#fffbe6; font-weight:700; font-size:.95em; line-height:1.1; white-space:nowrap;
        }
        .qty-chip.fact{ background:#e7f1ff; }
        .hl-barcode{ background-color:#fff3cd !important; }
        .small-muted{ font-size:.85em; color:#6c757d; }
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
                        <strong>Сканування</strong>
                    </div>
                    <div style="width:88px"></div>
                </div>
                <div class="mb-3">
                    <input id="freeBarcodeInput" type="text" class="form-control form-control-lg"
                           placeholder="Сканируйте штрихкод..." autocomplete="off">

                </div>

                {{-- Результаты из 1С (рендерим сюда) --}}
                <div id="oneCResults" class="list-group mb-3 d-none"></div>

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
            const cellRow    = @json($cellRow);

            console.log("📦 Ячейка:", activeCell);
            console.log("📋 Запись из таблицы skladskie_yacheiki:", cellRow);
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input  = document.getElementById('freeBarcodeInput');
            const logBox = document.getElementById('freeLog');
            const csrf   = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const URL    = @json(route('sklad.scan.free.store'));

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
                    try { data = raw ? JSON.parse(raw) : {}; } catch(e) {}

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
    </script>
    <script>
        console.log("📦 $activeCell =", @json($activeCell));
        console.log("📋 $cellRow =", @json($cellRow));
        console.log("🏷️ $cellName =", @json($cellName));
        console.log("🗄️ Сессия scan_state =", @json(session('scan_state')));
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input      = document.getElementById('freeBarcodeInput');
            const resultsBox = document.getElementById('oneCResults');

            const csrf   = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const URL    = @json(route('sklad.scan.free.store'));          // локальное сохранение
            const PING1C = @json(route('sklad.tsd.creating_blank'));       // прокси в 1С

            // авто-скан без Enter
            const AUTO_MIN_LEN = 8, AUTO_SILENCE_MS = 120;
            let autoTimer = null, justAutoCommitted = false;
            setTimeout(() => input?.focus(), 100);

            const makeCid = () => Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,8);

            // счётчик сканов по штрихкоду за текущую сессию страницы (для «Сканы: N»)
            const scanCountByBarcode = new Map();

            function renderOneCRow(item, count){
                const li = document.createElement('div');
                li.className = 'list-group-item';

                const title = document.createElement('div');
                title.className = 'pos-title';
                title.textContent = item.nomen || '—';

                const sub = document.createElement('div');
                sub.className = 'small-muted';
                const ch = (item.characteristic && String(item.characteristic).trim()) ? item.characteristic : '—';
                const pk = (item.package && String(item.package).trim()) ? item.package : '—';
                sub.textContent = `Характеристика: ${ch} · Упаковка: ${pk}`;

                const qty = document.createElement('div');
                qty.className = 'pos-qty';
                const chip = document.createElement('span');
                chip.className = 'qty-chip fact';
                chip.textContent = `Сканы: ${count}`;
                qty.appendChild(chip);

                const bc = document.createElement('div');
                bc.className = 'small-muted';
                bc.textContent = item.barcode ? `ШК: ${item.barcode}` : '';

                li.appendChild(title);
                li.appendChild(sub);
                li.appendChild(qty);
                li.appendChild(bc);
                return li;
            }

            function showInfoRow(text){
                resultsBox.innerHTML = '';
                resultsBox.classList.remove('d-none');
                const row = document.createElement('div');
                row.className = 'list-group-item text-muted';
                row.textContent = text;
                resultsBox.appendChild(row);
            }

            // БЕЗ ШАПКИ: только позиции; если пусто/ошибка — одна строка сообщения
            function renderOneCItems(items, meta = {}){
                if (!Array.isArray(items) || items.length === 0){
                    showInfoRow(meta.barcode ? `Не знайдено збігів (${meta.barcode})` : 'Не знайдено збігів');
                    return;
                }
                resultsBox.innerHTML = '';
                resultsBox.classList.remove('d-none');
                items.forEach(it => {
                    const bc = it.barcode || meta.barcode || '';
                    const cnt = scanCountByBarcode.get(bc) || 0;
                    resultsBox.appendChild( renderOneCRow(it, cnt) );
                });
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
                        body: JSON.stringify({ code: String(code).trim(), scan_id: cid }),
                    });
                    const raw = await r.text();
                    let data = {};
                    try { data = raw ? JSON.parse(raw) : {}; } catch(_){}
                    const payload = data?.items ? data : (data?.reply?.items ? data.reply : null);

                    if (!r.ok || !data.ok || !payload) {
                        renderOneCItems([], { barcode: code });
                        return;
                    }
                    renderOneCItems(payload.items || [], { barcode: payload.barcode || code });
                } catch(e) {
                    showInfoRow(`1С недоступна${code ? ' ('+code+')' : ''}`);
                }
            }

            async function saveLocal(code, cid) {
                const resp = await fetch(URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest',
                        'Accept':'application/json', 'Content-Type':'application/json',
                        'X-Scan-ID': cid
                    },
                    body: JSON.stringify({ code: String(code).trim(), quantity: 1, scan_id: cid }),
                });
                const raw = await resp.text();
                let data = {};
                try { data = raw ? JSON.parse(raw) : {}; } catch(_){}
                if (!resp.ok || !data.ok) throw new Error(data.msg || ('HTTP ' + resp.status));
                return data;
            }

            async function send(code) {
                const cid = makeCid();

                // учтём локальный счётчик «Сканы: N» для этого кода
                const bcKey = String(code).trim();
                scanCountByBarcode.set(bcKey, (scanCountByBarcode.get(bcKey) || 0) + 1);

                // параллельно: 1С + локально
                await Promise.allSettled([ call1C(code, cid), saveLocal(code, cid) ]);
                // никаких лент/логов — только перерисованный блок результатов
            }

            // авто-триггер без Enter
            input?.addEventListener('input', () => {
                clearTimeout(autoTimer);
                const v = input.value.trim(); if (!v) return;
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
                const v = input.value.trim(); if (!v) return;
                input.value = '';
                justAutoCommitted = true;
                setTimeout(() => justAutoCommitted = false, 200);
                await send(v);
            }
        });
    </script>
@endpush
