@extends('layouts.app')

@section('title', 'Server Error — BYC GROWTH')

@section('content')
<div class="page-shell">


    <div class="placeholder-card" style="text-align: center; padding: 64px 24px; max-width: 600px; margin: 40px auto;">
        <span class="placeholder-badge" style="background: var(--paper); color: var(--muted); border: 1px solid var(--line);">
            Error 500
        </span>
        <h1 style="font-family: 'Manrope', sans-serif; font-size: 32px; font-weight: 800; color: var(--ink); margin: 16px 0 12px;">
            Something Went Wrong
        </h1>
        <p style="color: var(--muted); line-height: 1.6; margin-bottom: 28px;">
            An unexpected error occurred while processing your request. Please try again later or contact the fellowship administrator.
        </p>
        <a href="{{ route('home') }}" class="button button-primary">
            Return to Homepage
        </a>
    </div>
</div>
@endsection
