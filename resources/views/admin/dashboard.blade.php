@extends('layouts.admin')

@section('title', 'Admin Dashboard — BYC GROWTH')

@section('content')
    {{-- Flash / Welcome --}}
    <div class="admin-welcome-card">
        <div>
            <span class="eyebrow" style="color: var(--lime);">Authentication & Authorization Active</span>
            <h1>Welcome back, {{ $admin->username ?? $admin->name }}</h1>
            <p>
                You are securely authenticated as an administrator with data and content management privileges.
            </p>
        </div>
        <div class="admin-stamp">
            <strong>ADMIN<br>SECURED</strong>
        </div>
    </div>

    @php
        $modules = [
            'homepage' => [
                'name' => 'Homepage',
                'desc' => 'Manage slideshow images, highlights, announcements, and editable showcase data.',
                'route' => route('admin.homepage'),
                'icon' => 'home',
            ],
            'activities' => [
                'name' => 'Activities',
                'desc' => 'Record community events, publish fellowship recaps, and upload event galleries.',
                'route' => route('admin.activities'),
                'icon' => 'sparkles',
            ],
            'members' => [
                'name' => 'Members',
                'desc' => 'Add/edit member profiles, upload photos, and maintain confidential birthday records.',
                'route' => route('admin.members'),
                'icon' => 'users',
            ],
            'games' => [
                'name' => 'Games',
                'desc' => 'Universal Game System: Guess Me! & BYC Growth 100 rounds, questions, and team configs.',
                'route' => route('admin.games'),
                'icon' => 'gamepad',
            ],
            'birthday_wishes' => [
                'name' => 'Birthday Wishes',
                'desc' => 'Full archive browse across all members, all years, and letter audit privileges.',
                'route' => route('admin.birthday-wishes'),
                'icon' => 'heart',
            ],
            'cash_management' => [
                'name' => 'Cash Management',
                'desc' => 'Transaction ledger, member shortcuts, transfer proof review, and treasury total.',
                'route' => route('admin.cash-management'),
                'icon' => 'cash',
            ],
            'roles' => [
                'name' => 'Roles / Accounts',
                'desc' => 'Manage user and admin accounts, system credentials, usernames, and access roles.',
                'route' => route('admin.roles'),
                'icon' => 'shield',
            ],
        ];

        $permittedModules = [];
        foreach ($modules as $key => $module) {
            if ($admin && $admin->hasPermission($key)) {
                $permittedModules[$key] = $module;
            }
        }
        $permittedCount = count($permittedModules);
    @endphp

    {{-- Protected Modules Overview --}}
    <div class="admin-section-heading">
        @if($permittedCount > 0)
            <h2>Authorized Management Modules ({{ $permittedCount }})</h2>
            <p>Access your administrator management tools and community data portals.</p>
        @else
            <h2>Access Permissions Restricted</h2>
            <p>No active management modules have been assigned to your administrator account.</p>
        @endif
    </div>

    @if($permittedCount > 0)
        <div class="feature-grid-3">
            @foreach($permittedModules as $key => $mod)
                <a href="{{ $mod['route'] }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
                    <div class="feature-box-icon">
                        <x-icon :name="$mod['icon']" />
                    </div>
                    <h3>{{ $mod['name'] }}</h3>
                    <p>{{ $mod['desc'] }}</p>
                </a>
            @endforeach
        </div>
    @else
        <div class="admin-card" style="text-align: center; padding: 56px 24px; background: var(--white); border: 1px solid var(--line); border-radius: 20px; box-shadow: var(--shadow);">
            <div style="font-size: 44px; margin-bottom: 14px;">🔒</div>
            <h3 style="font-family: 'Manrope', sans-serif; font-size: 20px; font-weight: 800; color: var(--ink); margin-bottom: 8px;">
                No Active Module Access Granted
            </h3>
            <p style="color: var(--muted); max-width: 520px; margin: 0 auto; line-height: 1.6; font-size: 14px;">
                Your administrator account is securely authenticated, but currently has no module permissions enabled. Please ask a supervising administrator in Roles / Accounts Management to activate permissions for your account.
            </p>
        </div>
    @endif
@endsection
