@extends('layouts.app')

@section('title', 'BYC GROWTH — Growing in Faith, Purpose & Community')

@section('content')
<main class="home">
    {{-- Hero / Introduction Section --}}
    <section class="home-hero">
        <div class="home-copy">
            <span class="eyebrow">Bethany Youth Community</span>
            <x-brand />
            <h1>Growing together in faith,<em>purpose, and community.</em></h1>
            <p>
                BYC Growth is a fellowship empowering young believers to step out in faith, build authentic lifelong friendships, and walk purposefully together in Christ.
            </p>
            <div class="home-actions">
                <a href="#destinations" class="button button-primary">
                    Explore Destinations <x-icon name="arrow" />
                </a>
                <a href="{{ route('about') }}" class="button button-secondary">
                    <x-icon name="book" /> About BYC
                </a>
            </div>
        </div>

        {{-- Large Group Photo Area (Temporary dummy image, easy to replace with real photo) --}}
        <div class="hero-photo-wrap">
            <div class="hero-photo-frame">
                {{-- NOTE: Replace 'assets/images/group-photo-dummy.svg' with the official group photo file when ready --}}
                <img
                    id="hero-group-photo"
                    src="{{ asset('assets/images/group-photo-dummy.svg') }}"
                    alt="BYC Growth Fellowship Group Photo"
                    class="hero-group-photo"
                >
                <div class="photo-caption-badge">
                    <span /> BYC Community Fellowship &bull; Surabaya
                </div>
            </div>
        </div>
    </section>

    {{-- Scripture / Statement Section --}}
    <section class="scripture-section">
        <div class="scripture-card">
            <div class="scripture-content">
                <span class="scripture-eyebrow">Our Guiding Scripture</span>
                <blockquote>
                    &ldquo;For we walk by faith, not by sight&rdquo;
                </blockquote>
                <div class="scripture-reference">
                    2 Corinthians 5:7
                </div>
                <p class="scripture-subtext">
                    Our journey as young believers is anchored in trust, rooted in God's promises, and guided by a vision that transcends what is visible. Together, we grow with boldness and unity.
                </p>
            </div>
            <div class="scripture-stamp">
                <strong>Faith<br>&bull;<br>Growth</strong>
            </div>
        </div>
    </section>

    {{-- Destination Section --}}
    <section class="destinations-section" id="destinations">
        <div class="destinations-heading">
            <div>
                <span class="eyebrow">Explore BYC Growth</span>
                <h2>What do you want to go through?</h2>
            </div>
            <p>
                Choose your destination to access interactive gaming challenges, browse our fellowship members, or check financial stewardship records.
            </p>
        </div>

        <div class="destinations-grid">
            {{-- 1. Game Center --}}
            <a href="{{ route('game.center') }}" class="destination-card dest-game">
                <div class="destination-card-top">
                    <div class="destination-icon-box">
                        <x-icon name="gamepad" />
                    </div>
                    <span class="dest-number">01</span>
                </div>
                <div class="destination-card-body">
                    <span class="destination-badge">Interactive Games</span>
                    <h3>Game Center</h3>
                    <p>
                        Engage in team-building games with Guess Me! and BYC Growth 100, featuring real-time scores and interactive stages.
                    </p>
                </div>
                <div class="destination-card-footer">
                    <span class="dest-action-label">Enter Game Center</span>
                    <span class="dest-arrow-btn">
                        <x-icon name="arrow" />
                    </span>
                </div>
            </a>

            {{-- 2. Cash Management (Protected for Public Users) --}}
            @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('cash-management') }}" class="destination-card dest-cash">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="cash" />
                        </div>
                        <span class="dest-number">02</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-badge">Treasury Portal</span>
                        <h3>Cash Management</h3>
                        <p>
                            View transparent community financial accounts, transaction ledgers, and stewardship overviews for the fellowship.
                        </p>
                    </div>
                    <div class="destination-card-footer">
                        <span class="dest-action-label">Open Treasury</span>
                        <span class="dest-arrow-btn">
                            <x-icon name="arrow" />
                        </span>
                    </div>
                </a>
            @else
                <div class="destination-card dest-cash destination-card-disabled" aria-disabled="true" title="Contact the admin to view your cash contribution.">
                    <div class="destination-card-top">
                        <div class="destination-icon-box" style="opacity: 0.75;">
                            <x-icon name="cash" />
                        </div>
                        <span class="dest-number">02</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-badge" style="color: var(--gold);">Restricted &bull; Admin Access</span>
                        <h3>Cash Management</h3>
                        <p class="cash-restricted-msg">
                            Contact the admin to view your cash contribution.
                        </p>
                    </div>
                    <div class="destination-card-footer">
                        <span class="dest-action-label" style="opacity: 0.65; font-size: 13px;">
                            Access Restricted &bull; Contact Admin
                        </span>
                    </div>
                </div>
            @endif

            {{-- 3. Members --}}
            <a href="{{ route('members') }}" class="destination-card dest-members">
                <div class="destination-card-top">
                    <div class="destination-icon-box">
                        <x-icon name="users" />
                    </div>
                    <span class="dest-number">03</span>
                </div>
                <div class="destination-card-body">
                    <span class="destination-badge">Community Directory</span>
                    <h3>Members</h3>
                    <p>
                        Connect with fellowship members, discover cell group clusters, and meet the youth ministry leadership team.
                    </p>
                </div>
                <div class="destination-card-footer">
                    <span class="dest-action-label">Browse Directory</span>
                    <span class="dest-arrow-btn">
                        <x-icon name="arrow" />
                    </span>
                </div>
            </a>
        </div>
    </section>
</main>
@endsection
