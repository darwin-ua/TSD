
&nbsp;
<div class="content" style="min-height:100%">
    <section class="content">
        <!-- Главное меню -->
        <div class="container-fluid" id="operationBlock">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex" style="gap: 5px;">
                    <button type="button" class="btn btn-warning btn-sm" onclick="location.reload()">Оновити</button>
                    <button type="button" class="btn btn-danger btn-sm">Помилка</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Принять</p>
                            <button type="button" class="btn btn-secondary btn-switch" data-target="receiveoperation">Так</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Отгрузить</p>
                            <button type="button" class="btn btn-secondary btn-switch" data-target="alloperation">Так</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Отгрузка -->
        <div class="container-fluid d-none" id="alloperation">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex" style="gap: 5px;">
                    <button type="button" class="btn btn-warning btn-sm" onclick="location.reload()">Оновити</button>
                    <button type="button" class="btn btn-danger btn-sm">Помилка</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Сканируйте штрихкод</p>
                            <input id="quickScanInput" type="text" class="form-control form-control-lg mt-2"
                                   placeholder="Скан..." autofocus autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const quickScanInput = document.getElementById('quickScanInput');
                    if (quickScanInput) {
                        // Автофокус каждые 500 мс
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
                                    // Очистка поля
                                    quickScanInput.value = '';
                                    // Редирект
                                    window.location.href = '/sklad/orders/gp';
                                }
                            }, 300);
                        });
                    }
                });
            </script>

            <div class="row">
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Сканувати</p>
                            <a href="/sklad/orders/gp" class="btn btn-secondary">Так</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-dark btn-switch" data-target="operationBlock">← Назад 4</button>
            </div>
        </div>
        <div class="container-fluid d-none" id="receiveoperation">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex" style="gap: 5px;">
                    <button type="button" class="btn btn-warning btn-sm" onclick="location.reload()">Оновити</button>
                    <button type="button" class="btn btn-danger btn-sm">Помилка</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="small-box" style="background-color:#b3b3b3;">
                        <div class="inner" style="color:#ffffff;">
                            <p>Приёмка</p>
                            <button id="btnAccept" type="button" class="btn btn-secondary">Так</button>
                        </div>
                    </div>
                </div>

{{--                <div class="col-12">--}}
{{--                    <div class="small-box" style="background-color: #b3b3b3;">--}}
{{--                        <div class="inner" style="color: #ffffff;">--}}
{{--                            <p>Размещение</p>--}}
{{--                            <a href="" class="btn btn-secondary">Так</a>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Размещение</p>
                            <button id="btnPick" type="button" class="btn btn-secondary">Так</button>
                        </div>
                    </div>
                </div>

                {{-- === ЭТО ДОБАВЛЯЕМ: окно сканирования === --}}
                {{-- ОДИН блок сканирования (инпут + кнопка "Так") --}}
                <div class="row d-none" id="placementScan">
                    <div class="col-12">
                        <div class="small-box" style="background-color:#b3b3b3;">
                            <div class="inner" style="color:#ffffff;">
                                <p>Сканируйте штрихкод</p>
                                <input id="placementBarcode" type="text"
                                       class="form-control form-control-lg mt-2"
                                       placeholder="Скан..." autocomplete="off">
                                <button id="placementScanSubmit" class="btn btn-secondary mt-2">Так</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- === конец вставки === --}}


                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Пересчёт</p>
                            <a href="" class="btn btn-secondary">Так</a>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Создать</p>
                            <a href="" class="btn btn-secondary">Так</a>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Проверка</p>
                            <a href="" class="btn btn-secondary">Так</a>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Перемещение</p>
                            <a href="" class="btn btn-secondary">Так</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-dark btn-switch" data-target="operationBlock">← Назад</button>
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

                    if (data.ok && data.redirect) {
                        window.location.href = data.redirect; // стандартный флоу: открываем документы
                        return;
                    }

                    // если бекенд ответил без redirect — покажем, что пришло
                    alert('Документы: ' + JSON.stringify(data.docs || []));

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

{{--<script>--}}
{{--    document.addEventListener('DOMContentLoaded', () => {--}}
{{--        /* ===== Переключатель экранов (Главное меню ↔ подпункты) ===== */--}}
{{--        document.querySelectorAll('.btn-switch').forEach((btn) => {--}}
{{--            btn.addEventListener('click', () => {--}}
{{--                const targetId = btn.getAttribute('data-target');--}}
{{--                // Скрыть все контейнеры-экраны--}}
{{--                document.querySelectorAll('.container-fluid').forEach(el => el.classList.add('d-none'));--}}
{{--                // Показать нужный--}}
{{--                const target = document.getElementById(targetId);--}}
{{--                if (target) target.classList.remove('d-none');--}}
{{--            });--}}
{{--        });--}}

{{--        /* ===== Кнопка "Обновить" (если есть в DOM) ===== */--}}
{{--        const refreshBtn = document.getElementById('refreshButton');--}}
{{--        if (refreshBtn) {--}}
{{--            refreshBtn.addEventListener('click', (e) => {--}}
{{--                e.preventDefault();--}}
{{--                const icon = document.getElementById('refreshIcon');--}}
{{--                if (icon) icon.classList.add('rotate');--}}

{{--                fetch(window.location.pathname + '?refresh=1')--}}
{{--                    .then((resp) => {--}}
{{--                        if (resp.ok) {--}}
{{--                            window.location.href = window.location.pathname;--}}
{{--                        } else {--}}
{{--                            console.error('Ошибка при обновлении данных');--}}
{{--                            if (icon) icon.classList.remove('rotate');--}}
{{--                        }--}}
{{--                    })--}}
{{--                    .catch((err) => {--}}
{{--                        console.error('Ошибка запроса:', err);--}}
{{--                        if (icon) icon.classList.remove('rotate');--}}
{{--                    });--}}
{{--            });--}}
{{--        }--}}

{{--        /* ===== Приёмка (btnAccept) ===== */--}}
{{--        const btnAccept = document.getElementById('btnAccept');--}}
{{--        if (btnAccept) {--}}
{{--            const ORIGINAL = btnAccept.textContent;--}}
{{--            btnAccept.addEventListener('click', async () => {--}}
{{--                btnAccept.disabled = true;--}}
{{--                btnAccept.textContent = 'Загрузка…';--}}

{{--                try {--}}
{{--                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';--}}
{{--                    const resp = await fetch("{{ route('sklad.orders.accept.fetch') }}", {--}}
{{--                        method: 'POST',--}}
{{--                        headers: {--}}
{{--                            'X-CSRF-TOKEN': csrf,--}}
{{--                            'Accept': 'application/json',--}}
{{--                            'Content-Type': 'application/json',--}}
{{--                            'X-Requested-With': 'XMLHttpRequest',--}}
{{--                        },--}}
{{--                        credentials: 'same-origin',--}}
{{--                        body: JSON.stringify({}),--}}
{{--                    });--}}

{{--                    const raw = await resp.text();--}}
{{--                    console.log('accept.fetch STATUS:', resp.status, resp.statusText);--}}
{{--                    console.log('accept.fetch RAW   :', raw);--}}

{{--                    if (!resp.ok) {--}}
{{--                        alert(`Ошибка запроса: HTTP ${resp.status}\n` + (raw?.slice(0, 500) || ''));--}}
{{--                        return;--}}
{{--                    }--}}

{{--                    let data;--}}
{{--                    try { data = raw ? JSON.parse(raw) : {}; }--}}
{{--                    catch {--}}
{{--                        alert('Некорректный ответ сервера (не JSON).');--}}
{{--                        return;--}}
{{--                    }--}}

{{--                    if (data.ok && data.redirect) {--}}
{{--                        window.location.href = data.redirect;--}}
{{--                    } else {--}}
{{--                        alert((data && (data.msg || data.body)) || 'Неизвестная ошибка.');--}}
{{--                    }--}}
{{--                } catch (err) {--}}
{{--                    console.error('Fetch error:', err);--}}
{{--                    alert('Сбой сети/сервера. Проверьте консоль и Network.');--}}
{{--                } finally {--}}
{{--                    btnAccept.disabled = false;--}}
{{--                    btnAccept.textContent = ORIGINAL;--}}
{{--                }--}}
{{--            });--}}
{{--        }--}}

{{--        /* ===== Размещение → Сканируйте штрихкод ===== */--}}
{{--        const btnPick      = document.getElementById('btnPick');            // кнопка "Размещение → Так"--}}
{{--        const receiveBlock = document.getElementById('receiveoperation');   // экран "Принять"--}}
{{--        const scanRow      = document.getElementById('placementScan');      // наш ряд с инпутом--}}
{{--        const barcodeEl    = document.getElementById('placementBarcode');   // input--}}
{{--        const submitBtn    = document.getElementById('placementScanSubmit');// внутренняя кнопка "Так"--}}

{{--        // Показать ТОЛЬКО блок "Сканируйте штрихкод"--}}
{{--        if (btnPick && receiveBlock && scanRow) {--}}
{{--            btnPick.addEventListener('click', (e) => {--}}
{{--                e.preventDefault();--}}

{{--                // Скрыть только верхнеуровневые карточки (соседи), не трогая вложенные--}}
{{--                const topCols = receiveBlock.querySelectorAll(':scope > .row > .col-12');--}}
{{--                topCols.forEach(col => col.classList.add('d-none'));--}}

{{--                // Показать наш блок-строку и его содержимое--}}
{{--                scanRow.classList.remove('d-none');--}}
{{--                scanRow.querySelectorAll('.col-12').forEach(col => col.classList.remove('d-none'));--}}

{{--                // Фокус в поле--}}
{{--                setTimeout(() => barcodeEl?.focus(), 0);--}}
{{--            });--}}
{{--        }--}}

{{--        // Enter в поле = нажать "Так"--}}
{{--        if (barcodeEl && submitBtn) {--}}
{{--            barcodeEl.addEventListener('keydown', (e) => {--}}
{{--                if (e.key === 'Enter') {--}}
{{--                    e.preventDefault();--}}
{{--                    submitBtn.click();--}}
{{--                }--}}
{{--            });--}}
{{--        }--}}

{{--        // Отправка скана--}}
{{--        if (submitBtn && barcodeEl) {--}}
{{--            submitBtn.addEventListener('click', async () => {--}}
{{--                const code = (barcodeEl.value || '').trim();--}}
{{--                if (!code) {--}}
{{--                    barcodeEl.classList.add('is-invalid');--}}
{{--                    setTimeout(() => barcodeEl.classList.remove('is-invalid'), 800);--}}
{{--                    barcodeEl.focus();--}}
{{--                    return;--}}
{{--                }--}}

{{--                try {--}}
{{--                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';--}}

{{--                    /* 1) (если настроено) — логируем скан в таблицу scan_code */--}}
{{--                    try {--}}
{{--                        await fetch("{{ route('sklad.scan.store') }}", {--}}
{{--                            method: 'POST',--}}
{{--                            headers: {--}}
{{--                                'X-CSRF-TOKEN': csrf,--}}
{{--                                'Accept': 'application/json',--}}
{{--                                'Content-Type': 'application/json',--}}
{{--                            },--}}
{{--                            credentials: 'same-origin',--}}
{{--                            body: JSON.stringify({--}}
{{--                                code: code,           // штрихкод--}}
{{--                                // Можно дополнить: document_id, warehouse_id, cell, amount, status, order_date--}}
{{--                            }),--}}
{{--                        });--}}
{{--                    } catch (e) {--}}
{{--                        // если маршрут ещё не создан — просто молча продолжаем--}}
{{--                        console.warn('scan.store недоступен (пока). Продолжаем…');--}}
{{--                    }--}}

{{--                    /* 2) основной шаг — поиск/переход к документам */--}}
{{--                    const resp = await fetch("{{ route('sklad.orders.pick.fetch') }}", {--}}
{{--                        method: 'POST',--}}
{{--                        headers: {--}}
{{--                            'X-CSRF-TOKEN': csrf,--}}
{{--                            'Accept': 'application/json',--}}
{{--                            'Content-Type': 'application/json',--}}
{{--                            'X-Requested-With': 'XMLHttpRequest',--}}
{{--                        },--}}
{{--                        credentials: 'same-origin',--}}
{{--                        body: JSON.stringify({ barcode: code }),--}}
{{--                    });--}}

{{--                    const raw = await resp.text();--}}
{{--                    let data = {};--}}
{{--                    try { data = raw ? JSON.parse(raw) : {}; } catch {}--}}

{{--                    if (data.ok && data.redirect) {--}}
{{--                        window.location.href = data.redirect; // стандартный флоу: открываем документы--}}
{{--                        return;--}}
{{--                    }--}}

{{--                    // если бекенд ответил без redirect — покажем, что пришло--}}
{{--                    alert('Документы: ' + JSON.stringify(data.docs || []));--}}

{{--                } catch (err) {--}}
{{--                    console.error(err);--}}
{{--                    alert('Помилка мережі/сервера');--}}
{{--                } finally {--}}
{{--                    barcodeEl.value = '';--}}
{{--                    barcodeEl.focus();--}}
{{--                }--}}
{{--            });--}}
{{--        }--}}
{{--    });--}}
{{--</script>--}}





