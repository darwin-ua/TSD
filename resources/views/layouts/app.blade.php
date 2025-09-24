<!DOCTYPE html>
<!-- saved from url=(0034) -->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">



    <title>SKLAD</title>
    <meta name="description" content="home">
    <meta name="keywords" content="home">

    <meta name="twitter:title" content="Eventhes">
    <meta name="twitter:description" content="home">
    <title>TSD</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

</head>
<body style="overflow: visible;">

<header>

</header>
<!-- End Header -->
<section id="search_container"
         style="background-image: url({{ asset('storage/files/istanbul.jpg') }}); background-repeat: round;">
    <div id="search_2">
        <div class="tab-content">
            <div class="tab-pane active show" id="tours">
            </div>
        </div>
    </div>
</section>
@yield('content')
<footer>
</footer>
{{-- 👉 ВСТАВЬ ЭТО ПЕРЕД </body> --}}
@stack('scripts')
</body>
</html>


