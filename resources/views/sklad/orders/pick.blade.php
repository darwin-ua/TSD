@extends('layouts.app')
@section('content')
    @include('sklad.header_adm')
    <style>
        body {
            background: #f5f6f7;
        }

        .sklad-page {
            min-height: calc(100vh - 70px);
            padding: 18px 12px 30px;
            background:
                linear-gradient(135deg, rgba(255, 198, 0, 0.07), rgba(255,255,255,0.96)),
                #f5f6f7;
        }

        .sklad-shell {
            max-width: 430px;
            margin: 0 auto;
        }

        .sklad-top-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.10);
            overflow: hidden;
            border-top: 5px solid #f3c400;
        }

        .sklad-header {
            padding: 18px 18px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #eceff3;
        }

        .sklad-header-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .sklad-header-text {
            min-width: 0;
        }

        .sklad-header-title {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            color: #171717;
            line-height: 1.2;
        }

        .sklad-header-subtitle {
            margin-top: 4px;
            font-size: 13px;
            color: #777777;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sklad-header-icon,
        .btn-arrow {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #171717 !important;
            color: #f3c400 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: none;
            font-size: 22px;
            font-weight: 800;
            padding: 0;
        }

        .btn-arrow.d-none {
            display: none !important;
        }

        .sklad-body {
            padding: 18px;
        }

        .sklad-tabs-wrap {
            margin-bottom: 14px;
        }

        .nav-tabs {
            border-bottom: none;
            display: flex;
            gap: 8px;
            margin-bottom: 0 !important;
        }

        .nav-tabs .nav-item {
            flex: 1;
        }

        .nav-tabs .nav-link.custom-tab {
            width: 100%;
            text-align: center;
            border: 1px solid #e7e9ee;
            border-radius: 11px;
            background: #eef0f3;
            color: #171717;
            font-weight: 800;
            padding: 10px 6px;
            font-size: 14px;
            transition: 0.18s ease;
        }

        .nav-tabs .nav-link.custom-tab.active {
            background: #171717;
            border-color: #171717;
            color: #f3c400;
        }

        .sklad-scan-input,
        #barcodeInput {
            height: 52px;
            border-radius: 11px;
            border: 1px solid #d9dde2;
            box-shadow: none;
            font-size: 16px;
        }

        .sklad-scan-input:focus,
        #barcodeInput:focus {
            border-color: #f3c400;
            box-shadow: 0 0 0 0.2rem rgba(243, 196, 0, 0.18);
        }

        .sklad-menu-card,
        #documentsList .card {
            background: #ffffff;
            border: 1px solid #e7e9ee;
            border-radius: 16px;
            padding: 0;
            margin-bottom: 14px;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.07);
            transition: 0.18s ease;
            overflow: hidden;
        }

        .sklad-menu-card:hover,
        #documentsList .card:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.10);
            border-color: rgba(243, 196, 0, 0.75);
        }

        .doc-header {
            font-weight: 800;
            background: #ffffff;
            padding: 16px 18px;
            margin-top: 0;
            cursor: pointer;
            color: #171717;
            line-height: 1.35;
            border-left: 5px solid #f3c400;
        }

        .doc-header small {
            display: block;
            margin-top: 6px;
            color: #777777;
            font-weight: 600;
        }

        .sklad-main-btn {
            width: 100%;
            min-height: 48px;
            border: none;
            border-radius: 11px;
            background: #171717;
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            transition: 0.18s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 12px 14px;
        }

        .sklad-main-btn:hover,
        .sklad-main-btn:focus {
            background: #f3c400;
            color: #171717;
            text-decoration: none;
        }

        .sklad-main-btn:disabled {
            opacity: .65;
            cursor: not-allowed;
        }

        .sklad-back-link {
            width: 100%;
            min-height: 44px;
            border: none;
            border-radius: 11px;
            background: #eef0f3;
            color: #171717;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 11px 14px;
        }

        .sklad-back-link:hover,
        .sklad-back-link:focus {
            background: #e0e3e8;
            color: #171717;
            text-decoration: none;
        }

        #activeCellBanner.alert {
            border: none;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        #activeCellBanner.alert-info {
            background: rgba(243, 196, 0, 0.15);
            color: #171717;
        }

        #activeCellBanner.alert-warning {
            background: #fff3cd;
            color: #664d03;
        }

        .list-group-item {
            font-size: 12px;
        }

        #positionsUl {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        #positionsUl .list-group-item {
            display: flex;
            flex-direction: column;
            gap: 7px;
            border: 1px solid #e7e9ee !important;
            border-left: 5px solid #f3c400 !important;
            border-radius: 14px !important;
            padding: 14px 14px 13px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        #positionsUl.list-group-flush .list-group-item + .list-group-item {
            border-top-width: 1px !important;
        }

        .pos-title {
            font-weight: 800;
            line-height: 1.25;
            word-break: break-word;
            color: #171717;
        }

        .pos-qty {
            margin-top: 2px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .qty-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px 9px;
            border: 1px solid rgba(23, 23, 23, .12);
            border-radius: 999px;
            background: rgba(243, 196, 0, 0.15);
            color: #171717;
            font-weight: 800;
            font-size: 12px;
            line-height: 1;
            white-space: nowrap;
        }

        .qty-chip.fact {
            background: #eef0f3;
        }

        .hl-barcode,
        .list-group-item.hl-barcode,
        #positionsUl.list-group-flush .list-group-item.hl-barcode {
            border-color: #ffca2c !important;
            background: #fff9e6 !important;
            font-weight: 600;
        }

        #positionsUl .list-group-item.border-danger {
            border: 2px solid #dc3545 !important;
            border-left: 5px solid #dc3545 !important;
            background: #ffe6e9 !important;
        }

        .scan-confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .scan-confirm-box {
            background: #fff;
            border-radius: 16px;
            padding: 18px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 12px 32px rgba(0,0,0,.25);
            border-top: 5px solid #f3c400;
        }

        .scan-confirm-title {
            font-weight: 800;
            font-size: 18px;
            margin-bottom: 10px;
            color: #171717;
        }

        .scan-confirm-text {
            font-size: 14px;
            white-space: pre-line;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 15px;
            color: #333333;
        }

        .scan-confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .scan-confirm-actions .btn {
            border-radius: 11px;
            font-weight: 800;
            padding: 10px 14px;
        }


        .send-status-icon {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(243, 196, 0, 0.18);
            color: #171717;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
            margin: 0 auto 12px;
        }

        .send-status-spinner {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 4px solid rgba(23, 23, 23, 0.12);
            border-top-color: #f3c400;
            animation: sendStatusSpin .8s linear infinite;
        }

        @keyframes sendStatusSpin {
            to { transform: rotate(360deg); }
        }

        .scan-confirm-box.send-status-box {
            text-align: center;
        }

        .send-status-box.success {
            border-top-color: #198754;
        }

        .send-status-box.success .send-status-icon {
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
        }

        .send-status-box.error {
            border-top-color: #dc3545;
        }

        .send-status-box.error .send-status-icon {
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
        }

        .send-status-box .scan-confirm-actions {
            justify-content: center;
        }
    </style>
    <div class="content sklad-page" style="min-height:100%">
        <section class="content">
            <div class="sklad-shell">
                <div class="sklad-top-card">

                    <div class="sklad-header">
                        <div class="sklad-header-left">
                            <button id="btnBack"
                                    type="button"
                                    class="btn-arrow d-none"
                                    aria-label="Назад">←</button>

                            <div class="sklad-header-text">
                                <h1 class="sklad-header-title" id="pageTitle">Документы отбора</h1>
                                <div class="sklad-header-subtitle">Дарвін · отбор и размещение</div>
                            </div>
                        </div>

                        <a href="{{ route('sklad.index') }}"
                           class="sklad-header-icon"
                           title="Главная">
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 fill="currentColor"
                                 viewBox="0 0 16 16">
                                <path d="M8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 2 8h1v6.5A1.5 1.5 0 0 0 4.5 16h7a1.5 1.5 0 0 0 1.5-1.5V8h1a.5.5 0 0 0 .354-.854zM12 7.707V14.5a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5V7.707l4-4z"/>
                            </svg>
                        </a>
                    </div>

                    <div class="sklad-body">
                        <div id="tabsContainer" class="sklad-tabs-wrap">
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
                        </div>

                        {{-- Поле штрихкода --}}
                        <div id="barcodeWrapper" class="mb-3 d-none">
                            <input id="barcodeInput"
                                   type="text"
                                   class="form-control form-control-lg sklad-scan-input"
                                   placeholder="Сканируйте номенклатуру или штрихкод..."
                                   autocomplete="off">
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
                            <button id="btnSend"
                                    type="button"
                                    class="sklad-main-btn d-none"
                                    data-send-url="{{ route('sklad.acceptance.finish') }}">
                                Отправить
                            </button>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('sklad.index') }}" class="sklad-back-link">Главная</a>
                        </div>
                    </div>
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

                        const firstBad = notScannedRows[0]?.element;
                        if (firstBad) {
                            firstBad.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        }

                        const allowSend = await askSendWithoutScanning(
                            'Есть строки, которые ни разу не сканировались:\n\n' +
                            msg +
                            '\n\nЧто делаем?'
                        );

                        if (!allowSend) {
                            input?.focus();
                            return;
                        }
                    }
                    try {
                        btnSend.disabled = true;
                        btnSend.textContent = 'Отправляем...';

                        showSendLoadingModal(
                            'Отправка данных',
                            'Идёт отправка документа на сервер. Не закрывайте страницу.'
                        );

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
                            closeSendStatusModal();
                            await showSendResultModal(
                                'error',
                                'Ошибка отправки',
                                (data && (data.msg || JSON.stringify(data))) || ('HTTP ' + resp.status)
                            );
                            return;
                        }

                        closeSendStatusModal();

                        // тут 1С уже отработала — покажем итог модалкой
                        await showSendResultModal(
                            'success',
                            'Готово',
                            'Документ успешно отправлен: ' + (data?.Документ || 'операция завершена')
                        );

                        // вернёмся на главную после кнопки "ОК"
                        window.location.href = '/sklad';
                    } catch (e) {
                        console.error('[finish_acceptance] error', e);
                        closeSendStatusModal();
                        await showSendResultModal(
                            'error',
                            'Ошибка отправки',
                            'Помилка мережі/сервера'
                        );
                    } finally {
                        btnSend.disabled = false;
                        btnSend.textContent = 'Отправить';
                    }
                });
            }
        });


        function closeSendStatusModal() {
            const old = document.querySelector('.send-status-overlay');
            if (old) old.remove();
        }

        function showSendLoadingModal(title, message) {
            closeSendStatusModal();

            const overlay = document.createElement('div');
            overlay.className = 'scan-confirm-overlay send-status-overlay';

            overlay.innerHTML = `
            <div class="scan-confirm-box send-status-box">
                <div class="send-status-icon">
                    <div class="send-status-spinner"></div>
                </div>

                <div class="scan-confirm-title">${title}</div>
                <div class="scan-confirm-text">${message}</div>
            </div>
        `;

            document.body.appendChild(overlay);
        }

        function showSendResultModal(type, title, message) {
            return new Promise(resolve => {
                closeSendStatusModal();

                const overlay = document.createElement('div');
                overlay.className = 'scan-confirm-overlay send-status-overlay';

                const icon = type === 'success' ? '✓' : '!';
                const buttonClass = type === 'success' ? 'btn btn-success' : 'btn btn-danger';

                overlay.innerHTML = `
            <div class="scan-confirm-box send-status-box ${type}">
                <div class="send-status-icon">${icon}</div>

                <div class="scan-confirm-title">${title}</div>
                <div class="scan-confirm-text">${message}</div>

                <div class="scan-confirm-actions">
                    <button type="button" class="${buttonClass}" data-action="ok">
                        ОК
                    </button>
                </div>
            </div>
        `;

                document.body.appendChild(overlay);

                overlay.querySelector('[data-action="ok"]').addEventListener('click', () => {
                    overlay.remove();
                    resolve(true);
                });
            });
        }

        function askSendWithoutScanning(message) {
            return new Promise(resolve => {
                const old = document.querySelector('.scan-confirm-overlay');
                if (old) old.remove();

                const overlay = document.createElement('div');
                overlay.className = 'scan-confirm-overlay';

                overlay.innerHTML = `
            <div class="scan-confirm-box">
                <div class="scan-confirm-title">Есть неотсканированные строки</div>

                <div class="scan-confirm-text">${message}</div>

                <div class="scan-confirm-actions">
                    <button type="button" class="btn btn-secondary" data-action="continue">
                        Сканировать
                    </button>

                    <button type="button" class="btn btn-danger" data-action="send">
                        Отправить без скана
                    </button>
                </div>
            </div>
        `;

                document.body.appendChild(overlay);

                overlay.querySelector('[data-action="continue"]').addEventListener('click', () => {
                    overlay.remove();
                    resolve(false);
                });

                overlay.querySelector('[data-action="send"]').addEventListener('click', () => {
                    overlay.remove();
                    resolve(true);
                });
            });
        }
    </script>

@endpush
