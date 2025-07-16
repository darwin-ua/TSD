@include('admin.header_adm')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" >
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('translate.Inline Charts') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home{{ __('translate.Inline Charts') }}</a></li>
                        <li class="breadcrumb-item active">Inline Charts{{ __('translate.Inline Charts') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
        <section class="content">
            <div class="container-fluid">
                <form method="POST" action="{{ route('admin.shedules.store') }}">
                    @csrf
                    <div class="row">
                    <div class="col-md-6">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('translate.Event date and time') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>{{ __('translate.Date') }}:</label>
                                    <div class="input-group date" id="reservationdatetime" data-target-input="nearest">
                                        <input type="text" class="form-control" id="reserv" name="reserv">
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade" id="myModal">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title">{{ __('translate.Enter values') }}</h4>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="modal-body">{{ __('translate.Start date') }}
                                                <div class="form-group row" id="start_date">
                                                    <div class="col">
                                                        <label for="year">{{ __('translate.Year') }}:</label>
                                                        <select class="form-control" id="year">
                                                            @for ($year = date('Y'); $year <= date('Y') + 5; $year++)
                                                                <option>{{ $year }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="col">
                                                        <label for="month">{{ __('translate.Month') }}:</label>
                                                        <select class="form-control" id="month">
                                                            @for ($month = 1; $month <= 12; $month++)
                                                                <option>{{ $month }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="col">
                                                        <label for="day">{{ __('translate.Day') }}:</label>
                                                        <select class="form-control" id="day">
                                                            @for ($day = 1; $day <= 31; $day++)
                                                                <option>{{ $day }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-body">{{ __('translate.End date') }}
                                                <div class="form-group row" id="end_date">
                                                    <div class="col">
                                                        <label for="endyear">{{ __('translate.Year') }}:</label>
                                                        <select class="form-control" id="endyear">
                                                            @for ($year = date('Y'); $year <= date('Y') + 5; $year++)
                                                                <option>{{ $year }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="col">
                                                        <label for="endmonth">{{ __('translate.Month') }}:</label>
                                                        <select class="form-control" id="endmonth">
                                                            @for ($month = 1; $month <= 12; $month++)
                                                                <option>{{ $month }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                    <div class="col">
                                                        <label for="endday">{{ __('translate.Day') }}:</label>
                                                        <select class="form-control" id="endday">
                                                            @for ($day = 1; $day <= 31; $day++)
                                                                <option>{{ $day }}</option>
                                                            @endfor
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('translate.Close') }}</button>
                                            <button type="button" class="btn btn-primary" id="saveChangesBtn">{{ __('translate.Save changes') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    <button type="submit" class="btn btn-primary">{{ __('translate.Save') }}</button>
                </form>
            </div>
        </section>
</div>
<script>
    function updateReservValue() {
        var startYear = document.getElementById('year').value;
        var startMonth = document.getElementById('month').value;
        var startDay = document.getElementById('day').value;
        var endYear = document.getElementById('endyear').value;
        var endMonth = document.getElementById('endmonth').value;
        var endDay = document.getElementById('endday').value;
        var startDate = startYear + '-' + startMonth + '-' + startDay;
        var endDate = endYear + '-' + endMonth + '-' + endDay;
        document.getElementById('reserv').value = 'Start date: ' + startDate + ' | End date: ' + endDate;
    }
    document.getElementById('saveChangesBtn').addEventListener('click', function() {
        updateReservValue();
        $('#myModal').modal('hide');
    });
    document.getElementById('reserv').addEventListener('click', function() {
        $('#myModal').modal('show');
    });
</script>
@include('admin.footer_adm')

