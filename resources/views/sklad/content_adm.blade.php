
&nbsp;
<div class="content" style="min-height:100%">
    <section class="content">
        <!-- Главное меню -->
        <div class="container-fluid" id="operationBlock">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex" style="gap: 5px;">
                    <button type="button" class="btn btn-warning btn-sm">Оновити</button>
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
                    <button type="button" class="btn btn-warning btn-sm">Оновити</button>
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
                <button class="btn btn-dark btn-switch" data-target="operationBlock">← Назад</button>
            </div>
        </div>
        <div class="container-fluid d-none" id="receiveoperation">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex" style="gap: 5px;">
                    <button type="button" class="btn btn-warning btn-sm">Оновити</button>
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
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-switch').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = btn.getAttribute('data-target');

                // Скрыть все контейнеры
                document.querySelectorAll('.container-fluid').forEach(function (el) {
                    el.classList.add('d-none');
                });

                // Показать нужный
                document.getElementById(targetId).classList.remove('d-none');
            });
        });
    });
</script>

<script>
    document.getElementById('refreshButton').addEventListener('click', function (e) {
        e.preventDefault(); // Отменяем переход
        const icon = document.getElementById('refreshIcon');
        icon.classList.add('rotate'); // Запускаем вращение

        fetch(window.location.pathname + '?refresh=1')
            .then(response => {
                if (response.ok) {
                    window.location.href = window.location.pathname; // Убираем ?refresh=1 из URL
                } else {
                    console.error('Ошибка при обновлении данных');
                    icon.classList.remove('rotate'); // Остановить вращение при ошибке
                }
            })
            .catch(error => {
                console.error('Ошибка запроса:', error);
                icon.classList.remove('rotate'); // Остановить вращение при ошибке
            });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnPick = document.getElementById('btnPick');
        if (!btnPick) return;

        const ORIGINAL_TEXT = btnPick.textContent;

        btnPick.addEventListener('click', async () => {
            btnPick.disabled = true;
            btnPick.textContent = 'Загрузка…';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const resp  = await fetch("{{ route('sklad.orders.pick.fetch') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',   // важно: передаём куки сессии
                    body: JSON.stringify({}),
                });

                const raw = await resp.text();  // сначала сырой текст, чтобы увидеть ошибки 419/500/HTML
                console.log('pick.fetch STATUS:', resp.status, resp.statusText);
                console.log('pick.fetch RAW   :', raw);

                // если не OK — покажем тело ответа как есть
                if (!resp.ok) {
                    alert(`Ошибка запроса: HTTP ${resp.status}\n` + (raw?.slice(0, 500) || ''));
                    return;
                }

                // пробуем распарсить JSON
                let data;
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch (e) {
                    console.error('JSON parse error:', e);
                    alert('Некорректный ответ сервера (не JSON).');
                    return;
                }

                if (data.ok && data.redirect) {
                    // идём на страницу отбора
                    window.location.href = data.redirect;
                } else {
                    alert((data && (data.msg || data.body)) || 'Неизвестная ошибка.');
                }

            } catch (err) {
                console.error('Fetch error:', err);
                alert('Сбой сети/сервера. Проверьте консоль и Network.');
            } finally {
                // вернём кнопку, если не было редиректа
                btnPick.disabled = false;
                btnPick.textContent = ORIGINAL_TEXT;
            }
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnAccept = document.getElementById('btnAccept');
        if (!btnAccept) return;

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
                try {
                    data = raw ? JSON.parse(raw) : {};
                } catch (e) {
                    console.error('JSON parse error:', e);
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
    });
</script>


