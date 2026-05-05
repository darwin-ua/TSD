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
        border-bottom: 1px solid #eceff3;
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
        color: #f3c400;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sklad-header-icon svg {
        width: 26px;
        height: 26px;
    }

    .sklad-body {
        padding: 18px;
    }

    .sklad-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }

    .sklad-btn-refresh {
        border: none;
        border-radius: 10px;
        background: #f3c400;
        color: #171717;
        font-weight: 700;
        padding: 9px 13px;
        font-size: 14px;
    }

    .sklad-btn-error {
        border: none;
        border-radius: 10px;
        background: #dc3545;
        color: #ffffff;
        font-weight: 700;
        padding: 9px 13px;
        font-size: 14px;
    }

    .sklad-menu-card {
        background: #ffffff;
        border: 1px solid #e7e9ee;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 14px;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.07);
        transition: 0.18s ease;
    }

    .sklad-menu-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 26px rgba(0, 0, 0, 0.10);
        border-color: rgba(243, 196, 0, 0.75);
    }

    .sklad-menu-title {
        margin: 0 0 12px;
        font-size: 17px;
        font-weight: 800;
        color: #171717;
    }

    .sklad-menu-text {
        margin: 0 0 12px;
        font-size: 13px;
        color: #777777;
    }

    .sklad-main-btn {
        width: 100%;
        height: 48px;
        border: none;
        border-radius: 11px;
        background: #171717;
        color: #ffffff;
        font-size: 15px;
        font-weight: 800;
        transition: 0.18s ease;
    }

    .sklad-main-btn:hover {
        background: #f3c400;
        color: #171717;
    }

    .sklad-back-btn {
        width: 100%;
        height: 44px;
        border: none;
        border-radius: 11px;
        background: #eef0f3;
        color: #171717;
        font-weight: 800;
        margin-top: 4px;
    }

    .sklad-scan-input {
        height: 52px;
        border-radius: 11px;
        border: 1px solid #d9dde2;
        box-shadow: none;
        font-size: 16px;
    }

    .sklad-scan-input:focus {
        border-color: #f3c400;
        box-shadow: 0 0 0 0.2rem rgba(243, 196, 0, 0.18);
    }

    .sklad-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 24px;
        border-radius: 999px;
        background: rgba(243, 196, 0, 0.15);
        color: #171717;
        font-weight: 800;
        font-size: 12px;
        margin-bottom: 10px;
    }
</style>

<div class="content sklad-page" style="min-height:100%">
    <section class="content">
        <div class="sklad-shell">
            <div class="sklad-top-card">

                <div class="sklad-header">
                    <div>
                        <h1 class="sklad-header-title">Склад</h1>
                        <div class="sklad-header-subtitle">Дарвін · складські операції</div>
                    </div>

                    <a href="{{ route('logout') }}"
                       class="sklad-header-icon"
                       title="Вийти"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             fill="currentColor"
                             class="bi bi-box-arrow-right"
                             viewBox="0 0 16 16">
                            <path fill-rule="evenodd"
                                  d="M10 12.5a.5.5 0 0 1-.5.5h-8A1.5 1.5 0 0 1 0 11.5v-7A1.5 1.5 0 0 1 1.5 3h8a.5.5 0 0 1 0 1h-8A.5.5 0 0 0 1 4.5v7a.5.5 0 0 0 .5.5h8a.5.5 0 0 1 .5.5"/>

                            <path fill-rule="evenodd"
                                  d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
                        </svg>
                    </a>

                    <form id="logout-form"
                          action="{{ route('logout') }}"
                          method="POST"
                          style="display: none;">
                        @csrf
                    </form>
                </div>

                <div class="sklad-body">

                    <!-- Главное меню -->
                    <div class="container-fluid px-0" id="operationBlock">
                        <div class="sklad-actions">
                            <button type="button" class="sklad-btn-refresh" onclick="location.reload()">Оновити</button>
                            <button type="button" class="sklad-btn-error">Помилка</button>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">01</div>
                                    <p class="sklad-menu-title">Прийняти</p>
                                    <p class="sklad-menu-text">Операції приймання та розміщення товару</p>
                                    <button type="button" class="sklad-main-btn btn-switch" data-target="receiveoperation">
                                        Перейти
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">02</div>
                                    <p class="sklad-menu-title">Відвантажити</p>
                                    <p class="sklad-menu-text">Сканування і відвантаження готової продукції</p>
                                    <button type="button" class="sklad-main-btn btn-switch" data-target="alloperation">
                                        Перейти
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Отгрузка -->
                    <div class="container-fluid px-0 d-none" id="alloperation">
                        <div class="sklad-actions">
                            <button type="button" class="sklad-btn-refresh" onclick="location.reload()">Оновити</button>
                            <button type="button" class="sklad-btn-error">Помилка</button>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">SCAN</div>
                                    <p class="sklad-menu-title">Сканируйте штрихкод</p>
                                    <p class="sklad-menu-text">После сканирования система перейдёт дальше автоматически</p>

                                    <input id="quickScanInput"
                                           type="text"
                                           class="form-control form-control-lg sklad-scan-input mt-2"
                                           placeholder="Скан..."
                                           autofocus
                                           autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const quickScanInput = document.getElementById('quickScanInput');
                                if (quickScanInput) {
                                    setInterval(() => {
                                        if (document.activeElement !== quickScanInput) {
                                            quickScanInput.focus();
                                        }
                                    }, 500);

                                    let quickScanTimeout;
                                    quickScanInput.addEventListener('input', function () {
                                        clearTimeout(quickScanTimeout);

                                        quickScanTimeout = setTimeout(() => {
                                            const value = quickScanInput.value.trim();
                                            if (value !== '') {
                                                console.log('📦 Быстрый скан:', value);
                                                quickScanInput.value = '';
                                                window.location.href = '/sklad/orders/gp';
                                            }
                                        }, 300);
                                    });
                                }
                            });
                        </script>

                        <div class="row">
                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <p class="sklad-menu-title">Сканувати</p>
                                    <p class="sklad-menu-text">Перейти к списку документов отгрузки</p>
                                    <a href="/sklad/orders/gp" class="sklad-main-btn d-flex align-items-center justify-content-center">
                                        Перейти
                                    </a>
                                </div>
                            </div>
                        </div>

                        <button class="sklad-back-btn btn-switch" data-target="operationBlock">← Назад</button>
                    </div>

                    <!-- Приёмка -->
                    <div class="container-fluid px-0 d-none" id="receiveoperation">
                        <div class="sklad-actions">
                            <button type="button" class="sklad-btn-refresh" onclick="location.reload()">Оновити</button>
                            <button type="button" class="sklad-btn-error">Помилка</button>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">01</div>
                                    <p class="sklad-menu-title">Приёмка</p>
                                    <p class="sklad-menu-text">Загрузить документы приёмки из 1С</p>
                                    <button id="btnAccept" type="button" class="sklad-main-btn">
                                        Перейти
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">02</div>
                                    <p class="sklad-menu-title">Размещение</p>
                                    <p class="sklad-menu-text">Сканирование ячейки и переход к размещению</p>
                                    <button id="btnPick" type="button" class="sklad-main-btn">
                                        Перейти
                                    </button>
                                </div>
                            </div>

                            <div class="col-12 d-none" id="placementScan">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">SCAN</div>
                                    <p class="sklad-menu-title">Сканируйте штрихкод</p>
                                    <p class="sklad-menu-text">Отсканируйте ячейку или штрихкод для размещения</p>

                                    <input id="placementBarcode"
                                           type="text"
                                           class="form-control form-control-lg sklad-scan-input mt-2"
                                           placeholder="Скан..."
                                           autocomplete="off">

                                    <button id="placementScanSubmit" class="sklad-main-btn mt-3">
                                        Подтвердить
                                    </button>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">03</div>
                                    <p class="sklad-menu-title">Пересчёт</p>
                                    <p class="sklad-menu-text">Инвентаризация и проверка остатков</p>
                                    <a href="" class="sklad-main-btn d-flex align-items-center justify-content-center">
                                        Перейти
                                    </a>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">04</div>
                                    <p class="sklad-menu-title">Создать</p>
                                    <p class="sklad-menu-text">Создание нового складского документа</p>
                                    <a href="" class="sklad-main-btn d-flex align-items-center justify-content-center">
                                        Перейти
                                    </a>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">05</div>
                                    <p class="sklad-menu-title">Проверка</p>
                                    <p class="sklad-menu-text">Проверка складских операций</p>
                                    <a href="" class="sklad-main-btn d-flex align-items-center justify-content-center">
                                        Перейти
                                    </a>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="sklad-menu-card">
                                    <div class="sklad-badge">06</div>
                                    <p class="sklad-menu-title">Перемещение</p>
                                    <p class="sklad-menu-text">Перемещение между ячейками или складами</p>
                                    <a href="" class="sklad-main-btn d-flex align-items-center justify-content-center">
                                        Перейти
                                    </a>
                                </div>
                            </div>
                        </div>

                        <button class="sklad-back-btn btn-switch" data-target="operationBlock">← Назад</button>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        /* ===== Переключатель экранов (Главное меню ↔ подпункты) ===== */
        document.querySelectorAll('.btn-switch').forEach((btn) => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                // Скрыть все контейнеры-экраны
                document.querySelectorAll('.container-fluid').forEach(el => el.classList.add('d-none'));
                // Показать нужный
                const target = document.getElementById(targetId);
                if (target) target.classList.remove('d-none');
            });
        });

        /* ===== Кнопка "Обновить" (если есть в DOM) ===== */
        const refreshBtn = document.getElementById('refreshButton');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const icon = document.getElementById('refreshIcon');
                if (icon) icon.classList.add('rotate');

                fetch(window.location.pathname + '?refresh=1')
                    .then((resp) => {
                        if (resp.ok) {
                            window.location.href = window.location.pathname;
                        } else {
                            console.error('Ошибка при обновлении данных');
                            if (icon) icon.classList.remove('rotate');
                        }
                    })
                    .catch((err) => {
                        console.error('Ошибка запроса:', err);
                        if (icon) icon.classList.remove('rotate');
                    });
            });
        }

        /* ===== Приёмка (btnAccept) ===== */
        const btnAccept = document.getElementById('btnAccept');
        if (btnAccept) {
            const ORIGINAL = btnAccept.textContent;
            btnAccept.addEventListener('click', async () => {
                btnAccept.disabled = true;
                btnAccept.textContent = 'Загрузка…';

                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    const resp = await fetch("{{ route('sklad.orders.accept.fetch') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({}),
                    });

                    const raw = await resp.text();
                    console.log('accept.fetch STATUS:', resp.status, resp.statusText);
                    console.log('accept.fetch RAW   :', raw);

                    if (!resp.ok) {
                        alert(`Ошибка запроса: HTTP ${resp.status}\n` + (raw?.slice(0, 500) || ''));
                        return;
                    }

                    let data;
                    try { data = raw ? JSON.parse(raw) : {}; }
                    catch {
                        alert('Некорректный ответ сервера (не JSON).');
                        return;
                    }

                    if (data.ok && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert((data && (data.msg || data.body)) || 'Неизвестная ошибка.');
                    }
                } catch (err) {
                    console.error('Fetch error:', err);
                    alert('Сбой сети/сервера. Проверьте консоль и Network.');
                } finally {
                    btnAccept.disabled = false;
                    btnAccept.textContent = ORIGINAL;
                }
            });
        }

        /* ===== Размещение → Сканируйте штрихкод ===== */
        const btnPick      = document.getElementById('btnPick');            // кнопка "Размещение → Так"
        const receiveBlock = document.getElementById('receiveoperation');   // экран "Принять"
        const scanRow      = document.getElementById('placementScan');      // наш ряд с инпутом
        const barcodeEl    = document.getElementById('placementBarcode');   // input
        const submitBtn    = document.getElementById('placementScanSubmit');// внутренняя кнопка "Так"

        // маршрут свободного сканирования (редирект если доков нет)
        const FREE_ROUTE = "{{ route('sklad.scan.free') }}";

        // Показать ТОЛЬКО блок "Сканируйте штрихкод"
        if (btnPick && receiveBlock && scanRow) {
            btnPick.addEventListener('click', (e) => {
                e.preventDefault();

                // Скрыть только верхнеуровневые карточки (соседи), не трогая вложенные
                const topCols = receiveBlock.querySelectorAll(':scope > .row > .col-12');
                topCols.forEach(col => col.classList.add('d-none'));

                // Показать наш блок-строку и его содержимое
                scanRow.classList.remove('d-none');
                scanRow.querySelectorAll('.col-12').forEach(col => col.classList.remove('d-none'));

                // Фокус в поле
                setTimeout(() => barcodeEl?.focus(), 0);
            });
        }

        // Enter в поле = нажать "Так"
        if (barcodeEl && submitBtn) {
            barcodeEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitBtn.click();
                }
            });
        }

        // Отправка скана (ячейки)
        if (submitBtn && barcodeEl) {
            submitBtn.addEventListener('click', async () => {
                const code = (barcodeEl.value || '').trim();
                if (!code) {
                    barcodeEl.classList.add('is-invalid');
                    setTimeout(() => barcodeEl.classList.remove('is-invalid'), 800);
                    barcodeEl.focus();
                    return;
                }

                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    /* 0) Сохраняем активную ячейку (сессия + кеш) */
                    try {
                        await fetch("{{ route('sklad.scan.session.cell') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                cell: code,
                                // warehouse_id: 1, // если есть в контексте — подставь
                            }),
                        });
                    } catch (e) {
                        console.warn('scan.session.cell недоступен. Продолжаем…');
                    }

                    /* 1) Логируем скан в scan_code (опционально) */
                    try {
                        await fetch("{{ route('sklad.scan.store') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                code: code,
                                cell: code,
                                // document_id: 0, warehouse_id: 0, amount: 1, status: 1 — при необходимости
                            }),
                        });
                    } catch (e) {
                        console.warn('scan.store недоступен. Продолжаем…');
                    }

                    /* 2) Основной шаг — поиск/переход к документам */
                    const resp = await fetch("{{ route('sklad.orders.pick.fetch') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ barcode: code }),
                    });

                    const raw = await resp.text();
                    let data = {};
                    try { data = raw ? JSON.parse(raw) : {}; } catch {}

                    // === КЛЮЧЕВОЕ МЕСТО ===
                    if (data.ok && data.redirect) {
                        // документы есть → идём в стандартный флоу
                        window.location.href = data.redirect;
                        return;
                    }

                    // нет redirect; проверяем массив docs и редиректим в free при пустом списке
                    const docs = Array.isArray(data.docs) ? data.docs : [];
                    if (docs.length === 0) {
                        // документов НЕТ → свободное сканирование
                        window.location.href = FREE_ROUTE;
                        return;
                    }

                    // fallback: документы есть, но бекенд не прислал redirect — сообщим
                    alert('Документы найдены, но redirect не пришёл. Проверь ответ сервера.');

                } catch (err) {
                    console.error(err);
                    alert('Помилка мережі/сервера');
                } finally {
                    barcodeEl.value = '';
                    barcodeEl.focus();
                }
            });
        }
    });
</script>

