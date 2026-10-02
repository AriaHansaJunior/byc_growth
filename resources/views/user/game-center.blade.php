@extends('layouts.app')

@section('title', 'Game Center — BYC GROWTH')

@section('content')
<div class="game-center-hub">
    {{-- Back Navigation --}}
    <nav class="back-nav-bar" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="back-nav-btn">
            <x-icon name="arrow-left" /> Back to Home
        </a>
    </nav>

    {{-- Game Center Hero --}}
    <section class="game-center-hero">
        <div class="game-center-hero-content">
            <span class="eyebrow">Interactive Gaming Arena</span>
            <h1>BYC Game Center</h1>
            <p>
                The central arena for BYC Growth fellowship games. Choose a challenge below to launch an active session, test team synergy, and celebrate every growth milestone together.
            </p>
            <div class="game-center-hero-actions">
                <a href="#game-selection" class="button button-primary">
                    Choose a Game <x-icon name="arrow" />
                </a>
                <button type="button" class="button button-secondary" id="btn-how-to-play">
                    <x-icon name="book" /> Rules & How to Play
                </button>
                <button type="button" class="button button-ghost" id="btn-reset-game" style="color: var(--muted);" title="Reset scores and gameplay rounds">
                    Reset Game
                </button>
            </div>
        </div>

        <div class="game-center-art-frame">
            <img src="{{ asset('assets/images/BYC_Growth.jpg') }}" alt="BYC Growth Team Games">
        </div>
    </section>

    {{-- Game Cards Grid --}}
    <section class="game-center-selection" id="game-selection">
        <div class="destinations-heading" style="margin-bottom: 32px;">
            <div>
                <span class="eyebrow" style="color: var(--forest);">Game Selection</span>
                <h2 style="color: var(--ink);">Choose Your Challenge</h2>
            </div>
            <p style="color: var(--muted);">Select a game below to launch. Each game is hosted live with real-time scoring and team competition.</p>
        </div>

        <div class="game-center-grid">
            @foreach($games as $game)
                @php
                    $routeExists = !empty($game['route']) && \Illuminate\Support\Facades\Route::has($game['route']);
                    $isAvailable = ($game['status'] ?? 'available') === 'available' && $routeExists;
                    $playUrl = $isAvailable ? route($game['route']) : '#';
                @endphp
                <article class="gc-card gc-card--{{ $game['theme'] ?? 'forest' }} {{ !$isAvailable ? 'gc-card--disabled' : '' }}" id="game-card-{{ $game['id'] }}">
                    <div class="gc-card-header">
                        <div class="gc-meta-tags">
                            <span class="gc-order-badge">Game {{ $game['order'] }}</span>
                            <span class="gc-category-tag">{{ $game['tag'] }}</span>
                        </div>
                        <div class="gc-icon-badge" aria-hidden="true">
                            {{ $game['icon'] }}
                        </div>
                    </div>

                    <div class="gc-card-body">
                        <h3 class="gc-card-title">{{ $game['title'] }}</h3>
                        <p class="gc-card-desc">{{ $game['description'] }}</p>

                        @if(!empty($game['features']))
                            <ul class="gc-features-list" aria-label="Key features">
                                @foreach($game['features'] as $feature)
                                    <li><x-icon name="check" /> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="gc-card-footer">
                        @if($isAvailable)
                            <a href="{{ $playUrl }}" class="button button-primary gc-play-btn" id="btn-play-{{ $game['id'] }}">
                                Play Now <x-icon name="arrow" />
                            </a>
                        @else
                            <button type="button" class="button button-ghost gc-play-btn" disabled>
                                Coming Soon
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Overall Scoreboard --}}
    <section class="gc-scoreboard">
        <div class="gc-scoreboard-info">
            <span class="eyebrow">Overall Standings</span>
            <h2 style="margin: 0; font: 800 28px 'Manrope', sans-serif;">Team Scoreboard</h2>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 14px;">Combined cumulative scores across all games.</p>
        </div>
        <x-score-pair :scores="['red' => $finalScores['final_red'] ?? 0, 'blue' => $finalScores['final_blue'] ?? 0]" />
        <a href="{{ route('game.final') }}" class="button button-ghost">
            View Final Results <x-icon name="arrow" />
        </a>
    </section>

    {{-- How to Play Modal --}}
    @include('partials.how-to-play')

    {{-- Reset Confirmation Modal --}}
    <div class="modal-backdrop" id="modal-reset-game" style="display: none;">
        <section aria-label="Confirm Game Reset" class="info-modal" style="max-width: 480px; text-align: center; padding: 32px 28px;">
            <div style="font-size: 40px; margin-bottom: 12px;">⚠️</div>
            <h2 style="font: 800 24px 'Manrope', sans-serif; margin-bottom: 10px;">Reset Gameplay?</h2>
            <p style="color: var(--muted); font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
                This action will reset scores for Game 1 and Game 2, active rounds, reveal status, and strikes back to zero.<br>
                <strong>Questions and images will not be deleted.</strong>
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="button button-secondary" id="btn-cancel-reset">Cancel</button>
                <button type="button" class="button button-danger" id="btn-confirm-reset">Yes, Reset Game</button>
            </div>
        </section>
    </div>
</div>
@endsection
