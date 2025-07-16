
@extends('layouts.filter')
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
@section('content')
    <main>
        <div class="container margin_60">
            <div class="main_title">
                <h2>EVENTHES
                    <span>Top</span> {{ __('translate.Events') }}</h2>
                <p>{{ __('translate.Your cabinet') }}</p>
            </div>
            <!-- HTML-код для вкладок и их содержимого -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#wishlist">{{ __('translate.Wishlist') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#orders">{{ __('translate.Orders') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#message">{{ __('translate.Message') }}</a>
                </li>
            </ul>
            <div class="tab-content">
                <div id="wishlist" class="tab-pane fade show active">
                    <div class="row">
                        @if(!empty($events))
                            @foreach($events as $event)
                                <div class="col-lg-4 col-md-6 wow zoomIn" data-wow-delay="0.1s">
                                    <div class="tour_container">
                                        <!-- Перенесли вывод переменных из массива $event -->
                                        <div class="ribbon_3 popular">
                                            <span>HOT</span>
                                        </div>
                                        <div class="img_container">
                                            <a href="">
                                                <!-- Использовали динамические данные из $event -->
                                                <img src="{{ asset('files/' . $event->user_id . '/' . $event->foto_title) }}"
                                                     width="800" height="533" class="img-fluid" alt="Image">
                                                <div class="short_info">
                                                    <!-- Использовали динамические данные из $event -->
                                                    <i></i>{{$event->reserv}}<span
                                                        class="price">{{(($event->amount == 0) ? 'FREE' : $event->amount.'$') }}</span>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="tour_title">
                                            <h3>
                                                <strong>{{$event->title}}</strong></h3>
                                            <div class="parent-container" style="display: flex; justify-content: flex-end;">
                                                <div class="rating">
                                                    <i class="fa fa-heart" style="font-size: 30px; color: #e14d67;  cursor: pointer;"
                                                       onclick="likeButtonClicked({{ $event->id }});"></i>
                                                </div><!-- end rating -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p>No events found</p>
                        @endif
                    </div>
                </div>
                <div id="orders" class="tab-pane fade">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Имя</th>
                            <th scope="col">Фамилия</th>
                            <th scope="col">Обращение</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <th scope="row">1</th>
                            <td>Mark</td>
                            <td>Otto</td>
                            <td>@mdo</td>
                        </tr>
                        <tr>
                            <th scope="row">2</th>
                            <td>Jacob</td>
                            <td>Thornton</td>
                            <td>@fat</td>
                        </tr>
                        <tr>
                            <th scope="row">3</th>
                            <td colspan="2">Larry the Bird</td>
                            <td>@twitter</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div id="message" class="tab-pane fade">
                Not message ...
                </div>
            </div>
        </div><!-- End container -->

    </main>
@endsection
<script>
    $(document).ready(function(){
        // Обработчик клика по ссылкам вкладок
        $('.nav-link').click(function(){
            // Удаляем класс 'active' со всех ссылок вкладок
            $('.nav-link').removeClass('active');
            // Добавляем класс 'active' только к нажатой ссылке
            $(this).addClass('active');
            // Получаем идентификатор вкладки из атрибута href и скрываем только предыдущую активную вкладку
            var tab_id = $(this).attr('href');
            $('.tab-content .tab-pane').removeClass('show active');
            // Показываем только выбранную вкладку
            $(tab_id).addClass('show active');
        });
    });
</script>
