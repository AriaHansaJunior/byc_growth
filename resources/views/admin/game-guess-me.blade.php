@extends('layouts.app')

@section('title', 'Host Game 01 — Guess Me! | Admin Game Center')
@section('no-header', true)
@section('no-footer', true)

@section('content')
@php
    $currentRoundIndex = max(0, min(count($rounds) - 1, (int) ($game1State['current_round'] ?? 0)));
    $currentRound = $rounds[$currentRoundIndex] ?? null;
    $roundId = (string) ($currentRound['id'] ?? 1);
    $isRevealed = (bool) ($game1State['revealed'][$roundId] ?? false);
    $isRoundHidden = !empty($currentRound['is_hidden']);
@endphp

<div class="game-shell">
    {{-- Topbar: Host Controls & Scores --}}
    <header class="topbar">
        <a href="{{ route('admin.games') }}" class="brand-button" aria-label="Back to Admin Games" title="Back to Admin Games">
            <x-brand compact="true" />
        </a>
        <div class="topbar-scores-wrap">
            <x-score-pair :teams="$teams" :compact="true" game="game1" />
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="button" class="button button-secondary" id="btn-toggle-scoreboard" style="height: 38px; padding: 0 20px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; text-align: center;">
                Scores &amp; Controls
            </button>
            <div class="live-pill" style="background: rgba(186, 210, 89, 0.2); color: var(--forest-dark); border-color: var(--lime);">
                <span style="background: var(--forest);"></span> Host: {{ Auth::user()->username ?? Auth::user()->name }}
            </div>
        </div>
    </header>

    {{-- Main Game Content --}}
    <main class="game-content">
        <div class="game-titlebar">
            <div>
                <span class="eyebrow">Game 01 &bull; Host Session</span>
                <h1>Guess Me!</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                {{-- Round Visibility Status Badge & Hide Button (No icons, text only) --}}
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span id="round-visibility-badge" style="{{ $isRoundHidden ? 'background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;' : 'background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7;' }} font-size: 11.5px; font-weight: 700; padding: 4px 12px; border-radius: 999px;">
                        {{ $isRoundHidden ? 'Hidden from Users' : 'Visible to Users' }}
                    </span>
                    <button type="button" 
                            id="btn-toggle-round-hide" 
                            data-round-id="{{ $currentRound['id'] ?? '' }}"
                            style="{{ $isRoundHidden ? 'background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5;' }} font-size: 12.5px; font-weight: 700; padding: 5px 14px; border-radius: 999px; cursor: pointer; transition: all 0.2s ease;">
                        {{ $isRoundHidden ? 'Unhide' : 'Hide' }}
                    </button>
                </div>

                <div class="round-counter">
                    <span>Round</span>
                    <strong id="round-indicator">
                        {{ str_pad($currentRoundIndex + 1, 2, '0', STR_PAD_LEFT) }}
                        <em>/ {{ str_pad(count($rounds), 2, '0', STR_PAD_LEFT) }}</em>
                    </strong>
                </div>
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
                        {{ $isRevealed ? 'Correct answer revealed. Click a team card in Scores & Controls to award the round points.' : 'Teams discuss and submit answers. The host reveals the answer when time is up.' }}
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
                <button type="button" class="button button-primary" id="btn-open-editor-empty">Add Round</button>
            </div>
        @endif

        {{-- Round Navigation: Centered, Text-only, NO arrows --}}
        <div class="round-nav">
            <button type="button" class="button button-secondary" id="btn-prev-round" {{ $currentRoundIndex === 0 ? 'disabled' : '' }}>
                Previous
            </button>
            <div class="round-dots" id="round-dots-container">
                @foreach ($rounds as $idx => $r)
                    <span class="{{ $currentRoundIndex === $idx ? 'active' : '' }}" data-index="{{ $idx }}" title="Round {{ $idx + 1 }}{{ !empty($r['is_hidden']) ? ' (Hidden)' : '' }}"></span>
                @endforeach
            </div>
            <button type="button" class="button button-primary" id="btn-next-round" {{ $currentRoundIndex >= count($rounds) - 1 ? 'disabled' : '' }}>
                Next
            </button>
        </div>
    </main>
</div>

{{-- Scoreboard & Host Controls Modal Overlay (Hidden by Default) --}}
<div id="modal-host-scoreboard" class="scoreboard-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="scoreboard-modal-title">
    <div class="scoreboard-modal-panel">
        <div class="scoreboard-modal-header">
            <div>
                <span class="eyebrow" style="color: var(--forest); font-size: 11px;">Host Controls</span>
                <h3 id="scoreboard-modal-title" style="font: 800 20px 'Manrope', sans-serif; color: var(--ink); margin: 2px 0 0;">
                    Round Point Assignment &amp; Team Scoring
                </h3>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="button" class="button button-ghost button-sm" id="btn-open-teams-modal" style="font-size: 12.5px; height: 34px;">
                    <x-icon name="user" /> Configure Teams
                </button>
                <button type="button" class="button button-ghost button-sm" id="btn-open-editor" style="font-size: 12.5px; height: 34px;">
                    <x-icon name="edit" /> Edit Rounds
                </button>
                <button type="button" class="icon-button" id="btn-close-scoreboard" aria-label="Close scoreboard modal" style="width: 34px; height: 34px;">
                    <x-icon name="x" />
                </button>
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

                    {{-- Clean & Centered Score Display --}}
                    <div class="host-team-card-score" style="display: flex; align-items: baseline; justify-content: center; gap: 12px; margin: 10px 0 8px; text-align: center;">
                        <div style="display: inline-flex; align-items: baseline; gap: 4px;">
                            <span class="host-team-game-score" id="host-team-score-{{ $team['id'] }}" style="font-size: 28px; font-weight: 800; font-family: 'Manrope', sans-serif;">{{ $team['scores']['game1'] ?? $team['score'] ?? 0 }}</span>
                            <span style="font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase;">pts</span>
                        </div>
                        <div style="font-size: 12.5px; color: var(--muted); font-weight: 600;">
                            (Total: <strong id="host-team-total-{{ $team['id'] }}" style="color: var(--ink);">{{ $team['total_score'] ?? $team['score'] ?? 0 }}</strong> pts)
                        </div>
                    </div>

                    <div class="host-team-card-actions" style="display: flex; justify-content: center; align-items: center; gap: 6px;">
                        <button type="button" class="button button-ghost btn-score-action" data-team="{{ $team['id'] }}" data-amount="-5" title="Deduct 5 points">−5</button>
                        <button type="button" class="button button-ghost btn-score-action" data-team="{{ $team['id'] }}" data-amount="5" title="Add 5 points">+5</button>
                        <button type="button" class="button {{ $isAwarded ? 'button-primary' : 'button-secondary' }} btn-award-round" data-team="{{ $team['id'] }}" id="btn-award-{{ $team['id'] }}">
                            {{ $isAwarded ? 'Points Awarded' : 'Award Round' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
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
        isAdmin: true
    };

    // Host Round Visibility Toggle Handler
    document.addEventListener('DOMContentLoaded', () => {
        const hideBtn = document.getElementById('btn-toggle-round-hide');
        const badge = document.getElementById('round-visibility-badge');

        function updateRoundVisibilityUI(round) {
            if (!hideBtn || !badge || !round) return;
            const isHidden = Boolean(round.is_hidden);
            hideBtn.dataset.roundId = round.id;
            hideBtn.textContent = isHidden ? 'Unhide' : 'Hide';
            hideBtn.style.background = isHidden ? '#ecfdf5' : '#fef2f2';
            hideBtn.style.color = isHidden ? '#065f46' : '#991b1b';
            hideBtn.style.border = isHidden ? '1px solid #6ee7b7' : '1px solid #fca5a5';

            badge.textContent = isHidden ? 'Hidden from Users' : 'Visible to Users';
            badge.style.background = isHidden ? '#fef2f2' : '#ecfdf5';
            badge.style.color = isHidden ? '#dc2626' : '#065f46';
            badge.style.border = isHidden ? '1px solid #fecaca' : '1px solid #6ee7b7';
        }

        // Attach hook into round navigation
        const origPrev = document.getElementById('btn-prev-round');
        const origNext = document.getElementById('btn-next-round');
        [origPrev, origNext].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => {
                    setTimeout(() => {
                        const curIdx = window.BYC_GAME1?.currentIndex ?? 0;
                        const curRound = window.BYC_GAME1?.rounds?.[curIdx];
                        if (curRound) updateRoundVisibilityUI(curRound);
                    }, 50);
                });
            }
        });

        if (hideBtn) {
            hideBtn.addEventListener('click', async () => {
                const roundId = hideBtn.dataset.roundId;
                if (!roundId) return;

                hideBtn.disabled = true;
                const orig = hideBtn.textContent;
                hideBtn.textContent = 'Updating...';

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const res = await fetch(`/admin/games/rounds/${roundId}/toggle-visibility`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json();
                    if (res.ok && data.success) {
                        const curIdx = window.BYC_GAME1?.currentIndex ?? 0;
                        if (window.BYC_GAME1?.rounds?.[curIdx]) {
                            window.BYC_GAME1.rounds[curIdx].is_hidden = data.is_hidden;
                            updateRoundVisibilityUI(window.BYC_GAME1.rounds[curIdx]);
                        }
                    } else {
                        alert(data.error || 'Failed to toggle round visibility.');
                        hideBtn.textContent = orig;
                    }
                } catch (err) {
                    alert('Error toggling round visibility: ' + err.message);
                    hideBtn.textContent = orig;
                } finally {
                    hideBtn.disabled = false;
                }
            });
        }
    });
</script>
@endsection
