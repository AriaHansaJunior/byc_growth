@extends('layouts.app')

@section('title', 'Game 02 — BYC Growth 100 | BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
@php
    $currentRoundIndex = max(0, min(count($rounds) - 1, (int) ($game2State['current_round'] ?? 0)));
    $currentRound = $rounds[$currentRoundIndex] ?? null;
    $roundId = (string) ($currentRound['id'] ?? 1);
    $revealedIndexes = $game2State['revealed'][$roundId] ?? [];
    $currentCrosses = (int) ($game2State['crosses'][$roundId] ?? 0);

    // Calculate sum of revealed answer points
    $roundRevealedPoints = 0;
    if ($currentRound && isset($currentRound['answers'])) {
        foreach ($currentRound['answers'] as $idx => $ans) {
            if (in_array($idx, $revealedIndexes)) {
                $roundRevealedPoints += (int) $ans['score'];
            }
        }
    }
@endphp

<div class="game-shell">
    {{-- Topbar --}}
    <header class="topbar">
        <a href="{{ route('game.center') }}" class="brand-button" aria-label="Back to Game Center" title="Back to Game Center">
            <x-brand compact="true" />
        </a>
        <x-score-pair :teams="$teams" :compact="true" game="game2" />
        <div style="display: flex; align-items: center; gap: 10px;">
            @if(Auth::check() && Auth::user()->isAdmin())
                <div class="live-pill" style="background: rgba(186, 210, 89, 0.2); color: var(--forest-dark); border-color: var(--lime);">
                    <span style="background: var(--forest);"></span> Host: {{ Auth::user()->name }}
                </div>
            @else
                <a href="{{ route('login') }}?redirect={{ urlencode(request()->getRequestUri()) }}" class="button button-ghost button-sm" style="font-size: 13px; padding: 6px 14px; border: 1px solid var(--line); border-radius: 99px; text-decoration: none;" title="Sign in as host to score rounds">
                    <x-icon name="lock" /> Login Host
                </a>
            @endif
        </div>
    </header>

    {{-- Main Game Content --}}
    <main class="game-content growth-content">
        <div class="growth-title">
            <div>
                <span class="eyebrow">Game 02</span>
                <h1>BYC Growth <em>100</em></h1>
            </div>
            <div class="round-total" id="round-total-container" aria-label="Round Points Progress">
                <div class="round-total-meta">
                    <span class="round-total-label">Round Progress</span>
                    <span class="round-total-pill" id="round-progress-percent">{{ $roundRevealedPoints }}%</span>
                </div>
                <div class="round-total-number">
                    <strong id="round-revealed-points">{{ $roundRevealedPoints }}</strong>
                    <span class="denom">/ 100 PTS</span>
                </div>
                <div class="round-progress-track" role="progressbar" aria-valuenow="{{ $roundRevealedPoints }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="round-progress-fill {{ $roundRevealedPoints === 100 ? 'is-complete' : '' }}" id="round-progress-bar" style="width: {{ $roundRevealedPoints }}%;"></div>
                </div>
            </div>
            <div class="round-counter">
                <span>Round</span>
                <strong id="round-indicator">
                    {{ str_pad($currentRoundIndex + 1, 2, '0', STR_PAD_LEFT) }}
                    <em>/ {{ str_pad(count($rounds), 2, '0', STR_PAD_LEFT) }}</em>
                </strong>
            </div>
        </div>

        @if ($currentRound)
            {{-- Survey Question --}}
            <section class="survey-question">
                <span>BYC Survey Says:</span>
                <h2 id="survey-question-text">{{ $currentRound['question'] }}</h2>
            </section>

            {{-- Answers Grid --}}
            <section class="answers-grid" id="answers-grid-container">
                @foreach ($currentRound['answers'] as $idx => $answer)
                    @php
                        $isOpen = in_array($idx, $revealedIndexes);
                    @endphp
                    <button type="button" class="answer-tile {{ $isOpen ? 'open' : '' }}" data-answer-index="{{ $idx }}">
                        <strong>{{ $idx + 1 }}</strong>
                        <span class="answer-text-label">{{ $isOpen ? $answer['text'] : 'Click to reveal' }}</span>
                        <em>{{ $isOpen ? $answer['score'] : '?' }}</em>
                    </button>
                @endforeach
            </section>

            {{-- Controls --}}
            <div class="growth-controls">
                <div class="cross-control" id="cross-control-panel">
                    <span class="cross-control-label">Strikes:</span>
                    <div class="cross-slots" id="cross-slots-container">
                        @for ($i = 1; $i <= 3; $i++)
                            <button type="button"
                                    class="btn-cross {{ $currentCrosses >= $i ? 'active' : '' }}"
                                    data-cross="{{ $i }}"
                                    id="btn-cross-{{ $i }}"
                                    title="Strike {{ $i }}"
                                    aria-label="Strike {{ $i }}">
                                ✕
                            </button>
                        @endfor
                    </div>
                    <button type="button" class="btn-cross-reset" id="btn-reset-crosses" title="Reset all strikes for this round" {{ $currentCrosses === 0 ? 'disabled' : '' }}>
                        <x-icon name="rotate-ccw" /> Reset Strikes
                    </button>
                </div>
                <div class="reveal-control">
                    <button type="button" class="button button-secondary" id="btn-reveal-all">Reveal All</button>
                    <button type="button" class="button button-ghost" id="btn-hide-all">Hide All</button>
                    @if(Auth::check() && Auth::user()->isAdmin())
                        <button type="button" class="button button-ghost" id="btn-open-teams-modal">
                            <x-icon name="user" /> Configure Teams
                        </button>
                        <button type="button" class="button button-ghost" id="btn-open-editor">
                            <x-icon name="edit" /> Edit Questions
                        </button>
                    @else
                        <a href="{{ route('login') }}?redirect={{ urlencode(request()->getRequestUri()) }}" class="button button-secondary button-sm" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                            <x-icon name="lock" /> Login Host / Admin
                        </a>
                    @endif
                </div>
            </div>

            {{-- Visual Cross Overlay (Hidden by default, triggered on cross action) --}}
            <div class="cross-overlay" id="cross-overlay" style="display: none;" aria-live="polite">
                <span id="cross-overlay-content">
                    <b>×</b>
                </span>
            </div>

            {{-- Dynamic Team Award & Navigation Footer --}}
            <div class="growth-footer">
                <div class="growth-teams-award-area" id="growth-teams-award-container">
                    <span class="award-section-title">Award Round Points (<span class="current-total-label">{{ $roundRevealedPoints }}</span> pts):</span>
                    <div class="growth-teams-grid">
                        @foreach ($teams as $team)
                            @php
                                $isAwarded = ($currentRound && ($currentRound['awarded_team_id'] ?? null) == $team['id']);
                                $hasOtherAwarded = ($currentRound && ($currentRound['awarded_team_id'] ?? null) && ($currentRound['awarded_team_id'] ?? null) != $team['id']);
                            @endphp
                            <div class="growth-team-card host-team-{{ $team['theme'] }} {{ $isAwarded ? 'is-selected' : '' }} {{ $hasOtherAwarded ? 'is-dimmed' : '' }}"
                                 data-team-id="{{ $team['id'] }}"
                                 id="growth-team-card-{{ $team['id'] }}">
                                <div class="growth-team-info">
                                    <span class="team-dot" style="background: {{ $team['color'] }};"></span>
                                    <strong class="growth-team-name">{{ $team['name'] }}</strong>
                                </div>
                                @if(Auth::check() && Auth::user()->isAdmin())
                                    <div class="growth-team-actions">
                                        <button type="button" class="button {{ $isAwarded ? 'button-primary' : 'button-secondary' }} btn-award-round"
                                                data-team="{{ $team['id'] }}"
                                                id="btn-award-growth-{{ $team['id'] }}">
                                            {{ $isAwarded ? 'Points Awarded' : 'Award Round' }}
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

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
            </div>
        @else
            <div style="padding: 40px; text-align: center; background: #14223a; border-radius: 20px;">
                <p>No survey questions available.</p>
                @if(Auth::check() && Auth::user()->isAdmin())
                    <button type="button" class="button button-primary" id="btn-open-editor-empty">+ Add Survey Question</button>
                @endif
            </div>
        @endif
    </main>
</div>

{{-- CRUD Editor Modal --}}
@include('partials.growth-100-editor')

{{-- Team Configuration Modal --}}
@include('partials.team-config-modal')

<script>
    window.BYC_GAME2 = {
        rounds: @json($rounds),
        state: @json($game2State),
        teams: @json($teams),
        currentIndex: {{ $currentRoundIndex }},
        isAdmin: {{ Auth::check() && Auth::user()->isAdmin() ? 'true' : 'false' }}
    };
</script>
@endsection
