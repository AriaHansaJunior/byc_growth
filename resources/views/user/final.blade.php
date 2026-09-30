@extends('layouts.app')

@section('title', 'Final Score — BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
@php
    $teams = $scores['teams'] ?? [];
    $maxScore = -1;
    $topTeams = [];

    foreach ($teams as $t) {
        $total = (int) ($t['total_score'] ?? 0);
        if ($total > $maxScore) {
            $maxScore = $total;
            $topTeams = [$t];
        } elseif ($total === $maxScore && $maxScore >= 0) {
            $topTeams[] = $t;
        }
    }

    $isTie = count($topTeams) > 1;
    $winnerName = $isTie ? 'Tie Game' : ($topTeams[0]['name'] ?? 'Champion Team');
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
        <span class="eyebrow">Game Complete</span>
        <h1>{{ $isTie ? "It's a Tie!" : "{$winnerName} Wins!" }}</h1>
        <p>
            {{ $isTie ? 'All teams performed exceptionally well with high fellowship and sportsmanship.' : 'Dedication and teamwork brought your team to the top of the leaderboard.' }}
        </p>
    </section>

    {{-- Dynamic Final Scoreboard --}}
    <section class="final-board" style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1px;">
        @foreach ($teams as $t)
            @php
                $isWinner = ($maxScore > 0 && ($t['total_score'] ?? 0) === $maxScore);
            @endphp
            <div class="final-team final-{{ $t['theme'] }} {{ $isWinner ? 'is-winner' : '' }}" style="flex: 1 1 200px; min-width: 170px;">
                @if ($isWinner)
                    <span class="winner-crown">👑</span>
                @endif
                <span>{{ $t['name'] }}</span>
                <strong>{{ $t['total_score'] ?? 0 }}</strong>
                <small>Total Points</small>
                <div style="margin-top: 14px; font-size: 11px; color: rgba(255,255,255,0.7); display: flex; flex-direction: column; gap: 4px;">
                    <span>Guess Me: <strong>{{ $t['scores']['game1'] ?? 0 }}</strong></span>
                    <span>Growth 100: <strong>{{ $t['scores']['game2'] ?? 0 }}</strong></span>
                </div>
            </div>
        @endforeach
    </section>

    <a href="{{ route('home') }}" class="button button-secondary" style="min-height: 48px; padding: 0 28px; font-size: 15px;">
        <x-icon name="home" /> Return to Home
    </a>
</main>
@endsection
