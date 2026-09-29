@extends('layouts.app')

@section('title', 'Members Directory — BYC GROWTH')

@section('content')
<div class="page-shell">
    {{-- Back Navigation --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>
    </nav>

    {{-- Page Header --}}
    <header class="page-header">
        <span class="eyebrow">Community</span>
        <h1>Members Directory</h1>
        <p>
            Connect with our fellowship members, cell group coordinators, and youth leadership team across BYC Growth.
        </p>
    </header>

    {{-- Structural Foundation Card --}}
    <div class="placeholder-card">
        <span class="placeholder-badge">
            <x-icon name="info" /> Structural Foundation &bull; Scope 1
        </span>
        <h2>Members Portal Architecture</h2>
        <p>
            The Members Directory module is currently in its structural stage. In subsequent phases, this section will host member rosters, small group affiliations, attendance check-ins, and profile management for the Bethany Youth Community fellowship.
        </p>

        <div class="feature-grid-3">
            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="users" />
                </div>
                <h3>Fellowship Directory</h3>
                <p>Searchable roster of active youth members, birthdays, and contact channels for cell group leaders.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="sparkles" />
                </div>
                <h3>Cell Groups</h3>
                <p>Structured groups for weekly Bible study, mentoring, and localized fellowship clusters in Surabaya.</p>
            </div>

            <div class="feature-box">
                <div class="feature-box-icon">
                    <x-icon name="trophy" />
                </div>
                <h3>Leadership Board</h3>
                <p>Ministry coordinators, worship leaders, creative team, and pastoral mentors serving the community.</p>
            </div>
        </div>
    </div>
</div>
@endsection
