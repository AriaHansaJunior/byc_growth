@extends('layouts.app')

@section('title', 'BYC GROWTH — Growing in Faith, Purpose & Community')

@section('content')
<main class="home">
    {{-- Hero / Introduction Section --}}
    <section class="home-hero">
        <div class="home-copy">
            <h1>Growing together in faith,<em>purpose, and community.</em></h1>
            <p>
                BYC Growth is a fellowship empowering young believers to step out in faith, build authentic lifelong friendships, and walk purposefully together in Christ.
            </p>
        </div>

        {{-- Large Group Photo Area (Database-Backed Slideshow with Fallback) --}}
        <div class="hero-photo-wrap">
            <div class="hero-photo-frame" id="hero-photo-slideshow" data-fallback="{{ asset('assets/images/group-photo-dummy.svg') }}">
                <div class="hero-slides-track">
                    @if(isset($slides) && $slides->isNotEmpty())
                        @foreach($slides as $index => $slide)
                            <img
                                @if($index === 0) id="hero-group-photo" @endif
                                src="{{ $slide->image_url }}"
                                alt="{{ $slide->title ?: 'BYC Growth Fellowship Slide ' . ($index + 1) }}"
                                class="hero-group-photo hero-slide {{ $index === 0 ? 'active' : '' }}"
                                data-slide-index="{{ $index }}"
                            >
                        @endforeach
                    @else
                        <img
                            id="hero-group-photo"
                            src="{{ asset('assets/images/hero-slide-1.jpg') }}"
                            alt="BYC Growth Fellowship Group Photo 1"
                            class="hero-group-photo hero-slide active"
                            data-slide-index="0"
                        >
                    @endif
                </div>

                {{-- Interactive Slide Indicators --}}
                @if(isset($slides) && $slides->count() > 1)
                    <div class="hero-slide-indicators" aria-label="Slideshow Indicators">
                        @foreach($slides as $index => $slide)
                            <button type="button" class="slide-dot {{ $index === 0 ? 'active' : '' }}" data-slide-to="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
                        @endforeach
                    </div>
                @elseif(!isset($slides) || $slides->isEmpty())
                    <div class="hero-slide-indicators" aria-label="Slideshow Indicators">
                        <button type="button" class="slide-dot active" data-slide-to="0" aria-label="Slide 1"></button>
                        <button type="button" class="slide-dot" data-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" class="slide-dot" data-slide-to="2" aria-label="Slide 3"></button>
                    </div>
                @endif
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
                Choose your destination to access interactive gaming challenges, explore our fellowship activities, or browse our community members.
            </p>
        </div>

        <div class="destinations-grid {{ auth()->check() && auth()->user()->isAdmin() ? 'has-admin' : '' }}">
            {{-- 1. Activity --}}
            <a href="{{ route('activity') }}" class="destination-card dest-activity">
                <div class="destination-card-top">
                    <div class="destination-icon-box">
                        <x-icon name="calendar" />
                    </div>
                    <span class="dest-number">01</span>
                </div>
                <div class="destination-card-body">
                    <span class="destination-badge">Community Events</span>
                    <h3>Activity</h3>
                    <p>
                        Discover our fellowship gatherings, worship moments, community outreach, and special youth events.
                    </p>
                </div>
                <div class="destination-card-footer">
                    <span class="dest-action-label">Explore Activities</span>
                    <span class="dest-arrow-btn">
                        <x-icon name="arrow" />
                    </span>
                </div>
            </a>

            {{-- 2. Members --}}
            <a href="{{ route('members') }}" class="destination-card dest-members">
                <div class="destination-card-top">
                    <div class="destination-icon-box">
                        <x-icon name="users" />
                    </div>
                    <span class="dest-number">02</span>
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

            {{-- 3. Game Center --}}
            <a href="{{ route('game.center') }}" class="destination-card dest-game">
                <div class="destination-card-top">
                    <div class="destination-icon-box">
                        <x-icon name="gamepad" />
                    </div>
                    <span class="dest-number">03</span>
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

            {{-- 4. Cash Management (Visible Only to Admin Role) --}}
            @if(auth()->check() && auth()->user()->isAdmin())
                <a href="{{ route('cash-management') }}" class="destination-card dest-cash">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="cash" />
                        </div>
                        <span class="dest-number">04</span>
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
                <div class="destination-card-disabled" aria-disabled="true" style="display: none;" aria-hidden="true">
                    Cash Management
                    Contact the admin to view your cash contribution.
                </div>
            @endif
        </div>
    </section>
</main>
@endsection
