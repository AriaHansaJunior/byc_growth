<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'BYC GROWTH — Growing in Faith & Fellowship')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Styles & Scripts (Always loads latest build with automatic cache-busting) -->
    @if (file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        @php
            $cssPath = public_path('build/assets/app.css');
            $jsPath = public_path('build/assets/app.js');
            $cssVer = file_exists($cssPath) ? substr(md5(filemtime($cssPath) . ':' . filesize($cssPath)), 0, 10) : time();
            $jsVer = file_exists($jsPath) ? substr(md5(filemtime($jsPath) . ':' . filesize($jsPath)), 0, 10) : time();
        @endphp
        <link rel="stylesheet" href="{{ asset('build/assets/app.css') }}?v={{ $cssVer }}">
        <script type="module" src="{{ asset('build/assets/app.js') }}?v={{ $jsVer }}"></script>
    @endif

    <!-- Critical Mobile Header Freeze & Smooth Scroll Safeguard -->
    <style>
        @media (max-width: 768px) {
            html, body {
                overflow-x: clip !important;
                max-width: 100% !important;
                width: 100% !important;
                scroll-behavior: auto !important;
            }
            body {
                min-width: 0 !important;
                touch-action: pan-y;
            }
            .site-wrapper, .site-main {
                overflow-x: clip !important;
                overflow-y: visible !important;
                max-width: 100% !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .global-header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 950 !important;
                width: 100% !important;
            }
            .site-wrapper.has-global-header,
            .site-wrapper:has(.global-header) {
                padding-top: 69px !important;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div id="app" class="site-wrapper {{ View::hasSection('no-header') ? 'no-global-header' : 'has-global-header' }}">
        @unless(View::hasSection('no-header'))
            @include('partials.header')
        @endunless

        <main class="site-main">
            @yield('content')
        </main>

        @unless(View::hasSection('no-footer'))
            @include('partials.footer')
        @endunless
    </div>

    @include('components.birthday-popup')
    @include('partials.alert-modal')
    @include('partials.welcome-toast')
    @include('partials.preserve-scroll')

    @stack('scripts')
</body>
</html>
