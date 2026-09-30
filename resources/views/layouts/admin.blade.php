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
        {{-- Unified Admin Topbar Header --}}
        <header class="admin-topbar">
            <div class="admin-brand-wrap">
                <a href="{{ route('home') }}" class="brand-link" title="Visit Public Website">
                    <x-brand :compact="true" />
                </a>
                <span class="admin-portal-badge">Admin Portal</span>
            </div>

            <nav class="admin-quick-nav" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Dashboard</a>
                <a href="{{ route('admin.homepage') }}" class="nav-item {{ request()->routeIs('admin.homepage*') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Homepage</a>
                <a href="{{ route('admin.members') }}" class="nav-item {{ request()->routeIs('admin.members*') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Members</a>
                <a href="{{ route('admin.activities') }}" class="nav-item {{ request()->routeIs('admin.activities*') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Activities</a>
                <a href="{{ route('admin.games') }}" class="nav-item {{ request()->routeIs('admin.games*') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Games</a>
                <a href="{{ route('admin.birthday-wishes') }}" class="nav-item {{ request()->routeIs('admin.birthday-wishes*') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Birthday Wishes</a>
                <a href="{{ route('admin.cash-management') }}" class="nav-item {{ request()->routeIs('admin.cash-management*') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Cash Management</a>
                <a href="{{ route('admin.roles') }}" class="nav-item {{ request()->routeIs('admin.roles*') ? 'active' : '' }}" style="font-size: 13px; padding: 6px 12px;">Roles / Accounts</a>
            </nav>

            <div class="admin-user-nav">
                @if($admin)
                    <div class="admin-user-info">
                        <strong>{{ $admin->name }} ({{ $admin->username }})</strong>
                        <small>{{ $admin->email }} &bull; <span class="role-badge">{{ ucfirst($admin->role) }}</span></small>
                    </div>
                @endif

                <a href="{{ route('home') }}" class="button button-ghost button-sm" id="btn-admin-view-site" title="Visit Public Website">
                    View Site
                </a>

                <form method="POST" action="{{ route('admin.logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="button button-danger button-sm" id="btn-admin-logout">
                        Sign Out
                    </button>
                </form>
            </div>
        </header>

        {{-- Unified Admin Content Shell --}}
        <main class="admin-container">
            {{-- Reusable Admin Page Header (if defined) --}}
            @hasSection('page-header')
                @yield('page-header')
            @endif

            {{-- Reusable Success / Status Feedback --}}
            @if(session('success') || session('status'))
                <div class="alert-box-success" role="status" style="margin-bottom: 24px; padding: 14px 20px; background: #eaf3dc; border: 1px solid var(--lime); border-radius: 12px; color: var(--forest-dark); font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <span>✓</span>
                    <span>{{ session('success') ?? session('status') }}</span>
                </div>
            @endif

            {{-- Reusable Error Feedback --}}
            @if(session('error'))
                <div class="alert-box-error" role="alert" style="margin-bottom: 24px; padding: 14px 20px; background: #fdf0ee; border: 1px solid var(--red); border-radius: 12px; color: var(--red); font-weight: 600; display: flex; align-items: center; gap: 10px;">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Reusable Validation Error Summary --}}
            @if($errors->any())
                <div class="alert-box-error" role="alert" style="margin-bottom: 24px; padding: 14px 20px; background: #fdf0ee; border: 1px solid var(--red); border-radius: 12px; color: var(--red); font-weight: 600;">
                    <strong style="display: block; margin-bottom: 6px;">Please correct the errors below:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
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
