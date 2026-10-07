@extends('layouts.app')

@section('title', 'Sign In — BYC GROWTH')
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
            <p>Enter your email or username to access your account.</p>
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
        <form method="POST" action="{{ route('login.submit') }}" class="auth-form" novalidate>
            @csrf
            @if(request('redirect') || old('redirect'))
                <input type="hidden" name="redirect" value="{{ request('redirect', old('redirect')) }}">
            @endif

            <div class="form-group">
                <label for="login" class="form-label">Email / Username</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    value="{{ old('login', old('email')) }}"
                    required
                    autofocus
                    autocomplete="username"
                    class="form-input @if($errors->has('login') || $errors->has('email')) input-invalid @enderror"
                    placeholder="Enter email or username"
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
                Sign In 
            </button>
        </form>

        {{-- Back Navigation --}}
        <div class="auth-footer">
            <a href="{{ route('home') }}" class="back-link-subtle">
             Return to Website
            </a>
        </div>
    </div>
</div>
@endsection
