@extends('layouts.admin')

@section('title', 'Games Management — BYC GROWTH')

@section('page-header')
<div class="admin-page-header">
    <div class="admin-header-title">
        <span class="eyebrow">Universal Game System</span>
        <h1>Games Management</h1>
        <p>
            Configure teams, rounds, survey questions, and monitor live gameplay scoring for fellowship game nights.
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="{{ route('game.center') }}" class="button button-ghost button-sm" target="_blank">
            <x-icon name="gamepad" /> View Public Game Center &rarr;
        </a>
    </div>
</div>
@endsection

@section('content')
    {{-- Games Grid Overview --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 32px;">
        @foreach($games as $game)
            <div class="admin-card" style="margin-bottom: 0;">
                <div class="admin-card-header">
                    <div>
                        <span class="eyebrow" style="color: var(--forest); font-size: 11px;">Game {{ $game['order'] }} &bull; {{ $game['tag'] }}</span>
                        <h2 style="font-size: 22px; margin-top: 4px;">{{ $game['title'] }}</h2>
                    </div>
                    <span class="module-status-badge" style="background: #eaf3dc; color: var(--forest-dark); margin: 0;">Active</span>
                </div>
                <p style="color: var(--muted); font-size: 14px; line-height: 1.5; margin: 0 0 20px;">
                    {{ $game['description'] }}
                </p>
                <div style="background: var(--paper); border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; font-weight: 700; color: var(--ink);">Rounds Bank</span>
                    <strong style="color: var(--forest); font-size: 14px;">10 Configured Rounds</strong>
                </div>
                <div class="admin-action-group" style="width: 100%; justify-content: flex-end;">
                    <a href="{{ route($game['route']) }}" class="button button-secondary button-sm" target="_blank">
                        Launch Game &rarr;
                    </a>
                    <button type="button" class="button button-primary button-sm">
                        ✎ Edit Rounds
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Team Scoring & Configuration Overview --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <h2>Active Team Rosters & Live Scores</h2>
                <small style="color: var(--muted); font-size: 13px;">Universal team configuration shared across game sessions.</small>
            </div>
            <span class="role-badge" style="background: var(--cream); color: var(--forest);">
                {{ count($teams) }} Active Teams
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            @foreach($teams as $team)
                @php
                    $tName = is_array($team) ? ($team['name'] ?? 'Team') : ($team->name ?? 'Team');
                    $tScore = is_array($team) ? ($team['total_score'] ?? $team['score'] ?? 0) : ($team->total_score ?? $team->score ?? 0);
                @endphp
                <div style="background: var(--paper); border: 1.5px solid var(--line); border-radius: 16px; padding: 20px; text-align: center;">
                    <span style="font-size: 28px; display: block; margin-bottom: 6px;">🏆</span>
                    <strong style="font-size: 16px; color: var(--ink); display: block; margin-bottom: 4px;">{{ $tName }}</strong>
                    <div style="font-size: 24px; font-weight: 800; color: var(--forest); font-family: 'Manrope', sans-serif;">
                        {{ $tScore }} <span style="font-size: 12px; font-weight: 600; color: var(--muted);">PTS</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
