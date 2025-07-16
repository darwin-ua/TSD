
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
                            <p>Отгрузить</p>
                            <button type="button" class="btn btn-secondary btn-switch" data-target="alloperation">Так</button>
                        </div>
                    </div>
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
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Приёмка</p>
                            <a href="" class="btn btn-secondary">Так</a>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Размещение</p>
                            <a href="" class="btn btn-secondary">Так</a>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="small-box" style="background-color: #b3b3b3;">
                        <div class="inner" style="color: #ffffff;">
                            <p>Отбор</p>
                            <a href="" class="btn btn-secondary">Так</a>
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
