<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Admin Portal — BYC GROWTH')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles & Scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body>
    @php
        /** @var \App\Models\User $admin */
        $admin = Auth::user();
    @endphp

    <div class="admin-shell">
        {{-- Unified Dedicated Admin Topbar Header --}}
        @include('admin.partials.header')

        {{-- Floating Toast Notifications (Top-Right Fixed, 5s Auto-dismiss, Stays visible on scroll) --}}
        <div id="admin-toast-container" class="admin-toast-container" aria-live="polite">
            @if(session('success') || session('status'))
                <div class="admin-toast-item toast-success alert-box-success" role="status">
                    <div class="toast-icon">✓</div>
                    <div class="toast-content">{{ session('success') ?? session('status') }}</div>
                    <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="admin-toast-item toast-error alert-box-error" role="alert">
                    <div class="toast-icon">⚠️</div>
                    <div class="toast-content">{{ session('error') }}</div>
                    <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
                </div>
            @endif

            @if($errors->any())
                <div class="admin-toast-item toast-error alert-box-error" role="alert">
                    <div class="toast-icon">⚠️</div>
                    <div class="toast-content">
                        <strong>Please correct the errors below:</strong>
                        <ul style="margin: 4px 0 0; padding-left: 18px; font-size: 13px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <button type="button" class="toast-close" aria-label="Dismiss">&times;</button>
                </div>
            @endif
        </div>
        <script>
            (function() {
                const toasts = document.querySelectorAll('.admin-toast-item');
                toasts.forEach(toast => {
                    const closeBtn = toast.querySelector('.toast-close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', () => {
                            toast.classList.add('toast-dismissed');
                            setTimeout(() => { if (toast.parentNode) toast.remove(); }, 420);
                        });
                    }
                    setTimeout(() => {
                        toast.classList.add('toast-dismissed');
                        setTimeout(() => { if (toast.parentNode) toast.remove(); }, 420);
                    }, 5000);
                });
            })();
        </script>

        {{-- Unified Admin Content Shell --}}
        <main class="admin-container">
            {{-- Reusable Admin Page Header (if defined) --}}
            @hasSection('page-header')
                @yield('page-header')
            @endif

            {{-- Page Content --}}
            @yield('content')
        </main>
    </div>

    {{-- Reusable Confirmation Modal Foundation --}}
    @include('admin.partials.confirm-modal')

    {{-- Welcome Toast (if just authenticated) --}}
    @include('partials.welcome-toast')

    @stack('scripts')
</body>
</html>
