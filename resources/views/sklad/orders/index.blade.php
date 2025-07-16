
@include('sklad.header_adm')
<style>
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
    }-
    .nav-tabs .nav-link {
        border-radius: 4px 4px 0 0;
    }

    .nav-tabs {
        border-bottom: none;
    }

    #nomenclatureList li {
        font-size: 10px;
    }
</style>
<div class="content" style="min-height: 100%; padding: 10px;">
    <section class="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-arrow bg-secondary text-white">&larr;</button>
                <div class="text-center flex-grow-1">
                    <strong>№ 89735</strong>
                </div>
                <button class="btn btn-arrow bg-secondary text-white">&rarr;</button>
            </div>

            <ul class="nav nav-tabs mb-3" id="tabMenu">
                <li class="nav-item">
                    <a class="nav-link custom-tab {{ request()->is('sklad/orders/gp') ? 'active' : '' }}" href="{{ route('sklad.orders.index') }}">ГП</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link custom-tab {{ request()->is('sklad/orders/dop') ? 'active' : '' }}" href="{{ route('sklad.orders.addition') }}">Доп</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link custom-tab {{ request()->is('sklad/orders/komp') ? 'active' : '' }}" href="{{ route('sklad.orders.equipm') }}">Комп</a>
                </li>
            </ul>

            <div class="mb-3">
                <input id="barcodeInput" type="text" class="form-control form-control-lg"
                       placeholder="Сканируйте штрихкод..." autofocus autocomplete="off">
            </div>
            <div class="card" style="min-height: 140px;">
                <div class="card-body p-0">
                    <div style="max-height: 140px; overflow-y: auto;">
                        <ul id="nomenclatureList" class="list-group">
                            <li class="list-group-item" data-barcode="1234567890001">Позиция 1 — ШК: 1234567890001</li>
                            <li class="list-group-item" data-barcode="1234567890002">Позиция 2 — ШК: 1234567890002</li>
                            <li class="list-group-item" data-barcode="1234567890003">Позиция 3 — ШК: 1234567890003</li>
                            <li class="list-group-item" data-barcode="1234567890004">Позиция 4 — ШК: 1234567890004</li>
                            <li class="list-group-item" data-barcode="1234567890005">Позиция 5 — ШК: 1234567890005</li>
                            <li class="list-group-item" data-barcode="1234567890006">Позиция 6 — ШК: 1234567890006</li>
                            <li class="list-group-item" data-barcode="1234567890007">Позиция 7 — ШК: 1234567890007</li>
                            <li class="list-group-item" data-barcode="1234567890008">Позиция 8 — ШК: 1234567890008</li>
                            <li class="list-group-item" data-barcode="1234567890009">Позиция 9 — ШК: 1234567890009</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="d-flex mt-3" id="proceedButtonWrapper" style="display: none;">
                <button type="button" class="btn btn-success me-2" id="go" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="bi bi-arrow-right-circle" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                              d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0M4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5z"/>
                    </svg>
                </button>

                <button onclick="localStorage.removeItem('scannedBarcodes'); location.reload();" class="btn btn-danger me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="bi bi-x-square" viewBox="0 0 16 16">
                        <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
                    </svg>
                </button>

                <button class="btn btn-light border"
                        onclick="localStorage.setItem('forceHighlight', 'true'); location.reload();"
                        title="Обновить страницу">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                         class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                              d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                    </svg>
                </button>
            </div>


            <div class="mt-3">
                <a href="/sklad" class="btn btn-dark">Главная</a>
            </div>
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log('✅ Скрипт загружен и запущен');

        const barcodeInput = document.getElementById('barcodeInput');
        if (!barcodeInput) {
            alert('❌ Не найден input с id=barcodeInput!');
            return;
        }

        const stored = localStorage.getItem('scannedBarcodes');
        const scannedBarcodes = new Set(stored ? JSON.parse(stored) : []);
        console.log('🟡 Загруженные сканированные штрихкоды из localStorage:', [...scannedBarcodes]);

        // ✅ Подсветка ранее сканированных
        scannedBarcodes.forEach(barcode => {
            const item = document.querySelector(`#nomenclatureList li[data-barcode="${barcode}"]`);
            if (item) {
                item.classList.add('bg-warning');
                console.log(`🟨 Подсвечено ранее отсканированное: ${barcode}`);
            }
        });

        // Проверка после загрузки
        setTimeout(checkIfAllScanned, 50);

        // Автофокус
        setInterval(() => {
            if (document.activeElement !== barcodeInput) {
                barcodeInput.focus();
                console.log('🔁 Возврат фокуса на поле ввода');
            }
        }, 1000);

        function checkIfAllScanned() {
            const allItems = document.querySelectorAll('#nomenclatureList li');
            const scannedItems = document.querySelectorAll('#nomenclatureList li.bg-warning');
            const buttonWrapper = document.getElementById('proceedButtonWrapper');
            const goButton = document.getElementById('go');

            console.log(`📊 Проверка сканирования: ${scannedItems.length} из ${allItems.length}`);

            if (allItems.length === scannedItems.length && allItems.length > 0) {
                buttonWrapper.style.display = 'flex';
                goButton.style.display = 'block'; // <-- добавлено!
                console.log('✅ Все позиции отсканированы — кнопка отображена');
            } else {
                buttonWrapper.style.display = 'none';
                goButton.style.display = 'none'; // <-- добавлено!
                console.log('⛔ Не все позиции отсканированы — кнопка скрыта');
            }
        }


        function highlightProduct(barcode) {
            document.querySelectorAll('#nomenclatureList li.bg-primary').forEach(item => {
                item.classList.remove('bg-primary');
            });

            const item = document.querySelector(`#nomenclatureList li[data-barcode="${barcode}"]`);
            if (item) {
                item.classList.add('bg-primary');
                item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                console.log(`🔍 Прокрутка и выделение позиции ${barcode}`);
            }
        }

        let scanTimeout;

        barcodeInput.addEventListener('input', function () {
            clearTimeout(scanTimeout);

            scanTimeout = setTimeout(() => {
                const barcode = barcodeInput.value.trim();
                console.log('🔘 Введён штрихкод:', barcode);
                if (barcode === '') return;

                const existing = localStorage.getItem('scannedBarcodes');
                const parsed = existing ? JSON.parse(existing) : [];

                const alreadyScanned = parsed.includes(barcode);
                const existsInDOM = document.querySelector(`#nomenclatureList li[data-barcode="${barcode}"]`);

                if (alreadyScanned && existsInDOM) {
                    alert('⚠️ Штрихкод уже сканирован и был найден в списке!');
                    console.warn('⛔ Повторный скан:', barcode);
                    barcodeInput.value = '';
                    return;
                }

                if (!existsInDOM) {
                    alert('❗️ Такой позиции нет в списке!');
                    console.error('⛔ Не найден элемент для штрихкода:', barcode);
                    barcodeInput.value = '';
                    return;
                }

                // ✅ Добавляем новый
                parsed.push(barcode);
                scannedBarcodes.add(barcode);
                localStorage.setItem('scannedBarcodes', JSON.stringify(parsed));

                barcodeInput.value = '';
                console.log(`✅ Новый скан добавлен: ${barcode}`);
                console.log('💾 Сохранено в localStorage:', parsed);

                const item = document.querySelector(`#nomenclatureList li[data-barcode="${barcode}"]`);
                if (item) {
                    item.classList.add('bg-warning');
                    console.log(`🟨 Подсвечено как отсканированное: ${barcode}`);
                }

                highlightProduct(barcode);
                checkIfAllScanned();
            }, 500);
        });
    });
</script>



{{--<script>--}}
{{--    document.addEventListener('DOMContentLoaded', function () {--}}
{{--        console.log('✅ Сценарий подключился');--}}

{{--        const barcodeInput = document.getElementById('barcodeInput');--}}
{{--        if (!barcodeInput) {--}}
{{--            alert('❌ Не найден input с id=barcodeInput!');--}}
{{--            return;--}}
{{--        }--}}

{{--        const stored = localStorage.getItem('scannedBarcodes');--}}
{{--        const scannedBarcodes = new Set(stored ? JSON.parse(stored) : []);--}}

{{--        scannedBarcodes.forEach(barcode => {--}}
{{--            const item = document.querySelector(`#nomenclatureList li[data-barcode="${barcode}"]`);--}}
{{--            if (item) {--}}
{{--                item.classList.add('bg-warning');--}}
{{--            }--}}
{{--        });--}}

{{--        setInterval(() => {--}}
{{--            if (document.activeElement !== barcodeInput) {--}}
{{--                barcodeInput.focus();--}}
{{--            }--}}
{{--        }, 1000);--}}

{{--        function highlightProduct(barcode) {--}}

{{--            document.querySelectorAll('#nomenclatureList li.bg-primary').forEach(item => {--}}
{{--                item.classList.remove('bg-primary');--}}
{{--            });--}}

{{--            const item = document.querySelector(`#nomenclatureList li[data-barcode="${barcode}"]`);--}}
{{--            if (item) {--}}
{{--                item.classList.add('bg-primary');--}}

{{--                item.scrollIntoView({--}}
{{--                    behavior: 'smooth',--}}
{{--                    block: 'center'--}}
{{--                });--}}

{{--                return true;--}}
{{--            } else {--}}
{{--                console.warn('❗️ Штрихкод не найден в списке:', barcode);--}}
{{--                return false;--}}
{{--            }--}}
{{--        }--}}

{{--        let scanTimeout;--}}

{{--        barcodeInput.addEventListener('input', function () {--}}
{{--            clearTimeout(scanTimeout);--}}

{{--            scanTimeout = setTimeout(() => {--}}
{{--                const barcode = barcodeInput.value.trim();--}}
{{--                if (barcode === '') return;--}}

{{--                const existing = localStorage.getItem('scannedBarcodes');--}}
{{--                const parsed = existing ? JSON.parse(existing) : [];--}}

{{--                const alreadyScanned = parsed.includes(barcode);--}}
{{--                const existsInDOM = document.querySelector(`#nomenclatureList li[data-barcode="${barcode}"]`);--}}

{{--                if (alreadyScanned && existsInDOM) {--}}
{{--                    alert('⚠️ Штрихкод уже сканирован и был найден в списке!');--}}
{{--                    barcodeInput.value = '';--}}
{{--                    return;--}}
{{--                }--}}

{{--                if (!existsInDOM) {--}}
{{--                    alert('❗️ Такой позиции нет в списке!');--}}
{{--                    barcodeInput.value = '';--}}
{{--                    return;--}}
{{--                }--}}

{{--                parsed.push(barcode);--}}
{{--                localStorage.setItem('scannedBarcodes', JSON.stringify(parsed));--}}

{{--                // Очистить поле--}}
{{--                barcodeInput.value = '';--}}
{{--                console.log('📦 Скан:', barcode);--}}

{{--                // Подсветить и прокрутить--}}
{{--                highlightProduct(barcode);--}}
{{--            }, 500);--}}
{{--        });--}}
{{--    });--}}
{{--</script>--}}


