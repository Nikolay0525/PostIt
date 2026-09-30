<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.theme-script')
    @vite('resources/js/app.js')
    @inertiaHead
    @routes
</head>
<body>
    @inertia
</body>
</html>