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
        <button
            type="button"
            class="button button-danger"
            id="btn-admin-reset-gameplay"
        >
            Reset Gameplay
        </button>
    </div>
</div>
@endsection

@section('content')
    {{-- Overall Scoreboard Section --}}
    <section class="gc-scoreboard" style="margin-bottom: 36px;">
        <div class="gc-scoreboard-info" style="flex: 0 1 300px; min-width: 220px;">
            <span class="eyebrow">Overall Standings</span>
            <h2 style="margin: 0; font: 800 28px 'Manrope', sans-serif;">Team Scoreboard</h2>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 14px;">Combined cumulative scores across all games in live database.</p>
        </div>
        <div class="gc-scoreboard-teams">
            <x-score-pair :teams="$finalScores['teams'] ?? $teams ?? []" />
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex-shrink: 0;">
            <button type="button" class="button button-ghost" id="btn-open-teams-modal" style="border: 1px solid var(--line); font-size: 13.5px;">
                Configure Teams
            </button>
            <a href="{{ route('game.final') }}" class="button button-ghost" style="border: 1px solid var(--line); font-size: 13.5px;">
                View Final Results
            </a>
        </div>
    </section>

    {{-- Game Cards Selection --}}
    <section class="game-center-selection" id="game-selection" style="margin-bottom: 40px;">
        <div class="destinations-heading" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
            <div>
                <span class="eyebrow" style="color: var(--forest);">Game Selection</span>
                <h2 style="color: var(--ink);">Choose Your Challenge</h2>
            </div>
            <p style="color: var(--muted); max-width: 440px;">Directly control each interactive game. Host editors, survey questions, and scoreboards are managed here.</p>
        </div>

        <div class="game-center-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px;">
            @foreach($games as $game)
                @php
                    $adminRoute = ($game['code'] ?? '') === 'game1' ? 'admin.games.guess-me.host' : 'admin.games.growth-100.host';
                    $playUrl = \Illuminate\Support\Facades\Route::has($adminRoute) ? route($adminRoute) : '#';
                    $isHidden = !empty($game['is_hidden']);
                @endphp
                <article class="gc-card gc-card--{{ $game['theme'] ?? 'forest' }}" id="admin-game-card-{{ $game['id'] }}" style="padding: 32px 32px 28px; display: flex; flex-direction: column; justify-content: space-between; border-radius: 24px; position: relative;">
                    <div class="gc-card-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px;">
                        <div class="gc-meta-tags" style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px; min-width: 0; flex: 1;">
                            <span class="gc-order-badge">Game {{ $game['order'] }}</span>
                            <span class="gc-category-tag">{{ $game['tag'] }}</span>
                            <span class="game-hidden-badge" id="badge-game-hidden-{{ $game['id'] }}" style="{{ $isHidden ? 'display: inline-block;' : 'display: none;' }} background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 11.5px; font-weight: 700; padding: 2px 10px; border-radius: 999px;">
                                Hidden from Users
                            </span>
                        </div>
                        {{-- HIDE / UNHIDE GAME BUTTON (No icons, text only with styled color) --}}
                        <button type="button" 
                                class="btn-toggle-game-hide" 
                                data-game-id="{{ $game['id'] }}" 
                                id="btn-toggle-game-{{ $game['id'] }}"
                                style="{{ $isHidden ? 'background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5;' }} font-size: 12.5px; font-weight: 700; padding: 5px 14px; border-radius: 999px; cursor: pointer; transition: all 0.2s ease;">
                            {{ $isHidden ? 'Unhide' : 'Hide' }}
                        </button>
                    </div>

                    <div class="gc-card-body" style="flex: 1; display: flex; flex-direction: column; margin-bottom: 20px;">
                        <h3 class="gc-card-title" style="margin: 0 0 10px; font: 800 26px/1.2 'Manrope', sans-serif;">{{ $game['title'] }}</h3>
                        <p class="gc-card-desc" style="margin: 0; font-size: 14.5px; line-height: 1.6;">{{ $game['description'] }}</p>
                    </div>

                    <div class="gc-card-footer" style="margin-top: auto;">
                        <a href="{{ $playUrl }}" class="button button-primary gc-play-btn" style="width: 100%; display: flex; align-items: center; justify-content: center; height: 44px; font-weight: 800; border-radius: 12px;">
                            Launch Game
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ============================================================== --}}
    {{-- S4: ROUNDS & QUESTIONS MANAGEMENT WITH TABS                    --}}
    {{-- ============================================================== --}}
    <div id="admin-game-management-section" style="margin-top: 50px; padding-top: 30px; border-top: 2px dashed var(--line); scroll-margin-top: 90px;">
        {{-- Section Tabs --}}
        <div class="admin-tabs-nav" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 28px;">
            <button type="button" class="button {{ $activeTab === 'guess-me' ? 'button-primary' : 'button-ghost' }} admin-tab-btn" data-target="tab-guess-me" id="tab-btn-guess-me" style="font-size: 13.5px; padding: 8px 18px;">
                Game 1 — Guess Me! Rounds ({{ count($guessMeRounds) }})
            </button>
            <button type="button" class="button {{ $activeTab === 'growth-100' ? 'button-primary' : 'button-ghost' }} admin-tab-btn" data-target="tab-growth-100" id="tab-btn-growth-100" style="font-size: 13.5px; padding: 8px 18px;">
                Game 2 — BYC GROWTH 100 Questions ({{ count($growth100Rounds) }})
            </button>
        </div>

        {{-- TAB 1: GUESS ME! ROUNDS --}}
        <section class="admin-tab-pane" id="tab-guess-me" style="display: {{ $activeTab === 'guess-me' ? 'block' : 'none' }};">
            <div class="destinations-heading" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span class="eyebrow" style="color: var(--forest);">Game 01 &bull; Visual Word Clues</span>
                    <h2 style="color: var(--ink); margin: 0 0 6px;">Guess Me! Rounds</h2>
                    <p style="color: var(--muted); margin: 0; font-size: 14px;">
                        Manage secret answers, missing-letter clue slots, visual image clues, and scoring for each round.
                    </p>
                </div>
                <button type="button" class="button button-primary" id="btn-admin-add-guess-round">
                    Add Round
                </button>
            </div>

            @if(empty($guessMeRounds))
                <div class="empty-state-box" style="padding: 48px; text-align: center; background: var(--white); border-radius: 20px; border: 1px dashed var(--line); margin-bottom: 40px;">
                    <p style="color: var(--muted); font-size: 16px; margin-bottom: 16px;">No rounds currently configured for Guess Me!.</p>
                </div>
            @else
                <div class="admin-guess-rounds-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 40px;">
                    @foreach($guessMeRounds as $round)
                        <article class="admin-round-card" id="admin-guess-round-{{ $round['id'] }}" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 20px; display: flex; flex-direction: column; gap: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.04); position: relative;">
                            {{-- Card Header --}}
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="gc-order-badge" style="background: var(--forest); color: #fff; font-size: 12px; font-weight: 800; padding: 4px 10px; border-radius: 999px;">
                                        Round {{ $round['round_number'] }}
                                    </span>
                                </div>
                                <span style="font-size: 13px; font-weight: 800; color: var(--forest-dark); background: #eaf3dc; padding: 3px 10px; border-radius: 8px; border: 1px solid var(--lime);">
                                    {{ $round['score'] }} PTS
                                </span>
                            </div>

                            {{-- Card Image Preview --}}
                            <div style="width: 100%; aspect-ratio: 4 / 3; border-radius: 14px; overflow: hidden; background: var(--paper); border: 1px solid var(--line); position: relative; display: flex; align-items: center; justify-content: center;">
                                @php
                                    $imgSrc = !empty($round['image_url']) ? $round['image_url'] : asset('assets/images/' . $round['image']);
                                @endphp
                                <img src="{{ $imgSrc }}" alt="Visual Clue Round {{ $round['round_number'] }}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('assets/images/BYC_Growth.jpg') }}';">
                                <span style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.65); color: #fff; font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 600;">
                                    Clue Image (4:3)
                                </span>
                            </div>

                            {{-- Clue & Answer Details --}}
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                <div style="background: var(--paper); border: 1px solid var(--line); border-radius: 10px; padding: 8px 12px;">
                                    <small style="display: block; font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 700; letter-spacing: 0.05em; margin-bottom: 2px;">
                                        Clue (Letter Slots)
                                    </small>
                                    <strong style="font-family: monospace; font-size: 16px; letter-spacing: 0.15em; color: var(--forest-dark);">
                                        {{ $round['clue'] }}
                                    </strong>
                                </div>

                                <div style="background: #fdf5d7; border: 1px solid var(--gold); border-radius: 10px; padding: 8px 12px;">
                                    <small style="display: block; font-size: 11px; text-transform: uppercase; color: #855b14; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 2px;">
                                        Correct Answer
                                    </small>
                                    <strong style="font-size: 15px; color: var(--ink); font-weight: 800;">
                                        {{ $round['correct_answer'] }}
                                    </strong>
                                </div>
                            </div>

                            {{-- Administrative Controls (Edit / Delete) --}}
                            <div class="admin-round-actions" style="display: flex; gap: 8px; margin-top: auto; padding-top: 8px; border-top: 1px solid var(--line);">
                                <button
                                    type="button"
                                    class="button button-secondary button-sm btn-edit-guess-round"
                                    data-id="{{ $round['id'] }}"
                                    data-answer="{{ $round['correct_answer'] }}"
                                    data-clue="{{ $round['clue'] }}"
                                    data-score="{{ $round['score'] }}"
                                    data-image-url="{{ $imgSrc }}"
                                    style="flex: 1; font-size: 12.5px; height: 34px;"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="button button-danger button-sm"
                                    data-admin-confirm="Are you sure you want to delete Guess Me Round {{ $round['round_number'] }}?"
                                    data-confirm-title="Delete Guess Me Round"
                                    data-confirm-body="Are you sure you want to delete Round {{ $round['round_number'] }} ({{ $round['correct_answer'] }})? Related media files will be cleaned up safely."
                                    data-confirm-btn="Yes, Delete Round"
                                    data-action="{{ route('admin.games.guess-me.delete-round', $round['id']) }}"
                                    data-method="DELETE"
                                    style="font-size: 12.5px; height: 34px; padding: 0 14px;"
                                >
                                    Delete
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- TAB 2: BYC GROWTH 100 QUESTIONS --}}
        <section class="admin-tab-pane" id="tab-growth-100" style="display: {{ $activeTab === 'growth-100' ? 'block' : 'none' }};">
            <div class="destinations-heading" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span class="eyebrow" style="color: var(--forest);">Game 02 &bull; Survey Trivia Challenge</span>
                    <h2 style="color: var(--ink); margin: 0 0 6px;">BYC GROWTH 100 Questions</h2>
                    <p style="color: var(--muted); margin: 0; font-size: 14px;">
                        Manage survey questions, answer choices, and scoring. Inviolable rule: the total score of all answers in each question must equal <strong>exactly 100</strong>.
                    </p>
                </div>
                <button type="button" class="button button-primary" id="btn-admin-add-growth-round">
                    Add Question
                </button>
            </div>

            @if(empty($growth100Rounds))
                <div class="empty-state-box" style="padding: 48px; text-align: center; background: var(--white); border-radius: 20px; border: 1px dashed var(--line); margin-bottom: 40px;">
                    <p style="color: var(--muted); font-size: 16px; margin-bottom: 16px;">No survey questions currently configured for BYC GROWTH 100.</p>
                </div>
            @else
                <div class="admin-growth-questions-list" style="display: flex; flex-direction: column; gap: 24px; margin-bottom: 40px;">
                    @foreach($growth100Rounds as $round)
                        @php
                            $sumScores = array_sum(array_column($round['answers'], 'score'));
                            $isValidTotal = ($sumScores === 100);
                        @endphp
                        <article class="admin-growth-card" id="admin-growth-round-{{ $round['id'] }}" style="background: var(--white); border: 1px solid var(--line); border-radius: 20px; padding: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
                            {{-- Question Header --}}
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                        <span class="gc-order-badge" style="background: var(--forest); color: #fff; font-size: 12px; font-weight: 800; padding: 4px 10px; border-radius: 999px;">
                                            Question {{ $round['round_number'] }}
                                        </span>
                                        <span class="status-pill {{ $isValidTotal ? 'status-pill--active' : 'status-pill--draft' }}" style="font-size: 12px; font-weight: 800; padding: 3px 10px; border-radius: 6px; background: {{ $isValidTotal ? '#eaf3dc' : '#fdf0ee' }}; color: {{ $isValidTotal ? 'var(--forest-dark)' : 'var(--red)' }}; border: 1px solid {{ $isValidTotal ? 'var(--lime)' : 'var(--red)' }};">
                                            {{ $isValidTotal ? 'Total: 100 / 100 PTS' : 'Invalid Total: ' . $sumScores . ' / 100 PTS' }}
                                        </span>
                                    </div>
                                    <h3 style="font: 800 18px 'Manrope', sans-serif; color: var(--ink); margin: 0; line-height: 1.4;">
                                        {{ $round['question'] }}
                                    </h3>
                                </div>

                                {{-- Actions --}}
                                <div style="display: flex; gap: 8px;">
                                    <button
                                        type="button"
                                        class="button button-secondary button-sm btn-edit-growth-round"
                                        data-id="{{ $round['id'] }}"
                                        data-question="{{ $round['question'] }}"
                                        data-answers='@json($round['answers'])'
                                        style="font-size: 12.5px; height: 34px;"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="button button-danger button-sm"
                                        data-admin-confirm="Are you sure you want to delete Question {{ $round['round_number'] }}?"
                                        data-confirm-title="Delete Survey Question"
                                        data-confirm-body="Are you sure you want to delete Question {{ $round['round_number'] }} ({{ $round['question'] }})? Associated survey answers will be removed safely."
                                        data-confirm-btn="Yes, Delete Question"
                                        data-action="{{ route('admin.games.growth-100.delete-round', $round['id']) }}"
                                        data-method="DELETE"
                                        style="font-size: 12.5px; height: 34px; padding: 0 14px;"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>

                            {{-- Survey Answers Grid --}}
                            <div class="admin-survey-answers-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; background: var(--paper); border: 1px solid var(--line); border-radius: 14px; padding: 14px;">
                                @foreach($round['answers'] as $idx => $ans)
                                    <div class="admin-survey-answer-item" style="background: var(--white); border: 1px solid var(--line); border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="width: 24px; height: 24px; border-radius: 50%; background: var(--paper); border: 1px solid var(--line); font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; color: var(--forest); flex-shrink: 0;">
                                                {{ $idx + 1 }}
                                            </span>
                                            <span style="font-size: 14px; font-weight: 700; color: var(--ink);">
                                                {{ $ans['text'] }}
                                            </span>
                                        </div>
                                        <span style="font-size: 13px; font-weight: 800; color: var(--forest); background: #eaf3dc; padding: 2px 8px; border-radius: 6px; border: 1px solid var(--lime); flex-shrink: 0;">
                                            {{ $ans['score'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Games Data for Client-Side JS --}}
    <div id="admin-games-data" data-teams='@json($teams)' style="display: none;"></div>

    {{-- Extracted Modal Partials --}}
    @include('admin.partials.games-guess-modal')
    @include('admin.partials.games-growth-modal')
    {{-- Team Configuration Modal --}}
    @include('partials.team-config-modal')
@endsection
