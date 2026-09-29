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
        <div>
            <span class="eyebrow">Interactive Gaming Arena</span>
            <h1>BYC Game Center</h1>
            <p>
                Two games, one team spirit. Test teamwork, solve visual clues, discover top survey answers, and celebrate every growth milestone together.
            </p>
            <div class="game-center-hero-actions">
                <a href="{{ route('game.guess-me') }}" class="button button-primary">
                    Play Now <x-icon name="arrow" />
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
    <section class="game-center-selection">
        <div class="destinations-heading" style="margin-bottom: 28px;">
            <div>
                <span class="eyebrow" style="color: var(--forest);">Game Selection</span>
                <h2 style="color: var(--ink);">Choose Your Challenge</h2>
            </div>
            <p style="color: var(--muted);">Start by guessing pictures or rack up survey points with your team.</p>
        </div>

        <div class="game-center-grid">
            <a href="{{ route('game.guess-me') }}" class="gc-card gc-guess">
                <span class="gc-icon">?</span>
                <div class="gc-copy">
                    <small>Game 01</small>
                    <strong>Guess Me!</strong>
                    <em>Decode secret words from visual clues and character slots.</em>
                </div>
                <div class="gc-arrow">
                    <x-icon name="arrow" />
                </div>
            </a>

            <a href="{{ route('game.growth-100') }}" class="gc-card gc-growth">
                <span class="gc-icon">100</span>
                <div class="gc-copy">
                    <small>Game 02</small>
                    <strong>BYC Growth 100</strong>
                    <em>Discover top survey answers and collect up to 100 points per round.</em>
                </div>
                <div class="gc-arrow">
                    <x-icon name="arrow" />
                </div>
            </a>
        </div>
    </section>

    {{-- Overall Scoreboard --}}
    <section class="gc-scoreboard">
        <div>
            <span class="eyebrow">Overall Standings</span>
            <h2 style="margin: 0; font: 800 28px 'Manrope', sans-serif;">Team Scoreboard</h2>
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
