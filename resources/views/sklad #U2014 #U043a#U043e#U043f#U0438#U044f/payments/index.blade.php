@include('sklad.header_adm')

<style>
    .tooltip-container {
        position: relative;
        display: inline-block;
    }

    .tooltip-text {
        visibility: hidden;
        width: 100px;
        background-color: black;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%; /* Расположите подсказку выше SVG */
        left: 50%;
        margin-left: -50px;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .tooltip-container:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }

    .refresh-button {
        position: fixed;
        top: 65px;
        right: 20px;
        z-index: 9999;
    }

    .refresh-button .btn {
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 8px;
        display: flex;
        align-items: center;
    }

    .rotate {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

</style>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{--                            @foreach (breadcrumbs() as $crumb)--}}
                            {{--                                <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }} /</a>--}}
                            {{--                            @endforeach</h3>--}}
                            <div class="card-tools"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Оплати від дилера <span class="d-inline-block" tabindex="0"
                                                            data-toggle="tooltip"
                                                            title="Disabled tooltip"><form
                                    action="{{ route('sklad.exportPayments') }}" method="POST">
    @csrf
    <input type="hidden" name="payments" value="{{ json_encode($payments) }}">
    <button type="submit" class="btn btn-success">В Excel
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="white"
             class="bi bi-file-earmark-spreadsheet" viewBox="0 0 16 16">
            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H3V2a1 1 0 0 1 1-1h5.5zM3 12v-2h2v2zm0 1h2v2H4a1 1 0 0 1-1-1zm3 2v-2h3v2zm4 0в2h3v1a1 1 0 0 1-1 1zm3-3h-3v-2h3zm-7 0в-2h3v2z"/>
        </svg>
    </button>
</form>

</span></h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content" style="margin-top: -60px;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    &nbsp;
                </div>
            </div>
        </div>

        <br>
        <div class="refresh-button">
            <a href="#" id="refreshButton" class="btn btn-warning d-flex align-items-center">
                <svg id="refreshIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                </svg>
                &nbsp;Обновить данные
            </a>
        </div>
        <script>
            document.getElementById('refreshButton').addEventListener('click', function (e) {
                e.preventDefault();
                const icon = document.getElementById('refreshIcon');
                icon.classList.add('rotate');

                fetch(window.location.pathname + '?refresh=1')
                    .then(response => {
                        if (response.ok) {
                            window.location.href = window.location.pathname;
                        } else {
                            console.error('Ошибка при обновлении данных');
                        }
                    })
                    .catch(error => {
                        console.error('Ошибка запроса:', error);
                    });
            });
        </script>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body table-responsive">
                                <button type="button" class="btn btn-primary" data-toggle="modal"
                                        data-target="#fullscreenModal" id="openModalButton"
                                        style="display: none;"></button>
                                <table>
                                    <tbody>
                                    <tr>
                                        <th>
                                            Переплата: (<span style="color: #da1f12">
                    {{ number_format($summary['overpay'], 2, ',', ' ') }}
                </span> грн.)
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>
                                            Загальний борг: (<span style="color: #da1f12">
                    {{ number_format($summary['debt'], 2, ',', ' ') }}
                </span> грн.)
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>
                                            За доставленими замовленнями: (<span style="color: #da1f12">
                    {{ number_format($summary['delivered'], 2, ',', ' ') }}
                </span> грн.)
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>
                <span style="color: #da1f12">
                    Нерозподілена сума: (
                    {{ number_format($summary['unallocated'], 2, ',', ' ') }} грн.)
                </span>
                                        </th>
                                    </tr>
                                    </tbody>
                                </table>

                                &nbsp;
                                <table id="example" class="display" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>Замовлення</th> <!-- Номер платежа -->
                                        <th>Сплачено</th> <!-- Статус оплаты -->
                                        <th>Дата платежу</th> <!-- Статус оплаты -->
                                        <th>{{ __('translate.Amount') }}</th> <!-- Сумма -->
                                        <th>{{ __('translate.Account') }}</th> <!-- Счет -->
                                        <th>{{ __('translate.Note') }}</th> <!-- Примечание -->
                                        <th>{{ __('translate.Manager') }}</th> <!-- Менеджер -->
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($payments as $payment)
                                        <tr>
                                            <td>{{ $payment['кнтНомерЗаказаLogiKal'] ?? 'Нет данных' }}</td>
                                            <td>
                                                <div class="tooltip-container">
                                                    @if ($payment['СтатусОплаты'] == '0' OR empty($payment['СтатусОплаты']))
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26"
                                                             fill="red" class="bi bi-x-lg icon-with-tooltip"
                                                             viewBox="0 0 16 16">
                                                            <path d="M2.146 2.146a.5.5 0 1 1 .708.708L8 7.707l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8.414l5.147 5.146a.5.5 0 0 1-.708.708L8 9.121l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8.414 2.146 3.268a.5.5 0 0 1 0-.708z"/>
                                                        </svg>
                                                        <span class="tooltip-text">Не сплачено</span>
                                                    @elseif ($payment['СтатусОплаты'] == '1')
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26"
                                                             fill="green" class="bi bi-check-lg icon-with-tooltip"
                                                             viewBox="0 0 16 16">
                                                            <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                                                        </svg>
                                                        <span class="tooltip-text">Сплачено</span>
                                                    @else
                                                        {{ 'Нет данных' }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $dateRaw = $payment['ДатаПлатежа'] ?? null;
                                                    $dateClean = null;

                                                    if (is_string($dateRaw)) {
                                                        $trimmed = trim(str_replace(['"', '[', ']'], '', $dateRaw));
                                                        if (!empty($trimmed) && strtolower($trimmed) !== 'null' && $trimmed !== '01.01.0001 0:00:00') {
                                                            $dateClean = $trimmed;
                                                        }
                                                    }
                                                @endphp

                                                @if ($dateClean)
                                                    {{ \Carbon\Carbon::parse($dateClean)->format('d.m.Y H:i') }}
                                                @else
                                                    -
                                                @endif


                                            </td>
                                            <td>
                                                @php
                                                    $rawAmount = $payment['кнтСумма'] ?? null;
                                                    $normalizedAmount = str_replace(',', '.', $rawAmount);

                                                    $formattedAmount = is_numeric($normalizedAmount)
                                                        ? number_format((float)$normalizedAmount, 2, ',', ' ')
                                                        : '-';
                                                @endphp
                                                {{ $formattedAmount }}
                                            </td>
                                            <td></td>
                                            <td>{{ $payment['ХозяйственнаяОперация'] ?? '-' }}</td>
                                            <td>{{ $payment['Ответственный'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

@include('sklad.footer_adm')
