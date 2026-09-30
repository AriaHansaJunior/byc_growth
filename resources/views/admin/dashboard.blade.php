@extends('layouts.admin')

@section('title', 'Admin Dashboard — BYC GROWTH')

@section('content')
    {{-- Flash / Welcome --}}
    <div class="admin-welcome-card">
        <div>
            <span class="eyebrow" style="color: var(--lime);">Authentication & Authorization Active</span>
            <h1>Welcome back, {{ $admin->username ?? $admin->name }}</h1>
            <p>
                You are securely authenticated as an administrator. Administration God Mode is active for data and content management.
            </p>
        </div>
        <div class="admin-stamp">
            <strong>ADMIN<br>SECURED</strong>
        </div>
    </div>

    {{-- Protected Modules Overview --}}
    <div class="admin-section-heading">
        <h2>Protected Management Modules</h2>
        <p>Access your administrator management tools and community data portals.</p>
    </div>

    <div class="feature-grid-3">
        {{-- 1. Dashboard / Overview --}}
        <a href="{{ route('admin.dashboard') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block; border-left: 4px solid var(--forest);">
            <div class="feature-box-icon">
                <x-icon name="chart" />
            </div>
            <h3>Dashboard</h3>
            <p>Centralized administration command center, system health, and navigation hub.</p>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Current &bull; Control Center</span>
        </a>

        {{-- 2. Homepage Management --}}
        <a href="{{ route('admin.homepage') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
            <div class="feature-box-icon">
                <x-icon name="home" />
            </div>
            <h3>Homepage</h3>
            <p>Manage slideshow images, highlights, announcements, and editable showcase data.</p>
            <span class="module-status-badge" style="background: var(--cream); color: var(--forest); border: 1px solid var(--line);">Manage Homepage &rarr;</span>
        </a>

        {{-- 3. Members Management --}}
        <a href="{{ route('admin.members') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
            <div class="feature-box-icon">
                <x-icon name="users" />
            </div>
            <h3>Members</h3>
            <p>Add/edit member profiles, upload photos, and maintain confidential birthday records.</p>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; View Roster &rarr;</span>
        </a>

        {{-- 4. Activities Management --}}
        <a href="{{ route('admin.activities') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
            <div class="feature-box-icon">
                <x-icon name="sparkles" />
            </div>
            <h3>Activities</h3>
            <p>Record community events, publish fellowship recaps, and upload event galleries.</p>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Manage Activities &rarr;</span>
        </a>

        {{-- 5. Games Center --}}
        <a href="{{ route('admin.games') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
            <div class="feature-box-icon">
                <x-icon name="gamepad" />
            </div>
            <h3>Games</h3>
            <p>Universal Game System: Guess Me! & BYC Growth 100 rounds, questions, and team configs.</p>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Open Games &rarr;</span>
        </a>

        {{-- 6. Birthday Wishes --}}
        <a href="{{ route('admin.birthday-wishes') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
            <div class="feature-box-icon">
                <x-icon name="heart" />
            </div>
            <h3>Birthday Wishes</h3>
            <p>Full archive browse across all members, all years, and letter audit privileges.</p>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Open Archive &rarr;</span>
        </a>

        {{-- 7. Cash Management --}}
        <a href="{{ route('admin.cash-management') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
            <div class="feature-box-icon">
                <x-icon name="cash" />
            </div>
            <h3>Cash Management</h3>
            <p>Transaction ledger, member shortcuts, transfer proof review, and treasury total.</p>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Open Ledger &rarr;</span>
        </a>

        {{-- 8. Roles / Accounts --}}
        <a href="{{ route('admin.roles') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
            <div class="feature-box-icon">
                <x-icon name="shield" />
            </div>
            <h3>Roles / Accounts</h3>
            <p>Manage user and admin accounts, system credentials, usernames, and access roles.</p>
            <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Manage Accounts &rarr;</span>
        </a>
    </div>
@endsection
