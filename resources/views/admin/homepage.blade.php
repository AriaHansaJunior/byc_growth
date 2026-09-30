@extends('layouts.admin')

@section('title', 'Homepage Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Homepage Architecture</span>
        <h1>Homepage Management</h1>
        <p>
            Manage the hero slideshow, scripture statement, and destination portals directly within the live homepage layout.
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="{{ route('home') }}" class="button button-ghost button-sm" target="_blank">
            <x-icon name="arrow" /> View Public Homepage &rarr;
        </a>
    </div>
</div>
@endsection

@section('content')
    {{-- Admin Controls Summary Strip --}}
    <div class="admin-controls-strip">
        <div class="admin-controls-badge">
            <x-icon name="sparkles" /> Live Homepage God Mode &bull; Real-time Presentation &amp; Management Controls
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="button button-primary button-sm" id="btn-edit-slideshow">
                <x-icon name="plus" /> Manage Slides
            </button>
        </div>
    </div>

    {{-- Main Home Structure (Identical Visual Foundation to User Homepage + Admin Controls) --}}
    <main class="home" style="margin-bottom: 40px;">
        {{-- Hero / Introduction Section --}}
        <section class="home-hero">
            <div class="home-copy">
                <h1>Growing together in faith,<em>purpose, and community.</em></h1>
                <p>
                    BYC Growth is a fellowship empowering young believers to step out in faith, build authentic lifelong friendships, and walk purposefully together in Christ.
                </p>
                <div style="margin-top: 24px; display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="button button-secondary button-sm">
                        ✎ Edit Hero Copy
                    </button>
                    <small style="color: var(--muted); font-size: 13px;">Live header text active on public site</small>
                </div>
            </div>

            {{-- Large Group Photo Area (3-Photo Slideshow with Admin Controls) --}}
            <div class="hero-photo-wrap" style="position: relative;">
                <div style="position: absolute; top: 12px; right: 12px; z-index: 20; display: flex; gap: 8px;">
                    <span class="role-badge" style="background: rgba(255, 255, 255, 0.9); color: var(--forest); box-shadow: var(--shadow);">
                        📷 3 Hero Slides
                    </span>
                    <button type="button" class="button button-primary button-sm" style="box-shadow: var(--shadow);">
                        ✎ Edit Slideshow
                    </button>
                </div>

                <div class="hero-photo-frame" id="hero-photo-slideshow" data-fallback="{{ asset('assets/images/group-photo-dummy.svg') }}">
                    <div class="hero-slides-track">
                        <img
                            id="hero-group-photo"
                            src="{{ asset('assets/images/hero-slide-1.jpg') }}"
                            alt="BYC Growth Fellowship Group Photo 1"
                            class="hero-group-photo hero-slide active"
                            data-slide-index="0"
                        >
                        <img
                            src="{{ asset('assets/images/hero-slide-2.jpg') }}"
                            alt="BYC Growth Fellowship Group Photo 2"
                            class="hero-group-photo hero-slide"
                            data-slide-index="1"
                        >
                        <img
                            src="{{ asset('assets/images/hero-slide-3.jpg') }}"
                            alt="BYC Growth Fellowship Group Photo 3"
                            class="hero-group-photo hero-slide"
                            data-slide-index="2"
                        >
                    </div>

                    {{-- Interactive Slide Indicators --}}
                    <div class="hero-slide-indicators" aria-label="Slideshow Indicators">
                        <button type="button" class="slide-dot active" data-slide-to="0" aria-label="Slide 1"></button>
                        <button type="button" class="slide-dot" data-slide-to="1" aria-label="Slide 2"></button>
                        <button type="button" class="slide-dot" data-slide-to="2" aria-label="Slide 3"></button>
                    </div>
                </div>
            </div>
        </section>

        {{-- Scripture / Statement Section (Identical Visual Foundation + Admin Controls) --}}
        <section class="scripture-section" style="position: relative;">
            <div style="position: absolute; top: -14px; right: 24px; z-index: 10;">
                <button type="button" class="button button-secondary button-sm" style="box-shadow: var(--shadow);">
                    ✎ Edit Scripture Card
                </button>
            </div>
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

        {{-- Destination Section (Identical Visual Foundation + Domain Admin Quick Links) --}}
        <section class="destinations-section" id="destinations" style="margin-top: 48px;">
            <div class="destinations-heading">
                <div>
                    <span class="eyebrow">Explore BYC Growth</span>
                    <h2>Destination Portals</h2>
                </div>
                <p>
                    Manage each functional domain of the website directly from its live destination card.
                </p>
            </div>

            <div class="destinations-grid has-admin">
                {{-- 1. Activity --}}
                <a href="{{ route('admin.activities') }}" class="destination-card dest-activity">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="calendar" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Timeline & Events</span>
                        <h3>Community Activities</h3>
                        <p>Curate event timeline milestones, youth retreats, and photo galleries.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Activities Admin &rarr;</span>
                    </div>
                </a>

                {{-- 2. Member --}}
                <a href="{{ route('admin.members') }}" class="destination-card dest-member">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="users" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Community Roster</span>
                        <h3>Members Directory</h3>
                        <p>Manage member profiles, confidential birthdates, and photo records.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Members Admin &rarr;</span>
                    </div>
                </a>

                {{-- 3. Game Center --}}
                <a href="{{ route('admin.games') }}" class="destination-card dest-game">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="sparkles" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Interactive Gaming</span>
                        <h3>Game Center</h3>
                        <p>Control teams, survey answers, live round points, and cumulative scores.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Games Admin &rarr;</span>
                    </div>
                </a>

                {{-- 4. Cash Management --}}
                <a href="{{ route('admin.cash-management') }}" class="destination-card dest-cash">
                    <div class="destination-card-top">
                        <div class="destination-icon-box">
                            <x-icon name="cash" />
                        </div>
                        <span class="destination-badge">Manage Domain &rarr;</span>
                    </div>
                    <div class="destination-card-body">
                        <span class="destination-tag">Financial Stewardship</span>
                        <h3>Cash Management</h3>
                        <p>Record member contributions, verify transfer proofs, and track treasury balance.</p>
                    </div>
                    <div class="destination-card-footer">
                        <span>Open Cash Admin &rarr;</span>
                    </div>
                </a>
            </div>
        </section>
    </main>
@endsection
