@extends('layouts.app')

@section('title', 'Game 01 — Guess Me! | BYC GROWTH')

@section('content')
@php
    $currentRoundIndex = max(0, min(count($rounds) - 1, (int) ($game1State['current_round'] ?? 0)));
    $currentRound = $rounds[$currentRoundIndex] ?? null;
    $roundId = (string) ($currentRound['id'] ?? 1);
    $isRevealed = (bool) ($game1State['revealed'][$roundId] ?? false);
    $redScore = (int) ($game1State['scores']['red'] ?? 0);
    $blueScore = (int) ($game1State['scores']['blue'] ?? 0);
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
    <main class="game-content">
        <div class="game-titlebar">
            <div>
                <span class="eyebrow">Game 01</span>
                <h1>Guess Me!</h1>
            </div>
            <div class="round-counter">
                <span>Ronde</span>
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
                    <img id="guess-image" src="{{ asset('assets/images/' . $currentRound['image']) }}" alt="Visual petunjuk ronde">
                    <span>Visual clue</span>
                </div>
                <div class="guess-content">
                    <span class="clue-label">Clue untuk tim</span>
                    <div class="answer-display {{ $isRevealed ? 'revealed' : '' }}" id="answer-box">
                        <small id="answer-status-label">{{ $isRevealed ? 'Jawaban benar' : 'Lengkapi karakter berikut' }}</small>
                        <strong id="answer-text">{{ $isRevealed ? $currentRound['correct_answer'] : $currentRound['clue'] }}</strong>
                    </div>
                    <p id="answer-description">
                        {{ $isRevealed ? 'Jawaban telah ditampilkan. Berikan poin kepada tim yang menjawab benar.' : 'Diskusikan bersama tim. Host dapat menampilkan jawaban saat waktunya habis.' }}
                    </p>
                    <button type="button" class="button {{ $isRevealed ? 'button-secondary' : 'button-primary' }}" id="btn-toggle-reveal">
                        {{ $isRevealed ? 'Sembunyikan jawaban' : 'Tampilkan jawaban' }}
                    </button>
                </div>
                <div class="point-badge">
                    <strong id="round-score-badge">{{ $currentRound['score'] }}</strong>
                    <span>Poin</span>
                </div>
            </section>
        @else
            <div style="padding: 40px; text-align: center; background: white; border-radius: 20px;">
                <p>Belum ada ronde yang tersedia.</p>
                <button type="button" class="button button-primary" id="btn-open-editor-empty">+ Tambah Ronde</button>
            </div>
        @endif

        {{-- Host Control Panel --}}
        <aside class="host-panel">
            <div class="host-heading">
                <div>
                    <span>Host control</span>
                    <strong>Atur skor ronde</strong>
                </div>
                <button type="button" class="button button-ghost" id="btn-open-editor">
                    <x-icon name="edit" /> Edit ronde
                </button>
            </div>
            <div class="host-teams">
                <div class="host-team host-red">
                    <span>Tim Red</span>
                    <div>
                        <button type="button" class="button button-ghost btn-score-action" data-team="red" data-amount="-5">−5</button>
                        <button type="button" class="button button-ghost btn-score-action" data-team="red" data-amount="5">+5</button>
                        <button type="button" class="button button-primary btn-score-round" data-team="red">+ Poin ronde</button>
                    </div>
                </div>
                <div class="host-team host-blue">
                    <span>Tim Blue</span>
                    <div>
                        <button type="button" class="button button-ghost btn-score-action" data-team="blue" data-amount="-5">−5</button>
                        <button type="button" class="button button-ghost btn-score-action" data-team="blue" data-amount="5">+5</button>
                        <button type="button" class="button button-primary btn-score-round" data-team="blue">+ Poin ronde</button>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Round Navigation --}}
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
    </main>
</div>

{{-- CRUD Editor Modal --}}
@include('partials.guess-me-editor')

<script>
    window.BYC_GAME1 = {
        rounds: @json($rounds),
        state: @json($game1State),
        currentIndex: {{ $currentRoundIndex }},
        isRevealed: {{ $isRevealed ? 'true' : 'false' }}
    };
</script>
@endsection
