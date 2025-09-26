@extends('layouts.app')
@section('content')
    @include('sklad.header_adm')
    <style>
        .hl-barcode {
            background-color: #fff3cd !important; /* мягко-жёлтый */
        }
        .list-group-item.hl-barcode {
            font-weight: 600;
        }
        .btn-arrow {
            width: 40px;
            height: 40px;
            font-size: 20px;
            border-radius: 5px;
            border: none;
        }
        .custom-tab {
            background-color: #b3b3b3;
            color: white;
            border: 1px solid #999;
        }
        .custom-tab.active {
            background-color: #999999;
            color: white;
        }
        .nav-tabs .nav-link {
            border-radius: 4px 4px 0 0;
        }
        .nav-tabs {
            border-bottom: none;
        }
        .doc-header {
            font-weight: bold;
            background: #f2f2f2;
            padding: 8px 12px;
            margin-top: 10px;
            cursor: pointer;
        }
        .list-group-item {
            font-size: 12px;
        }
    </style>
    <div class="content" style="min-height: 100%; padding: 10px;">
        <section class="content">
            <div class="container-fluid">

                {{-- Верхняя панель --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button id="btnBack" type="button" class="btn btn-arrow bg-secondary text-white d-none" aria-label="Назад">←</button>

                    <div class="text-center flex-grow-1">
                        <strong id="pageTitle">Документы отбора</strong>
                    </div>
{{--                    <button class="btn btn-arrow bg-secondary text-white" disabled>&rarr;</button>--}}
                </div>
                {{-- Табы --}}
                <ul class="nav nav-tabs mb-3" id="docTabs">
                    <li class="nav-item">
                        <a class="nav-link custom-tab active" href="#" data-tab="gp">ГП</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link custom-tab" href="#" data-tab="dopy">ДО</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link custom-tab" href="#" data-tab="kom">КО</a>
                    </li>
                </ul>
                {{-- Поле штрихкода --}}
                <div id="barcodeWrapper" class="mb-3 d-none">
                    <input id="barcodeInput" type="text" class="form-control form-control-lg"
                           placeholder="Сканируйте номенклатуру или штрихкод..." autocomplete="off">

                </div>
                {{-- Список документов --}}
                <div id="documentsList">
                    @foreach(session('pick_orders', []) as $i => $doc)
                        <div class="card mb-2">
                            <div class="doc-header select-doc" data-doc-index="{{ $i }}">
                                {{ $doc['Ссылка'] ?? 'Без названия' }} — Статус: {{ $doc['Статус'] ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>
                {{-- Табличная часть выбранного документа --}}
                <div id="positionsList" class="d-none">
                    <ul class="list-group list-group-flush" id="positionsUl">
                        {{-- строки вставляются через JS --}}
                    </ul>
                </div>
                <div class="mt-3">
                    <a href="{{ route('sklad.index') }}" class="btn btn-dark">Главная</a>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const documents = @json(session('pick_orders', [])) || [];

            // ===== DOM =====
            const docList = document.getElementById('documentsList');
            const posList = document.getElementById('positionsList');
            const posUl   = document.getElementById('positionsUl');
            const backBtn = document.getElementById('btnBack');
            const title   = document.getElementById('pageTitle');
            const input   = document.getElementById('barcodeInput');
            const barcodeWrapper = document.getElementById('barcodeWrapper');

            // ===== Глобальные константы/роуты =====
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const CODE_MAX = 11; // длина столбца scan_position_document.code
            const POS_SAVE_URL   = @json(route('sklad.scan.position.store'));
            const STATE_FETCH_URL= @json(route('sklad.scan.session.state'));
            console.log('[INIT] POS_SAVE_URL =', POS_SAVE_URL);

            // ===== Активная ячейка (state из сессии/кеша) =====
            let activeState = null;

            // Баннер активной ячейки (создадим, если нет)
            let banner = document.getElementById('activeCellBanner');
            if (!banner) {
                banner = document.createElement('div');
                banner.id = 'activeCellBanner';
                banner.className = 'alert alert-info d-none';
                const container = document.querySelector('#tabsContainer') || document.body;
                container.prepend(banner);
            }

            // Tabs
            const tabs = document.querySelectorAll('.custom-tab');
            let activeTab = 'gp';
            tabs.forEach(t => t.dataset.baseLabel = t.textContent.trim());

            const norm = s => (String(s || '')).trim().toLowerCase();

            // ======== Маппинг вкладок -> Помещения ========
            const ROOM_BY_TAB = {
                gp:   ['гп (ячейки)', 'гп', 'готовая продукция'],
                dopy: ['до', 'доп', 'допы', 'доп. материалы'],
                kom:  ['ко', 'комплект', 'комплектующие']
            };

            // ======== Словари для определения склада по ячейке ========
            const CELL_KEYWORDS = {
                gp:   ['гп', 'готов', 'хранение гп', 'яч гп'],
                dopy: ['доп', 'до'],
                kom:  ['ком', 'комплект']
            };

            const WAREHOUSE_BY_TAB = { gp: 1, dopy: 2, kom: 3 }; // реальные id складов

            function detectTabByCellText(cellText) {
                const c = (cellText || '').toString().trim().toLowerCase();
                if (!c) return null;
                if (CELL_KEYWORDS.gp.some(k => c.includes(k)))   return 'gp';
                if (CELL_KEYWORDS.dopy.some(k => c.includes(k))) return 'dopy';
                if (CELL_KEYWORDS.kom.some(k => c.includes(k)))  return 'kom';
                return null;
            }

            function inferWarehouseFromFirstRowCell() {
                const firstLi = document.querySelector('#positionsUl li');
                if (!firstLi) return null;
                const cellTxt = firstLi.dataset.cell || '';
                const tab = detectTabByCellText(cellTxt);
                return tab ? (WAREHOUSE_BY_TAB[tab] ?? null) : null;
            }

            function setWarehouseInStateIfMissing() {
                if (activeState && !activeState.warehouse_id) {
                    activeState.warehouse_id = currentWarehouseId;
                    console.log('[STATE] warehouse_id set →', currentWarehouseId);
                }
            }

            function matchesTabByRoom(room, tab = activeTab) {
                const r = norm(room);
                const patterns = ROOM_BY_TAB[tab] || [];
                if (!patterns.length) return true;
                return patterns.some(p => r.includes(p));
            }

            function matchesTabByCell(cell, tab = activeTab) {
                const c = norm(cell);
                if (tab === 'gp')   return c.includes('гп') || c.includes('готов') || c === '';
                if (tab === 'dopy') return c.includes('доп') || c === '';
                if (tab === 'kom')  return c.includes('ком') || c === '';
                return true;
            }

            function updateTabBadges(counts) {
                const fallback = { gp: 'ГП', dopy: 'ДО', kom: 'КО' };
                tabs.forEach(t => {
                    const code = t.dataset.tab || 'gp';
                    const base = t.dataset.baseLabel || fallback[code] || t.textContent.trim();
                    t.textContent = `${base} ${counts[code] ?? 0}`;
                });
            }

            // ===== Активная ячейка: загрузка и баннер =====
            async function loadCellState() {
                try {
                    const r = await fetch(STATE_FETCH_URL, { headers: { 'Accept': 'application/json' }});
                    const j = await r.json().catch(() => ({}));
                    activeState = j.state || null;
                    console.log('[STATE] activeState =', activeState);

                    banner.classList.remove('d-none', 'alert-warning');
                    if (activeState?.cell) {
                        banner.classList.add('alert-info');
                        banner.textContent = 'Активная ячейка: ' + activeState.cell;
                    } else {
                        banner.classList.remove('alert-info');
                        banner.classList.add('alert-warning');
                        banner.textContent = 'Активная ячейка не выбрана. Сначала отсканируйте ячейку на экране "Размещение".';
                    }
                } catch(e) {
                    console.warn('Не удалось получить state ячейки', e);
                }
            }

            // ============== список документов ==============
            function renderDocuments() {
                docList.innerHTML = '';

                const docCounts = { gp:0, dopy:0, kom:0 };
                documents.forEach(d => {
                    if (matchesTabByRoom(d.Помещение, 'gp'))   docCounts.gp++;
                    if (matchesTabByRoom(d.Помещение, 'dopy')) docCounts.dopy++;
                    if (matchesTabByRoom(d.Помещение, 'kom'))  docCounts.kom++;
                });
                updateTabBadges(docCounts);

                const filtered = documents.filter(d => matchesTabByRoom(d.Помещение, activeTab));
                filtered.forEach((doc) => {
                    const realIndex = documents.indexOf(doc);
                    const card = document.createElement('div');
                    card.className = 'card mb-2';
                    card.innerHTML = `
              <div class="doc-header select-doc" data-doc-index="${realIndex}">
                ${doc.Ссылка ?? 'Без названия'} — Статус: ${doc.Статус ?? '-'}<br>
                <small>Помещение: ${doc.Помещение ?? '-'}</small>
              </div>`;
                    docList.appendChild(card);
                });

                docList.querySelectorAll('.select-doc').forEach(el => {
                    el.addEventListener('click', () => showPositions(el.dataset.docIndex));
                });
            }

            function showDocuments() {
                docList.classList.remove('d-none');
                posList.classList.add('d-none');
                backBtn.classList.add('d-none');
                barcodeWrapper.classList.add('d-none');
                title.textContent = 'Документы отбора';
                posUl.innerHTML = '';
                input.value = '';

                tabs.forEach(t => {
                    t.classList.remove('d-none', 'active');
                    t.textContent = t.dataset.baseLabel || t.textContent.trim();
                });
                activeTab = 'gp';
                tabs[0]?.classList.add('active');

                renderDocuments();
                loadCellState();
            }

            // ============== экран позиций документа ==============
            function recomputeCountsByCells() {
                const counts = { gp:0, dopy:0, kom:0 };
                document.querySelectorAll('#positionsUl li').forEach(li => {
                    const cell = li.dataset.cell || '';
                    if (matchesTabByCell(cell, 'gp'))   counts.gp++;
                    if (matchesTabByCell(cell, 'dopy')) counts.dopy++;
                    if (matchesTabByCell(cell, 'kom'))  counts.kom++;
                });
                updateTabBadges(counts);
            }

            function applyTabFilterInPositions() {
                document.querySelectorAll('#positionsUl li').forEach(li => {
                    const cell = li.dataset.cell || '';
                    li.classList.toggle('d-none', !matchesTabByCell(cell));
                });
                document.querySelectorAll('#positionsUl li.d-none')
                    .forEach(li => li.classList.remove('hl-barcode'));
            }

            function detectTabByRoom(room) {
                const r = norm(room);
                if (ROOM_BY_TAB.gp.some(p => r.includes(p)))   return 'gp';
                if (ROOM_BY_TAB.dopy.some(p => r.includes(p))) return 'dopy';
                if (ROOM_BY_TAB.kom.some(p => r.includes(p)))  return 'kom';
                return 'gp';
            }

            function getDocumentNoFromDoc(doc) {
                if (typeof doc.document_id === 'string' && doc.document_id.trim()) {
                    return doc.document_id.trim();
                }
                const m = String(doc.Ссылка || '').match(/\b00-\d+\b/);
                return m ? m[0] : '';
            }

            let currentDocIndex = null;
            let currentDocNo = '';
            let currentDoc = null;
            let currentWarehouseId = null;

            function showPositions(index) {
                const doc = documents[index];
                if (!doc) return;

                currentDocIndex = index;
                currentDocNo = getDocumentNoFromDoc(doc);
                currentDoc = doc;

                currentWarehouseId = (doc.warehouse_id ?? activeState?.warehouse_id) ?? null;

                window.currentDocumentId = currentDocNo;
                console.log('[DOC] opened', { link: doc?.Ссылка, currentDocNo, currentWarehouseId });

                const docTab = detectTabByRoom(doc.Помещение);
                activeTab = docTab;
                tabs.forEach(t => {
                    if (t.dataset.tab === docTab) {
                        t.classList.add('active');
                        t.classList.remove('d-none');
                    } else {
                        t.classList.add('d-none');
                        t.classList.remove('active');
                    }
                });

                docList.classList.add('d-none');
                posList.classList.remove('d-none');
                backBtn.classList.remove('d-none');
                barcodeWrapper.classList.remove('d-none');
                setTimeout(() => input.focus(), 100);

                title.textContent = doc.Ссылка?.match(/(00-\d+)/)?.[1] ?? 'Позиции документа';

                posUl.innerHTML = '';
                const rows = Array.isArray(doc.ТоварыРазмещение) ? doc.ТоварыРазмещение : [];
                rows.forEach((line, idx) => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    li.dataset.nom     = norm(line.Номенклатура);
                    li.dataset.barcode = norm(line.Штрихкод);
                    li.dataset.cell    = norm(line.Ячейка);
                    li.dataset.line    = String(line.НомерСтроки ?? (idx + 1)); // ← номер позиции

                    li.textContent =
                        `#${line.НомерСтроки ?? idx + 1} — ${line.Номенклатура ?? '-'}, ` +
                        `Кол: ${line.Количество ?? 0}, ` +
                        `Уп: ${line.КоличествоУпаковок ?? 0}` +
                        (line.Ячейка ? ` | Яч: ${line.Ячейка}` : '');

                    posUl.appendChild(li);
                });

                if (!currentWarehouseId) {
                    const wh = inferWarehouseFromFirstRowCell();
                    if (wh) {
                        currentWarehouseId = wh;
                        console.log('[WH] inferred from first row cell →', currentWarehouseId);
                    }
                }

                recomputeCountsByCells();
                applyTabFilterInPositions();

                setWarehouseInStateIfMissing();
                loadCellState();
            }

            // ============== переключение вкладок ==============
            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    activeTab = tab.dataset.tab || 'gp';

                    const onDocsScreen = !docList.classList.contains('d-none');
                    if (onDocsScreen) {
                        renderDocuments();
                    } else {
                        applyTabFilterInPositions();
                    }
                });
            });

            // ============== кнопка Назад ==============
            if (backBtn) {
                backBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    showDocuments();
                });
            }

            // ============== Сохранение позиции в БД ==============
            async function savePositionScan(rawCode) {
                const code = String(rawCode || '').trim();
                console.log('[SAVE] attempt', { code, currentDocNo, activeState, currentWarehouseId });

                if (!code) {
                    console.warn('[SAVE] empty code - skip');
                    return;
                }
                if (!activeState || !activeState.cell) {
                    console.warn('[SAVE] no active cell in state');
                    alert('Сперва отсканируйте ячейку (экран "Размещение").');
                    return;
                }
                if (!currentDocNo) {
                    console.warn('[SAVE] no currentDocNo');
                    alert('Не удалось определить номер документа.');
                    return;
                }

                // усечение кода по длине колонки
                const safeCode = code.length > CODE_MAX ? code.slice(0, CODE_MAX) : code;

                // найдём номер позиции по штрихкоду (первая подходящая строка)
                let numberPosition = null;
                const val = code.toLowerCase();
                document.querySelectorAll('#positionsUl li').forEach(li => {
                    if (numberPosition !== null) return; // уже нашли
                    const bc = (li.dataset.barcode || '').toLowerCase();
                    if (bc && (bc === val || bc.includes(val))) {
                        const n = parseInt(li.dataset.line || '', 10);
                        if (!Number.isNaN(n)) numberPosition = n;
                    }
                });

                try {
                    console.log('[SAVE] POST ->', POS_SAVE_URL);
                    const resp = await fetch(POS_SAVE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            document_id: String(currentDocNo),
                            warehouse_id: currentWarehouseId,
                            code: safeCode,
                            quantity: 1,

                            // новое:
                            number_position: numberPosition,

                            // чисто для логов:
                            doc_link: currentDoc?.Ссылка || null,
                            nom: null,
                            line_no: numberPosition,
                        }),
                    });

                    const raw = await resp.text();
                    console.log('[SAVE] HTTP', resp.status, resp.statusText);
                    console.log('[SAVE] RAW', raw);

                    let data = {};
                    try { data = raw ? JSON.parse(raw) : {}; } catch(e) {}

                    if (!resp.ok || !data.ok) {
                        console.warn('[SAVE] backend says NOT ok', data);
                        alert((data && (data.msg || JSON.stringify(data))) || ('HTTP ' + resp.status));
                        return;
                    }

                    // визуальная подсветка совпавшей строки
                    let matched = false;
                    document.querySelectorAll('#positionsUl li').forEach(li => {
                        const bc = (li.dataset.barcode || '').toLowerCase();
                        if (!matched && bc && (bc === val || bc.includes(val))) {
                            li.classList.add('hl-barcode');
                            matched = true;
                            li.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }
                    });
                } catch (e) {
                    console.error('[SAVE] fetch error', e);
                    alert('Помилка мережі/сервера');
                }
            }

            // ============== поиск/сканер ==============
            let debounceTimer, autoSaveTimer;

            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const valRaw = input.value.trim();
                    const val = valRaw.toLowerCase();
                    const isBarcodeQuery = /^\d{6,}$/.test(valRaw);

                    document.querySelectorAll('#positionsUl li').forEach(li => {
                        if (li.classList.contains('d-none')) {
                            li.classList.remove('hl-barcode');
                            return;
                        }
                        const nom = li.dataset.nom || '';
                        const bc  = li.dataset.barcode || '';
                        const match = isBarcodeQuery ? (bc === val || bc.includes(val)) : nom.includes(val);
                        li.classList.toggle('hl-barcode', Boolean(val) && match);
                    });

                    const first = document.querySelector('#positionsUl li.hl-barcode:not(.d-none)');
                    if (first) first.scrollIntoView({ block:'center', behavior:'smooth' });

                    clearTimeout(autoSaveTimer);
                    if (isBarcodeQuery) {
                        autoSaveTimer = setTimeout(() => {
                            console.log('[AUTO] trigger savePositionScan by input');
                            const v = input.value.trim();
                            input.value = '';
                            savePositionScan(v);
                        }, 120);
                    }
                }, 150);
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    const val = input.value.trim();
                    input.value = '';
                    console.log('[KEY] ' + e.key + ' -> savePositionScan');
                    savePositionScan(val);
                }
            });

            // старт
            showDocuments();
        });
    </script>

    {{--    <script>--}}
{{--        document.addEventListener('DOMContentLoaded', () => {--}}
{{--            const documents = @json(session('pick_orders', [])) || [];--}}

{{--            // ===== DOM =====--}}
{{--            const docList = document.getElementById('documentsList');--}}
{{--            const posList = document.getElementById('positionsList');--}}
{{--            const posUl   = document.getElementById('positionsUl');--}}
{{--            const backBtn = document.getElementById('btnBack');--}}
{{--            const title   = document.getElementById('pageTitle');--}}
{{--            const input   = document.getElementById('barcodeInput');--}}
{{--            const barcodeWrapper = document.getElementById('barcodeWrapper');--}}

{{--            // ===== Глобальные константы/роуты =====--}}
{{--            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';--}}
{{--            const CODE_MAX = 11; // длина столбца scan_position_document.code--}}
{{--            const POS_SAVE_URL   = @json(route('sklad.scan.position.store'));--}}
{{--            const STATE_FETCH_URL= @json(route('sklad.scan.session.state'));--}}
{{--            console.log('[INIT] POS_SAVE_URL =', POS_SAVE_URL);--}}

{{--            // ===== Активная ячейка (state из сессии/кеша) =====--}}
{{--            let activeState = null;--}}

{{--            // Баннер активной ячейки (создадим, если нет)--}}
{{--            let banner = document.getElementById('activeCellBanner');--}}
{{--            if (!banner) {--}}
{{--                banner = document.createElement('div');--}}
{{--                banner.id = 'activeCellBanner';--}}
{{--                banner.className = 'alert alert-info d-none';--}}
{{--                const container = document.querySelector('#tabsContainer') || document.body;--}}
{{--                container.prepend(banner);--}}
{{--            }--}}

{{--            // Tabs--}}
{{--            const tabs = document.querySelectorAll('.custom-tab');--}}
{{--            let activeTab = 'gp';--}}
{{--            tabs.forEach(t => t.dataset.baseLabel = t.textContent.trim());--}}

{{--            const norm = s => (String(s || '')).trim().toLowerCase();--}}

{{--            // ======== Маппинг вкладок -> Помещения ========--}}
{{--            const ROOM_BY_TAB = {--}}
{{--                gp:   ['гп (ячейки)', 'гп', 'готовая продукция'],--}}
{{--                dopy: ['до', 'доп', 'допы', 'доп. материалы'],--}}
{{--                kom:  ['ко', 'комплект', 'комплектующие']--}}
{{--            };--}}

{{--            // ======== Словари для определения склада по ячейке ========--}}
{{--            const CELL_KEYWORDS = {--}}
{{--                gp:   ['гп', 'готов', 'хранение гп', 'яч гп'],--}}
{{--                dopy: ['доп', 'до'],--}}
{{--                kom:  ['ком', 'комплект']--}}
{{--            };--}}

{{--            const WAREHOUSE_BY_TAB = { gp: 1, dopy: 2, kom: 3 }; // реальные id складов--}}

{{--            function detectTabByCellText(cellText) {--}}
{{--                const c = (cellText || '').toString().trim().toLowerCase();--}}
{{--                if (!c) return null;--}}
{{--                if (CELL_KEYWORDS.gp.some(k => c.includes(k)))   return 'gp';--}}
{{--                if (CELL_KEYWORDS.dopy.some(k => c.includes(k))) return 'dopy';--}}
{{--                if (CELL_KEYWORDS.kom.some(k => c.includes(k)))  return 'kom';--}}
{{--                return null;--}}
{{--            }--}}

{{--            function inferWarehouseFromFirstRowCell() {--}}
{{--                const firstLi = document.querySelector('#positionsUl li');--}}
{{--                if (!firstLi) return null;--}}
{{--                const cellTxt = firstLi.dataset.cell || '';--}}
{{--                const tab = detectTabByCellText(cellTxt);--}}
{{--                return tab ? (WAREHOUSE_BY_TAB[tab] ?? null) : null;--}}
{{--            }--}}

{{--            function setWarehouseInStateIfMissing() {--}}
{{--                if (activeState && !activeState.warehouse_id) {--}}
{{--                    activeState.warehouse_id = currentWarehouseId;--}}
{{--                    console.log('[STATE] warehouse_id set →', currentWarehouseId);--}}
{{--                }--}}
{{--            }--}}

{{--            function matchesTabByRoom(room, tab = activeTab) {--}}
{{--                const r = norm(room);--}}
{{--                const patterns = ROOM_BY_TAB[tab] || [];--}}
{{--                if (!patterns.length) return true;--}}
{{--                return patterns.some(p => r.includes(p));--}}
{{--            }--}}

{{--            function matchesTabByCell(cell, tab = activeTab) {--}}
{{--                const c = norm(cell);--}}
{{--                if (tab === 'gp')   return c.includes('гп') || c.includes('готов') || c === '';--}}
{{--                if (tab === 'dopy') return c.includes('доп') || c === '';--}}
{{--                if (tab === 'kom')  return c.includes('ком') || c === '';--}}
{{--                return true;--}}
{{--            }--}}

{{--            function updateTabBadges(counts) {--}}
{{--                const fallback = { gp: 'ГП', dopy: 'ДО', kom: 'КО' };--}}
{{--                tabs.forEach(t => {--}}
{{--                    const code = t.dataset.tab || 'gp';--}}
{{--                    const base = t.dataset.baseLabel || fallback[code] || t.textContent.trim();--}}
{{--                    t.textContent = `${base} ${counts[code] ?? 0}`;--}}
{{--                });--}}
{{--            }--}}

{{--            // ===== Активная ячейка: загрузка и баннер =====--}}
{{--            async function loadCellState() {--}}
{{--                try {--}}
{{--                    const r = await fetch(STATE_FETCH_URL, { headers: { 'Accept': 'application/json' }});--}}
{{--                    const j = await r.json().catch(() => ({}));--}}
{{--                    activeState = j.state || null;--}}
{{--                    console.log('[STATE] activeState =', activeState);--}}

{{--                    banner.classList.remove('d-none', 'alert-warning');--}}
{{--                    if (activeState?.cell) {--}}
{{--                        banner.classList.add('alert-info');--}}
{{--                        banner.textContent = 'Активная ячейка: ' + activeState.cell;--}}
{{--                    } else {--}}
{{--                        banner.classList.remove('alert-info');--}}
{{--                        banner.classList.add('alert-warning');--}}
{{--                        banner.textContent = 'Активная ячейка не выбрана. Сначала отсканируйте ячейку на экране "Размещение".';--}}
{{--                    }--}}
{{--                } catch(e) {--}}
{{--                    console.warn('Не удалось получить state ячейки', e);--}}
{{--                }--}}
{{--            }--}}

{{--            // ============== список документов ==============--}}
{{--            function renderDocuments() {--}}
{{--                docList.innerHTML = '';--}}

{{--                const docCounts = { gp:0, dopy:0, kom:0 };--}}
{{--                documents.forEach(d => {--}}
{{--                    if (matchesTabByRoom(d.Помещение, 'gp'))   docCounts.gp++;--}}
{{--                    if (matchesTabByRoom(d.Помещение, 'dopy')) docCounts.dopy++;--}}
{{--                    if (matchesTabByRoom(d.Помещение, 'kom'))  docCounts.kom++;--}}
{{--                });--}}
{{--                updateTabBadges(docCounts);--}}

{{--                const filtered = documents.filter(d => matchesTabByRoom(d.Помещение, activeTab));--}}
{{--                filtered.forEach((doc) => {--}}
{{--                    const realIndex = documents.indexOf(doc);--}}
{{--                    const card = document.createElement('div');--}}
{{--                    card.className = 'card mb-2';--}}
{{--                    card.innerHTML = `--}}
{{--              <div class="doc-header select-doc" data-doc-index="${realIndex}">--}}
{{--                ${doc.Ссылка ?? 'Без названия'} — Статус: ${doc.Статус ?? '-'}<br>--}}
{{--                <small>Помещение: ${doc.Помещение ?? '-'}</small>--}}
{{--              </div>`;--}}
{{--                    docList.appendChild(card);--}}
{{--                });--}}

{{--                docList.querySelectorAll('.select-doc').forEach(el => {--}}
{{--                    el.addEventListener('click', () => showPositions(el.dataset.docIndex));--}}
{{--                });--}}
{{--            }--}}

{{--            function showDocuments() {--}}
{{--                docList.classList.remove('d-none');--}}
{{--                posList.classList.add('d-none');--}}
{{--                backBtn.classList.add('d-none');--}}
{{--                barcodeWrapper.classList.add('d-none');--}}
{{--                title.textContent = 'Документы отбора';--}}
{{--                posUl.innerHTML = '';--}}
{{--                input.value = '';--}}

{{--                tabs.forEach(t => {--}}
{{--                    t.classList.remove('d-none', 'active');--}}
{{--                    t.textContent = t.dataset.baseLabel || t.textContent.trim();--}}
{{--                });--}}
{{--                activeTab = 'gp';--}}
{{--                tabs[0]?.classList.add('active');--}}

{{--                renderDocuments();--}}
{{--                loadCellState();--}}
{{--            }--}}

{{--            // ============== экран позиций документа ==============--}}
{{--            function recomputeCountsByCells() {--}}
{{--                const counts = { gp:0, dopy:0, kom:0 };--}}
{{--                document.querySelectorAll('#positionsUl li').forEach(li => {--}}
{{--                    const cell = li.dataset.cell || '';--}}
{{--                    if (matchesTabByCell(cell, 'gp'))   counts.gp++;--}}
{{--                    if (matchesTabByCell(cell, 'dopy')) counts.dopy++;--}}
{{--                    if (matchesTabByCell(cell, 'kom'))  counts.kom++;--}}
{{--                });--}}
{{--                updateTabBadges(counts);--}}
{{--            }--}}

{{--            function applyTabFilterInPositions() {--}}
{{--                document.querySelectorAll('#positionsUl li').forEach(li => {--}}
{{--                    const cell = li.dataset.cell || '';--}}
{{--                    li.classList.toggle('d-none', !matchesTabByCell(cell));--}}
{{--                });--}}
{{--                document.querySelectorAll('#positionsUl li.d-none')--}}
{{--                    .forEach(li => li.classList.remove('hl-barcode'));--}}
{{--            }--}}

{{--            function detectTabByRoom(room) {--}}
{{--                const r = norm(room);--}}
{{--                if (ROOM_BY_TAB.gp.some(p => r.includes(p)))   return 'gp';--}}
{{--                if (ROOM_BY_TAB.dopy.some(p => r.includes(p))) return 'dopy';--}}
{{--                if (ROOM_BY_TAB.kom.some(p => r.includes(p)))  return 'kom';--}}
{{--                return 'gp';--}}
{{--            }--}}

{{--            function getDocumentNoFromDoc(doc) {--}}
{{--                if (typeof doc.document_id === 'string' && doc.document_id.trim()) {--}}
{{--                    return doc.document_id.trim();--}}
{{--                }--}}
{{--                const m = String(doc.Ссылка || '').match(/\b00-\d+\b/);--}}
{{--                return m ? m[0] : '';--}}
{{--            }--}}

{{--            let currentDocIndex = null;--}}
{{--            let currentDocNo = '';--}}
{{--            let currentDoc = null;--}}
{{--            let currentWarehouseId = null;--}}

{{--            function showPositions(index) {--}}
{{--                const doc = documents[index];--}}
{{--                if (!doc) return;--}}

{{--                currentDocIndex = index;--}}
{{--                currentDocNo = getDocumentNoFromDoc(doc);--}}
{{--                currentDoc = doc;--}}

{{--                currentWarehouseId = (doc.warehouse_id ?? activeState?.warehouse_id) ?? null;--}}

{{--                window.currentDocumentId = currentDocNo;--}}
{{--                console.log('[DOC] opened', { link: doc?.Ссылка, currentDocNo, currentWarehouseId });--}}

{{--                const docTab = detectTabByRoom(doc.Помещение);--}}
{{--                activeTab = docTab;--}}
{{--                tabs.forEach(t => {--}}
{{--                    if (t.dataset.tab === docTab) {--}}
{{--                        t.classList.add('active');--}}
{{--                        t.classList.remove('d-none');--}}
{{--                    } else {--}}
{{--                        t.classList.add('d-none');--}}
{{--                        t.classList.remove('active');--}}
{{--                    }--}}
{{--                });--}}

{{--                docList.classList.add('d-none');--}}
{{--                posList.classList.remove('d-none');--}}
{{--                backBtn.classList.remove('d-none');--}}
{{--                barcodeWrapper.classList.remove('d-none');--}}
{{--                setTimeout(() => input.focus(), 100);--}}

{{--                title.textContent = doc.Ссылка?.match(/(00-\d+)/)?.[1] ?? 'Позиции документа';--}}

{{--                posUl.innerHTML = '';--}}
{{--                const rows = Array.isArray(doc.ТоварыРазмещение) ? doc.ТоварыРазмещение : [];--}}
{{--                rows.forEach((line, idx) => {--}}
{{--                    const li = document.createElement('li');--}}
{{--                    li.className = 'list-group-item';--}}
{{--                    li.dataset.nom     = norm(line.Номенклатура);--}}
{{--                    li.dataset.barcode = norm(line.Штрихкод);--}}
{{--                    li.dataset.cell    = norm(line.Ячейка);--}}

{{--                    li.textContent =--}}
{{--                        `#${line.НомерСтроки ?? idx + 1} — ${line.Номенклатура ?? '-'}, ` +--}}
{{--                        `Кол: ${line.Количество ?? 0}, ` +--}}
{{--                        `Уп: ${line.КоличествоУпаковок ?? 0}` +--}}
{{--                        (line.Ячейка ? ` | Яч: ${line.Ячейка}` : '');--}}

{{--                    posUl.appendChild(li);--}}
{{--                });--}}

{{--                if (!currentWarehouseId) {--}}
{{--                    const wh = inferWarehouseFromFirstRowCell();--}}
{{--                    if (wh) {--}}
{{--                        currentWarehouseId = wh;--}}
{{--                        console.log('[WH] inferred from first row cell →', currentWarehouseId);--}}
{{--                    }--}}
{{--                }--}}

{{--                recomputeCountsByCells();--}}
{{--                applyTabFilterInPositions();--}}

{{--                setWarehouseInStateIfMissing();--}}
{{--                loadCellState();--}}
{{--            }--}}

{{--            // ============== переключение вкладок ==============--}}
{{--            tabs.forEach(tab => {--}}
{{--                tab.addEventListener('click', (e) => {--}}
{{--                    e.preventDefault();--}}
{{--                    tabs.forEach(t => t.classList.remove('active'));--}}
{{--                    tab.classList.add('active');--}}
{{--                    activeTab = tab.dataset.tab || 'gp';--}}

{{--                    const onDocsScreen = !docList.classList.contains('d-none');--}}
{{--                    if (onDocsScreen) {--}}
{{--                        renderDocuments();--}}
{{--                    } else {--}}
{{--                        applyTabFilterInPositions();--}}
{{--                    }--}}
{{--                });--}}
{{--            });--}}

{{--            // ============== кнопка Назад ==============--}}
{{--            if (backBtn) {--}}
{{--                backBtn.addEventListener('click', (e) => {--}}
{{--                    e.preventDefault();--}}
{{--                    e.stopPropagation();--}}
{{--                    showDocuments();--}}
{{--                });--}}
{{--            }--}}

{{--            // ============== Сохранение позиции в БД ==============--}}
{{--            async function savePositionScan(rawCode) {--}}
{{--                const code = String(rawCode || '').trim();--}}
{{--                console.log('[SAVE] attempt', { code, currentDocNo, activeState, currentWarehouseId });--}}

{{--                if (!code) {--}}
{{--                    console.warn('[SAVE] empty code - skip');--}}
{{--                    return;--}}
{{--                }--}}
{{--                if (!activeState || !activeState.cell) {--}}
{{--                    console.warn('[SAVE] no active cell in state');--}}
{{--                    alert('Сперва отсканируйте ячейку (экран "Размещение").');--}}
{{--                    return;--}}
{{--                }--}}
{{--                if (!currentDocNo) {--}}
{{--                    console.warn('[SAVE] no currentDocNo');--}}
{{--                    alert('Не удалось определить номер документа.');--}}
{{--                    return;--}}
{{--                }--}}

{{--                const safeCode = code.length > CODE_MAX ? code.slice(0, CODE_MAX) : code;--}}

{{--                try {--}}
{{--                    console.log('[SAVE] POST ->', POS_SAVE_URL);--}}
{{--                    const resp = await fetch(POS_SAVE_URL, {--}}
{{--                        method: 'POST',--}}
{{--                        headers: {--}}
{{--                            'X-CSRF-TOKEN': csrf,--}}
{{--                            'X-Requested-With': 'XMLHttpRequest',--}}
{{--                            'Accept': 'application/json',--}}
{{--                            'Content-Type': 'application/json',--}}
{{--                        },--}}
{{--                        credentials: 'same-origin',--}}
{{--                        body: JSON.stringify({--}}
{{--                            document_id: String(currentDocNo),--}}
{{--                            warehouse_id: currentWarehouseId,--}}
{{--                            code: safeCode,--}}
{{--                            quantity: 1,--}}

{{--                            doc_link: currentDoc?.Ссылка || null,--}}
{{--                            nom: null,--}}
{{--                            line_no: null,--}}
{{--                        }),--}}
{{--                    });--}}

{{--                    const raw = await resp.text();--}}
{{--                    console.log('[SAVE] HTTP', resp.status, resp.statusText);--}}
{{--                    console.log('[SAVE] RAW', raw);--}}

{{--                    let data = {};--}}
{{--                    try { data = raw ? JSON.parse(raw) : {}; } catch(e) {}--}}

{{--                    if (!resp.ok || !data.ok) {--}}
{{--                        console.warn('[SAVE] backend says NOT ok', data);--}}
{{--                        alert((data && (data.msg || JSON.stringify(data))) || ('HTTP ' + resp.status));--}}
{{--                        return;--}}
{{--                    }--}}

{{--                    const val = code.toLowerCase();--}}
{{--                    let matched = false;--}}
{{--                    document.querySelectorAll('#positionsUl li').forEach(li => {--}}
{{--                        const bc = (li.dataset.barcode || '').toLowerCase();--}}
{{--                        if (!matched && bc && (bc === val || bc.includes(val))) {--}}
{{--                            li.classList.add('hl-barcode');--}}
{{--                            matched = true;--}}
{{--                            li.scrollIntoView({ block: 'center', behavior: 'smooth' });--}}
{{--                        }--}}
{{--                    });--}}
{{--                } catch (e) {--}}
{{--                    console.error('[SAVE] fetch error', e);--}}
{{--                    alert('Помилка мережі/сервера');--}}
{{--                }--}}
{{--            }--}}

{{--            // ============== поиск/сканер ==============--}}
{{--            let debounceTimer, autoSaveTimer;--}}

{{--            input.addEventListener('input', () => {--}}
{{--                clearTimeout(debounceTimer);--}}
{{--                debounceTimer = setTimeout(() => {--}}
{{--                    const valRaw = input.value.trim();--}}
{{--                    const val = valRaw.toLowerCase();--}}
{{--                    const isBarcodeQuery = /^\d{6,}$/.test(valRaw);--}}

{{--                    document.querySelectorAll('#positionsUl li').forEach(li => {--}}
{{--                        if (li.classList.contains('d-none')) {--}}
{{--                            li.classList.remove('hl-barcode');--}}
{{--                            return;--}}
{{--                        }--}}
{{--                        const nom = li.dataset.nom || '';--}}
{{--                        const bc  = li.dataset.barcode || '';--}}
{{--                        const match = isBarcodeQuery ? (bc === val || bc.includes(val)) : nom.includes(val);--}}
{{--                        li.classList.toggle('hl-barcode', Boolean(val) && match);--}}
{{--                    });--}}

{{--                    const first = document.querySelector('#positionsUl li.hl-barcode:not(.d-none)');--}}
{{--                    if (first) first.scrollIntoView({ block:'center', behavior:'smooth' });--}}

{{--                    clearTimeout(autoSaveTimer);--}}
{{--                    if (isBarcodeQuery) {--}}
{{--                        autoSaveTimer = setTimeout(() => {--}}
{{--                            console.log('[AUTO] trigger savePositionScan by input');--}}
{{--                            const v = input.value.trim();--}}
{{--                            input.value = '';--}}
{{--                            savePositionScan(v);--}}
{{--                        }, 120);--}}
{{--                    }--}}
{{--                }, 150);--}}
{{--            });--}}

{{--            input.addEventListener('keydown', (e) => {--}}
{{--                if (e.key === 'Enter' || e.key === 'Tab') {--}}
{{--                    e.preventDefault();--}}
{{--                    const val = input.value.trim();--}}
{{--                    input.value = '';--}}
{{--                    console.log('[KEY] ' + e.key + ' -> savePositionScan');--}}
{{--                    savePositionScan(val);--}}
{{--                }--}}
{{--            });--}}

{{--            // старт--}}
{{--            showDocuments();--}}
{{--        });--}}
{{--    </script>--}}




    {{-- <script>--}}
{{--                    document.addEventListener('DOMContentLoaded', () => {--}}
{{--                        const documents = @json(session('pick_orders', [])) || [];--}}

{{--                        // DOM--}}
{{--                        const docList = document.getElementById('documentsList');--}}
{{--                        const posList = document.getElementById('positionsList');--}}
{{--                        const posUl   = document.getElementById('positionsUl');--}}
{{--                        const backBtn = document.getElementById('btnBack');--}}
{{--                        const title   = document.getElementById('pageTitle');--}}
{{--                        const input   = document.getElementById('barcodeInput');--}}
{{--                        const barcodeWrapper = document.getElementById('barcodeWrapper');--}}

{{--                        // Tabs--}}
{{--                        const tabs = document.querySelectorAll('.custom-tab');--}}
{{--                        let activeTab = 'gp';--}}
{{--                        tabs.forEach(t => t.dataset.baseLabel = t.textContent.trim());--}}

{{--                        const norm = s => (String(s || '')).trim().toLowerCase();--}}

{{--                        // ======== Маппинг вкладок -> Помещения ========--}}
{{--                        const ROOM_BY_TAB = {--}}
{{--                            gp:   ['гп (ячейки)', 'гп', 'готовая продукция'],--}}
{{--                            dopy: ['до', 'доп', 'допы', 'доп. материалы'],--}}
{{--                            kom:  ['ко', 'комплект', 'комплектующие']--}}
{{--                        };--}}

{{--                        function matchesTabByRoom(room, tab = activeTab) {--}}
{{--                            const r = norm(room);--}}
{{--                            const patterns = ROOM_BY_TAB[tab] || [];--}}
{{--                            if (!patterns.length) return true;--}}
{{--                            return patterns.some(p => r.includes(p));--}}
{{--                        }--}}

{{--                        function matchesTabByCell(cell, tab = activeTab) {--}}
{{--                            const c = norm(cell);--}}
{{--                            if (tab === 'gp')   return c.includes('гп') || c.includes('готов') || c === '';--}}
{{--                            if (tab === 'dopy') return c.includes('доп') || c === '';   // ← показываем и пустые ячейки--}}
{{--                            if (tab === 'kom')  return c.includes('ком')  || c === '';  // ← показываем и пустые ячейки--}}
{{--                            return true;--}}
{{--                        }--}}

{{--                        function updateTabBadges(counts) {--}}
{{--                            const fallback = { gp: 'ГП', dopy: 'ДО', kom: 'КО' };--}}
{{--                            tabs.forEach(t => {--}}
{{--                                const code = t.dataset.tab || 'gp';--}}
{{--                                const base = t.dataset.baseLabel || fallback[code] || t.textContent.trim();--}}
{{--                                t.textContent = `${base} ${counts[code] ?? 0}`;--}}
{{--                            });--}}
{{--                        }--}}

{{--                        // ============== список документов ==============--}}
{{--                        function renderDocuments() {--}}
{{--                            docList.innerHTML = '';--}}

{{--                            const docCounts = { gp:0, dopy:0, kom:0 };--}}
{{--                            documents.forEach(d => {--}}
{{--                                if (matchesTabByRoom(d.Помещение, 'gp'))   docCounts.gp++;--}}
{{--                                if (matchesTabByRoom(d.Помещение, 'dopy')) docCounts.dopy++;--}}
{{--                                if (matchesTabByRoom(d.Помещение, 'kom'))  docCounts.kom++;--}}
{{--                            });--}}
{{--                            updateTabBadges(docCounts);--}}

{{--                            const filtered = documents.filter(d => matchesTabByRoom(d.Помещение, activeTab));--}}
{{--                            filtered.forEach((doc, i) => {--}}
{{--                                const realIndex = documents.indexOf(doc);--}}
{{--                                const card = document.createElement('div');--}}
{{--                                card.className = 'card mb-2';--}}
{{--                                card.innerHTML = `--}}
{{--        <div class="doc-header select-doc" data-doc-index="${realIndex}">--}}
{{--          ${doc.Ссылка ?? 'Без названия'} — Статус: ${doc.Статус ?? '-'}<br>--}}
{{--          <small>Помещение: ${doc.Помещение ?? '-'}</small>--}}
{{--        </div>`;--}}
{{--                                docList.appendChild(card);--}}
{{--                            });--}}

{{--                            docList.querySelectorAll('.select-doc').forEach(el => {--}}
{{--                                el.addEventListener('click', () => showPositions(el.dataset.docIndex));--}}
{{--                            });--}}
{{--                        }--}}

{{--                        function showDocuments() {--}}
{{--                            docList.classList.remove('d-none');--}}
{{--                            posList.classList.add('d-none');--}}
{{--                            backBtn.classList.add('d-none');--}}
{{--                            barcodeWrapper.classList.add('d-none');--}}
{{--                            title.textContent = 'Документы отбора';--}}
{{--                            posUl.innerHTML = '';--}}
{{--                            input.value = '';--}}

{{--                            // вернуть все вкладки--}}
{{--                            tabs.forEach(t => {--}}
{{--                                t.classList.remove('d-none');--}}
{{--                                t.classList.remove('active');--}}
{{--                                t.textContent = t.dataset.baseLabel || t.textContent.trim();--}}
{{--                            });--}}
{{--                            activeTab = 'gp';--}}
{{--                            tabs[0].classList.add('active');--}}

{{--                            renderDocuments();--}}
{{--                        }--}}

{{--                        // ============== экран позиций документа ==============--}}
{{--                        function recomputeCountsByCells() {--}}
{{--                            const counts = { gp:0, dopy:0, kom:0 };--}}
{{--                            document.querySelectorAll('#positionsUl li').forEach(li => {--}}
{{--                                const cell = li.dataset.cell || '';--}}
{{--                                if (matchesTabByCell(cell, 'gp'))   counts.gp++;--}}
{{--                                if (matchesTabByCell(cell, 'dopy')) counts.dopy++;--}}
{{--                                if (matchesTabByCell(cell, 'kom'))  counts.kom++;--}}
{{--                            });--}}
{{--                            updateTabBadges(counts);--}}
{{--                        }--}}

{{--                        function applyTabFilterInPositions() {--}}
{{--                            document.querySelectorAll('#positionsUl li').forEach(li => {--}}
{{--                                const cell = li.dataset.cell || '';--}}
{{--                                li.classList.toggle('d-none', !matchesTabByCell(cell));--}}
{{--                            });--}}
{{--                            document.querySelectorAll('#positionsUl li.d-none')--}}
{{--                                .forEach(li => li.classList.remove('hl-barcode'));--}}
{{--                        }--}}

{{--                        function detectTabByRoom(room) {--}}
{{--                            const r = norm(room);--}}
{{--                            if (ROOM_BY_TAB.gp.some(p => r.includes(p)))   return 'gp';--}}
{{--                            if (ROOM_BY_TAB.dopy.some(p => r.includes(p))) return 'dopy';--}}
{{--                            if (ROOM_BY_TAB.kom.some(p => r.includes(p)))  return 'kom';--}}
{{--                            return 'gp';--}}
{{--                        }--}}

{{--                        function showPositions(index) {--}}
{{--                            const doc = documents[index];--}}
{{--                            if (!doc) return;--}}

{{--                            // выбрать вкладку документа--}}
{{--                            const docTab = detectTabByRoom(doc.Помещение);--}}
{{--                            activeTab = docTab;--}}
{{--                            tabs.forEach(t => {--}}
{{--                                if (t.dataset.tab === docTab) {--}}
{{--                                    t.classList.add('active');--}}
{{--                                    t.classList.remove('d-none');--}}
{{--                                } else {--}}
{{--                                    t.classList.add('d-none');--}}
{{--                                    t.classList.remove('active');--}}
{{--                                }--}}
{{--                            });--}}

{{--                            docList.classList.add('d-none');--}}
{{--                            posList.classList.remove('d-none');--}}
{{--                            backBtn.classList.remove('d-none');--}}
{{--                            barcodeWrapper.classList.remove('d-none');--}}
{{--                            setTimeout(() => input.focus(), 100);--}}

{{--                            title.textContent = doc.Ссылка?.match(/(00-\d+)/)?.[1] ?? 'Позиции документа';--}}

{{--                            posUl.innerHTML = '';--}}
{{--                            const rows = Array.isArray(doc.ТоварыРазмещение) ? doc.ТоварыРазмещение : [];--}}
{{--                            rows.forEach((line, idx) => {--}}
{{--                                const li = document.createElement('li');--}}
{{--                                li.className = 'list-group-item';--}}
{{--                                li.dataset.nom     = norm(line.Номенклатура);--}}
{{--                                li.dataset.barcode = norm(line.Штрихкод);--}}
{{--                                li.dataset.cell    = norm(line.Ячейка);--}}

{{--                                li.textContent =--}}
{{--                                    `#${line.НомерСтроки ?? idx + 1} — ${line.Номенклатура ?? '-'}, ` +--}}
{{--                                    `Кол: ${line.Количество ?? 0}, ` +--}}
{{--                                    `Уп: ${line.КоличествоУпаковок ?? 0}` +--}}
{{--                                    (line.Ячейка ? ` | Яч: ${line.Ячейка}` : '');--}}

{{--                                posUl.appendChild(li);--}}
{{--                            });--}}

{{--                            recomputeCountsByCells();--}}
{{--                            applyTabFilterInPositions();--}}
{{--                        }--}}

{{--                        // ============== переключение вкладок ==============--}}
{{--                        tabs.forEach(tab => {--}}
{{--                            tab.addEventListener('click', (e) => {--}}
{{--                                e.preventDefault();--}}
{{--                                tabs.forEach(t => t.classList.remove('active'));--}}
{{--                                tab.classList.add('active');--}}
{{--                                activeTab = tab.dataset.tab || 'gp';--}}

{{--                                const onDocsScreen = !docList.classList.contains('d-none');--}}
{{--                                if (onDocsScreen) {--}}
{{--                                    renderDocuments();--}}
{{--                                } else {--}}
{{--                                    applyTabFilterInPositions();--}}
{{--                                }--}}
{{--                            });--}}
{{--                        });--}}

{{--                        // ============== кнопка Назад ==============--}}
{{--                        if (backBtn) {--}}
{{--                            backBtn.addEventListener('click', (e) => {--}}
{{--                                e.preventDefault();--}}
{{--                                e.stopPropagation();--}}
{{--                                showDocuments();--}}
{{--                            });--}}
{{--                        }--}}

{{--                        // ============== поиск/сканер ==============--}}
{{--                        let timer;--}}
{{--                        input.addEventListener('input', () => {--}}
{{--                            clearTimeout(timer);--}}
{{--                            timer = setTimeout(() => {--}}
{{--                                const valRaw = input.value.trim();--}}
{{--                                const val = valRaw.toLowerCase();--}}
{{--                                const isBarcodeQuery = /^\d{6,}$/.test(valRaw);--}}

{{--                                document.querySelectorAll('#positionsUl li').forEach(li => {--}}
{{--                                    if (li.classList.contains('d-none')) {--}}
{{--                                        li.classList.remove('hl-barcode');--}}
{{--                                        return;--}}
{{--                                    }--}}
{{--                                    const nom = li.dataset.nom || '';--}}
{{--                                    const bc  = li.dataset.barcode || '';--}}
{{--                                    const match = isBarcodeQuery ? (bc === val || bc.includes(val)) : nom.includes(val);--}}
{{--                                    li.classList.toggle('hl-barcode', Boolean(val) && match);--}}
{{--                                });--}}

{{--                                const first = document.querySelector('#positionsUl li.hl-barcode:not(.d-none)');--}}
{{--                                if (first) first.scrollIntoView({ block:'center', behavior:'smooth' });--}}
{{--                            }, 200);--}}
{{--                        });--}}

{{--                        // старт--}}
{{--                        showDocuments();--}}
{{--                    });--}}
{{--                </script>--}}
@endpush
