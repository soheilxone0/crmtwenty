<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['fa', 'ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') — @lang('platform::app.name')</title>
    <link rel="icon" href="{{ asset('platform/favicon.ico') }}">
    <link rel="preload" href="{{ asset('platform/fonts/Vazirmatn-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('platform/platform.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('platform/icons.css') }}?v=2">
</head>
<body>
    @yield('body')

    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('data-toggle-password'));
                var icon = button.querySelector('.pi');

                input.type = input.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('pi-eye');
                icon.classList.toggle('pi-eye-slash');
            });
        });

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (! window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('form[data-busy]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var button = form.querySelector('button[type="submit"]');

                if (button) {
                    button.disabled = true;
                    button.style.opacity = '.75';
                    button.querySelector('.label') && (button.querySelector('.label').textContent = form.getAttribute('data-busy'));
                }
            });
        });
    </script>
</body>
</html>
