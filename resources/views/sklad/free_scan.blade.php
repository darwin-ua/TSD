
@extends('layouts.app')
@section('content')


    @include('sklad.header_adm')

    @if(!empty($activeCell))
        @php
            // Приоритет: ssylka → room → "№ number" → сам activeCell
            $displayCell = $cellName
                ?? ($cellRow->ssylka ?? null)
                ?? ($cellRow->room   ?? null)
                ?? (!empty($cellRow->number) ? '№ '.$cellRow->number : null)
                ?? $activeCell;
        @endphp
        <div class="alert alert-info">
            Ячейка: <b>{{ $displayCell }}</b>
        </div>
    @else
        <div class="alert alert-warning">
            Ячейка не выбрана. Відскануйте ячейку на екрані «Розміщення».
        </div>
    @endif


    <div class="content" style="min-height:100%; padding:10px;">
        <section class="content">
            <div class="container-fluid" id="freeScanContainer">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="{{ route('sklad.index') }}" class="btn btn-secondary">←</a>
                    <div class="text-center flex-grow-1">
                        <strong>Сканування</strong>
                    </div>
                    <div style="width:88px"></div>
                </div>
                <div class="mb-3">
                    <input id="freeBarcodeInput" type="text" class="form-control form-control-lg"
                           placeholder="Сканируйте штрихкод..." autocomplete="off">
                    <small class="text-muted">Штрихкоди зберігаються без прив’язки до документа.</small>
                </div>

                <div id="freeLog" class="list-group"></div>

                <div class="mt-3">
                    <a href="{{ route('sklad.index') }}" class="btn btn-dark">Головна</a>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const activeCell = @json($activeCell);
            const cellRow    = @json($cellRow);

            console.log("📦 Ячейка:", activeCell);
            console.log("📋 Запись из таблицы skladskie_yacheiki:", cellRow);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const input  = document.getElementById('freeBarcodeInput');
            const logBox = document.getElementById('freeLog');
            const csrf   = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const URL    = @json(route('sklad.scan.free.store'));

            setTimeout(() => input?.focus(), 100);

            async function send(code) {
                try {
                    const resp = await fetch(URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            code: String(code).trim(),
                            quantity: 1
                        }),
                    });

                    const raw = await resp.text();
                    let data = {};
                    try { data = raw ? JSON.parse(raw) : {}; } catch(e) {}

                    if (!resp.ok || !data.ok) {
                        addRow('❌ ' + (data.msg || ('HTTP ' + resp.status)));
                        return;
                    }
                    addRow('✅ Принято: ' + code);
                } catch (e) {
                    addRow('❌ Помилка мережі/сервера');
                }
            }

            function addRow(text) {
                const a = document.createElement('div');
                a.className = 'list-group-item';
                a.textContent = '[' + (new Date()).toLocaleTimeString() + '] ' + text;
                logBox.prepend(a);
            }

            input?.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === 'Tab') {
                    e.preventDefault();
                    const v = input.value.trim();
                    input.value = '';
                    if (!v) return;
                    send(v);
                }
            });
        });
    </script>
    <script>
        console.log("📦 $activeCell =", @json($activeCell));
        console.log("📋 $cellRow =", @json($cellRow));
        console.log("🏷️ $cellName =", @json($cellName));
        console.log("🗄️ Сессия scan_state =", @json(session('scan_state')));
    </script>


@endpush
