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
        <div class="admin-welcome-badge" title="Administrator Session Verified" aria-label="Administrator Session Verified">
            <svg class="admin-badge-svg" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                <defs>
                    <!-- Background Radial Glow -->
                    <radialGradient id="badgeGlow" cx="60" cy="60" r="58" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#bad259" stop-opacity="0.25" />
                        <stop offset="65%" stop-color="#bad259" stop-opacity="0.06" />
                        <stop offset="100%" stop-color="#bad259" stop-opacity="0" />
                    </radialGradient>

                    <!-- Shield Gradient -->
                    <linearGradient id="shieldGrad" x1="60" y1="24" x2="60" y2="92" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#244d37" />
                        <stop offset="100%" stop-color="#11271b" />
                    </linearGradient>

                    <!-- Shield Metallic Rim Gradient -->
                    <linearGradient id="rimGrad" x1="33" y1="24" x2="87" y2="92" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#e2f58e" />
                        <stop offset="45%" stop-color="#bad259" />
                        <stop offset="100%" stop-color="#728c2e" />
                    </linearGradient>

                    <!-- Glass Reflection Highlight -->
                    <linearGradient id="glassGloss" x1="60" y1="27" x2="83" y2="60" gradientUnits="userSpaceOnUse">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.24" />
                        <stop offset="60%" stop-color="#ffffff" stop-opacity="0.03" />
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                    </linearGradient>

                    <!-- Soft Drop Shadow -->
                    <filter id="badgeShadow" x="-15%" y="-15%" width="140%" height="140%">
                        <feDropShadow dx="0" dy="6" stdDeviation="7" flood-color="#05120b" flood-opacity="0.45" />
                    </filter>
                </defs>

                <!-- Ambient Radial Glow -->
                <circle cx="60" cy="60" r="56" fill="url(#badgeGlow)" />

                <!-- Orbital Accent Rings -->
                <circle cx="60" cy="60" r="53" stroke="#bad259" stroke-opacity="0.26" stroke-width="1.2" stroke-dasharray="4 4" />
                <circle cx="60" cy="60" r="45" stroke="#ffffff" stroke-opacity="0.08" stroke-width="1" />

                <!-- Decorative Sparkle Stars & Nodes -->
                <path d="M92 26L93.5 30.5L98 32L93.5 33.5L92 38L90.5 33.5L86 32L90.5 30.5Z" fill="#bad259" opacity="0.9" />
                <path d="M25 73L26.2 76.8L30 78L26.2 79.2L25 83L23.8 79.2L20 78L23.8 76.8Z" fill="#bad259" opacity="0.75" />
                <circle cx="28" cy="38" r="1.5" fill="#bad259" opacity="0.55" />
                <circle cx="94" cy="74" r="1.5" fill="#bad259" opacity="0.5" />

                <!-- Layered Security Shield -->
                <g filter="url(#badgeShadow)">
                    <!-- Outer Shield Base with Rim -->
                    <path d="M60 24C73 28 83 29 87 34C87 56 80 77 60 92C40 77 33 56 33 34C37 29 47 28 60 24Z" 
                          fill="url(#shieldGrad)" 
                          stroke="url(#rimGrad)" 
                          stroke-width="2.2" 
                          stroke-linejoin="round" />

                    <!-- Inner Facet / Glass Specular Highlight -->
                    <path d="M60 27C71 30.5 79 31.5 83 36C83 54 77 72 60 86.5V27Z" 
                          fill="url(#glassGloss)" />

                    <!-- Concentric Inner Accent Contour -->
                    <path d="M60 32C70 35 77 36 81 40C81 57 75 71 60 82C45 71 39 57 39 40C43 36 50 35 60 32Z" 
                          stroke="#bad259" 
                          stroke-opacity="0.22" 
                          stroke-width="1" 
                          fill="none" />

                    <!-- Verified Security Checkmark -->
                    <path d="M50 56.5L57 63.5L71 48.5" 
                          stroke="#bad259" 
                          stroke-width="3.8" 
                          stroke-linecap="round" 
                          stroke-linejoin="round" />
                </g>
            </svg>
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
