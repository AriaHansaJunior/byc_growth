@extends('layouts.app')

@section('title', 'Game Center — BYC GROWTH')

@section('content')
<div class="game-center-hub">
    {{-- Game Center Hero --}}
    <section class="game-center-hero">
        <div class="game-center-hero-content">
            <span class="eyebrow">Interactive Gaming Arena</span>
            <h1>BYC Game Center</h1>
            <p>
                The central arena for BYC Growth fellowship games. Choose a challenge below to launch an active session, test team synergy, and celebrate every growth milestone together.
            </p>
            <div class="game-center-hero-actions">
                <button type="button" class="button button-secondary" id="btn-how-to-play">
                    <x-icon name="book" /> Rules & How to Play
                </button>
            </div>
        </div>

        <div class="game-center-art-frame">
            <img src="{{ asset('assets/images/BYC_Growth.jpg') }}" alt="BYC Growth Team Games">
        </div>
    </section>

    {{-- Game Cards Grid --}}
    <section class="game-center-selection" id="game-selection">
        <div class="destinations-heading" style="margin-bottom: 32px;">
            <div>
                <span class="eyebrow" style="color: var(--forest);">Game Selection</span>
                <h2 style="color: var(--ink);">Choose Your Challenge</h2>
            </div>
            <p style="color: var(--muted);">Select a game below to launch. Each game is hosted live with real-time scoring and team competition.</p>
        </div>

        <div class="game-center-grid">
            @foreach($games as $game)
                @php
                    $routeExists = !empty($game['route']) && \Illuminate\Support\Facades\Route::has($game['route']);
                    $isAvailable = ($game['status'] ?? 'available') === 'available' && $routeExists;
                    $playUrl = $isAvailable ? route($game['route']) : '#';
                @endphp
                <article class="gc-card gc-card--{{ $game['theme'] ?? 'forest' }} {{ !$isAvailable ? 'gc-card--disabled' : '' }}" id="game-card-{{ $game['id'] }}">
                    <div class="gc-card-header">
                        <div class="gc-meta-tags">
                            <span class="gc-order-badge">Game {{ $game['order'] }}</span>
                            <span class="gc-category-tag">{{ $game['tag'] }}</span>
                        </div>
                        <div class="gc-icon-badge" aria-hidden="true">
                            {{ $game['icon'] }}
                        </div>
                    </div>

                    <div class="gc-card-body">
                        <h3 class="gc-card-title">{{ $game['title'] }}</h3>
                        <p class="gc-card-desc">{{ $game['description'] }}</p>
                    </div>

                    <div class="gc-card-footer">
                        @if($isAvailable)
                            <a href="{{ $playUrl }}" class="button button-primary gc-play-btn" id="btn-play-{{ $game['id'] }}">
                                Play Now
                            </a>
                        @else
                            <button type="button" class="button button-ghost gc-play-btn" disabled>
                                Coming Soon
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- Overall Scoreboard --}}
    <section class="gc-scoreboard">
        <div class="gc-scoreboard-info">
            <span class="eyebrow">Overall Standings</span>
            <h2 style="margin: 0; font: 800 28px 'Manrope', sans-serif;">Team Scoreboard</h2>
            <p style="margin: 4px 0 0; color: var(--muted); font-size: 14px;">Combined cumulative scores across all games.</p>
        </div>

        @php
            $activeBoardTeams = $finalScores['teams'] ?? $teams ?? [];
            $teamCount = count($activeBoardTeams);
        @endphp

        <div class="gc-scoreboard-teams">
            @if($teamCount <= 3)
                <div class="gc-scoreboard-row">
                    @foreach($activeBoardTeams as $t)
                        @php
                            $tScore = $t['total_score'] ?? $t['score'] ?? 0;
                            $tColor = $t['color'] ?? '#284e3b';
                            $tTheme = in_array($t['code'] ?? '', ['red', 'blue', 'forest', 'gold', 'purple', 'teal']) ? $t['code'] : 'forest';
                        @endphp
                        <div class="team-score team-{{ $tTheme }}" style="--team-accent: {{ $tColor }};">
                            <span class="team-name">{{ $t['name'] }}</span>
                            <strong class="team-score-num">{{ $tScore }}</strong>
                        </div>
                    @endforeach
                </div>
            @else
                @php
                    $half = (int) ceil($teamCount / 2);
                    $row1 = array_slice($activeBoardTeams, 0, $half);
                    $row2 = array_slice($activeBoardTeams, $half);
                @endphp
                <div class="gc-scoreboard-row">
                    @foreach($row1 as $t)
                        @php
                            $tScore = $t['total_score'] ?? $t['score'] ?? 0;
                            $tColor = $t['color'] ?? '#284e3b';
                            $tTheme = in_array($t['code'] ?? '', ['red', 'blue', 'forest', 'gold', 'purple', 'teal']) ? $t['code'] : 'forest';
                        @endphp
                        <div class="team-score team-{{ $tTheme }}" style="--team-accent: {{ $tColor }};">
                            <span class="team-name">{{ $t['name'] }}</span>
                            <strong class="team-score-num">{{ $tScore }}</strong>
                        </div>
                    @endforeach
                </div>
                <div class="gc-scoreboard-row">
                    @foreach($row2 as $t)
                        @php
                            $tScore = $t['total_score'] ?? $t['score'] ?? 0;
                            $tColor = $t['color'] ?? '#284e3b';
                            $tTheme = in_array($t['code'] ?? '', ['red', 'blue', 'forest', 'gold', 'purple', 'teal']) ? $t['code'] : 'forest';
                        @endphp
                        <div class="team-score team-{{ $tTheme }}" style="--team-accent: {{ $tColor }};">
                            <span class="team-name">{{ $t['name'] }}</span>
                            <strong class="team-score-num">{{ $tScore }}</strong>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <a href="{{ route('game.final') }}" class="button button-ghost" style="white-space: nowrap;">
            View Final Results
        </a>
    </section>

    {{-- How to Play Modal --}}
    @include('partials.how-to-play')
</div>
@endsection
