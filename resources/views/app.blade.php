<!DOCTYPE html>
@php($themeSettings = auth()->user()?->settings)
{{-- The theme script in <head> reads these before first paint; guests get its default, 'browser'. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"@if ($themeSettings) data-theme-mode="{{ $themeSettings->theme_mode->value }}" data-theme-manual="{{ $themeSettings->dark_theme ? 'dark' : 'light' }}"@endif>
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