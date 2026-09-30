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
    {{-- Topbar --}}
    <header class="topbar">
        <a href="{{ route('game.center') }}" class="brand-button" aria-label="Back to Game Center" title="Back to Game Center">
            <x-brand compact="true" />
        </a>
        <x-score-pair :teams="$teams" :compact="true" game="game1" />
        <div style="display: flex; align-items: center; gap: 10px;">
            @if(Auth::check() && Auth::user()->isAdmin())
                <div class="live-pill" style="background: rgba(186, 210, 89, 0.2); color: var(--forest-dark); border-color: var(--lime);">
                    <span style="background: var(--forest);"></span> Host: {{ Auth::user()->name }}
                </div>
            @endif
        </div>
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
                    <span>Visual Clue</span>
                </div>
                <div class="guess-content">
                    <span class="clue-label">Clue for Teams</span>
                    <div class="answer-display {{ $isRevealed ? 'revealed' : '' }}" id="answer-box">
                        <small id="answer-status-label">{{ $isRevealed ? 'Correct Answer' : 'Complete the characters' }}</small>
                        <strong id="answer-text">{{ $isRevealed ? $currentRound['correct_answer'] : $currentRound['clue'] }}</strong>
                    </div>
                    <p id="answer-description">
                        {{ $isRevealed ? 'Correct answer revealed. Click a team card below to award the round points.' : 'Teams discuss and submit answers. The host reveals the answer when time is up.' }}
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
                <p>No rounds available.</p>
                @if(Auth::check() && Auth::user()->isAdmin())
                    <button type="button" class="button button-primary" id="btn-open-editor-empty">+ Add Round</button>
                @endif
            </div>
        @endif

        {{-- Host Control Panel --}}
        <aside class="host-panel">
            <div class="host-heading">
                <div>
                    <span>Host Controls</span>
                    <strong>Round Point Assignment & Team Scoring</strong>
                </div>
                <div style="display: flex; gap: 8px;">
                    @if(Auth::check() && Auth::user()->isAdmin())
                        <button type="button" class="button button-ghost" id="btn-open-teams-modal">
                            <x-icon name="user" /> Configure Teams
                        </button>
                        <button type="button" class="button button-ghost" id="btn-open-editor">
                            <x-icon name="edit" /> Edit Rounds
                        </button>
                    @endif
                </div>
            </div>

            {{-- Dynamic Host Teams Grid --}}
            <div class="host-teams-grid" id="host-teams-container">
                @foreach ($teams as $team)
                    @php
                        $isAwarded = ($currentRound && ($currentRound['awarded_team_id'] ?? null) == $team['id']);
                        $hasOtherAwarded = ($currentRound && ($currentRound['awarded_team_id'] ?? null) && ($currentRound['awarded_team_id'] ?? null) != $team['id']);
                    @endphp
                    <div class="host-team-card host-team-{{ $team['theme'] }} {{ $isAwarded ? 'is-selected' : '' }} {{ $hasOtherAwarded ? 'is-dimmed' : '' }}"
                         data-team-id="{{ $team['id'] }}"
                         id="host-team-card-{{ $team['id'] }}">
                        <div class="host-team-card-header">
                            <div class="host-team-title-wrap">
                                <span class="team-color-indicator" style="background: {{ $team['color'] }};"></span>
                                <strong class="host-team-label">{{ $team['name'] }}</strong>
                            </div>
                            <span class="award-status-pill" id="award-pill-{{ $team['id'] }}" style="{{ $isAwarded ? '' : 'display: none;' }}">
                                Recipient (+{{ $currentRound ? $currentRound['score'] : 0 }})
                            </span>
                        </div>
                        <div class="host-team-card-score">
                            <span class="host-team-game-score" id="host-team-score-{{ $team['id'] }}">{{ $team['scores']['game1'] ?? 0 }}</span>
                            <small>Total: <span id="host-team-total-{{ $team['id'] }}">{{ $team['total_score'] ?? 0 }}</span></small>
                        </div>
                        @if(Auth::check() && Auth::user()->isAdmin())
                            <div class="host-team-card-actions">
                                <button type="button" class="button button-ghost btn-score-action" data-team="{{ $team['id'] }}" data-amount="-5" title="Deduct 5 points">−5</button>
                                <button type="button" class="button button-ghost btn-score-action" data-team="{{ $team['id'] }}" data-amount="5" title="Add 5 points">+5</button>
                                <button type="button" class="button {{ $isAwarded ? 'button-primary' : 'button-secondary' }} btn-award-round" data-team="{{ $team['id'] }}" id="btn-award-{{ $team['id'] }}">
                                    {{ $isAwarded ? 'Points Awarded' : 'Award Round' }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </aside>

        {{-- Round Navigation --}}
        <div class="round-nav">
            <button type="button" class="button button-secondary" id="btn-prev-round" {{ $currentRoundIndex === 0 ? 'disabled' : '' }}>
                ← Previous
            </button>
            <div class="round-dots" id="round-dots-container">
                @foreach ($rounds as $idx => $r)
                    <span class="{{ $currentRoundIndex === $idx ? 'active' : '' }}" data-index="{{ $idx }}"></span>
                @endforeach
            </div>
            <button type="button" class="button button-primary" id="btn-next-round" {{ $currentRoundIndex >= count($rounds) - 1 ? 'disabled' : '' }}>
                Next →
            </button>
        </div>
    </main>
</div>

{{-- CRUD Editor Modal --}}
@include('partials.guess-me-editor')

{{-- Team Configuration Modal --}}
@include('partials.team-config-modal')

<script>
    window.BYC_GAME1 = {
        rounds: @json($rounds),
        state: @json($game1State),
        teams: @json($teams),
        currentIndex: {{ $currentRoundIndex }},
        isRevealed: {{ $isRevealed ? 'true' : 'false' }},
        isAdmin: {{ Auth::check() && Auth::user()->isAdmin() ? 'true' : 'false' }}
    };
</script>
@endsection
