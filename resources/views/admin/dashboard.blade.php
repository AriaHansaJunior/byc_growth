@extends('layouts.app')

@section('title', 'Admin Dashboard — BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
<div class="admin-shell">
    {{-- Admin Topbar --}}
    <header class="admin-topbar">
        <div class="admin-brand-wrap">
            <a href="{{ route('home') }}" class="brand-link" title="Visit Public Website">
                <x-brand :compact="true" />
            </a>
            <span class="admin-portal-badge">Admin Portal</span>
        </div>

        <div class="admin-user-nav">
            <div class="admin-user-info">
                <strong>{{ $admin->name }}</strong>
                <small>{{ $admin->email }} &bull; <span class="role-badge">{{ ucfirst($admin->role) }}</span></small>
            </div>

            <a href="{{ route('home') }}" class="button button-ghost button-sm">
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

    {{-- Main Admin Content --}}
    <main class="admin-container">
        {{-- Flash / Welcome --}}
        <div class="admin-welcome-card">
            <div>
                <span class="eyebrow" style="color: var(--lime);">Authentication & Authorization Active</span>
                <h1>Welcome back, {{ $admin->name }}</h1>
                <p>
                    You are securely authenticated as an administrator. All Scope 8 management modules are active and protected.
                </p>
            </div>
            <div class="admin-stamp">
                <strong>ADMIN<br>SECURED</strong>
            </div>
        </div>

        {{-- Protected Modules Overview --}}
        <div class="admin-section-heading">
            <h2>Protected Management Modules</h2>
            <p>Access your administrator management tools and community record portals.</p>
        </div>

        <div class="feature-grid-3">
            {{-- Role & Account Management --}}
            <a href="{{ route('admin.roles') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
                <div class="feature-box-icon">
                    <x-icon name="users" />
                </div>
                <h3>Role & Accounts</h3>
                <p>Manage user and admin accounts, system credentials, and access roles.</p>
                <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Manage Accounts &rarr;</span>
            </a>

            {{-- Cash Management --}}
            <a href="{{ route('cash-management') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
                <div class="feature-box-icon">
                    <x-icon name="cash" />
                </div>
                <h3>Cash Management</h3>
                <p>Transaction ledger, member shortcuts, transfer proof review, and treasury total.</p>
                <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Open Ledger &rarr;</span>
            </a>

            {{-- Members Management --}}
            <a href="{{ route('members') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
                <div class="feature-box-icon">
                    <x-icon name="users" />
                </div>
                <h3>Members Management</h3>
                <p>Add/edit member profiles, upload photos, and maintain internal birthday records.</p>
                <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; View Roster &rarr;</span>
            </a>

            {{-- Activity Management --}}
            <a href="{{ route('activity') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
                <div class="feature-box-icon">
                    <x-icon name="sparkles" />
                </div>
                <h3>Activity & Events</h3>
                <p>Record community events, publish fellowship recaps, and upload photo galleries.</p>
                <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Manage Activities &rarr;</span>
            </a>

            {{-- Game Management --}}
            <a href="{{ route('game.center') }}" class="feature-box" style="text-decoration: none; color: inherit; display: block;">
                <div class="feature-box-icon">
                    <x-icon name="gamepad" />
                </div>
                <h3>Game Center</h3>
                <p>Host tools for Guess Me! and BYC Growth 100, team configs, and score controls.</p>
                <span class="module-status-badge" style="background: var(--forest); color: var(--white);">Active &bull; Open Games &rarr;</span>
            </a>
        </div>
    </main>
</div>
@endsection
