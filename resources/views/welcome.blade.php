@extends('layouts.app')

@section('title', 'BYC GROWTH — Tumbuh Bersama, Menang Bersama')

@section('content')
<main class="home">
    {{-- Hero Section --}}
    <section class="home-hero">
        <div class="home-copy">
            <div class="eyebrow">Interactive team game</div>
            <x-brand />
            <h1>Tumbuh bersama,<br><em>menang bersama.</em></h1>
            <p>Dua permainan, satu semangat. Uji kekompakan tim dan rayakan setiap proses bertumbuh bersama BYC.</p>
            <div class="home-actions">
                <a href="{{ route('game.guess-me') }}" class="button button-primary">
                    Mulai bermain <x-icon name="arrow" />
                </a>
                <button type="button" class="button button-secondary" id="btn-how-to-play">
                    <x-icon name="book" /> Cara Bermain
                </button>
                <button type="button" class="button button-ghost" id="btn-reset-game" style="color: var(--muted);" title="Reset skor dan ronde gameplay">
                    Reset Game
                </button>
            </div>
        </div>

        <div class="hero-art">
            <div class="image-frame">
                <img src="{{ asset('assets/images/BYC_Growth.jpg') }}" alt="BYC Growth bersama karakter Groot">
            </div>
            <div class="round-stamp">
                <strong>2</strong>
                <span>Games</span>
            </div>
        </div>
    </section>

    {{-- Game Selection Section --}}
    <section class="game-select" id="games">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Pilih permainan</span>
                <h2>Siap menguji timmu?</h2>
            </div>
            <p>Mulai dari tebak gambar atau kumpulkan poin lewat survei.</p>
        </div>

        <div class="game-grid">
            <a href="{{ route('game.guess-me') }}" class="game-card guess-card">
                <span class="game-number">01</span>
                <span class="game-icon">?</span>
                <span class="game-card-copy">
                    <small>Game pertama</small>
                    <strong>Guess Me!</strong>
                    <em>Tebak jawaban dari gambar dan clue yang tersedia.</em>
                </span>
                <span class="card-arrow">
                    <x-icon name="arrow" />
                </span>
            </a>

            <a href="{{ route('game.growth-100') }}" class="game-card growth-card">
                <span class="game-number">02</span>
                <span class="game-icon">100</span>
                <span class="game-card-copy">
                    <small>Game kedua</small>
                    <strong>BYC Growth 100</strong>
                    <em>Temukan jawaban survei dan kumpulkan 100 poin.</em>
                </span>
                <span class="card-arrow">
                    <x-icon name="arrow" />
                </span>
            </a>
        </div>
    </section>

    {{-- Overall Progress Section --}}
    <section class="overall">
        <div>
            <span class="eyebrow">Score keseluruhan</span>
            <h2>Perjalanan tim hari ini</h2>
        </div>
        <x-score-pair :scores="['red' => $finalScores['final_red'] ?? 0, 'blue' => $finalScores['final_blue'] ?? 0]" />
        <a href="{{ route('game.final') }}" class="button button-ghost">
            Lihat rincian <x-icon name="arrow" />
        </a>
    </section>

    {{-- Modals --}}
    @include('partials.how-to-play')

    {{-- Reset Confirmation Modal --}}
    <div class="modal-backdrop" id="modal-reset-game" style="display: none;">
        <section aria-label="Konfirmasi Reset Permainan" class="info-modal" style="max-width: 480px; text-align: center; padding: 32px 28px;">
            <div style="font-size: 40px; margin-bottom: 12px;">⚠️</div>
            <h2 style="font: 800 24px 'Manrope', sans-serif; margin-bottom: 10px;">Reset Gameplay?</h2>
            <p style="color: var(--muted); font-size: 14px; line-height: 1.6; margin-bottom: 24px;">
                Tindakan ini akan mengembalikan score Game 1, score Game 2, ronde aktif, status revealed, dan tanda silang ke kondisi awal (0).<br>
                <strong>Data soal dan gambar tidak akan terhapus.</strong>
            </p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button type="button" class="button button-secondary" id="btn-cancel-reset">Batal</button>
                <button type="button" class="button button-danger" id="btn-confirm-reset">Ya, Reset Game</button>
            </div>
        </section>
    </div>
</main>
@endsection
