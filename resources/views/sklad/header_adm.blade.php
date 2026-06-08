@php
    $theme = \App\Support\TsdOneC::theme(auth()->user());
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="tsd-company" content="{{ $theme['company'] }}">

    <title>Склад - система работы с складами</title>
    <link rel="icon" type="image/svg+xml" href="{{ $theme['favicon'] }}">
    <link rel="shortcut icon" href="{{ $theme['favicon'] }}">
    <link rel="apple-touch-icon" href="{{ $theme['favicon'] }}">
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

    <style>
        :root {
            --theme-accent: {{ $theme['accent'] }};
            --theme-accent-rgb: {{ $theme['accent_rgb'] }};
            --theme-accent-soft: {{ $theme['accent_soft'] }};
            --theme-accent-soft-strong: {{ $theme['accent_soft_strong'] }};
            --theme-secondary-accent: {{ $theme['secondary_accent'] }};
            --theme-secondary-accent-rgb: {{ $theme['secondary_accent_rgb'] }};
            --theme-dark: {{ $theme['dark'] }};
            --theme-heading: {{ $theme['heading'] }};
            --theme-muted: {{ $theme['muted'] }};
            --theme-surface: {{ $theme['surface'] }};
            --theme-surface-alt: {{ $theme['surface_alt'] }};
            --theme-border: {{ $theme['border'] }};
            --theme-button-text: {{ $theme['button_text'] }};
            --theme-body-bg: {{ $theme['body_bg'] }};
            --theme-gradient-from: {{ $theme['gradient_from'] }};
            --theme-gradient-to: {{ $theme['gradient_to'] }};
        }

        body {
            background-color: var(--theme-body-bg) !important;
            color: var(--theme-heading);
        }

        .content-wrapper,
        .wrapper {
            background-color: transparent !important;
        }

        .btn-primary,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: var(--theme-accent) !important;
            border-color: var(--theme-accent) !important;
            color: #fff !important;
            box-shadow: 0 0 0 .2rem rgba(var(--theme-accent-rgb), .18) !important;
        }

        .btn-primary:hover {
            background-color: var(--theme-dark) !important;
            border-color: var(--theme-dark) !important;
            color: #fff !important;
        }

        .btn-dark,
        .btn-dark:focus,
        .btn-dark:active {
            background-color: var(--theme-dark) !important;
            border-color: var(--theme-dark) !important;
            color: #fff !important;
        }

        .btn-dark:hover {
            background-color: var(--theme-accent) !important;
            border-color: var(--theme-accent) !important;
            color: #fff !important;
        }

        .btn-secondary,
        .btn-secondary:focus,
        .btn-secondary:active {
            background-color: var(--theme-accent-soft) !important;
            border-color: rgba(var(--theme-accent-rgb), .18) !important;
            color: var(--theme-heading) !important;
        }

        .btn-secondary:hover {
            background-color: var(--theme-accent-soft-strong) !important;
            color: var(--theme-heading) !important;
        }

        .form-control:focus,
        .custom-select:focus,
        .select2-container--bootstrap4.select2-container--focus .select2-selection {
            border-color: var(--theme-accent) !important;
            box-shadow: 0 0 0 .2rem rgba(var(--theme-accent-rgb), .18) !important;
        }

        .card,
        .small-box,
        .info-box,
        .content-card,
        .list-group-item {
            border-color: var(--theme-border) !important;
        }

        .card-primary:not(.card-outline) > .card-header,
        .bg-primary,
        .badge-primary {
            background-color: var(--theme-accent) !important;
            border-color: var(--theme-accent) !important;
            color: #fff !important;
        }

        .alert-info {
            color: var(--theme-heading) !important;
            background-color: rgba(var(--theme-accent-rgb), 0.12) !important;
            border-color: rgba(var(--theme-accent-rgb), 0.22) !important;
        }

        .alert-warning {
            color: var(--theme-heading) !important;
            background-color: rgba(var(--theme-secondary-accent-rgb), 0.14) !important;
            border-color: rgba(var(--theme-secondary-accent-rgb), 0.22) !important;
        }

        a {
            color: var(--theme-accent);
        }

        a:hover {
            color: var(--theme-dark);
        }

        .page-item.active .page-link,
        .page-link:focus {
            background-color: var(--theme-accent) !important;
            border-color: var(--theme-accent) !important;
            box-shadow: 0 0 0 .2rem rgba(var(--theme-accent-rgb), .18) !important;
        }

        .page-link {
            color: var(--theme-accent);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini tsd-company-{{ $theme['company'] }}" style="background-color:var(--theme-body-bg);">
