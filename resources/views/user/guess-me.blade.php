@extends('layouts.app')

@section('title', 'Game 01 — Guess Me! | BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
@php
    $currentRoundIndex = max(0, min(count($rounds) - 1, (int) ($game1State['current_round'] ?? 0)));
    $currentRound = $rounds[$currentRoundIndex] ?? null;
    $roundId = (string) ($currentRound['id'] ?? 1);
    $isRevealed = (bool) ($game1State['revealed'][$roundId] ?? false);
@endphp

<div class="game-shell">
    {{-- Topbar: Brand & Live Scores Only --}}
    <header class="topbar">
        <a href="{{ route('game.center') }}" class="brand-button" aria-label="Back to Game Center" title="Back to Game Center">
            <x-brand compact="true" />
        </a>
        <div class="topbar-scores-wrap">
            <x-score-pair :teams="$teams" :compact="true" game="game1" />
        </div>
        <div>{{-- Spacer to balance topbar --}}</div>
    </header>

    {{-- Main Game Content --}}
    <main class="game-content">
        <div class="game-titlebar">
            <div>
                <span class="eyebrow">Game 01</span>
                <h1>Guess Me!</h1>
            </div>
            <div class="round-counter">
                <span>Round</span>
                <strong id="round-indicator">
                    {{ str_pad($currentRoundIndex + 1, 2, '0', STR_PAD_LEFT) }}
                    <em>/ {{ str_pad(count($rounds), 2, '0', STR_PAD_LEFT) }}</em>
                </strong>
            </div>
        </div>

        {{-- Guess Stage --}}
        @if ($currentRound)
            <section class="guess-stage">
                <div class="guess-image">
                    <img id="guess-image" src="{{ asset('assets/images/' . $currentRound['image']) }}" alt="Visual clue for active round">
                </div>
                <div class="guess-content">
                    <span class="clue-label">Clue for Teams</span>
                    <div class="answer-display {{ $isRevealed ? 'revealed' : '' }}" id="answer-box">
                        <small id="answer-status-label">{{ $isRevealed ? 'Correct Answer' : 'Complete the characters' }}</small>
                        <strong id="answer-text">{{ $isRevealed ? $currentRound['correct_answer'] : $currentRound['clue'] }}</strong>
                    </div>
                    <p id="answer-description">
                        {{ $isRevealed ? 'Correct answer revealed!' : 'Teams discuss and submit answers. The host reveals the answer when time is up.' }}
                    </p>
                    <button type="button" class="button {{ $isRevealed ? 'button-secondary' : 'button-primary' }}" id="btn-toggle-reveal">
                        {{ $isRevealed ? 'Hide Answer' : 'Reveal Answer' }}
                    </button>
                </div>
                <div class="point-badge">
                    <strong id="round-score-badge">{{ $currentRound['score'] }}</strong>
                    <span>Points</span>
                </div>
            </section>
        @else
            <div style="padding: 40px; text-align: center; background: white; border-radius: 20px;">
                <p style="color: var(--muted); font-size: 16px; margin: 0;">No rounds currently available for Guess Me!.</p>
            </div>
        @endif

        {{-- Round Navigation: Previous, Dots, Next (Centered, Text-only, NO arrows) --}}
        <div class="round-nav">
            <button type="button" class="button button-secondary" id="btn-prev-round" {{ $currentRoundIndex === 0 ? 'disabled' : '' }}>
                Previous
            </button>
            <div class="round-dots" id="round-dots-container">
                @foreach ($rounds as $idx => $r)
                    <span class="{{ $currentRoundIndex === $idx ? 'active' : '' }}" data-index="{{ $idx }}"></span>
                @endforeach
            </div>
            <button type="button" class="button button-primary" id="btn-next-round" {{ $currentRoundIndex >= count($rounds) - 1 ? 'disabled' : '' }}>
                Next
            </button>
        </div>
    </main>
</div>

<script>
    window.BYC_GAME1 = {
        rounds: @json($rounds),
        state: @json($game1State),
        teams: @json($teams),
        currentIndex: {{ $currentRoundIndex }},
        isRevealed: {{ $isRevealed ? 'true' : 'false' }},
        isAdmin: false
    };
</script>
@endsection
