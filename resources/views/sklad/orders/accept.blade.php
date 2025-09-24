@extends('layouts.app')
@section('content')
    @include('sklad.header_adm')

    <style>
        .hl-barcode { background-color:#fff3cd !important; }
        .list-group-item.hl-barcode { font-weight:600; }
        .doc-header { font-weight:bold; background:#f2f2f2; padding:8px 12px; margin-top:10px; cursor:pointer; }
        .btn-arrow { width:40px; height:40px; font-size:20px; border-radius:5px; border:none; }
        .list-group-item { font-size:12px; }
        .qty-highlight {
            font-weight: bold;        /* жирный */
            font-size: 1.1em;         /* примерно в 2 раза больше */
            border: 2px solid #333;   /* рамка */
            border-radius: 6px;       /* скругление */
            padding: 2px 6px;
            background: #fffbe6;      /* слегка подсветить фон */
        }

    </style>

    <div class="content" style="min-height:100%; padding:10px;">
        <section class="content">
            <div class="container-fluid">

                {{-- Верхняя панель --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button id="btnBack" class="btn btn-arrow bg-secondary text-white d-none">&larr;</button>
                    <div class="text-center flex-grow-1">
                        <strong id="pageTitle">Документы приёмки</strong>
                    </div>
                </div>

                {{-- Поле штрихкода (покажется при входе в документ) --}}
                {{-- Поле штрихкода --}}
                <div id="barcodeWrapper" class="mb-3 d-none sticky-top bg-white p-2" style="z-index:1000;">
                    <input id="barcodeInput" type="text" class="form-control form-control-lg"
                           placeholder="Сканируйте номенклатуру или штрихкод..." autocomplete="off">
                </div>


                {{-- Список документов --}}
                <div id="documentsList">
                    @forelse(session('accept_orders', []) as $i => $doc)
                        <div class="card mb-2">
                            <div class="doc-header select-doc" data-doc-index="{{ $i }}">
                                {{ $doc['Ссылка'] ?? 'Без названия' }} — Статус: {{ $doc['Статус'] ?? '-' }}
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info">Нет документов приёмки.</div>
                    @endforelse
                </div>

                {{-- Позиции выбранного документа --}}
                <div id="positionsList" class="d-none">
                    <ul class="list-group list-group-flush" id="positionsUl"></ul>
                </div>
                <div class="mt-3">
                    <a href="{{ route('sklad.index') }}" class="btn btn-dark">Дом</a>
                    <button id="theend" class="btn btn-primary ml-1 d-none">Принять</button>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <style>
        /* чтобы элементы внутри li шли столбцом */
        #positionsUl .list-group-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .pos-title {
            font-weight: 600;
            line-height: 1.25;
            word-break: break-word;        /* длинные названия не ломают верстку */
        }

        .pos-qty {                       /* контейнер для чипа количества */
            margin-top: 2px;
        }

        /* твой чип количества – сделаем его блочным и аккуратным */
        .qty-highlight {
            display: inline-block;
            padding: 2px 8px;
            border: 2px solid #333;
            border-radius: 8px;
            background: #fffbe6;
            font-weight: 700;
            font-size: 0.95em;
            line-height: 1.1;
            white-space: nowrap;
        }
    </style>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const documents = @json(session('accept_orders', []));
            const docList   = document.getElementById('documentsList');
            const posList   = document.getElementById('positionsList');
            const posUl     = document.getElementById('positionsUl');
            const backBtn   = document.getElementById('btnBack');
            const titleEl   = document.getElementById('pageTitle');
            const barcodeWrapper = document.getElementById('barcodeWrapper');
            const input     = document.getElementById('barcodeInput');
            const theendBtn = document.getElementById('theend');

            // Индексы: одна визуальная строка на rownum, и быстрый поиск по любому ШК
            const rowMap       = new Map(); // rownum -> <li>
            const barcodeIndex = new Map(); // barcode -> rownum

            const norm = s => (String(s || '')).trim().toLowerCase();

            // ======================== Утилиты ========================

            function renderLi(li) {
                const rownum = li.dataset.rownum || '';
                const nom    = li.dataset.nomOriginal || '-';
                const qty    = Number(li.dataset.qty || 0);

                li.innerHTML = `
    <div class="pos-title">#${rownum} — ${nom}</div>
    <div class="pos-qty">
      <span class="qty-highlight">Кол: ${qty}</span>
    </div>
  `;
            }


            function showDocuments() {
                docList.classList.remove('d-none');
                posList.classList.add('d-none');
                backBtn.classList.add('d-none');
                barcodeWrapper.classList.add('d-none');
                titleEl.textContent = 'Документы приёмки';
                posUl.innerHTML = '';
                input.value = '';
                theendBtn.classList.add('d-none');
            }

            // Собираем ТОЛЬКО отсканированные и СУММИРУЕМ по НомерСтроки
            function buildPositionsScannedOnly() {
                const lis = Array.from(document.querySelectorAll('#positionsUl li'));
                const sumByRow = new Map();

                lis.forEach(li => {
                    const row = Number(li.dataset.rownum || 0);
                    if (!row) return;

                    // берем только те, что реально сканировались
                    if (li.dataset.scanned !== '1') return;

                    let qty = parseFloat(li.dataset.qty);
                    if (isNaN(qty)) qty = 0;
                    qty = Math.max(0, Math.floor(qty)); // целое, неотрицательное

                    sumByRow.set(row, (sumByRow.get(row) || 0) + qty);
                });

                return Array.from(sumByRow.entries())
                    .map(([НомерСтроки, НовоеКоличество]) => ({ НомерСтроки, НовоеКоличество }))
                    // если кому-то важно — можно пропускать нули, но мы оставим ноль, т.к. 1С теперь его принимает
                    .sort((a, b) => a.НомерСтроки - b.НомерСтроки);
            }

            // ======================== Показ позиций (merge дублей) ========================

            function showPositions(index) {
                const doc = documents[index];
                if (!doc) return;

                const numMatch  = String(doc.Ссылка || '').match(/(00-\d{6,})/);
                const docNumber = numMatch ? numMatch[1] : '';
                titleEl.textContent = docNumber || 'Позиции документа';
                document.body.dataset.docNumber = docNumber;

                docList.classList.add('d-none');
                posList.classList.remove('d-none');
                backBtn.classList.remove('d-none');
                barcodeWrapper.classList.remove('d-none');

                const rows = Array.isArray(doc.Товары) ? doc.Товары : [];
                posUl.innerHTML = '';

                rowMap.clear();
                barcodeIndex.clear();

                const toLog = [];

                rows.forEach((line, idx) => {
                    const rownum  = Number(line.НомерСтроки ?? (idx + 1));
                    const barcode = (String(line.Штрихкод || '')).trim().toLowerCase();
                    const nom     = (line.Номенклатура ?? '-');
                    const chr     = (line.Характеристика ?? '');
                    const packs   = (line.КоличествоУпаковок ?? 0);
                    const qty0    = Number(line.Количество ?? 0) || 0;

                    if (!rownum) return;

                    // Любой штрихкод ведёт в одну и ту же строку
                    if (barcode) barcodeIndex.set(barcode, rownum);

                    if (!rowMap.has(rownum)) {
                        // Создаём ОДНУ строку (первая встреченная)
                        const li = document.createElement('li');
                        li.className = 'list-group-item';

                        li.dataset.nom     = nom.trim().toLowerCase();
                        li.dataset.barcode = barcode; // базовый (для фолбэка)
                        li.dataset.char    = chr.trim().toLowerCase();

                        li.dataset.nomOriginal     = nom;
                        li.dataset.barcodeOriginal = barcode; // отображаем первый
                        li.dataset.charOriginal    = chr;
                        li.dataset.rownum          = String(rownum);

                        li.dataset.qtyOriginal = String(qty0);
                        li.dataset.qty         = String(qty0);
                        li.dataset.packs       = String(packs);

                        // ФЛАГИ СКАНИРОВАНИЯ
                        li.dataset.scanned   = '0'; // не сканировался
                        li.dataset.scanDelta = '0'; // сколько раз прибавили

                        renderLi(li);
                        posUl.appendChild(li);
                        rowMap.set(rownum, li);
                    } else {
                        // ДУБЛИКАТ СТРОКИ — НЕ выводим, только суммируем в существующую
                        const li = rowMap.get(rownum);
                        const curOrig = Number(li.dataset.qtyOriginal || 0);
                        const curNow  = Number(li.dataset.qty || 0);

                        li.dataset.qtyOriginal = String(curOrig + qty0);
                        li.dataset.qty         = String(curNow  + qty0);

                        // Доп. штрихкоды тоже индексируем на ту же строку
                        if (barcode) {
                            barcodeIndex.set(barcode, rownum);
                            if (!li.dataset._extraBarcodes?.includes(barcode)) {
                                li.dataset._extraBarcodes =
                                    (li.dataset._extraBarcodes ? (li.dataset._extraBarcodes + ',') : '') + barcode;
                            }
                        }

                        // не меняем флаги сканирования: это просто merge исходных данных
                        renderLi(li);
                    }

                    toLog.push({
                        rownum, qty_original: qty0, barcode: barcode || '', nom: nom, characteristic: chr
                    });
                });

                console.log(`==== К сканированию (после merge): ${rowMap.size} строк (документ ${docNumber}) ====`);
                console.table(Array.from(rowMap.values()).map(li => ({
                    rownum: li.dataset.rownum,
                    qty_original_total: li.dataset.qtyOriginal,
                    qty_now_total: li.dataset.qty,
                    scanned: li.dataset.scanned,
                    scanDelta: li.dataset.scanDelta,
                    barcode_primary: li.dataset.barcodeOriginal,
                    barcodes_extra: li.dataset._extraBarcodes || ''
                })));

                theendBtn.classList.remove('d-none');
                setTimeout(() => input.focus(), 100);
            }

            // ======================== Отправка ========================

            async function sendFinishAcceptance() {
                const Номер = (document.body.dataset.docNumber || '').trim();
                if (!Номер) { alert('Не удалось определить номер документа.'); return; }

                const Позиции = buildPositionsScannedOnly();
                if (!Позиции.length) {
                    alert('Ничего не отсканировано — отправлять нечего.');
                    return;
                }

                const payload = { Номер, Позиции };
                console.log('==== FinishAcceptance → payload (SCANNED ONLY, SUMMED) ====');
                console.table(Позиции);
                console.log(JSON.stringify(payload, null, 2));

                const originalText = theendBtn.textContent;
                theendBtn.disabled = true;
                theendBtn.textContent = 'Відправляю…';

                try {
                    const resp = await fetch("{{ route('sklad.acceptance.finish') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    const raw = await resp.text();
                    let data; try { data = JSON.parse(raw); } catch { data = { raw }; }

                    if (!resp.ok || data?.ok === false) {
                        console.error('FinishAcceptance error', { status: resp.status, data });
                        const msg = data?.error || data?.message || `HTTP ${resp.status}`;
                        alert('Помилка при завершенні приймання: ' + msg);
                        return;
                    }

                    const changed = (data && data.ИзмененоСтрок != null)
                        ? `, змінено рядків: ${data.ИзмененоСтрок}` : '';
                    alert('Приймання завершено' + changed);
                    window.location.href = '/sklad';
                } catch (e) {
                    console.error(e);
                    alert('Мережа/сервер недоступні або таймаут з’єднання.');
                } finally {
                    theendBtn.disabled = false;
                    theendBtn.textContent = originalText;
                }
            }

            // ======================== Навигация ========================

            document.querySelectorAll('.select-doc').forEach(el => {
                el.addEventListener('click', () => showPositions(el.dataset.docIndex));
            });

            backBtn.addEventListener('click', showDocuments);

            // ======================== Сканер ========================

            let timer;
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const raw = input.value.trim();
                    const cleaned = raw.replace(/[\r\n\t]+/g, '');
                    const q = norm(cleaned);
                    const looksLikeBarcode = /^\d{6,}$/.test(cleaned);

                    if (looksLikeBarcode && q) {
                        // 1) Быстрый путь по индексу: любой ШК ведёт к одной строке
                        const row = barcodeIndex.get(q);
                        if (row && rowMap.has(row)) {
                            const li = rowMap.get(row);
                            const oldQty = Number(li.dataset.qty || 0);
                            li.dataset.qty = String(oldQty + 1);

                            // флаг и счётчик сканов
                            li.dataset.scanned = '1';
                            li.dataset.scanDelta = String(Number(li.dataset.scanDelta || '0') + 1);

                            renderLi(li);
                            li.classList.add('hl-barcode');
                            li.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            input.value = '';
                            input.focus();
                            return;
                        }

                        // 2) Фолбэк — прямой поиск по базовому data-barcode первого LI
                        const exact = Array.from(document.querySelectorAll('#positionsUl li'))
                            .find(li => (li.dataset.barcode || '') === q);

                        if (exact) {
                            const oldQty = Number(exact.dataset.qty || 0);
                            exact.dataset.qty = String(oldQty + 1);

                            exact.dataset.scanned = '1';
                            exact.dataset.scanDelta = String(Number(exact.dataset.scanDelta || '0') + 1);

                            renderLi(exact);
                            exact.classList.add('hl-barcode');
                            exact.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            input.value = '';
                            input.focus();
                        } else {
                            console.warn('Штрихкод не найден в позициях:', cleaned);
                        }
                    }
                }, 180);
            });

            // «Принять»
            theendBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                sendFinishAcceptance();
            });
        });
    </script>


@endpush

