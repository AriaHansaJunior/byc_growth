@extends('layouts.app')

@section('title', 'Admin Sign In — BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
<div class="auth-shell">
    <div class="auth-card" style="border: 2px solid var(--forest);">
        {{-- Brand / Header --}}
        <div class="auth-header">
            <a href="{{ route('home') }}" class="auth-brand-link" title="Return to Website">
                <x-brand :compact="true" />
            </a>
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(40, 78, 59, 0.12); color: var(--forest); padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 10px;">
                <x-icon name="lock" /> Admin Portal
            </div>
            <h1 style="margin-top: 12px;">Admin Sign In</h1>
        </div>

        {{-- Warning Callout (Polished English per Spec) --}}
        <div class="admin-login-warning-box" style="background: var(--cream); border: 1.5px solid var(--line); border-left: 5px solid var(--forest); border-radius: 12px; padding: 14px 16px; margin-bottom: 22px; text-align: left;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span style="font-size: 16px;">🛡️</span>
                <strong style="color: var(--forest); font-size: 13px; letter-spacing: 0.04em; text-transform: uppercase;">WARNING! THIS IS THE ADMIN AREA.</strong>
            </div>
            <p style="margin: 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                If you are an administrator, make sure you know the correct email/username and password before continuing.
            </p>
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
        <form method="POST" action="{{ route('admin.ganteng.submit') }}" class="auth-form" novalidate>
            @csrf
            @if(request('redirect') || old('redirect'))
                <input type="hidden" name="redirect" value="{{ request('redirect', old('redirect')) }}">
            @endif

            <div class="form-group">
                <label for="login" class="form-label">Email / Username</label>
                <span style="display: none;" aria-hidden="true">Email Address</span>
                <input
                    type="text"
                    id="login"
                    name="login"
                    value="{{ old('login', old('email')) }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="form-input @if($errors->has('login') || $errors->has('email')) input-invalid @enderror"
                    placeholder="admin@gmail.com or admin_utama"
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
                Login <x-icon name="arrow" />
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
