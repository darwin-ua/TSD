@include('admin.header_adm')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Users All{{ __('translate.Save') }}</h1>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <section class="content" style="margin-top: -55px;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <a href="/partner/users/create" type="submit" value="Create new User" class="btn btn-success float-right">+ {{ __('translate.Events') }}</a>
                </div>
            </div>
        </div>
        <br>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('translate.Responsive Hover Table') }}</h3>
                                <div class="card-tools">
                                    <div class="input-group input-group-sm" style="width: 150px;">
                                        <input type="text" name="table_search" class="form-control float-right" placeholder="{{ __('translate.Save') }}">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-default">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email</th>
                                        <th>Settings</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($userDataRecords as $record)
                                        <tr>
                                            <td>{{ $record->id }}</td>
                                            <td>{{ $record->email }}</td>
                                            <td>{{ $record->settings }}</td>
                                                                                    <td>
                                                                                        <form action="{{ route('admin.users.destroyUserData', $record->id) }}" method="POST">
                                                                                            @csrf
                                                                                            @method('DELETE')
                                                                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Вы уверены, что хотите удалить эти данные?')">Очистить</button>
                                                                                        </form>
                                                                                    </td>
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
    </section>
</div>
<script>
    $(document).ready(function(){
        // Для каждой строки в теле таблицы
        $('table.table-hover tbody tr').on('click', function(){
            // Открыть модальное окно с ID 'myModal'
            $('#myModal').modal('show');
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






