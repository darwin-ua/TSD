<!DOCTYPE html>
<!-- saved from url=(0034) -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <script src="storage/Home_files/livechatinit2.js"></script>
    <script src="storage/Home_files/resources2.aspx"></script>
    <link id="mlc_chatinlie_styletag" rel="stylesheet" href="storage/Home_files/chatinline.css">
    <link rel="stylesheet" href="storage/Home_files/css">
    <title>Sklad</title>
    <meta name="description" content="home">
    <meta name="keywords" content="home">
    <meta property="og:title" content="LK">
    <meta property="og:description" content="home">
    <meta property="og:url" content="https://eventhes.com">
    <meta property="og:type" content="website">
    <meta property="og:image" content="uploads/settings/site_logo.png">
    <meta name="twitter:title" content="LK">
    <meta name="twitter:description" content="home">
    <title>{{ config('app.name', 'LK') }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('storage/AdminLTE/dist/img/sklad.png') }}">
    <link media="all" type="text/css" rel="stylesheet" href="/storage/Home_files/bootstrap.min.css">
    <link media="all" type="text/css" rel="stylesheet" href="/storage/Home_files/style.css">
    <link media="all" type="text/css" rel="stylesheet" href="/storage/Home_files/vendors.css">
    <link media="all" type="text/css" rel="stylesheet" href="/storage/Home_files/custom.css">
    <link href="storage/Home_files/css2" rel="stylesheet">

    <style id="css-ddslick" type="text/css"></style>
</head>

<body>
<section>

        @yield('content')

</section>
</body>
</html>
