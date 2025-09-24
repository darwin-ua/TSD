@include('admin.header_adm')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Users All{{ __('translate.Save') }} -  <span class="d-inline-block" tabindex="0" data-toggle="tooltip" title="Disabled tooltip">  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16">
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
                    <a href="/partner/events/create" type="submit" value="Create new Events" class="btn btn-success float-right">+ {{ __('translate.CreateEvents') }}</a>
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
                            <table id="example" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>{{ __('translate.User') }}</th>
                                    <th>{{ __('translate.Email') }}</th>
                                    <th>{{ __('translate.Date') }}</th>
                                    <th>{{ __('translate.Data') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($users as $user)
                                    <tr data-user-id="{{ $user->id }}"  style="cursor: pointer;">
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->created_at }}</td>
                                        <td><a href="{{ route('admin.users.redact', $user->id) }}" class="btn btn-info btn-sm">{{ __('translate.Edit') }}</a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <form action="{{ route('admin.users.storeData') }}" method="POST">
                            @csrf <!-- CSRF токен для защиты от атак -->
                            <div class="modal fade" id="myModal">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <!-- Заголовок и кнопка закрытия модального окна... -->
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <label>Данные:</label>
                                                    <input type="hidden" name="user_id" id="user-id" value="">
                                                    <textarea name="settings" id="summernote"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('translate.Close') }}</button>
                                            <input type="submit" class="btn btn-primary" value="{{ __('translate.Save changes') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </section>
</div>
<script>
    $(document).ready(function(){
        // Обработчик клика на строке таблицы
        $('table.table-hover tbody tr').on('click', function(){
            $('#myModal').modal('show');
        });

        // Предотвратить всплытие события клика на кнопке редактирования
        $('table.table-hover tbody tr .btn-info').on('click', function(e){
            e.stopPropagation(); // Предотвращает всплытие к родительским элементам
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Когда пользователь кликает на строку таблицы
        $('.user-table tbody tr').on('click', function() {
            // Получаем user_id из атрибута строки
            var userId = $(this).data('user-id');

            // Устанавливаем значение user_id в скрытое поле формы
            $('#user-id').val(userId);

            // Открываем модальное окно
            $('#myModal').modal('show');
        });
    });
</script>


@include('admin.footer_adm')






