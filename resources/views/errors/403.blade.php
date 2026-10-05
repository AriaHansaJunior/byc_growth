@extends('layouts.app')

@section('title', 'Access Forbidden — BYC GROWTH')

@section('content')
<div class="page-shell">


    <div class="placeholder-card" style="text-align: center; padding: 64px 24px; max-width: 600px; margin: 40px auto;">
        <span class="placeholder-badge" style="background: #fdf0ee; color: var(--red); border: 1px solid var(--red);">
            Error 403
        </span>
        <h1 style="font-family: 'Manrope', sans-serif; font-size: 32px; font-weight: 800; color: var(--ink); margin: 16px 0 12px;">
            Access Forbidden
        </h1>
        <p style="color: var(--muted); line-height: 1.6; margin-bottom: 28px;">
            You do not have authorization to view or manage this restricted resource. Administrator privileges are required.
        </p>
        <a href="{{ (Auth::check() && Auth::user()->isAdmin()) ? route('admin.dashboard') : route('home') }}" class="button button-primary">
            Return to Homepage
        </a>
    </div>
</div>
@endsection
