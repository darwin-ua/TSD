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

        #positionsUl .list-group-item{ display:flex; flex-direction:column; gap:6px;   border:1px solid #cfd4da !important;
            border-radius:12px !important;}
        .pos-title{ font-weight:600; line-height:1.25; word-break:break-word; }
        .pos-qty{ margin-top:2px; display:flex; gap:8px; flex-wrap:wrap; }
        .qty-chip{
            display:inline-block; padding:1px 8px; border:1px solid #333;
            border-radius:5px; background:#fffbe6; font-weight:700; font-size:.95em; line-height:1; white-space:nowrap;
        }
        .qty-chip.fact{ background:#e7f1ff; } /* визуально отличаем Факт */
        .hl-barcode{ background-color:#fff3cd !important; }
        /* прибираємо фіксовані "роздільні" бордери, що ставить flush */
        #positionsUl.list-group-flush .list-group-item + .list-group-item{
            border-top-width:1px !important; /* щоб рамка зберігалася повністю */
        }

        /* підсвітка знайденого скану */
        #positionsUl.list-group-flush .list-group-item.hl-barcode{
            border-color:#ffca2c !important;
            background:#fff9e6 !important;
        }


        #positionsUl .list-group-item.border-danger {
            border: 2px solid #dc3545 !important;
            background: #ffe6e9 !important;
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
                           placeholder="Сканируйте номенклатуру или штрихкод иии..." autocomplete="off"> </div>
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
                    <button id="btnSend"
                            type="button"
                            class="btn btn-primary btn-lg w-100 d-none"
                            data-send-url="{{ route('sklad.acceptance.finish') }}">
                        Отправить
                    </button>
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
            console.log('[pick] DOM ready');

            const documents = @json(session('pick_orders', [])) || [];

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

            const CODE_MAX        = 11; // длина scan_position_document.code
            const POS_SAVE_URL    = @json(route('sklad.scan.position.store'));
            const STATE_FETCH_URL = @json(route('sklad.scan.session.state'));
            const SEND_URL        = @json(route('sklad.scan.send'));
            const SEARCH_BARCODE_URL = @json(route('sklad.scan.search.barcode'));
            const ADD_EXTERNAL_POS_URL = @json(route('sklad.scan.addLineByNumber'));

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const FINISH_URL = @json(route('sklad.tsd.finish_acceptance'));

            const CELL_LABEL_URL = @json(route('sklad.cell.label'));
            console.log('[pick] routes:', { POS_SAVE_URL, STATE_FETCH_URL, SEND_URL, SEARCH_BARCODE_URL });

            // ===== Активная ячейка (state из сессии/кеша) =====
            let activeCellTextNorm = '';

            let activeState = null;
            let currentCellNameFor1C = null; // имя ячейки, которое шлём в 1С как cell_name


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

            // Глобально выше по файлу:
// let activeState = null;
// let activeCellTextNorm = ''; // НОРМАЛИЗОВАННЫЙ текст активной ячейки

            async function loadCellState() {
                try {
                    // 1) Забираем state
                    const r = await fetch(STATE_FETCH_URL, { headers: { 'Accept': 'application/json' } });
                    const j = await r.json().catch(() => ({}));
                    activeState = j.state || null;

                    // 2) Сбрасываем и подготавливаем баннер
                    banner.classList.remove('d-none', 'alert-warning', 'alert-info');

                    const raw = activeState?.cell ?? '';
                    if (!raw) {
                        activeCellTextNorm = '';
                        banner.classList.add('alert-warning');
                        banner.textContent = 'Ячейка не выбрана. Сначала отсканируйте ячейку на экране "Размещение".';
                        return;
                    }

                    // 3) Пытаемся получить красивую метку/помещение/вкладку с бэка
                    let nice = null, room = null, tab = null;
                    try {
                        const r2  = await fetch(CELL_LABEL_URL + '?number=' + encodeURIComponent(raw));
                        const j2  = await r2.json();
                        nice = (j2?.label ?? null);
                        room = (j2?.room  ?? null);
                        tab  = (j2?.tab   ?? null);
                    } catch (_) {
                        // игнор — используем локальные эвристики
                    }

                    // 4) Баннер
                    banner.classList.add('alert-info');
                    const label = nice || raw;
                    banner.textContent = room ? `Ячейка: ${label} (Помещение: ${room})` : `Ячейка: ${label}`;

                    // 5) Нормализованный текст активной ячейки — нужен для проверки инкремента «Факт»
                    activeCellTextNorm = norm(label || raw || '');

                    // 5.1) Сохраняем «имя ячейки для 1С» (его и будем отправлять как cell_name)
                    currentCellNameFor1C = label || raw || null;


                    // 6) Если бэк не вернул вкладку — пробуем определить по тексту ячейки
                    if (!tab) {
                        tab = detectTabByCellText(label) || detectTabByCellText(raw) || null;
                    }

                    // 7) Пробуем проставить warehouse_id, если его ещё нет
                    //    Сначала по вычисленной вкладке, иначе — эвристика из первой строки позиций
                    if (activeState && !activeState.warehouse_id) {
                        let wh = null;
                        if (tab && WAREHOUSE_BY_TAB && WAREHOUSE_BY_TAB[tab]) {
                            wh = WAREHOUSE_BY_TAB[tab];
                        }
                        if (!wh) {
                            wh = inferWarehouseFromFirstRowCell(); // может вернуть null — ок
                        }
                        if (wh) {
                            activeState.warehouse_id = wh;
                            // Если есть локальная переменная текущего склада — обновим и её
                            if (typeof currentWarehouseId !== 'undefined' && (currentWarehouseId == null)) {
                                currentWarehouseId = wh;
                            }
                            console.log('[STATE] warehouse_id set →', wh);
                        }
                    }

                    // 8) Если определили вкладку — показываем только её и перерисовываем контент
                    if (tab) {
                        activeTab = tab;

                        // Спрятать неактивные вкладки
                        tabs.forEach(t => {
                            const isActive = (t.dataset.tab === activeTab);
                            t.classList.toggle('active', isActive);
                            t.classList.toggle('d-none', !isActive);
                        });

                        // Если сейчас экран документов — перерисуем список;
                        // если экран позиций — просто применим фильтр (если функция есть)
                        const onDocsScreen = !docList.classList.contains('d-none');
                        if (onDocsScreen) {
                            renderDocuments();
                        } else if (typeof applyTabFilterInPositions === 'function') {
                            applyTabFilterInPositions();
                        }
                    }
                } catch (e) {
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
// стало: сначала определяем вкладку по активной ячейке, она сама дернёт renderDocuments() при необходимости
                loadCellState();

                // renderDocuments();
                // loadCellState();
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

            function renderLi(li) {
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



            function getNextLineNumber() {
                let max = 0;
                document.querySelectorAll('#positionsUl li').forEach(li => {
                    const n = parseInt(li.dataset.line || '', 10);
                    if (!Number.isNaN(n) && n > max) max = n;
                });
                return max + 1;
            }

            function aggKeyFor(found, code) {
                const bc = String(found?.barcode || code || '').trim().toLowerCase();
                if (bc) return 'bc:' + bc;            // агрегируем по штрихкоду
                const nom = String(found?.nomen || '').trim().toLowerCase();
                return 'nom:' + nom;                  // фолбек по номенклатуре
            }

            function getOrCreateAggLi(key, displayNom, displayBarcode) {
                let li = document.querySelector(`#positionsUl li[data-agg-key="${key}"]`);
                if (li) return li;

                li = document.createElement('li');
                li.className = 'list-group-item agg-line';
                li.dataset.aggKey      = key;
                li.dataset.nom         = (displayNom || '').toLowerCase();
                li.dataset.nomOriginal = displayNom || '-';
                li.dataset.barcode     = (displayBarcode || '').toLowerCase();
                li.dataset.cell        = norm(currentCellNameFor1C || activeState?.cell || '');
                li.dataset.line        = String(getNextLineNumber()); // локальный номер для UI
                li.dataset.qty         = li.dataset.qty || '0';
                li.dataset.fact        = '0';

                const badge = document.createElement('span');
                badge.className = 'badge badge-warning ml-2';
                badge.dataset.badge = 'pending';
                badge.textContent = 'Отправляем...';

                li.innerHTML = `
    <div class="pos-title">#${li.dataset.line} — ${li.dataset.nomOriginal}</div>
    <div class="pos-qty">
      <span class="qty-chip plan">План: ${Number(li.dataset.qty) || 0}</span>
      <span class="qty-chip fact">Факт: 0</span>
    </div>
  `;
                li.querySelector('.pos-title')?.appendChild(badge);

                posUl.appendChild(li);
                // актуализируем бейджи/фильтр, как у тебя принято
                if (typeof recomputeCountsByCells === 'function') recomputeCountsByCells();
                if (typeof applyTabFilterInPositions === 'function') applyTabFilterInPositions();

                return li;
            }

            function updateLiCounts(li) {
                const qtyPln = Number(li.dataset.qty || 0) || 0;
                const qtyFct = Number(li.dataset.fact || 0) || 0;
                li.querySelector('.pos-qty .plan')?.replaceChildren(document.createTextNode(`План: ${qtyPln}`));
                li.querySelector('.pos-qty .fact')?.replaceChildren(document.createTextNode(`Факт: ${qtyFct}`));
            }

            function setPending(li, delta) {
                const cur = Number(li.dataset.pending || 0);
                const next = Math.max(0, cur + delta);
                li.dataset.pending = String(next);
                let badge = li.querySelector('[data-badge="pending"]');

                if (next > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge badge-warning ml-2';
                        badge.dataset.badge = 'pending';
                        li.querySelector('.pos-title')?.appendChild(badge);
                    }
                    badge.textContent = `Отправляем... (${next})`;
                    badge.className = 'badge badge-warning ml-2';
                } else {
                    if (badge) {
                        badge.textContent = 'В документе';
                        badge.className = 'badge badge-success ml-2';
                    }
                }
            }
            async function appendExternalItem(found, code) {
                const displayNom     = found?.nomen || '(по штрихкоду)';
                const displayBarcode = found?.barcode || code || '';

                // 1) одна агрегированная строка на штрихкод/ном
                const key = aggKeyFor(found, code);
                const li  = getOrCreateAggLi(key, displayNom, displayBarcode);

                // 2) UI: оптимистично увеличиваем «Факт» на +1
                li.dataset.fact = String((Number(li.dataset.fact || 0) || 0) + 1);
                li.classList.add('hl-barcode');
                updateLiCounts(li);
                li.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

                // 3) считаем «в полёте»
                setPending(li, +1);

                // 4) На сервер — ВСЕГДА новая строка
                try {
                    const payload = {
                        document_no: String(currentDocNo),
                        code: String(displayBarcode),
                        quantity: 1,

                        // диагностическое
                        document_id: String(currentDocNo),
                        warehouse_id: currentWarehouseId,
                        active_cell: activeState?.cell || null,
                        barcode: String(displayBarcode),
                        nomen: found?.nomen || null,
                        characteristic: found?.characteristic || null,
                        fill_placed: true,
                        line_no_hint: Number(li.dataset.line) || undefined,
                        doc_link: currentDoc?.Ссылка || null,

                        // важно: шлём ИМЯ ячейки
                        cell_name: currentCellNameFor1C || null,

                        scan_id: (window.crypto?.randomUUID?.() || Date.now()+''),
                    };

                    const resp = await fetch(ADD_EXTERNAL_POS_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(payload),
                    });

                    const raw = await resp.text();
                    let data = {};
                    try { data = raw ? JSON.parse(raw) : {}; } catch(e) {}

                    if (!resp.ok || !data.ok) {
                        // откат UI
                        li.dataset.fact = String(Math.max(0, (Number(li.dataset.fact || 0) || 0) - 1));
                        updateLiCounts(li);
                        setPending(li, -1);
                        alert((data && (data.msg || JSON.stringify(data))) || ('HTTP ' + resp.status));
                        return;
                    }

                    setPending(li, -1); // успех: уменьшаем pending

                } catch (e) {
                    console.error('[ADD-EXTERNAL] fetch error', e);
                    // откат UI
                    li.dataset.fact = String(Math.max(0, (Number(li.dataset.fact || 0) || 0) - 1));
                    updateLiCounts(li);
                    setPending(li, -1);
                    alert('Ошибка при добавлении позиции в документ.');
                }
            }

            function aggKeyFromRow(line) {
                const bc  = String(line.Штрихкод || '').trim().toLowerCase();
                if (bc) return 'bc:' + bc;                  // основной ключ — по штрихкоду
                const nom = String(line.Номенклатура || '').trim().toLowerCase();
                return 'nom:' + nom;                        // фолбек — по номенклатуре
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
                // — отрисовка позиций «План + Факт» (АГРЕГИРОВАНО) —
                posUl.innerHTML = '';
                const rows = Array.isArray(doc.ТоварыРазмещение) ? doc.ТоварыРазмещение : [];

// 1) Группируем строки ТЧ: один ключ = одна визуальная строка
                const agg = new Map();
                for (const line of rows) {
                    const key = aggKeyFromRow(line);
                    if (!key) continue;
                    if (!agg.has(key)) {
                        agg.set(key, {
                            key,
                            displayNom: String(line.Номенклатура || '-'),
                            displayBC:  String(line.Штрихкод || ''),
                            cell:       String(line.Ячейка || ''),
                            planSum:    Number(line.Количество ?? 0) || 0,
                        });
                    } else {
                        const a = agg.get(key);
                        a.planSum += Number(line.Количество ?? 0) || 0; // складываем План
                    }
                }

// 2) Рендерим ТОЛЬКО по одной LI на ключ
                let localLineCounter = 0;
                for (const a of agg.values()) {
                    const li = document.createElement('li');
                    li.className = 'list-group-item agg-line';

                    // datasets — как у тебя, чтобы фильтры/поиск работали
                    li.dataset.aggKey      = a.key;
                    li.dataset.nom         = a.displayNom.toLowerCase();
                    li.dataset.nomOriginal = a.displayNom;
                    li.dataset.barcode     = a.displayBC.toLowerCase();
                    li.dataset.cell        = norm(a.cell);
                    li.dataset.line        = String(++localLineCounter);  // локальная нумерация для UI
                    li.dataset.qty         = String(a.planSum);           // суммарный ПЛАН по группе
                    li.dataset.fact        = '0';                         // Факт на старте = 0

                    renderLi(li);
                    posUl.appendChild(li);
                }

// 3) Обновим бейджи/фильтр
                if (typeof recomputeCountsByCells === 'function') recomputeCountsByCells();
                if (typeof applyTabFilterInPositions === 'function') applyTabFilterInPositions();

// 4) Показать/скрыть кнопку "Отправить" по числу агрегированных строк
                const renderedCount = agg.size;
                if (btnSend) {
                    if (renderedCount > 0) {
                        btnSend.classList.remove('d-none');
                    } else {
                        btnSend.classList.add('d-none');
                    }
                }


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

            // === Надёжный поиск номера позиции в DOM (без фолбеков на первую строку) ===
            function resolveNumberPosition(rawCode) {
                const code = String(rawCode || '').trim();
                if (!code) return null;

                const isBarcode = /^\d{6,}$/.test(code);
                let found = null;

                // 1) По штрихкоду — только полное совпадение
                if (isBarcode) {
                    const val = code.toLowerCase();
                    document.querySelectorAll('#positionsUl li:not(.d-none)').forEach(li => {
                        if (found !== null) return;
                        const bc = (li.dataset.barcode || '').toLowerCase();
                        if (bc && bc === val) {
                            const n = parseInt(li.dataset.line || '', 10);
                            if (!Number.isNaN(n)) found = n;
                        }
                    });
                }

                // 2) По названию — подстрочное совпадение
                if (found === null && !isBarcode) {
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

                // Без фолбеков!
                return found;
            }

            // ============== Сохранение позиции в БД ==============
            async function savePositionScan(rawCode) {
                const code = String(rawCode || '').trim();
                console.log('[SAVE] force external add', { code, currentDocNo, activeState, currentWarehouseId });

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

                const safeForSearch = code.length > CODE_MAX ? code.slice(0, CODE_MAX) : code;

                try {
                    // Всегда спрашиваем 1С для красивого названия/хар-ки
                    const resp = await fetch(SEARCH_BARCODE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ barcode: safeForSearch }),
                    });

                    const raw = await resp.text();
                    let data = {};
                    try { data = raw ? JSON.parse(raw) : {}; } catch(e) {}

                    console.log('[SEARCH BARCODE] HTTP', resp.status, data);

                    if (resp.ok && data.ok && Array.isArray(data.items) && data.items.length > 0) {
                        const it = data.items[0];
                        appendExternalItem(it, code);    // важно: передаём ПОЛНЫЙ code
                        return;
                    }

                    // если не нашли — добавим как «сырой» штрихкод
                    appendExternalItem({ nomen: '(по штрихкоду)', characteristic: null, barcode: code }, code);
                } catch (e) {
                    console.error('[SEARCH BARCODE] fetch error', e);
                    // на сетевой ошибке тоже добавляем как «сырой» скан
                    appendExternalItem({ nomen: '(скан)', characteristic: null, barcode: code }, code);
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

                    let matchesCount = 0;

                    document.querySelectorAll('#positionsUl li').forEach(li => {
                        if (li.classList.contains('d-none')) {
                            li.classList.remove('hl-barcode');
                            return;
                        }
                        const nom = (li.dataset.nom || '');
                        const bc  = (li.dataset.barcode || '');
                        // для штрихкода — ТОЛЬКО полное совпадение, без includes
                        const match = isBarcodeQuery ? (bc === val) : nom.includes(val);
                        li.classList.toggle('hl-barcode', Boolean(val) && match);
                        if (match) matchesCount++;
                    });

                    // Скроллим только если есть совпадения
                    if (matchesCount > 0) {
                        const first = document.querySelector('#positionsUl li.hl-barcode:not(.d-none)');
                        if (first) first.scrollIntoView({ block:'center', behavior:'smooth' });
                    }

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

            function getNotScannedRows() {
                const badRows = [];

                document.querySelectorAll('#positionsUl li').forEach(li => {
                    const plan = Number(li.dataset.qty || 0) || 0;
                    const fact = Number(li.dataset.fact || 0) || 0;

                    // строка есть в плане, но ни разу не сканировалась
                    if (plan > 0 && fact <= 0) {
                        badRows.push({
                            line: li.dataset.line || '-',
                            nom: li.dataset.nomOriginal || '-',
                            plan: plan,
                            fact: fact,
                            element: li,
                        });
                    }
                });

                return badRows;
            }


            // === Кнопка "Отправить" ===
            if (!btnSend) {
                console.warn('[pick] btnSend not found');
            } else {
                console.log('[pick] btnSend wired');
                btnSend.addEventListener('click', async () => {
                    // номер документа — сперва из логики страницы, иначе из заголовка
                    const pageTitleText = document.getElementById('pageTitle')?.textContent || '';
                    const titleNo = (pageTitleText.match(/(00-\d+)/) || [])[1] || '';
                    const number = (window.currentDocumentId && String(window.currentDocumentId)) || titleNo;

                    if (!number) {
                        alert('Не удалось определить номер документа.');
                        return;
                    }
// ===== ПРОВЕРКА: есть ли строки, которые ни разу не сканировались =====
                    const notScannedRows = getNotScannedRows();

                    if (notScannedRows.length > 0) {
                        document.querySelectorAll('#positionsUl li').forEach(li => {
                            li.classList.remove('border-danger');
                        });

                        notScannedRows.forEach(row => {
                            row.element.classList.add('border-danger');
                        });

                        const msg = notScannedRows
                            .map(row => `#${row.line} — ${row.nom} | План: ${row.plan}, Факт: ${row.fact}`)
                            .join('\n');

                        alert(
                            'Есть строки, которые ни разу не сканировались.\n\n' +
                            msg +
                            '\n\nСначала отсканируйте эти позиции, потом отправляйте документ.'
                        );

                        const firstBad = notScannedRows[0]?.element;
                        if (firstBad) {
                            firstBad.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }

                        return;
                    }
                    try {
                        btnSend.disabled = true;
                        btnSend.textContent = 'Отправляем...';

                        const resp = await fetch(FINISH_URL, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                number: number,      // <- Laravel ждёт "number"
                                // at: new Date().toISOString(), // при желании можно пробрасывать время
                            }),
                        });

                        const raw = await resp.text();
                        let data = {};
                        try { data = raw ? JSON.parse(raw) : {}; } catch (_) {}

                        if (!resp.ok) {
                            alert((data && (data.msg || JSON.stringify(data))) || ('HTTP ' + resp.status));
                            return;
                        }

                        // тут 1С уже отработала — покажем итог
                        alert('Готово: ' + (data?.Документ || 'операция завершена'));

                        // вернёмся на главную
                        window.location.href = '/sklad';
                    } catch (e) {
                        console.error('[finish_acceptance] error', e);
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
