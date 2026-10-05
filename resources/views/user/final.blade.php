@extends('layouts.app')

@section('title', 'Final Score — BYC GROWTH')
@section('no-header', true)
@section('no-footer', true)

@section('content')
@php
    $rawTeams = !empty($scores['teams']) ? $scores['teams'] : (!empty($teams) ? $teams : []);
    if (empty($rawTeams)) {
        // Fallback for default two-team matches if no dynamic teams loaded
        $rawTeams = [
            [
                'name' => 'Red Team',
                'code' => 'red',
                'theme' => 'red',
                'total_score' => $scores['final_red'] ?? 0,
                'scores' => [
                    'game1' => $scores['game1']['red'] ?? 0,
                    'game2' => $scores['game2']['red'] ?? 0,
                ],
            ],
            [
                'name' => 'Blue Team',
                'code' => 'blue',
                'theme' => 'blue',
                'total_score' => $scores['final_blue'] ?? 0,
                'scores' => [
                    'game1' => $scores['game1']['blue'] ?? 0,
                    'game2' => $scores['game2']['blue'] ?? 0,
                ],
            ],
        ];
    }

    // Sort teams by total_score descending
    $teams = $rawTeams;
    usort($teams, function ($a, $b) {
        $scoreB = (int) ($b['total_score'] ?? $b['score'] ?? 0);
        $scoreA = (int) ($a['total_score'] ?? $a['score'] ?? 0);
        return $scoreB <=> $scoreA;
    });

    // Standard competition ranking (1, 2, 2, 4...)
    $prevScore = null;
    $prevRank = 1;
    foreach ($teams as $idx => &$t) {
        $score = (int) ($t['total_score'] ?? $t['score'] ?? 0);
        $t['total_score'] = $score;
        if ($prevScore === null) {
            $t['rank'] = 1;
        } elseif ($score === $prevScore) {
            $t['rank'] = $prevRank;
        } else {
            $t['rank'] = $idx + 1;
        }
        $prevScore = $score;
        $prevRank = $t['rank'];
    }
    unset($t);

    $teamCount = count($teams);
    $topScore = (int) ($teams[0]['total_score'] ?? 0);
    // If multiple teams share the top score (or tie at 0), it's a tie
    $isTie = ($teamCount > 1 && $topScore === (int) ($teams[1]['total_score'] ?? 0));
    $winnerName = $isTie ? 'Tie Game' : ($teams[0]['name'] ?? 'Champion Team');

    // Categorize top 3 podium teams and remaining spectator teams
    $firstTeam = $teams[0] ?? null;
    $secondTeam = $teams[1] ?? null;
    $thirdTeam = $teams[2] ?? null;
    $spectatorTeams = array_slice($teams, 3);

    // Rule: if 2nd and 3rd place tie, both are 2nd place and there is no 3rd place
    $isSecondThirdTie = false;
    if ($secondTeam && $thirdTeam) {
        $isSecondThirdTie = ((int) ($secondTeam['total_score'] ?? 0) === (int) ($thirdTeam['total_score'] ?? 0));
    }

    $getOrdinal = function ($number) {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }
        return $number . $ends[$number % 10];
    };
@endphp

<main class="final-screen">
    <div class="confetti confetti-one"></div>
    <div class="confetti confetti-two"></div>
    <div class="confetti confetti-three"></div>
    <div class="confetti confetti-four"></div>

    {{-- Brand Home / Back Link (Pinned to top left) --}}
    <a href="{{ (Auth::check() && Auth::user()->isAdmin()) ? route('admin.games') : route('game.center') }}" class="brand-button final-brand-link" aria-label="{{ (Auth::check() && Auth::user()->isAdmin()) ? 'Back to Admin Games' : 'Back to Game Center' }}" title="{{ (Auth::check() && Auth::user()->isAdmin()) ? 'Back to Admin Games' : 'Back to Game Center' }}">
        <x-brand compact="true" />
    </a>

    {{-- Winner / Header Section --}}
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

    @if ($isTie)
        {{-- TIE SCENARIO: Equal balanced cards for all teams --}}
        <section class="final-tie-board">
            @foreach ($teams as $t)
                @php
                    $theme = !empty($t['theme']) ? $t['theme'] : (!empty($t['code']) ? $t['code'] : 'forest');
                    $customColor = !empty($t['color']) ? "background: {$t['color']};" : '';
                @endphp
                <div class="final-team final-{{ $theme }}" style="{{ $customColor }}">
                    <div class="podium-rank-badge tie-badge">TIE</div>
                    <span class="team-label">{{ $t['name'] }}</span>
                    <strong class="team-score">{{ $t['total_score'] ?? 0 }}</strong>
                    <small class="team-points-label">Total Points</small>
                    <div class="podium-subscores">
                        <span>Guess Me: <strong>{{ $t['scores']['game1'] ?? 0 }}</strong></span>
                        <span class="subscore-dot">&bull;</span>
                        <span>Growth 100: <strong>{{ $t['scores']['game2'] ?? 0 }}</strong></span>
                    </div>
                </div>
            @endforeach
        </section>
    @else
        {{-- WINNER PODIUM SCENARIO: 2nd on Left, 1st in Center, 3rd on Right --}}
        <section class="final-podium-container {{ count($teams) === 2 ? 'is-two-teams' : '' }}">
            {{-- Left: 2nd Place --}}
            @if ($secondTeam)
                @php
                    $t = $secondTeam;
                    $theme = !empty($t['theme']) ? $t['theme'] : (!empty($t['code']) ? $t['code'] : 'blue');
                    $customColor = !empty($t['color']) ? "background: {$t['color']};" : '';
                @endphp
                <div class="podium-card podium-card-second final-{{ $theme }}" style="{{ $customColor }}">
                    <div class="podium-rank-badge badge-silver">2nd Place</div>
                    <span class="team-label">{{ $t['name'] }}</span>
                    <strong class="team-score">{{ $t['total_score'] ?? 0 }}</strong>
                    <small class="team-points-label">Total Points</small>
                    <div class="podium-subscores">
                        <span>GM: <strong>{{ $t['scores']['game1'] ?? 0 }}</strong></span>
                        <span class="subscore-dot">&bull;</span>
                        <span>G100: <strong>{{ $t['scores']['game2'] ?? 0 }}</strong></span>
                    </div>
                    <div class="podium-pedestal-base base-silver">
                        <span>2</span>
                    </div>
                </div>
            @endif

            {{-- Center: 1st Place (Winner / Champion) --}}
            @if ($firstTeam)
                @php
                    $t = $firstTeam;
                    $theme = !empty($t['theme']) ? $t['theme'] : (!empty($t['code']) ? $t['code'] : 'forest');
                    $customColor = !empty($t['color']) ? "background: {$t['color']};" : '';
                @endphp
                <div class="podium-card podium-card-first final-{{ $theme }}" style="{{ $customColor }}">
                    <div class="podium-rank-badge badge-gold">
                        <span class="crown-icon">👑</span> 1st Place
                    </div>
                    <span class="team-label">{{ $t['name'] }}</span>
                    <strong class="team-score">{{ $t['total_score'] ?? 0 }}</strong>
                    <small class="team-points-label">Total Points</small>
                    <div class="podium-subscores">
                        <span>GM: <strong>{{ $t['scores']['game1'] ?? 0 }}</strong></span>
                        <span class="subscore-dot">&bull;</span>
                        <span>G100: <strong>{{ $t['scores']['game2'] ?? 0 }}</strong></span>
                    </div>
                    <div class="podium-pedestal-base base-gold">
                        <span>1</span>
                    </div>
                </div>
            @endif

            {{-- Right: 3rd Place (or 2nd Place if Tied) --}}
            @if ($thirdTeam)
                @php
                    $t = $thirdTeam;
                    $theme = !empty($t['theme']) ? $t['theme'] : (!empty($t['code']) ? $t['code'] : 'gold');
                    $customColor = !empty($t['color']) ? "background: {$t['color']};" : '';
                    $isTiedWithSecond = $isSecondThirdTie;
                @endphp
                <div class="podium-card {{ $isTiedWithSecond ? 'podium-card-second' : 'podium-card-third' }} final-{{ $theme }}" style="{{ $customColor }}">
                    <div class="podium-rank-badge {{ $isTiedWithSecond ? 'badge-silver' : 'badge-bronze' }}">
                        {{ $isTiedWithSecond ? '2nd Place' : '3rd Place' }}
                    </div>
                    <span class="team-label">{{ $t['name'] }}</span>
                    <strong class="team-score">{{ $t['total_score'] ?? 0 }}</strong>
                    <small class="team-points-label">Total Points</small>
                    <div class="podium-subscores">
                        <span>GM: <strong>{{ $t['scores']['game1'] ?? 0 }}</strong></span>
                        <span class="subscore-dot">&bull;</span>
                        <span>G100: <strong>{{ $t['scores']['game2'] ?? 0 }}</strong></span>
                    </div>
                    <div class="podium-pedestal-base {{ $isTiedWithSecond ? 'base-silver' : 'base-bronze' }}">
                        <span>{{ $isTiedWithSecond ? '2' : '3' }}</span>
                    </div>
                </div>
            @endif
        </section>

        {{-- 4th Place and Beyond: Equal horizontal spectator cards below podium --}}
        @if (!empty($spectatorTeams))
            <section class="final-spectators-row">
                @foreach ($spectatorTeams as $rem)
                    @php
                        $theme = !empty($rem['theme']) ? $rem['theme'] : (!empty($rem['code']) ? $rem['code'] : 'forest');
                        $customColor = !empty($rem['color']) ? "background: {$rem['color']};" : '';
                    @endphp
                    <div class="spectator-card final-{{ $theme }}" style="{{ $customColor }}">
                        <span class="spectator-rank-pill">{{ $getOrdinal($rem['rank']) }}</span>
                        <div class="spectator-meta">
                            <span class="spectator-name">{{ $rem['name'] }}</span>
                            <span class="spectator-breakdown">GM: {{ $rem['scores']['game1'] ?? 0 }} &bull; G100: {{ $rem['scores']['game2'] ?? 0 }}</span>
                        </div>
                        <div class="spectator-total">
                            <strong>{{ $rem['total_score'] ?? 0 }}</strong>
                            <small>pts</small>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    @endif

    {{-- Bottom Return Action --}}
    <a href="{{ (Auth::check() && Auth::user()->isAdmin()) ? route('admin.dashboard') : route('home') }}" class="button button-secondary final-return-btn">
        <x-icon name="home" /> Return to Home
    </a>
</main>
@endsection
