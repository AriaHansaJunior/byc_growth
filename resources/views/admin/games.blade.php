@extends('layouts.admin')

@section('title', 'Games Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Interactive Gaming Arena</span>
        <h1>Games Management</h1>
        <p>
            The central administration arena for BYC Growth fellowship games. Manage team rosters, rounds, questions, and live game session states directly on the game arena.
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="{{ route('game.center') }}" class="button button-ghost button-sm" target="_blank">
            <x-icon name="gamepad" /> View Public Game Center &rarr;
        </a>
    </div>
</div>
@endsection

@section('content')
    {{-- Admin Controls Summary Strip --}}
    <div class="admin-controls-strip">
        <div class="admin-controls-badge">
            <x-icon name="sparkles" /> Universal Game System &bull; 2 Active Games &bull; {{ count($teams) }} Registered Teams
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('game.final') }}" class="button button-ghost button-sm" target="_blank">
                Final Results &rarr;
            </a>
        </div>
    </div>

    {{-- Game Center Hero (Identical Visual Foundation to User Page + Admin Controls) --}}
    <section class="game-center-hero" style="margin-bottom: 40px;">
        <div class="game-center-hero-content">
            <span class="eyebrow">Interactive Gaming Arena</span>
            <h1>BYC Game Center</h1>
            <p>
                The central arena for BYC Growth fellowship games. Manage game configurations below, test rounds, inspect scoring logic, or launch live interactive sessions.
            </p>
            <div class="game-center-hero-actions">
                <a href="#game-selection" class="button button-primary">
                    Manage Games <x-icon name="arrow" />
                </a>
                <a href="{{ route('game.center') }}" class="button button-secondary" target="_blank">
                    <x-icon name="book" /> Live Player View
                </a>
                <button
                    type="button"
                    class="button button-ghost"
                    style="color: var(--muted);"
                    data-admin-confirm="Are you sure you want to reset all game scores and active round states?"
                    data-confirm-title="Reset Game Session"
                    data-confirm-body="This action resets cumulative scores for Guess Me! and Growth 100 back to zero. Questions and uploaded images remain safe."
                    data-confirm-btn="Yes, Reset Game"
                >
                    Reset Gameplay
                </button>
            </div>
        </div>

        <div class="game-center-art-frame">
            <img src="{{ asset('assets/images/BYC_Growth.jpg') }}" alt="BYC Growth Team Games">
            <div class="game-center-art-badge">
                <span class="live-dot" aria-hidden="true"></span> 2 Games Active
            </div>
        </div>
    </section>

    {{-- Game Cards Selection (Using User gc-card Grid Structure + Admin Controls) --}}
    <section class="game-center-selection" id="game-selection" style="margin-bottom: 40px;">
        <div class="destinations-heading" style="margin-bottom: 28px;">
            <div>
                <span class="eyebrow" style="color: var(--forest);">Game Selection</span>
                <h2 style="color: var(--ink);">Choose Your Challenge</h2>
            </div>
            <p style="color: var(--muted);">Directly control each interactive game. Host editors, survey questions, and scoreboards are managed here.</p>
        </div>

        <div class="game-center-grid">
            @foreach($games as $game)
                @php
                    $routeExists = !empty($game['route']) && \Illuminate\Support\Facades\Route::has($game['route']);
                    $playUrl = $routeExists ? route($game['route']) : '#';
                @endphp
                <article class="gc-card gc-card--{{ $game['theme'] ?? 'forest' }}" id="admin-game-card-{{ $game['id'] }}">
                    <div class="gc-card-header">
                        <div class="gc-meta-tags">
                            <span class="gc-order-badge">Game {{ $game['order'] }}</span>
                            <span class="gc-category-tag">{{ $game['tag'] }}</span>
                        </div>
                        <div class="gc-icon-badge" aria-hidden="true">
                            @if($game['id'] === 'guess-me') 🧩 @else 📊 @endif
                        </div>
                    </div>

                    <div class="gc-card-body">
                        <h3 class="gc-card-title">{{ $game['title'] }}</h3>
                        <p class="gc-card-desc">{{ $game['description'] }}</p>

                        <div style="background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; margin-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--ink);">Configured Rounds</span>
                            <span style="font-size: 13px; font-weight: 800; color: var(--forest);">10 Live Rounds</span>
                        </div>
                    </div>

                    <div class="gc-card-footer" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="{{ $playUrl }}" class="button button-primary gc-play-btn" style="flex: 1;" target="_blank">
                            Launch Live &rarr;
                        </a>
                        <a href="{{ $playUrl }}" class="button button-secondary" style="font-size: 13px;" target="_blank" title="Open game host panel">
                            ✎ Host Panel
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Overall Scoreboard Section (Identical Visual Foundation to User Page) --}}
    <section class="gc-scoreboard" style="margin-bottom: 24px;">
        <div class="gc-scoreboard-info">
            <span class="eyebrow">Overall Standings</span>
            <h2 style="margin: 0; font: 800 28px 'Manrope', sans-serif;">Team Scoreboard</h2>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 14px;">Combined cumulative scores across all games in live database.</p>
        </div>
        <x-score-pair :scores="['red' => $finalScores['final_red'] ?? 0, 'blue' => $finalScores['final_blue'] ?? 0]" />
        <a href="{{ route('game.final') }}" class="button button-ghost" target="_blank">
            View Final Results <x-icon name="arrow" />
        </a>
    </section>
@endsection
