@extends('layouts.app')

@section('title', 'Session Expired — BYC GROWTH')

@section('content')
<div class="page-shell">
    <div class="placeholder-card" style="text-align: center; padding: 64px 24px; max-width: 600px; margin: 40px auto;">
        <span class="placeholder-badge" style="background: #fdf0ee; color: var(--red); border: 1px solid var(--red);">
            Error 419
        </span>
        <h1 style="font-family: 'Manrope', sans-serif; font-size: 32px; font-weight: 800; color: var(--ink); margin: 16px 0 12px;">
            Session Expired
        </h1>
        <p style="color: var(--muted); line-height: 1.6; margin-bottom: 28px;">
            Your session has timed out due to inactivity. Please refresh the page or sign in again to continue.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="javascript:location.reload();" class="button button-primary">
                Refresh Page
            </a>
            <a href="{{ route('home') }}" class="button button-secondary">
                Return to Homepage
            </a>
        </div>
    </div>
</div>
@endsection
