@props(['scores' => ['red' => 65, 'blue' => 80], 'compact' => false])

<div class="score-pair {{ $compact ? 'score-pair-compact' : '' }}">
    <div class="team-score team-red">
        <span>Tim Red</span>
        <strong>{{ $scores['red'] ?? 0 }}</strong>
    </div>
    <div class="score-divider">VS</div>
    <div class="team-score team-blue">
        <span>Tim Blue</span>
        <strong>{{ $scores['blue'] ?? 0 }}</strong>
    </div>
</div>
