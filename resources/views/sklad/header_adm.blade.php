<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sklad</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link href="{{ asset('storage/AdminLTE/plugins/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('storage/AdminLTE/dist/css/adminlte.min.css') }}">





    <link rel="stylesheet" href="{{ asset('storage/AdminLTE/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">

    <link rel="stylesheet" href="{{ asset('storage/AdminLTE/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/AdminLTE/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/AdminLTE/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('storage/AdminLTE/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css') }}">



    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('storage/AdminLTE/dist/css/adminlte.min.css') }}">

</head>
<body class="hold-transition sidebar-mini" style="background-color:#f4f6f9;">

<div class="w-100" style="background-color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">

    <!-- Логотип слева -->
    <div class="logo">
        <a href="/" style="text-decoration: none; color: black; font-weight: bold; font-size: 18px;">
            <span  alt="Склад (Дарвiн)" style="height: 30px;">Склад (Дарвiн)</span>
        </a>
    </div>

    <!-- Меню справа -->
    <ul class="navbar-nav d-flex flex-row align-items-center mb-0" style="gap: 20px; list-style: none;">
        <li class="nav-item">
            <div class="navbar-search-block" style="display: none;">
                <form class="form-inline">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <li class="nav-item dropdown">
            <a class="dropdown-item p-0" href="{{ route('logout') }}"
               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                     class="bi bi-door-open" viewBox="0 0 16 16">
                    <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1"/>
                    <path d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z"/>
                </svg>
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </li>
    </ul>

</div>










