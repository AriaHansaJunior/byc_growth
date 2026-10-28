@extends('layouts.app')

@section('title', 'Admin Sign In — BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
<div class="auth-shell">
    <div class="auth-card auth-card--admin">
        {{-- Brand / Header --}}
        <div class="auth-header">
            <div class="auth-brand-wrap">
                <x-brand :compact="true" />
            </div>
            <h1>Admin Sign In</h1>
        </div>

        {{-- Warning Callout (Polished English per Spec) --}}
        <div class="admin-login-warning-box">
            <div class="warning-header" style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <span class="warning-icon" style="font-size: 16px;">🛡️</span>
                <strong style="color: var(--forest); font-size: 13px; letter-spacing: 0.04em; text-transform: uppercase;">WARNING! THIS IS THE ADMIN AREA.</strong>
            </div>
            <p style="margin: 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                If you are an administrator, make sure you know your registered email and password before continuing.
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
                    @foreach (array_unique($errors->all()) as $error)
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
                <label for="email" class="form-label">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', old('login')) }}"
                    required
                    autofocus
                    autocomplete="email"
                    class="form-input @if($errors->has('email') || $errors->has('login')) input-invalid @enderror"
                    placeholder="Enter admin email address"
                >
            </div>

            <div class="form-group form-group--last">
                <label for="admin-password" class="form-label">Password</label>
                <div class="password-field-wrapper">
                    <input
                        type="password"
                        id="admin-password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="form-input @error('password') input-invalid @enderror"
                        placeholder="••••••••"
                        style="width: 100%; padding-right: 42px;"
                    >
                    <button
                        type="button"
                        class="btn-toggle-password"
                        data-target="admin-password"
                        title="Show password"
                        aria-label="Show password"
                    >
                        <span class="icon-eye"><x-icon name="eye" /></span>
                        <span class="icon-eye-off" style="display: none;"><x-icon name="eye-off" /></span>
                    </button>
                </div>
            </div>

            <button type="submit" class="button button-primary" style="width: 100%;">
                Login 
            </button>
        </form>
    </div>
</div>
@endsection
