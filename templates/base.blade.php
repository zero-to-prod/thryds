<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Thryds')</title>
    {{-- Emits <link> + <script type="module"> tags for the Vite app entry (resources/js/app.js + CSS). Defined in public/index.php via Vite::directivePhp(). --}}
    @vite
    @hotReload
    @stack('head:scripts')
    @yield('head')
</head>
<body>
    @yield('body')
</body>
</html>