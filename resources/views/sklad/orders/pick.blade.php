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

                        // DOM
                        const docList = document.getElementById('documentsList');
                        const posList = document.getElementById('positionsList');
                        const posUl   = document.getElementById('positionsUl');
                        const backBtn = document.getElementById('btnBack');
                        const title   = document.getElementById('pageTitle');
                        const input   = document.getElementById('barcodeInput');
                        const barcodeWrapper = document.getElementById('barcodeWrapper');

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

                        function matchesTabByRoom(room, tab = activeTab) {
                            const r = norm(room);
                            const patterns = ROOM_BY_TAB[tab] || [];
                            if (!patterns.length) return true;
                            return patterns.some(p => r.includes(p));
                        }

                        function matchesTabByCell(cell, tab = activeTab) {
                            const c = norm(cell);
                            if (tab === 'gp')   return c.includes('гп') || c.includes('готов') || c === '';
                            if (tab === 'dopy') return c.includes('доп') || c === '';   // ← показываем и пустые ячейки
                            if (tab === 'kom')  return c.includes('ком')  || c === '';  // ← показываем и пустые ячейки
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
                            filtered.forEach((doc, i) => {
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

                            // вернуть все вкладки
                            tabs.forEach(t => {
                                t.classList.remove('d-none');
                                t.classList.remove('active');
                                t.textContent = t.dataset.baseLabel || t.textContent.trim();
                            });
                            activeTab = 'gp';
                            tabs[0].classList.add('active');

                            renderDocuments();
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

                        function showPositions(index) {
                            const doc = documents[index];
                            if (!doc) return;

                            // выбрать вкладку документа
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

                                li.textContent =
                                    `#${line.НомерСтроки ?? idx + 1} — ${line.Номенклатура ?? '-'}, ` +
                                    `Кол: ${line.Количество ?? 0}, ` +
                                    `Уп: ${line.КоличествоУпаковок ?? 0}` +
                                    (line.Ячейка ? ` | Яч: ${line.Ячейка}` : '');

                                posUl.appendChild(li);
                            });

                            recomputeCountsByCells();
                            applyTabFilterInPositions();
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

                        // ============== поиск/сканер ==============
                        let timer;
                        input.addEventListener('input', () => {
                            clearTimeout(timer);
                            timer = setTimeout(() => {
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
                            }, 200);
                        });

                        // старт
                        showDocuments();
                    });
                </script>
@endpush
