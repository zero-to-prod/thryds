<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Thryds')</title>
    @vite
    @stack('head:scripts')
    @yield('head')
</head>
<body>
    @yield('body')
</body>
</html>