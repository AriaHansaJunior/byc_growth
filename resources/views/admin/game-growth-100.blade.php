@extends('layouts.app')

@section('title', 'Host Game 02 — BYC Growth 100 | Admin Game Center')
@section('no-header', true)
@section('no-footer', true)

@section('content')
@php
    $currentRoundIndex = max(0, min(count($rounds) - 1, (int) ($game2State['current_round'] ?? 0)));
    $currentRound = $rounds[$currentRoundIndex] ?? null;
    $roundId = (string) ($currentRound['id'] ?? 1);
    $revealedIndexes = $game2State['revealed'][$roundId] ?? [];
    $currentCrosses = (int) ($game2State['crosses'][$roundId] ?? 0);
    $isRoundHidden = !empty($currentRound['is_hidden']);

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
    {{-- Topbar: Host Controls & Scores --}}
    <header class="topbar">
        <a href="{{ route('admin.games') }}" class="brand-button" aria-label="Back to Admin Games" title="Back to Admin Games">
            <x-brand compact="true" />
        </a>
        <div class="topbar-scores-wrap">
            <x-score-pair :teams="$teams" :compact="true" game="game2" />
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
            <button type="button" class="button button-secondary" id="btn-toggle-scoreboard" style="height: 38px; padding: 0 20px; font-size: 13.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; text-align: center;">
                Scores &amp; Controls
            </button>
            <div class="live-pill" style="background: rgba(186, 210, 89, 0.2); color: var(--forest-dark); border-color: var(--lime);">
                <span style="background: var(--forest);"></span> Host: {{ Auth::user()->username ?? Auth::user()->name }}
            </div>
        </div>
    </header>

    {{-- Main Game Content --}}
    <main class="game-content growth-content">
        <div class="growth-title">
            <div>
                <span class="eyebrow">Game 02 &bull; Host Session</span>
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
                    <button type="button" class="button button-ghost" id="btn-open-teams-modal">
                        <x-icon name="user" /> Configure Teams
                    </button>
                    <button type="button" class="button button-ghost" id="btn-open-editor">
                        <x-icon name="edit" /> Edit Questions
                    </button>
                </div>
            </div>

            {{-- Visual Cross Overlay (Hidden by default, triggered on cross action) --}}
            <div class="cross-overlay" id="cross-overlay" style="display: none;" aria-live="polite">
                <span id="cross-overlay-content">
                    <b>×</b>
                </span>
            </div>

            {{-- Round Navigation: Centered, Text-only, NO arrows --}}
            <div class="round-nav" style="margin-top: 18px; display: flex; justify-content: center; align-items: center; gap: 14px;">
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
        @else
            <div style="padding: 40px; text-align: center; background: #bad3ff; border-radius: 20px;">
                <p>No survey questions available.</p>
                <button type="button" class="button button-primary" id="btn-open-editor-empty">Add Survey Question</button>
            </div>
        @endif
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
                <button type="button" class="button button-ghost button-sm" id="btn-open-teams-modal-header" style="font-size: 12.5px; height: 34px;" onclick="document.getElementById('btn-open-teams-modal').click();">
                    <x-icon name="user" /> Configure Teams
                </button>
                <button type="button" class="button button-ghost button-sm" id="btn-open-editor-header" style="font-size: 12.5px; height: 34px;" onclick="document.getElementById('btn-open-editor').click();">
                    <x-icon name="edit" /> Edit Questions
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
                            Recipient (<span class="award-points-display">+{{ $roundRevealedPoints }}</span>)
                        </span>
                    </div>

                    {{-- Clean & Centered Score Display --}}
                    <div class="host-team-card-score" style="display: flex; align-items: baseline; justify-content: center; gap: 12px; margin: 10px 0 8px; text-align: center;">
                        <div style="display: inline-flex; align-items: baseline; gap: 4px;">
                            <span class="host-team-game-score" id="host-team-score-{{ $team['id'] }}" style="font-size: 28px; font-weight: 800; font-family: 'Manrope', sans-serif;">{{ $team['scores']['game2'] ?? $team['score'] ?? 0 }}</span>
                            <span style="font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase;">pts</span>
                        </div>
                        <div style="font-size: 12.5px; color: var(--muted); font-weight: 600;">
                            (Total: <strong id="host-team-total-{{ $team['id'] }}" style="color: var(--ink);">{{ $team['total_score'] ?? $team['score'] ?? 0 }}</strong> pts)
                        </div>
                    </div>

                    <div class="host-team-card-actions" style="display: flex; justify-content: center; align-items: center; gap: 6px;">
                        <button type="button" class="button button-ghost btn-score-action" data-team="{{ $team['id'] }}" data-amount="-5" title="Deduct 5 points">−5</button>
                        <button type="button" class="button button-ghost btn-score-action" data-team="{{ $team['id'] }}" data-amount="5" title="Add 5 points">+5</button>
                        <button type="button" class="button {{ $isAwarded ? 'button-primary' : 'button-secondary' }} btn-award-round" data-team="{{ $team['id'] }}" id="btn-award-growth-{{ $team['id'] }}">
                            {{ $isAwarded ? 'Points Awarded' : 'Award Round' }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
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
                        const curIdx = window.BYC_GAME2?.currentIndex ?? 0;
                        const curRound = window.BYC_GAME2?.rounds?.[curIdx];
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
                        const curIdx = window.BYC_GAME2?.currentIndex ?? 0;
                        if (window.BYC_GAME2?.rounds?.[curIdx]) {
                            window.BYC_GAME2.rounds[curIdx].is_hidden = data.is_hidden;
                            updateRoundVisibilityUI(window.BYC_GAME2.rounds[curIdx]);
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
