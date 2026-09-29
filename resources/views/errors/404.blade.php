@extends('layouts.app')

@section('title', 'Page Not Found — BYC GROWTH')

@section('content')
<div class="page-shell">
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>
    </nav>

    <div class="placeholder-card" style="text-align: center; padding: 64px 24px; max-width: 600px; margin: 40px auto;">
        <span class="placeholder-badge" style="background: var(--paper); color: var(--forest); border: 1px solid var(--line);">
            Error 404
        </span>
        <h1 style="font-family: 'Manrope', sans-serif; font-size: 32px; font-weight: 800; color: var(--ink); margin: 16px 0 12px;">
            Resource Not Found
        </h1>
        <p style="color: var(--muted); line-height: 1.6; margin-bottom: 28px;">
            The page or resource you are looking for does not exist, has been removed, or is temporarily unavailable.
        </p>
        <a href="{{ route('home') }}" class="button button-primary">
            Return to Homepage
        </a>
    </div>
</div>
@endsection
