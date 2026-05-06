@extends('layouts.app')
@section('content')
    @include('sklad.header_adm')

    <style>
        body {
            background: #f5f6f7;
        }

        header,
        footer,
        #search_container {
            display: none !important;
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
            border-bottom: 1px solid #eceff3;
            gap: 12px;
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
        }

        .sklad-header-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #171717;
            color: #f3c400 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            text-decoration: none !important;
            font-size: 24px;
            font-weight: 900;
        }

        .sklad-body {
            padding: 18px;
        }

        .btn-arrow {
            width: 44px;
            height: 44px;
            font-size: 22px;
            border-radius: 12px;
            border: none;
            background: #171717 !important;
            color: #f3c400 !important;
            font-weight: 900;
        }

        #documentsList {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        #documentsList .doc-line {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #ffffff !important;
            border: 1px solid #e7e9ee !important;
            border-radius: 16px !important;
            padding: 16px 44px 16px 16px !important;
            margin: 0 !important;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.07);
            cursor: pointer;
            position: relative;
            color: #171717;
            transition: 0.18s ease;
            overflow: hidden;
        }

        #documentsList .doc-line:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(0, 0, 0, 0.10);
            border-color: rgba(243, 196, 0, 0.75) !important;
        }

        #documentsList .doc-line::after {
            content: "›";
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #dc3545;
            font-size: 30px;
            font-weight: 900;
        }

        .doc-title {
            font-weight: 800;
            line-height: 1.35;
            word-break: break-word;
            color: #171717;
            font-size: 14px;
        }

        .doc-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 24px;
            border-radius: 999px;
            background: rgba(243, 196, 0, 0.15);
            color: #171717;
            font-weight: 900;
            font-size: 11px;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .doc-meta {
            color: #777777;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .empty-docs {
            border: 1px solid rgba(243, 196, 0, 0.35) !important;
            border-radius: 14px !important;
            background: rgba(243, 196, 0, 0.13) !important;
            color: #171717 !important;
            font-weight: 800;
            font-size: 13px;
            padding: 12px 14px !important;
        }

        #barcodeInput {
            height: 52px;
            border-radius: 11px;
            border: 1px solid #d9dde2;
            box-shadow: none;
            font-size: 16px;
            font-weight: 700;
        }

        #barcodeInput:focus {
            border-color: #f3c400;
            box-shadow: 0 0 0 0.2rem rgba(243, 196, 0, 0.18);
        }

        #positionsUl .list-group-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: #ffffff !important;
            border: 1px solid #e7e9ee !important;
            border-radius: 16px !important;
            padding: 16px !important;
            margin-bottom: 12px;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.07);
        }

        .pos-title {
            font-weight: 800;
            line-height: 1.25;
            word-break: break-word;
            color: #171717;
            font-size: 14px;
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
            padding: 5px 10px;
            border: none;
            border-radius: 999px;
            background: rgba(243, 196, 0, 0.15);
            color: #171717;
            font-weight: 900;
            font-size: 12px;
            line-height: 1;
            white-space: nowrap;
        }

        .qty-chip.fact {
            background: #171717;
            color: #f3c400;
        }

        .hl-barcode,
        #positionsUl.list-group-flush .list-group-item.hl-barcode {
            background: rgba(243, 196, 0, 0.12) !important;
            border-color: #f3c400 !important;
            font-weight: 800;
        }

        #theend {
            width: 100%;
            height: 48px;
            border: none !important;
            border-radius: 11px !important;
            background: #dc3545 !important;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 800;
        }

        .btn-dark {
            width: 100%;
            height: 44px;
            border: none !important;
            border-radius: 11px !important;
            background: #eef0f3 !important;
            color: #171717 !important;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-status-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .send-status-overlay.show {
            display: flex;
        }

        .send-status-box {
            background: #fff;
            border-radius: 16px;
            border-top: 5px solid #f3c400;
            padding: 24px 20px;
            max-width: 380px;
            width: 100%;
            text-align: center;
            box-shadow: 0 12px 32px rgba(0,0,0,.25);
        }

        .send-status-icon {
            width: 62px;
            height: 62px;
            border-radius: 16px;
            background: #171717;
            color: #f3c400;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 34px;
            font-weight: 900;
        }

        .send-status-icon.success {
            background: #198754;
            color: #ffffff;
        }

        .send-status-title {
            font-size: 20px;
            font-weight: 900;
            color: #171717;
            margin-bottom: 6px;
        }

        .send-status-text {
            font-size: 14px;
            color: #777777;
            font-weight: 700;
        }

        .send-status-spinner {
            width: 34px;
            height: 34px;
            border: 4px solid rgba(243, 196, 0, 0.25);
            border-top-color: #f3c400;
            border-radius: 50%;
            animation: sendSpin .8s linear infinite;
        }

        @keyframes sendSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            .sklad-page {
                padding: 12px 10px 24px;
            }

            .sklad-body {
                padding: 14px;
            }

            .sklad-header-title {
                font-size: 20px;
            }

            #documentsList .doc-line,
            .doc-title,
            .pos-title {
                font-size: 13px;
            }
        }
    </style>

    <div class="content sklad-page" style="min-height:100%">
        <section class="content">
            <div class="sklad-shell">
                <div class="sklad-top-card">

                    <div class="sklad-header">
                        <div>
                            <h1 class="sklad-header-title" id="pageTitle">Документы приёмки</h1>
                            <div class="sklad-header-subtitle">Приёмка · складские операции</div>
                        </div>

                        <a href="{{ route('sklad.index') }}"
                           class="sklad-header-icon"
                           title="Главная">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house-down" viewBox="0 0 16 16">
                                <path d="M7.293 1.5a1 1 0 0 1 1.414 0L11 3.793V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v3.293l2.354 2.353a.5.5 0 0 1-.708.708L8 2.207l-5 5V13.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 1 0 1h-4A1.5 1.5 0 0 1 2 13.5V8.207l-.646.647a.5.5 0 1 1-.708-.708z"/>
                                <path d="M12.5 9a3.5 3.5 0 1 1 0 7 3.5 3.5 0 0 1 0-7m.354 5.854 1.5-1.5a.5.5 0 0 0-.708-.707l-.646.646V10.5a.5.5 0 0 0-1 0v2.793l-.646-.646a.5.5 0 0 0-.708.707l1.5 1.5a.5.5 0 0 0 .708 0"/>
                            </svg>
                        </a>
                    </div>

                    <div class="sklad-body">

                        <button id="btnBack"
                                type="button"
                                class="btn-arrow d-none mb-3"
                                aria-label="Назад">←</button>

                        <div id="barcodeWrapper" class="mb-3 d-none">
                            <input id="barcodeInput"
                                   type="text"
                                   class="form-control form-control-lg"
                                   placeholder="Сканируйте номенклатуру или штрихкод..."
                                   autocomplete="off">
                        </div>

                        <div id="documentsList">
                            @forelse(session('accept_orders', []) as $i => $doc)
                                <div class="doc-line select-doc" data-doc-index="{{ $i }}">
                                    <div class="doc-title">
                                        <span class="doc-badge">DOC</span>
                                        {{ $doc['Ссылка'] ?? 'Без названия' }} — Статус: {{ $doc['Статус'] ?? '-' }}
                                    </div>

                                    <div class="doc-meta">
                                        Документ приёмки
                                    </div>
                                </div>
                            @empty
                                <div class="empty-docs">Нет документов приёмки.</div>
                            @endforelse
                        </div>

                        <div id="positionsList" class="d-none">
                            <ul class="list-group list-group-flush" id="positionsUl"></ul>
                        </div>

                        <div class="mt-3">
                            <button id="theend"
                                    type="button"
                                    class="btn btn-primary btn-lg w-100 d-none">
                                Отправить
                            </button>
                        </div>

                        <div class="mt-3">
                            <a href="{{ route('sklad.index') }}" class="btn btn-dark">Главная</a>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </div>

    <div id="sendStatusOverlay" class="send-status-overlay">
        <div class="send-status-box">
            <div id="sendStatusIcon" class="send-status-icon">
                <div class="send-status-spinner"></div>
            </div>

            <div id="sendStatusTitle" class="send-status-title">
                Данные отправляются...
            </div>

            <div id="sendStatusText" class="send-status-text">
                Подождите, идёт передача данных в 1С
            </div>
        </div>
    </div>
@endsection

@push('scripts')
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

            const sendStatusOverlay = document.getElementById('sendStatusOverlay');
            const sendStatusIcon    = document.getElementById('sendStatusIcon');
            const sendStatusTitle   = document.getElementById('sendStatusTitle');
            const sendStatusText    = document.getElementById('sendStatusText');

            function showSendingModal() {
                if (!sendStatusOverlay) return;

                sendStatusIcon.className = 'send-status-icon';
                sendStatusIcon.innerHTML = '<div class="send-status-spinner"></div>';

                sendStatusTitle.textContent = 'Данные отправляются...';
                sendStatusText.textContent = 'Подождите, идёт передача данных в 1С';

                sendStatusOverlay.classList.add('show');
            }

            function showSentModal(message = 'Данные успешно отправлены') {
                if (!sendStatusOverlay) return;

                sendStatusIcon.className = 'send-status-icon success';
                sendStatusIcon.innerHTML = '✓';

                sendStatusTitle.textContent = 'Отправлено';
                sendStatusText.textContent = message;
            }

            const rowMap       = new Map(); // rownum -> <li>
            const barcodeIndex = new Map(); // barcode -> rownum

            const norm = s => (String(s || '')).trim().toLowerCase();

            function renderLi(li) {
                const rownum = li.dataset.rownum || '';
                const nom    = li.dataset.nomOriginal || '-';
                const qty    = Number(li.dataset.qty || 0);

                li.innerHTML = `
                    <div class="pos-title">#${rownum} — ${nom}</div>
                    <div class="pos-qty">
                        <span class="qty-chip">Кол: ${qty}</span>
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

            function buildPositionsScannedOnly() {
                const lis = Array.from(document.querySelectorAll('#positionsUl li'));
                const sumByRow = new Map();

                lis.forEach(li => {
                    const row = Number(li.dataset.rownum || 0);
                    if (!row) return;

                    if (li.dataset.scanned !== '1') return;

                    let qty = parseFloat(li.dataset.qty);
                    if (isNaN(qty)) qty = 0;
                    qty = Math.max(0, Math.floor(qty));

                    sumByRow.set(row, (sumByRow.get(row) || 0) + qty);
                });

                return Array.from(sumByRow.entries())
                    .map(([НомерСтроки, НовоеКоличество]) => ({ НомерСтроки, НовоеКоличество }))
                    .sort((a, b) => a.НомерСтроки - b.НомерСтроки);
            }

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

                rows.forEach((line, idx) => {
                    const rownum  = Number(line.НомерСтроки ?? (idx + 1));
                    const barcode = (String(line.Штрихкод || '')).trim().toLowerCase();
                    const nom     = (line.Номенклатура ?? '-');
                    const chr     = (line.Характеристика ?? '');
                    const packs   = (line.КоличествоУпаковок ?? 0);
                    const qty0    = Number(line.Количество ?? 0) || 0;

                    if (!rownum) return;

                    if (barcode) barcodeIndex.set(barcode, rownum);

                    if (!rowMap.has(rownum)) {
                        const li = document.createElement('li');
                        li.className = 'list-group-item';

                        li.dataset.nom     = nom.trim().toLowerCase();
                        li.dataset.barcode = barcode;
                        li.dataset.char    = chr.trim().toLowerCase();

                        li.dataset.nomOriginal     = nom;
                        li.dataset.barcodeOriginal = barcode;
                        li.dataset.charOriginal    = chr;
                        li.dataset.rownum          = String(rownum);

                        li.dataset.qtyOriginal = String(qty0);
                        li.dataset.qty         = String(qty0);
                        li.dataset.packs       = String(packs);

                        li.dataset.scanned   = '0';
                        li.dataset.scanDelta = '0';

                        renderLi(li);
                        posUl.appendChild(li);
                        rowMap.set(rownum, li);
                    } else {
                        const li = rowMap.get(rownum);
                        const curOrig = Number(li.dataset.qtyOriginal || 0);
                        const curNow  = Number(li.dataset.qty || 0);

                        li.dataset.qtyOriginal = String(curOrig + qty0);
                        li.dataset.qty         = String(curNow  + qty0);

                        if (barcode) {
                            barcodeIndex.set(barcode, rownum);
                            if (!li.dataset._extraBarcodes?.includes(barcode)) {
                                li.dataset._extraBarcodes =
                                    (li.dataset._extraBarcodes ? (li.dataset._extraBarcodes + ',') : '') + barcode;
                            }
                        }

                        renderLi(li);
                    }
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

            async function sendFinishAcceptance() {
                const Номер = (document.body.dataset.docNumber || '').trim();
                if (!Номер) {
                    alert('Не удалось определить номер документа.');
                    return;
                }

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
                theendBtn.textContent = 'Отправляем...';

                showSendingModal();

                try {
                    const resp = await fetch("{{ route('sklad.acceptance.finish') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload)
                    });

                    const raw = await resp.text();
                    let data;
                    try { data = JSON.parse(raw); } catch { data = { raw }; }

                    if (!resp.ok || data?.ok === false) {
                        sendStatusOverlay?.classList.remove('show');
                        console.error('FinishAcceptance error', { status: resp.status, data });
                        const msg = data?.error || data?.message || `HTTP ${resp.status}`;
                        alert('Помилка при завершенні приймання: ' + msg);
                        return;
                    }

                    const changed = (data && data.ИзмененоСтрок != null)
                        ? `Змінено рядків: ${data.ИзмененоСтрок}`
                        : 'Приймання завершено';

                    showSentModal(changed);

                    setTimeout(() => {
                        window.location.href = '/sklad';
                    }, 1200);
                } catch (e) {
                    sendStatusOverlay?.classList.remove('show');
                    console.error(e);
                    alert('Мережа/сервер недоступні або таймаут з’єднання.');
                } finally {
                    theendBtn.disabled = false;
                    theendBtn.textContent = originalText;
                }
            }

            document.querySelectorAll('.select-doc').forEach(el => {
                el.addEventListener('click', () => showPositions(el.dataset.docIndex));
            });

            backBtn.addEventListener('click', showDocuments);

            let timer;
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const raw = input.value.trim();
                    const cleaned = raw.replace(/[\r\n\t]+/g, '');
                    const q = norm(cleaned);
                    const looksLikeBarcode = /^\d{6,}$/.test(cleaned);

                    if (looksLikeBarcode && q) {
                        const row = barcodeIndex.get(q);
                        if (row && rowMap.has(row)) {
                            const li = rowMap.get(row);
                            const oldQty = Number(li.dataset.qty || 0);
                            li.dataset.qty = String(oldQty + 1);

                            li.dataset.scanned = '1';
                            li.dataset.scanDelta = String(Number(li.dataset.scanDelta || '0') + 1);

                            renderLi(li);
                            li.classList.add('hl-barcode');
                            li.scrollIntoView({ block: 'center', behavior: 'smooth' });
                            input.value = '';
                            input.focus();
                            return;
                        }

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

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();

                    const raw = input.value.trim();
                    if (!raw) return;

                    input.dispatchEvent(new Event('input'));
                }
            });

            theendBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                sendFinishAcceptance();
            });
        });
    </script>
@endpush
