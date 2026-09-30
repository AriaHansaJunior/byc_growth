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
    <div class="admin-header-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="{{ route('game.guess-me') }}" class="button button-ghost button-sm" target="_blank">
            <x-icon name="gamepad" /> Live Guess Me &rarr;
        </a>
        <a href="{{ route('game.growth-100') }}" class="button button-ghost button-sm" target="_blank">
            <x-icon name="gamepad" /> Live Growth 100 &rarr;
        </a>
        <a href="{{ route('game.center') }}" class="button button-ghost button-sm" target="_blank">
            <x-icon name="book" /> Public Game Center &rarr;
        </a>
    </div>
</div>
@endsection

@section('content')
    {{-- Admin Controls Summary Strip --}}
    <div class="admin-controls-strip" style="margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
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
                <a href="#admin-game-management-section" class="button button-primary">
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

    {{-- Overall Scoreboard Section (Identical Visual Foundation to User Page) --}}
    <section class="gc-scoreboard" style="margin-bottom: 36px;">
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

    {{-- Game Cards Selection (Using User gc-card Grid Structure + Admin Controls) --}}
    <section class="game-center-selection" id="game-selection" style="margin-bottom: 40px;">
        <div class="destinations-heading" style="margin-bottom: 24px;">
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
                            <span style="font-size: 13px; font-weight: 700; color: var(--ink);">Configured Items</span>
                            <span style="font-size: 13px; font-weight: 800; color: var(--forest);">{{ $game['rounds_count'] }} Live Items</span>
                        </div>
                    </div>

                    <div class="gc-card-footer" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="{{ $playUrl }}" class="button button-primary gc-play-btn" style="flex: 1;" target="_blank">
                            Launch Live &rarr;
                        </a>
                        <button type="button" class="button button-secondary btn-switch-to-game-tab" data-tab="{{ $game['id'] }}" style="font-size: 13px;">
                            ✎ Manage Data
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ============================================================== --}}
    {{-- S4: ROUNDS & QUESTIONS MANAGEMENT WITH TABS                    --}}
    {{-- ============================================================== --}}
    <div id="admin-game-management-section" style="margin-top: 50px; padding-top: 30px; border-top: 2px dashed var(--line);">
        {{-- Section Tabs --}}
        <div class="admin-tabs-nav" style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 28px;">
            <button type="button" class="button {{ $activeTab === 'guess-me' ? 'button-primary' : 'button-ghost' }} admin-tab-btn" data-target="tab-guess-me" id="tab-btn-guess-me" style="font-size: 13.5px; padding: 8px 18px;">
                🧩 Game 1 — Guess Me! Rounds ({{ count($guessMeRounds) }})
            </button>
            <button type="button" class="button {{ $activeTab === 'growth-100' ? 'button-primary' : 'button-ghost' }} admin-tab-btn" data-target="tab-growth-100" id="tab-btn-growth-100" style="font-size: 13.5px; padding: 8px 18px;">
                📊 Game 2 — BYC GROWTH 100 Questions ({{ count($growth100Rounds) }})
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
                    + Add Round
                </button>
            </div>

            @if(empty($guessMeRounds))
                <div class="empty-state-box" style="padding: 48px; text-align: center; background: var(--white); border-radius: 20px; border: 1px dashed var(--line); margin-bottom: 40px;">
                    <p style="color: var(--muted); font-size: 16px; margin-bottom: 16px;">No rounds currently configured for Guess Me!.</p>
                    <button type="button" class="button button-primary" onclick="document.getElementById('btn-admin-add-guess-round').click();">
                        + Create First Round
                    </button>
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
                            <div style="width: 100%; height: 160px; border-radius: 14px; overflow: hidden; background: var(--paper); border: 1px solid var(--line); position: relative; display: flex; align-items: center; justify-content: center;">
                                @php
                                    $imgSrc = !empty($round['image_url']) ? $round['image_url'] : asset('assets/images/' . $round['image']);
                                @endphp
                                <img src="{{ $imgSrc }}" alt="Visual Clue Round {{ $round['round_number'] }}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.onerror=null; this.src='{{ asset('assets/images/BYC_Growth.jpg') }}';">
                                <span style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.65); color: #fff; font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 600;">
                                    Clue Image
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
                                    ✎ Edit
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
                    + Add Question
                </button>
            </div>

            @if(empty($growth100Rounds))
                <div class="empty-state-box" style="padding: 48px; text-align: center; background: var(--white); border-radius: 20px; border: 1px dashed var(--line); margin-bottom: 40px;">
                    <p style="color: var(--muted); font-size: 16px; margin-bottom: 16px;">No survey questions currently configured for BYC GROWTH 100.</p>
                    <button type="button" class="button button-primary" onclick="document.getElementById('btn-admin-add-growth-round').click();">
                        + Create First Question
                    </button>
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
                                            {{ $isValidTotal ? 'Total: 100 / 100 PTS ✓' : 'Invalid Total: ' . $sumScores . ' / 100 PTS ⚠️' }}
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
                                        ✎ Edit Question
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

    {{-- ============================================================== --}}
    {{-- MODAL: GUESS ME ROUND (ADD / EDIT)                             --}}
    {{-- ============================================================== --}}
    <div class="modal-backdrop" id="modal-admin-guess-round" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="guess-modal-title">
        <section class="editor-panel" style="width: min(560px, 94vw); max-height: 90vh; overflow-y: auto; background: var(--white); border-radius: 24px; padding: 32px; box-shadow: var(--shadow); border: 1px solid var(--line);">
            <div class="modal-heading" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <span class="eyebrow" style="color: var(--forest); font-size: 11px;">Guess Me! Management</span>
                    <h2 id="guess-modal-title" style="font: 800 22px 'Manrope', sans-serif; color: var(--ink); margin: 0;">Add Guess Me Round</h2>
                </div>
                <button type="button" aria-label="Close dialog" class="icon-button" id="btn-close-guess-modal">
                    <x-icon name="x" />
                </button>
            </div>

            <form action="{{ route('admin.games.guess-me.save-round') }}" method="POST" enctype="multipart/form-data" id="form-admin-guess-round">
                @csrf
                <input type="hidden" name="id" id="guess-form-id">

                {{-- Image Preview & Upload --}}
                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 8px;">
                        Visual Clue Image
                    </label>
                    <div id="guess-image-preview-container" style="display: none; margin-bottom: 10px; width: 100%; height: 160px; border-radius: 12px; overflow: hidden; border: 1px solid var(--line); background: var(--paper);">
                        <img id="guess-image-preview" src="" alt="Clue Preview" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <label class="upload-box" id="guess-upload-trigger" style="display: block; cursor: pointer; border: 2px dashed var(--line); border-radius: 14px; padding: 18px; text-align: center; background: var(--paper);">
                        <input type="file" name="image" id="guess-image-input" accept="image/jpeg,image/png,image/webp,image/jpg" style="display: none;">
                        <span id="guess-upload-text" style="font-weight: 700; color: var(--forest); font-size: 13.5px; display: block;">
                            📷 Click or drag image to upload / replace
                        </span>
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">
                            Supported formats: JPG, PNG, WEBP (Max: 5 MB)
                        </small>
                    </label>
                </div>

                {{-- Correct Answer --}}
                <div style="margin-bottom: 16px;">
                    <label for="guess-input-answer" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                        Correct Answer <span style="color: var(--red);">*</span>
                    </label>
                    <input type="text" name="correct_answer" id="guess-input-answer" placeholder="Example: GROOT" required style="width: 100%; height: 44px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px; font-weight: 700; text-transform: uppercase;">
                    <small style="color: var(--muted); font-size: 12px; display: block; margin-top: 4px;">Automatically normalized to uppercase.</small>
                </div>

                {{-- Clue (Letter Slots) & Score --}}
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label for="guess-input-clue" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                            Clue (Letter Slots) <span style="color: var(--red);">*</span>
                        </label>
                        <input type="text" name="clue" id="guess-input-clue" placeholder="Example: G _ O _ T" required style="width: 100%; height: 44px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px; font-family: monospace; font-weight: 700; text-transform: uppercase;">
                    </div>
                    <div>
                        <label for="guess-input-score" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                            Points <span style="color: var(--red);">*</span>
                        </label>
                        <input type="number" name="score" id="guess-input-score" value="20" min="1" required style="width: 100%; height: 44px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px; font-weight: 700;">
                    </div>
                </div>

                <div id="guess-clue-validation-feedback" style="min-height: 22px; font-size: 12px; color: var(--muted); margin-bottom: 24px;">
                    Use underscore `_` for hidden characters. Clue character length must match the answer length.
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="button button-ghost" id="btn-cancel-guess-modal">
                        Cancel
                    </button>
                    <button type="submit" class="button button-primary" id="btn-submit-guess-modal">
                        Save Round
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- ============================================================== --}}
    {{-- MODAL: BYC GROWTH 100 QUESTION (ADD / EDIT)                    --}}
    {{-- ============================================================== --}}
    <div class="modal-backdrop" id="modal-admin-growth-round" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="growth-modal-title">
        <section class="editor-panel" style="width: min(620px, 94vw); max-height: 90vh; overflow-y: auto; background: var(--white); border-radius: 24px; padding: 32px; box-shadow: var(--shadow); border: 1px solid var(--line);">
            <div class="modal-heading" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <span class="eyebrow" style="color: var(--forest); font-size: 11px;">BYC GROWTH 100 Management</span>
                    <h2 id="growth-modal-title" style="font: 800 22px 'Manrope', sans-serif; color: var(--ink); margin: 0;">Add Survey Question</h2>
                </div>
                <button type="button" aria-label="Close dialog" class="icon-button" id="btn-close-growth-modal">
                    <x-icon name="x" />
                </button>
            </div>

            <form action="{{ route('admin.games.growth-100.save-round') }}" method="POST" id="form-admin-growth-round">
                @csrf
                <input type="hidden" name="id" id="growth-form-id">

                {{-- Question Text --}}
                <div style="margin-bottom: 20px;">
                    <label for="growth-input-question" style="display: block; font-weight: 700; font-size: 13.5px; color: var(--ink); margin-bottom: 6px;">
                        Survey Question <span style="color: var(--red);">*</span>
                    </label>
                    <textarea name="question" id="growth-input-question" rows="3" placeholder="Enter survey question..." required style="width: 100%; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; font-weight: 600; line-height: 1.5; font-family: inherit;"></textarea>
                </div>

                {{-- Answers Header & Live Sum Indicator --}}
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--line);">
                    <strong style="font-size: 14px; color: var(--ink);">Survey Answers</strong>
                    <span id="growth-modal-total-indicator" style="font-size: 12.5px; font-weight: 800; padding: 4px 10px; border-radius: 6px; background: #eaf3dc; color: var(--forest-dark); border: 1px solid var(--lime);">
                        Total: 100 / 100 PTS ✓
                    </span>
                </div>

                {{-- Answers Dynamic Container --}}
                <div id="growth-answers-dynamic-list" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                    {{-- Populated via JS --}}
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <button type="button" class="button button-secondary button-sm" id="btn-growth-add-answer-row">
                        + Add Answer Choice
                    </button>
                    <small style="color: var(--muted); font-size: 12px;">
                        * Total sum of answer points must equal <strong>exactly 100</strong>.
                    </small>
                </div>

                <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="button button-ghost" id="btn-cancel-growth-modal">
                        Cancel
                    </button>
                    <button type="submit" class="button button-primary" id="btn-submit-growth-modal">
                        Save Question
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // ------------------------------------------------------------------
    // TAB SWITCHING
    // ------------------------------------------------------------------
    const tabBtns = document.querySelectorAll('.admin-tab-btn');
    const tabPanes = document.querySelectorAll('.admin-tab-pane');

    function activateTab(targetId) {
        tabPanes.forEach(pane => {
            pane.style.display = (pane.id === targetId) ? 'block' : 'none';
        });
        tabBtns.forEach(b => {
            if (b.getAttribute('data-target') === targetId) {
                b.classList.remove('button-ghost');
                b.classList.add('button-primary');
            } else {
                b.classList.remove('button-primary');
                b.classList.add('button-ghost');
            }
        });
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            activateTab(targetId);

            // Update URL without reload
            const tabCode = targetId.replace('tab-', '');
            const url = new URL(window.location);
            url.searchParams.set('tab', tabCode);
            window.history.replaceState({}, '', url);
        });
    });

    document.querySelectorAll('.btn-switch-to-game-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.getAttribute('data-tab');
            const targetId = 'tab-' + tab;
            activateTab(targetId);
            const section = document.getElementById('admin-game-management-section');
            if (section) section.scrollIntoView({ behavior: 'smooth' });
        });
    });

    // ------------------------------------------------------------------
    // GUESS ME MODAL HANDLING
    // ------------------------------------------------------------------
    const guessModal = document.getElementById('modal-admin-guess-round');
    const btnAddGuess = document.getElementById('btn-admin-add-guess-round');
    const btnCloseGuess = document.getElementById('btn-close-guess-modal');
    const btnCancelGuess = document.getElementById('btn-cancel-guess-modal');
    const formGuess = document.getElementById('form-admin-guess-round');
    const guessFormId = document.getElementById('guess-form-id');
    const guessInputAnswer = document.getElementById('guess-input-answer');
    const guessInputClue = document.getElementById('guess-input-clue');
    const guessInputScore = document.getElementById('guess-input-score');
    const guessImgInput = document.getElementById('guess-image-input');
    const guessImgPreview = document.getElementById('guess-image-preview');
    const guessImgContainer = document.getElementById('guess-image-preview-container');
    const guessTitle = document.getElementById('guess-modal-title');
    const guessFeedback = document.getElementById('guess-clue-validation-feedback');

    function openGuessModal(isEdit = false, data = {}) {
        if (!guessModal) return;
        guessTitle.textContent = isEdit ? 'Edit Guess Me Round' : 'Add Guess Me Round';
        guessFormId.value = isEdit ? (data.id || '') : '';
        guessInputAnswer.value = isEdit ? (data.answer || '') : '';
        guessInputClue.value = isEdit ? (data.clue || '') : '';
        guessInputScore.value = isEdit ? (data.score || 20) : 20;

        if (isEdit && data.imageUrl) {
            guessImgPreview.src = data.imageUrl;
            guessImgContainer.style.display = 'block';
        } else {
            guessImgPreview.src = '';
            guessImgContainer.style.display = 'none';
        }
        if (guessImgInput) guessImgInput.value = '';
        validateClueLive();

        guessModal.style.display = 'grid';
        document.body.style.overflow = 'hidden';
    }

    function closeGuessModal() {
        if (!guessModal) return;
        guessModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (btnAddGuess) {
        btnAddGuess.addEventListener('click', () => openGuessModal(false));
    }
    if (btnCloseGuess) btnCloseGuess.addEventListener('click', closeGuessModal);
    if (btnCancelGuess) btnCancelGuess.addEventListener('click', closeGuessModal);

    document.querySelectorAll('.btn-edit-guess-round').forEach(btn => {
        btn.addEventListener('click', () => {
            openGuessModal(true, {
                id: btn.getAttribute('data-id'),
                answer: btn.getAttribute('data-answer'),
                clue: btn.getAttribute('data-clue'),
                score: btn.getAttribute('data-score'),
                imageUrl: btn.getAttribute('data-image-url')
            });
        });
    });

    if (guessImgInput) {
        guessImgInput.addEventListener('change', (e) => {
            const file = e.target.files && e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    guessImgPreview.src = ev.target.result;
                    guessImgContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    function validateClueLive() {
        if (!guessInputAnswer || !guessInputClue || !guessFeedback) return;
        const ans = guessInputAnswer.value.trim().toUpperCase();
        const clue = guessInputClue.value.trim().toUpperCase();
        if (!ans || !clue) {
            guessFeedback.textContent = 'Use underscore _ for hidden characters. Clue character length must match the answer length.';
            guessFeedback.style.color = 'var(--muted)';
            return;
        }

        const clueClean = clue.replace(/\s+/g, '');
        if (clueClean.length !== ans.length) {
            guessFeedback.textContent = `⚠️ Clue length (${clueClean.length}) does not match answer length (${ans.length}).`;
            guessFeedback.style.color = 'var(--red)';
            return;
        }

        for (let i = 0; i < ans.length; i++) {
            const c = clueClean[i];
            const a = ans[i];
            if (c !== '_' && c !== '-' && c !== '.' && c !== a) {
                guessFeedback.textContent = `⚠️ Character at position ${i + 1} ('${c}') does not match letter in answer ('${a}').`;
                guessFeedback.style.color = 'var(--red)';
                return;
            }
        }

        guessFeedback.textContent = '✓ Clue characters correctly match the answer!';
        guessFeedback.style.color = 'var(--forest)';
    }

    if (guessInputAnswer) guessInputAnswer.addEventListener('input', validateClueLive);
    if (guessInputClue) guessInputClue.addEventListener('input', validateClueLive);

    // ------------------------------------------------------------------
    // GROWTH 100 MODAL HANDLING
    // ------------------------------------------------------------------
    const growthModal = document.getElementById('modal-admin-growth-round');
    const btnAddGrowth = document.getElementById('btn-admin-add-growth-round');
    const btnCloseGrowth = document.getElementById('btn-close-growth-modal');
    const btnCancelGrowth = document.getElementById('btn-cancel-growth-modal');
    const growthFormId = document.getElementById('growth-form-id');
    const growthInputQuestion = document.getElementById('growth-input-question');
    const growthAnswersList = document.getElementById('growth-answers-dynamic-list');
    const btnAddAnswerRow = document.getElementById('btn-growth-add-answer-row');
    const growthTotalIndicator = document.getElementById('growth-modal-total-indicator');
    const btnSubmitGrowth = document.getElementById('btn-submit-growth-modal');
    const growthTitle = document.getElementById('growth-modal-title');

    function createAnswerRow(text = '', score = 25, index = 0) {
        const row = document.createElement('div');
        row.className = 'growth-answer-editor-row';
        row.style.cssText = 'display: grid; grid-template-columns: 28px 1fr 90px 32px; gap: 8px; align-items: center;';

        row.innerHTML = `
            <span class="row-num" style="width: 24px; height: 24px; border-radius: 50%; background: var(--paper); border: 1px solid var(--line); font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; color: var(--forest);">${index + 1}</span>
            <input type="text" name="answers[${index}][text]" value="${text.replace(/"/g, '&quot;')}" placeholder="Answer choice..." required style="height: 38px; padding: 6px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 13.5px; font-weight: 600;">
            <input type="number" name="answers[${index}][score]" value="${score}" min="1" max="100" required class="growth-score-input" style="height: 38px; padding: 6px 10px; border: 1px solid var(--line); border-radius: 8px; font-size: 13.5px; font-weight: 800; text-align: center;">
            <button type="button" class="btn-remove-answer-row" style="background: none; border: none; color: var(--muted); cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; height: 32px; width: 32px; border-radius: 6px;" title="Remove row">✕</button>
        `;

        const removeBtn = row.querySelector('.btn-remove-answer-row');
        removeBtn.addEventListener('click', () => {
            if (growthAnswersList.children.length <= 1) {
                alert('At least one survey answer is required.');
                return;
            }
            row.remove();
            reindexAnswerRows();
            calcGrowthTotalLive();
        });

        const scoreInput = row.querySelector('.growth-score-input');
        scoreInput.addEventListener('input', calcGrowthTotalLive);

        return row;
    }

    function reindexAnswerRows() {
        const rows = growthAnswersList.querySelectorAll('.growth-answer-editor-row');
        rows.forEach((row, i) => {
            const numEl = row.querySelector('.row-num');
            if (numEl) numEl.textContent = i + 1;
            const textInput = row.querySelector('input[type="text"]');
            if (textInput) textInput.name = `answers[${i}][text]`;
            const scoreInput = row.querySelector('input[type="number"]');
            if (scoreInput) scoreInput.name = `answers[${i}][score]`;
        });
    }

    function calcGrowthTotalLive() {
        let total = 0;
        const scoreInputs = growthAnswersList.querySelectorAll('.growth-score-input');
        scoreInputs.forEach(input => {
            total += parseInt(input.value || 0, 10);
        });

        if (growthTotalIndicator) {
            if (total === 100) {
                growthTotalIndicator.textContent = 'Total: 100 / 100 PTS ✓';
                growthTotalIndicator.style.background = '#eaf3dc';
                growthTotalIndicator.style.color = 'var(--forest-dark)';
                growthTotalIndicator.style.borderColor = 'var(--lime)';
                if (btnSubmitGrowth) btnSubmitGrowth.disabled = false;
            } else {
                const diff = total - 100;
                growthTotalIndicator.textContent = `Total: ${total} / 100 (${diff > 0 ? '+' + diff : diff}) ⚠️`;
                growthTotalIndicator.style.background = '#fdf0ee';
                growthTotalIndicator.style.color = 'var(--red)';
                growthTotalIndicator.style.borderColor = 'var(--red)';
            }
        }
    }

    function openGrowthModal(isEdit = false, data = {}) {
        if (!growthModal) return;
        growthTitle.textContent = isEdit ? 'Edit Survey Question' : 'Add Survey Question';
        growthFormId.value = isEdit ? (data.id || '') : '';
        growthInputQuestion.value = isEdit ? (data.question || '') : '';

        growthAnswersList.innerHTML = '';
        if (isEdit && Array.isArray(data.answers) && data.answers.length > 0) {
            data.answers.forEach((ans, i) => {
                growthAnswersList.appendChild(createAnswerRow(ans.text, ans.score, i));
            });
        } else {
            growthAnswersList.appendChild(createAnswerRow('Choice 1', 40, 0));
            growthAnswersList.appendChild(createAnswerRow('Choice 2', 30, 1));
            growthAnswersList.appendChild(createAnswerRow('Choice 3', 20, 2));
            growthAnswersList.appendChild(createAnswerRow('Choice 4', 10, 3));
        }

        calcGrowthTotalLive();
        growthModal.style.display = 'grid';
        document.body.style.overflow = 'hidden';
    }

    function closeGrowthModal() {
        if (!growthModal) return;
        growthModal.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (btnAddGrowth) btnAddGrowth.addEventListener('click', () => openGrowthModal(false));
    if (btnCloseGrowth) btnCloseGrowth.addEventListener('click', closeGrowthModal);
    if (btnCancelGrowth) btnCancelGrowth.addEventListener('click', closeGrowthModal);

    if (btnAddAnswerRow) {
        btnAddAnswerRow.addEventListener('click', () => {
            const nextIdx = growthAnswersList.children.length;
            growthAnswersList.appendChild(createAnswerRow('', 10, nextIdx));
            calcGrowthTotalLive();
        });
    }

    document.querySelectorAll('.btn-edit-growth-round').forEach(btn => {
        btn.addEventListener('click', () => {
            let answers = [];
            try {
                answers = JSON.parse(btn.getAttribute('data-answers') || '[]');
            } catch (e) {
                answers = [];
            }
            openGrowthModal(true, {
                id: btn.getAttribute('data-id'),
                question: btn.getAttribute('data-question'),
                answers: answers
            });
        });
    });

    [guessModal, growthModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (guessModal && guessModal.style.display !== 'none') closeGuessModal();
            if (growthModal && growthModal.style.display !== 'none') closeGrowthModal();
        }
    });
});
</script>
@endpush
