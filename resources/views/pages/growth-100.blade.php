@extends('layouts.app')

@section('title', 'Game 02 — BYC Growth 100 | BYC GROWTH')

@section('content')
@php
    $currentRoundIndex = max(0, min(count($rounds) - 1, (int) ($game2State['current_round'] ?? 0)));
    $currentRound = $rounds[$currentRoundIndex] ?? null;
    $roundId = (string) ($currentRound['id'] ?? 1);
    $revealedIndexes = $game2State['revealed'][$roundId] ?? [];
    $currentCrosses = (int) ($game2State['crosses'][$roundId] ?? 0);
    $redScore = (int) ($game2State['scores']['red'] ?? 0);
    $blueScore = (int) ($game2State['scores']['blue'] ?? 0);

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
        <a href="{{ route('home') }}" class="brand-button" aria-label="Kembali ke beranda">
            <x-brand compact="true" />
        </a>
        <x-score-pair :scores="['red' => $redScore, 'blue' => $blueScore]" :compact="true" />
        <div class="live-pill">
            <span /> Game session
        </div>
    </header>

    {{-- Main Game Content --}}
    <main class="game-content growth-content">
        <div class="growth-title">
            <div>
                <span class="eyebrow">Game 02</span>
                <h1>BYC Growth <em>100</em></h1>
            </div>
            <div class="round-total">
                <span>Total poin ronde</span>
                <strong>
                    <span id="round-revealed-points">{{ $roundRevealedPoints }}</span>
                    <small>/100</small>
                </strong>
            </div>
            <div class="round-counter">
                <span>Ronde</span>
                <strong id="round-indicator">
                    {{ str_pad($currentRoundIndex + 1, 2, '0', STR_PAD_LEFT) }}
                    <em>/ {{ str_pad(count($rounds), 2, '0', STR_PAD_LEFT) }}</em>
                </strong>
            </div>
        </div>

        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <x-score-pair :scores="['red' => $redScore, 'blue' => $blueScore]" :compact="true" />
        </div>

        @if ($currentRound)
            {{-- Survey Question --}}
            <section class="survey-question">
                <span>Survei BYC berkata:</span>
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
                        <span class="answer-text-label">{{ $isOpen ? $answer['text'] : 'Klik untuk buka' }}</span>
                        <em>{{ $isOpen ? $answer['score'] : '?' }}</em>
                    </button>
                @endforeach
            </section>

            {{-- Controls --}}
            <div class="growth-controls">
                <div class="cross-control">
                    <span>Kesempatan salah</span>
                    @for ($i = 1; $i <= 3; $i++)
                        <button type="button" class="btn-cross {{ $currentCrosses >= $i ? 'active' : '' }}" data-cross="{{ $i }}">
                            ×{{ $i }}
                        </button>
                    @endfor
                    <button type="button" class="btn-cross-reset" id="btn-reset-crosses">Reset</button>
                </div>
                <div class="reveal-control">
                    <button type="button" class="button button-secondary" id="btn-reveal-all">Buka semua</button>
                    <button type="button" class="button button-ghost" id="btn-hide-all">Tutup kembali</button>
                    <button type="button" class="button button-ghost" id="btn-open-editor">
                        <x-icon name="edit" /> Edit soal
                    </button>
                </div>
            </div>

            {{-- Visual Cross Overlay (Hidden by default, triggered on cross action) --}}
            <div class="cross-overlay" id="cross-overlay" style="display: none;" aria-live="polite">
                <span id="cross-overlay-content">
                    <b>×</b>
                </span>
            </div>

            {{-- Quick Score & Navigation Footer --}}
            <div class="growth-footer">
                <div class="quick-score">
                    <span>Tambah skor ronde:</span>
                    <button type="button" class="button button-danger" id="btn-add-total-red" {{ $roundRevealedPoints === 0 ? 'disabled' : '' }}>
                        Tim Red +<span class="current-total-label">{{ $roundRevealedPoints }}</span>
                    </button>
                    <button type="button" class="button button-primary" id="btn-add-total-blue" {{ $roundRevealedPoints === 0 ? 'disabled' : '' }}>
                        Tim Blue +<span class="current-total-label">{{ $roundRevealedPoints }}</span>
                    </button>
                    <div style="margin-left: 10px; display: inline-flex; gap: 4px;">
                        <button type="button" class="button button-ghost btn-score-action" data-team="red" data-amount="5" title="Tambah 5 ke Red">+5 Red</button>
                        <button type="button" class="button button-ghost btn-score-action" data-team="blue" data-amount="5" title="Tambah 5 ke Blue">+5 Blue</button>
                    </div>
                </div>

                <div class="round-nav">
                    <button type="button" class="button button-secondary" id="btn-prev-round" {{ $currentRoundIndex === 0 ? 'disabled' : '' }}>
                        ← Sebelumnya
                    </button>
                    <div class="round-dots" id="round-dots-container">
                        @foreach ($rounds as $idx => $r)
                            <span class="{{ $currentRoundIndex === $idx ? 'active' : '' }}" data-index="{{ $idx }}"></span>
                        @endforeach
                    </div>
                    <button type="button" class="button button-primary" id="btn-next-round" {{ $currentRoundIndex >= count($rounds) - 1 ? 'disabled' : '' }}>
                        Berikutnya →
                    </button>
                </div>
            </div>
        @else
            <div style="padding: 40px; text-align: center; background: #14223a; border-radius: 20px;">
                <p>Belum ada soal survei yang tersedia.</p>
                <button type="button" class="button button-primary" id="btn-open-editor-empty">+ Tambah Soal Survei</button>
            </div>
        @endif
    </main>
</div>

{{-- CRUD Editor Modal --}}
@include('partials.growth-100-editor')

<script>
    window.BYC_GAME2 = {
        rounds: @json($rounds),
        state: @json($game2State),
        currentIndex: {{ $currentRoundIndex }}
    };
</script>
@endsection
