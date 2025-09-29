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
    <style>
        #positionsUl .list-group-item{ display:flex; flex-direction:column; gap:6px; }
        .pos-title{ font-weight:600; line-height:1.25; word-break:break-word; }
        .pos-qty{ margin-top:2px; display:flex; gap:8px; flex-wrap:wrap; }
        .qty-chip{
            display:inline-block; padding:2px 8px; border:2px solid #333;
            border-radius:8px; background:#fffbe6; font-weight:700; font-size:.95em; line-height:1.1; white-space:nowrap;
        }
        .qty-chip.fact{ background:#e7f1ff; } /* визуально отличаем Факт */
        .hl-barcode{ background-color:#fff3cd !important; }
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
                           placeholder="Сканируйте номенклатуру или штрихкод..." autocomplete="off"> </div>
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
                    <button id="btnSend" type="button" class="btn btn-primary d-none"
                            onclick="console.log('btnSend inline clicked')"
                            data-send-url="{{ route('sklad.scan.send') }}">Отправить</button>
                </div>
            </div>
        </section>
    </div>
@endsection
@push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                console.log('[pick] DOM ready');

                const documents = @json(session('pick_orders', [])) || [];

// +++ ДОБАВЬ ЭТО +++
                const FREE_SCAN_PAGE = @json(route('sklad.scan.free'));
                const hasDocuments = Array.isArray(documents) && documents.length > 0;
                if (!hasDocuments) {
                    window.location.replace(FREE_SCAN_PAGE);
                    return;
                }

                // ===== DOM =====
                const docList = document.getElementById('documentsList');
                const posList = document.getElementById('positionsList');
                const posUl   = document.getElementById('positionsUl');
                const backBtn = document.getElementById('btnBack');
                const title   = document.getElementById('pageTitle');
                const input   = document.getElementById('barcodeInput');
                const barcodeWrapper = document.getElementById('barcodeWrapper');
                const btnSend = document.getElementById('btnSend');

                // скрываем кнопку отправки по умолчанию
                if (btnSend) btnSend.classList.add('d-none');

                // ===== Константы/роуты =====
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const CODE_MAX        = 11; // длина scan_position_document.code
                const POS_SAVE_URL    = @json(route('sklad.scan.position.store'));
                const STATE_FETCH_URL = @json(route('sklad.scan.session.state'));
                const SEND_URL        = @json(route('sklad.scan.send'));

                const CELL_LABEL_URL = @json(route('sklad.cell.label'));
                console.log('[pick] routes:', { POS_SAVE_URL, STATE_FETCH_URL, SEND_URL });

                // ===== Активная ячейка (state из сессии/кеша) =====
                let activeState = null;

                // Баннер активной ячейки
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

                async function loadCellState() {
                    try {
                        const r = await fetch(STATE_FETCH_URL, { headers: { 'Accept': 'application/json' }});
                        const j = await r.json().catch(() => ({}));
                        activeState = j.state || null;

                        banner.classList.remove('d-none', 'alert-warning');

                        const raw = activeState?.cell ?? '';
                        if (!raw) {
                            banner.classList.add('alert-warning');
                            banner.textContent = 'Ячейка не выбрана. Сначала отсканируйте ячейку на экране "Размещение".';
                            return;
                        }

                        // Тянем сразу label + room + tab
                        let nice = null, room = null, tab = null;
                        try {
                            const r2 = await fetch(CELL_LABEL_URL + '?number=' + encodeURIComponent(raw));
                            const j2 = await r2.json();
                            nice = j2?.label || null;
                            room = j2?.room  || null;
                            tab  = j2?.tab   || null;
                        } catch (_) {}

                        // Баннер
                        banner.classList.add('alert-info');
                        banner.textContent = 'Ячейка: ' + (nice || raw);

                        // Если вычислился tab — показываем только одну вкладку
                        if (tab) {
                            activeTab = tab;
                            // спрятать неактивные
                            tabs.forEach(t => {
                                const isActive = (t.dataset.tab === activeTab);
                                t.classList.toggle('active', isActive);
                                t.classList.toggle('d-none', !isActive);
                            });
                            // Перерисовать список документов уже под нужную вкладку
                            renderDocuments();
                        }
                    } catch(e) {
                        console.warn('Не удалось получить state ячейки', e);
                    }
                }

                // ===== Активная ячейка: загрузка и баннер =====
                // async function loadCellState() {
                //     try {
                //         const r = await fetch(STATE_FETCH_URL, { headers: { 'Accept': 'application/json' }});
                //         const j = await r.json().catch(() => ({}));
                //         activeState = j.state || null;
                //
                //         banner.classList.remove('d-none', 'alert-warning');
                //
                //         const raw = activeState?.cell ?? '';
                //         if (!raw) {
                //             banner.classList.add('alert-warning');
                //             banner.textContent = 'Активная ячейка не выбрана. Сначала отсканируйте ячейку на экране "Размещение".';
                //             return;
                //         }
                //
                //         // второй запрос — вытянуть подпись
                //         let nice = null;
                //         try {
                //             const r2 = await fetch(CELL_LABEL_URL + '?number=' + encodeURIComponent(raw));
                //             const j2 = await r2.json();
                //             nice = j2?.label || null;
                //         } catch (_) {}
                //
                //         banner.classList.add('alert-info');
                //         banner.textContent = 'Активная ячейка: ' + (nice || raw);
                //     } catch(e) {
                //         console.warn('Не удалось получить state ячейки', e);
                //     }
                // }

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

// ⬇️ если в текущей вкладке пусто — уходим в free
                    if (filtered.length === 0) {
                        window.location.replace(FREE_SCAN_PAGE);
                        return;
                    }

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

                    // прячем кнопку "Отправить" на экране списка документов
                    if (btnSend) btnSend.classList.add('d-none');

                    // Оставляем только уже выбранную вкладку (activeTab выставили в loadCellState())
                    tabs.forEach(t => {
                        const isActive = (t.dataset.tab === activeTab);
                        t.classList.toggle('active', isActive);
                        t.classList.toggle('d-none', !isActive);
                        // вернуть исходный текст, если нужно
                        t.textContent = t.dataset.baseLabel || t.textContent.trim();
                    });



                    // tabs.forEach(t => {
                    //     t.classList.remove('d-none', 'active');
                    //     t.textContent = t.dataset.baseLabel || t.textContent.trim();
                    // });
                    // activeTab = 'gp';
                    // tabs[0]?.classList.add('active');

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
                    const m = String(doc.Ссылка || '').match(/\b(00-\d+)\b/);
                    return m ? m[1] : '';
                }

                let currentDocIndex = null;
                let currentDocNo = '';
                let currentDoc = null;
                let currentWarehouseId = null;

                // === UI-шаблон позиции: «План + Факт» ===
                function renderLi(li){
                    const rownum = li.dataset.line || '';
                    const nom    = li.dataset.nomOriginal || '-';
                    const qtyPln = Number(li.dataset.qty || 0);
                    const qtyFct = Number(li.dataset.fact || 0);
                    li.innerHTML = `
      <div class="pos-title">#${rownum} — ${nom}</div>
      <div class="pos-qty">
        <span class="qty-chip plan">План: ${qtyPln}</span>
        <span class="qty-chip fact">Факт: ${qtyFct}</span>
      </div>`;
                }

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
                    setTimeout(() => input?.focus(), 100);

                    title.textContent = doc.Ссылка?.match(/(00-\d+)/)?.[1] ?? 'Позиции документа';

                    // — отрисовка позиций «План + Факт» —
                    posUl.innerHTML = '';
                    const rows = Array.isArray(doc.ТоварыРазмещение) ? doc.ТоварыРазмещение : [];
                    rows.forEach((line, idx) => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';

                        const rownum = Number(line.НомерСтроки ?? (idx + 1));
                        const qtyPln = Number(line.Количество ?? 0) || 0;
                        const qtyFct = Number(line.Факт ?? line.Отобрано ?? 0) || 0;

                        // datasets для поиска/сканирования/фильтрации
                        li.dataset.nom          = norm(line.Номенклатура);
                        li.dataset.nomOriginal  = (line.Номенклатура ?? '-');
                        li.dataset.barcode      = norm(line.Штрихкод);
                        li.dataset.cell         = norm(line.Ячейка);
                        li.dataset.line         = String(rownum); // номер строки
                        li.dataset.qty          = String(qtyPln); // план
                        li.dataset.fact         = String(qtyFct); // факт

                        renderLi(li);
                        posUl.appendChild(li);
                    });

                    // показать/скрыть кнопку "Отправить" в зависимости от наличия строк
                    if (btnSend) {
                        if (rows.length > 0) {
                            btnSend.classList.remove('d-none');
                        } else {
                            btnSend.classList.add('d-none');
                        }
                    }

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

                // === Надёжный поиск номера позиции в DOM ===
                function resolveNumberPosition(rawCode) {
                    const code = String(rawCode || '').trim();
                    const isBarcode = /^\d{6,}$/.test(code);
                    let found = null;

                    // 1) По штрихкоду
                    if (code) {
                        const val = code.toLowerCase();
                        document.querySelectorAll('#positionsUl li:not(.d-none)').forEach(li => {
                            if (found !== null) return;
                            const bc = (li.dataset.barcode || '').toLowerCase();
                            if (bc && (bc === val || bc.includes(val))) {
                                const n = parseInt(li.dataset.line || '', 10);
                                if (!Number.isNaN(n)) found = n;
                            }
                        });
                    }

                    // 2) По названию
                    if (found === null && code && !isBarcode) {
                        const val = code.toLowerCase();
                        document.querySelectorAll('#positionsUl li:not(.d-none)').forEach(li => {
                            if (found !== null) return;
                            const nom = (li.dataset.nom || '').toLowerCase();
                            if (nom && nom.includes(val)) {
                                const n = parseInt(li.dataset.line || '', 10);
                                if (!Number.isNaN(n)) found = n;
                            }
                        });
                    }

                    // 3) Первая подсвеченная
                    if (found === null) {
                        const hi = document.querySelector('#positionsUl li.hl-barcode:not(.d-none)');
                        if (hi) {
                            const n = parseInt(hi.dataset.line || '', 10);
                            if (!Number.isNaN(n)) found = n;
                        }
                    }

                    // 4) Первая видимая
                    if (found === null) {
                        const first = document.querySelector('#positionsUl li:not(.d-none)');
                        if (first) {
                            const n = parseInt(first.dataset.line || '', 10);
                            if (!Number.isNaN(n)) found = n;
                        }
                    }

                    return found; // может быть null
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

                    // Надёжно определим номер строки
                    const numberPosition = resolveNumberPosition(code);
                    if (numberPosition == null) {
                        alert('Не удалось определить позицию для этого кода.');
                        return;
                    }

                    // усечение кода по длине колонки
                    const safeCode = code.length > CODE_MAX ? code.slice(0, CODE_MAX) : code;

                    // найдём сам <li> для подсветки и увеличения «Факт»
                    let matchedLi = null;
                    document.querySelectorAll('#positionsUl li').forEach(li => {
                        const n = parseInt(li.dataset.line || '', 10);
                        if (n === numberPosition && !matchedLi) matchedLi = li;
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
                                number_position: numberPosition,
                                lines: [numberPosition],
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

                        // Успех: увеличиваем «Факт»
                        if (matchedLi) {
                            const cur = parseInt(matchedLi.dataset.fact || '0', 10) || 0;
                            matchedLi.dataset.fact = String(cur + 1);
                            matchedLi.classList.add('hl-barcode');
                            renderLi(matchedLi);
                            matchedLi.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }

                    } catch (e) {
                        console.error('[SAVE] fetch error', e);
                        alert('Помилка мережі/сервера');
                    }
                }

                // ============== поиск/сканер ==============
                let debounceTimer, autoSaveTimer;

                input?.addEventListener('input', () => {
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
                            const nom = (li.dataset.nom || '');
                            const bc  = (li.dataset.barcode || '');
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

                input?.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === 'Tab') {
                        e.preventDefault();
                        const val = input.value.trim();
                        input.value = '';
                        console.log('[KEY]', e.key, '-> savePositionScan');
                        savePositionScan(val);
                    }
                });

                // старт: показываем список доков
                showDocuments();

                // === Кнопка "Отправить" ===
                if (!btnSend) {
                    console.warn('[pick] btnSend not found');
                } else {
                    console.log('[pick] btnSend wired');
                    btnSend.addEventListener('click', async () => {
                        console.log('[pick] SEND click. currentDocumentId =', window.currentDocumentId);
                        if (!window.currentDocumentId) {
                            alert('Не выбран документ.');
                            return;
                        }
                        try {
                            btnSend.disabled = true;
                            btnSend.textContent = 'Отправляем...';

                            const resp = await fetch(SEND_URL, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    document_id: String(window.currentDocumentId),
                                    mode: 'delta',           // отправляем дельты (СканДельта)
                                    only_active_cell: true,  // только активная ячейка
                                    fill_placed: true        // пробрасываем флаг в 1С
                                }),
                            });

                            const raw = await resp.text();
                            console.log('[pick] SEND HTTP', resp.status, resp.statusText, raw);

                            let data = {};
                            try { data = raw ? JSON.parse(raw) : {}; } catch(e) { console.warn('[pick] JSON parse fail', e); }

                            if (!resp.ok || !data.ok) {
                                alert((data && (data.msg || JSON.stringify(data))) || ('HTTP ' + resp.status));
                                return;
                            }

                            alert(`Отправлено: позиций ${data.sent_positions}, сканов ${data.sent_scans}`);

                            // ✅ Перенаправление на главную страницу склада
                            window.location.href = '/sklad';

                        } catch (e) {
                            console.error('[pick] SEND error', e);
                            alert('Помилка мережі/сервера');
                        } finally {
                            btnSend.disabled = false;
                            btnSend.textContent = 'Отправить';
                        }
                    });
                }
            });
        </script>
@endpush
