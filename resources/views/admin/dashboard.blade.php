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
                    You are securely authenticated as an administrator. Scope 2 authentication and backend authorization are active.
                </p>
            </div>
            <div class="admin-stamp">
                <strong>ADMIN<br>SECURED</strong>
            </div>
        </div>

        {{-- Upcoming Protected Modules Overview --}}
        <div class="admin-section-heading">
            <h2>Protected Management Modules</h2>
            <p>Future management interfaces will be placed under the current administrator authorization shield.</p>
        </div>

        <div class="feature-grid-3">
            {{-- Game Management --}}
            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="gamepad" />
                </div>
                <h3>Game Management</h3>
                <p>Question editor, clue management, image uploads, and round configurations.</p>
                <span class="module-status-badge">Protected &bull; Coming in Later Scope</span>
            </div>

            {{-- Cash Management --}}
            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="cash" />
                </div>
                <h3>Cash Management</h3>
                <p>Ledger entries, transaction approvals, financial statements, and balance tracking.</p>
                <span class="module-status-badge">Protected &bull; Coming in Later Scope</span>
            </div>

            {{-- Members Management --}}
            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="users" />
                </div>
                <h3>Members Management</h3>
                <p>Member profile CRUD, cell group allocations, and contact records.</p>
                <span class="module-status-badge">Protected &bull; Coming in Later Scope</span>
            </div>
        </div>
    </main>
</div>
@endsection
