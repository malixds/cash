<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SteamTopUp RU')</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    @stack('styles')
</head>
<body>
@include('includes.header')

@yield('content')

@include('includes.footer')
@stack('scripts')
</body>
</html>
