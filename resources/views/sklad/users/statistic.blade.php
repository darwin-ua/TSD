@include('admin.header_adm')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ __('translate.Users All') }} -  <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Disabled tooltip">  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                            <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286m1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94"/>
                                        </svg></span></h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content" style="margin-top: -55px;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <a href="/partner/events/create" type="submit" value="Create new Project" class="btn btn-success float-right">+ {{ __('translate.CreateEvents') }}</a>
                </div>
            </div>
        </div>
        <br>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">

                            <!-- /.card-header -->
                            <div class="card-body table-responsive">
{{--                                <table class="table table-hover text-nowrap">--}}
                                <table id="example" class="display" style="width:100%">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>{{ __('translate.Text') }}</th>
                                        <th>{{ __('translate.Created') }}</th>
                                        <th>{{ __('translate.Status') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($alertCount as $data)
                                        <tr>
                                            <td>{{ $data->id }}</td>
                                            <td>{{ $data->text }}</td>
                                            <td>{{ $data->created_at }}</td>
                                            @if ($data->status == 1)
                                                <td>
                                                    {{ __('translate.Views') }}
                                                </td>
                                            @endif
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
{{--        <nav aria-label="Page navigation example">--}}
{{--            <ul class="pagination">--}}
{{--                @if ($alertCount->onFirstPage())--}}
{{--                    <li class="page-item disabled"><span class="page-link">{{ __('translate.Previous') }}</span></li>--}}
{{--                @else--}}
{{--                    <li class="page-item"><a class="page-link" href="{{ $alertCount->previousPageUrl() }}">{{ __('translate.Previous') }}</a></li>--}}
{{--                @endif--}}
{{--                @for ($i = 1; $i <=  $alertCount->lastPage(); $i++)--}}
{{--                    @if ($i ==  $alertCount->currentPage())--}}
{{--                        <li class="page-item active"><span class="page-link">{{ $i }}</span></li>--}}
{{--                    @else--}}
{{--                        <li class="page-item"><a class="page-link" href="{{  $alertCount->url($i) }}">{{ $i }}</a></li>--}}
{{--                    @endif--}}
{{--                @endfor--}}

{{--                <!-- Кнопка "Следующий" -->--}}
{{--                @if ( $alertCount->hasMorePages())--}}
{{--                    <li class="page-item"><a class="page-link" href="{{  $alertCount->nextPageUrl() }}">{{ __('translate.Next') }}</a></li>--}}
{{--                @else--}}
{{--                    <li class="page-item disabled"><span class="page-link">{{ __('translate.Next') }}</span></li>--}}
{{--                @endif--}}
{{--            </ul>--}}
{{--        </nav>--}}
    </section>
</div>

@include('admin.footer_adm')
