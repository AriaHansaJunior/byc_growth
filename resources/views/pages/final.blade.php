@extends('layouts.app')

@section('title', 'Final Score — BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
@php
    $finalRed = (int) ($scores['final_red'] ?? 0);
    $finalBlue = (int) ($scores['final_blue'] ?? 0);
    $winner = $finalRed === $finalBlue ? 'Tie' : ($finalRed > $finalBlue ? 'Red Team' : 'Blue Team');
@endphp

<main class="final-screen">
    <div class="confetti confetti-one"></div>
    <div class="confetti confetti-two"></div>
    <div class="confetti confetti-three"></div>
    <div class="confetti confetti-four"></div>

    <a href="{{ route('game.center') }}" class="brand-button" style="align-self: flex-start;" aria-label="Back to Game Center" title="Back to Game Center">
        <x-brand compact="true" />
    </a>

    <section class="winner">
        <div class="trophy">
            <x-icon name="trophy" />
        </div>
        <span class="eyebrow">Permainan selesai</span>
        <h1>{{ $winner === 'Seri' ? 'Hasilnya seri!' : "{$winner} menang!" }}</h1>
        <p>
            {{ $winner === 'Seri' ? 'Kedua tim tampil sama hebat hari ini dengan sportivitas tinggi.' : 'Kerja sama dan semangat bertumbuh membawa tim Anda ke puncak kemenangan.' }}
        </p>
    </section>

    <section class="final-board">
        <div class="final-team final-red {{ $finalRed > $finalBlue ? 'is-winner' : '' }}">
            @if ($finalRed > $finalBlue)
                <span class="winner-crown">👑</span>
            @endif
            <span>Tim Red</span>
            <strong>{{ $finalRed }}</strong>
            <small>Total poin</small>
        </div>

        <div class="final-details">
            <div>
                <span>Guess Me!</span>
                <strong>{{ $scores['game1']['red'] ?? 0 }} — {{ $scores['game1']['blue'] ?? 0 }}</strong>
            </div>
            <div>
                <span>BYC Growth 100</span>
                <strong>{{ $scores['game2']['red'] ?? 0 }} — {{ $scores['game2']['blue'] ?? 0 }}</strong>
            </div>
            <em>Final score</em>
        </div>

        <div class="final-team final-blue {{ $finalBlue > $finalRed ? 'is-winner' : '' }}">
            @if ($finalBlue > $finalRed)
                <span class="winner-crown">👑</span>
            @endif
            <span>Tim Blue</span>
            <strong>{{ $finalBlue }}</strong>
            <small>Total poin</small>
        </div>
    </section>

    <a href="{{ route('home') }}" class="button button-secondary" style="min-height: 48px; padding: 0 28px; font-size: 15px;">
        <x-icon name="home" /> Kembali ke beranda
    </a>
</main>
@endsection
