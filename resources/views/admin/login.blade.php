@extends('layouts.app')

@section('title', 'Admin Sign In — BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
<div class="auth-shell">
    <div class="auth-card">
        {{-- Brand / Header --}}
        <div class="auth-header">
            <a href="{{ route('home') }}" class="auth-brand-link" title="Return to Website">
                <x-brand :compact="true" />
            </a>
            <h1>Sign In</h1>
            <p>Enter your administrator credentials to continue.</p>
        </div>

        {{-- Flash / Status Messages --}}
        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Sign In Form --}}
        <form method="POST" action="{{ route('admin.login.submit') }}" class="auth-form" novalidate>
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="form-input @error('email') input-invalid @enderror"
                    placeholder="admin_byc@gmail.com"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="form-input @error('password') input-invalid @enderror"
                    placeholder="••••••••"
                >
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="remember-checkbox" style="display: inline-flex; align-items: center; gap: 8px; font-size: 14px; color: var(--muted); cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} style="accent-color: var(--forest); width: 16px; height: 16px;">
                    Remember this device
                </label>
            </div>

            <button type="submit" class="button button-primary" style="width: 100%;">
                Sign In <x-icon name="arrow" />
            </button>
        </form>

        {{-- Back Navigation --}}
        <div class="auth-footer">
            <a href="{{ route('home') }}" class="back-link-subtle">
                <x-icon name="arrow-left" /> Return to Website
            </a>
        </div>
    </div>
</div>
@endsection
