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
            <p>Enter your email to access your account.</p>
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
                    placeholder="Enter your email"
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-field-wrapper">
                    <input
                        type="password"
                        id="password"
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
                        data-target="password"
                        title="Show password"
                        aria-label="Show password"
                    >
                        <span class="icon-eye"><x-icon name="eye" /></span>
                        <span class="icon-eye-off" style="display: none;"><x-icon name="eye-off" /></span>
                    </button>
                </div>
            </div>

            <div class="form-group form-group--last">
                <label class="remember-checkbox">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} class="remember-input">
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
