@props(['teams' => null, 'scores' => null, 'compact' => false, 'game' => null])

@php
    if (!$teams && $scores) {
        $dbTeams = app(\App\Services\GameStorageService::class)->getTeamsWithScores($game);
        if (!empty($dbTeams)) {
            $teams = $dbTeams;
            foreach ($teams as $idx => &$t) {
                $code = $t['code'] ?? ($idx === 0 ? 'red' : ($idx === 1 ? 'blue' : ''));
                if (isset($scores[$code])) {
                    $t['score'] = $scores[$code];
                } elseif ($code === 'red' && isset($scores['final_red'])) {
                    $t['score'] = $scores['final_red'];
                } elseif ($code === 'blue' && isset($scores['final_blue'])) {
                    $t['score'] = $scores['final_blue'];
                }
            }
            unset($t);
        } else {
            $teams = [
                ['id' => 1, 'code' => 'red', 'name' => 'Team Red', 'theme' => 'red', 'color' => '#bd4c42', 'score' => $scores['red'] ?? $scores['final_red'] ?? 0],
                ['id' => 2, 'code' => 'blue', 'name' => 'Team Blue', 'theme' => 'blue', 'color' => '#315e89', 'score' => $scores['blue'] ?? $scores['final_blue'] ?? 0],
            ];
        }
    } elseif (!$teams) {
        $teams = app(\App\Services\GameStorageService::class)->getTeamsWithScores($game);
    }
@endphp

<div class="score-pair {{ $compact ? 'score-pair-compact' : '' }} {{ count($teams) > 2 ? 'score-pair-multi' : '' }}" id="scoreboard-strip">
    @foreach ($teams as $idx => $team)
        @php
            $scoreVal = $team['score'] ?? ($game ? ($team['scores'][$game] ?? 0) : ($team['total_score'] ?? 0));
            $theme = $team['theme'] ?? $team['code'] ?? 'forest';
        @endphp
        <div class="team-score team-{{ $theme }}" style="--team-accent: {{ $team['color'] ?? '#284e3b' }};" data-team-id="{{ $team['id'] ?? $idx }}" id="scoreboard-team-{{ $team['id'] ?? $idx }}">
            <span class="team-name">{{ $team['name'] }}</span>
            <strong class="team-score-num" id="team-score-val-{{ $team['id'] ?? $idx }}">{{ $scoreVal }}</strong>
        </div>
        @if (count($teams) === 2 && $idx === 0)
            <div class="score-divider">VS</div>
        @endif
    @endforeach
</div>
