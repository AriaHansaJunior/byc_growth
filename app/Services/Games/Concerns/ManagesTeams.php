<?php

namespace App\Services\Games\Concerns;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

trait ManagesTeams
{
    /**
     * Get or create Team model by code.
     */
    public function getTeam(string $code, ?string $gameCode = 'game1'): Team
    {
        $game = $this->getGame($gameCode ?: 'game1');
        return Team::firstOrCreate(
            ['game_id' => $game->id, 'code' => $code],
            [
                'name' => ucfirst($code) . ' Team',
                'color' => $code === 'red' ? '#bd4c42' : ($code === 'blue' ? '#315e89' : '#284e3b'),
                'sort_order' => $code === 'red' ? 0 : ($code === 'blue' ? 1 : 2),
                'is_active' => true,
            ]
        );
    }

    /**
     * Get active teams for a specific game or across games.
     */
    public function getActiveTeams(?string $gameCode = 'game1')
    {
        if ($gameCode !== null) {
            $game = $this->getGame($gameCode);

            // Ensure at least 2 default teams exist for this specific game
            if (Team::where('game_id', $game->id)->where('is_active', true)->count() < 2) {
                $this->getTeam('red', $gameCode);
                $this->getTeam('blue', $gameCode);
            }

            return Team::where('game_id', $game->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return Team::where('is_active', true)
            ->orderBy('game_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get dynamic teams with scores for a specific game or combined across games.
     */
    public function getTeamsWithScores(?string $gameCode = null): array
    {
        $palette = Team::COLOR_PALETTE;

        if ($gameCode !== null) {
            $game = $this->getGame($gameCode);
            $teams = $this->getActiveTeams($gameCode);
            $result = [];

            $paletteByCode = collect($palette)->keyBy('code')->toArray();
            $paletteByColor = collect($palette)->keyBy('color')->toArray();

            foreach ($teams as $idx => $team) {
                $score = GameScore::firstOrCreate(['game_id' => $game->id, 'team_id' => $team->id], ['score' => 0]);
                $paletteItem = $palette[$idx % count($palette)];

                // Resolve theme (must be valid css theme code: red, blue, forest, gold, purple, teal)
                $theme = $paletteItem['theme'];
                if (!empty($team->code) && isset($paletteByCode[$team->code])) {
                    $theme = $team->code;
                } elseif (!empty($team->color) && isset($paletteByCode[$team->color])) {
                    $theme = $team->color;
                } elseif (!empty($team->color) && isset($paletteByColor[$team->color])) {
                    $theme = $paletteByColor[$team->color]['theme'];
                }

                // Resolve hex color
                $color = $paletteItem['color'];
                if (!empty($team->color) && str_starts_with($team->color, '#')) {
                    $color = $team->color;
                } elseif (!empty($team->color) && isset($paletteByCode[$team->color])) {
                    $color = $paletteByCode[$team->color]['color'];
                } elseif (!empty($team->code) && isset($paletteByCode[$team->code])) {
                    $color = $paletteByCode[$team->code]['color'];
                }

                $result[] = [
                    'id' => (int) $team->id,
                    'game_id' => (int) $team->game_id,
                    'code' => $team->code,
                    'name' => $team->name,
                    'color' => $color,
                    'theme' => $theme,
                    'sort_order' => (int) $team->sort_order,
                    'scores' => [
                        $gameCode => (int) $score->score,
                        'game1' => $gameCode === 'game1' ? (int) $score->score : 0,
                        'game2' => $gameCode === 'game2' ? (int) $score->score : 0,
                    ],
                    'score' => (int) $score->score,
                    'total_score' => (int) $score->score,
                ];
            }

            return $result;
        }

        // Combined overall teams for both games
        return $this->getOverallTeamsWithScores();
    }

    /**
     * Get combined overall teams with cumulative scores across all games.
     */
    public function getOverallTeamsWithScores(): array
    {
        $g1Teams = $this->getTeamsWithScores('game1');
        $g2Teams = $this->getTeamsWithScores('game2');

        if (empty($g1Teams) && empty($g2Teams)) {
            return [];
        }

        if (empty($g2Teams)) {
            return $g1Teams;
        }

        if (empty($g1Teams)) {
            return $g2Teams;
        }

        // 1. Check if teams match by normalized name
        $g2ByName = [];
        foreach ($g2Teams as $t2) {
            $key = mb_strtolower(trim($t2['name']));
            $g2ByName[$key] = $t2;
        }

        $allMatchByName = true;
        foreach ($g1Teams as $t1) {
            $key = mb_strtolower(trim($t1['name']));
            if (!isset($g2ByName[$key])) {
                $allMatchByName = false;
                break;
            }
        }

        $overall = [];
        $g2MatchedIds = [];

        if ($allMatchByName) {
            foreach ($g1Teams as $t1) {
                $key = mb_strtolower(trim($t1['name']));
                $t2 = $g2ByName[$key];
                $g2MatchedIds[] = $t2['id'];

                $g1Score = $t1['scores']['game1'] ?? $t1['score'] ?? 0;
                $g2Score = $t2['scores']['game2'] ?? $t2['score'] ?? 0;
                $total = $g1Score + $g2Score;

                $overall[] = [
                    'id' => $t1['id'],
                    'game_id' => $t1['game_id'],
                    'code' => $t1['code'],
                    'name' => $t1['name'],
                    'color' => $t1['color'],
                    'theme' => $t1['theme'],
                    'sort_order' => $t1['sort_order'],
                    'scores' => [
                        'game1' => $g1Score,
                        'game2' => $g2Score,
                    ],
                    'score' => $total,
                    'total_score' => $total,
                ];
            }
        } elseif (count($g1Teams) === count($g2Teams)) {
            foreach ($g1Teams as $idx => $t1) {
                $t2 = $g2Teams[$idx];
                $g2MatchedIds[] = $t2['id'];

                $g1Score = $t1['scores']['game1'] ?? $t1['score'] ?? 0;
                $g2Score = $t2['scores']['game2'] ?? $t2['score'] ?? 0;
                $total = $g1Score + $g2Score;

                $overall[] = [
                    'id' => $t1['id'],
                    'game_id' => $t1['game_id'],
                    'code' => $t1['code'],
                    'name' => $t1['name'],
                    'color' => $t1['color'],
                    'theme' => $t1['theme'],
                    'sort_order' => $t1['sort_order'],
                    'scores' => [
                        'game1' => $g1Score,
                        'game2' => $g2Score,
                    ],
                    'score' => $total,
                    'total_score' => $total,
                ];
            }
        } else {
            foreach ($g1Teams as $idx => $t1) {
                $key = mb_strtolower(trim($t1['name']));
                $t2 = $g2ByName[$key] ?? null;

                if (!$t2 && isset($g2Teams[$idx]) && !in_array($g2Teams[$idx]['id'], $g2MatchedIds)) {
                    $t2 = $g2Teams[$idx];
                }

                $g1Score = $t1['scores']['game1'] ?? $t1['score'] ?? 0;
                $g2Score = 0;
                if ($t2) {
                    $g2MatchedIds[] = $t2['id'];
                    $g2Score = $t2['scores']['game2'] ?? $t2['score'] ?? 0;
                }
                $total = $g1Score + $g2Score;

                $overall[] = [
                    'id' => $t1['id'],
                    'game_id' => $t1['game_id'],
                    'code' => $t1['code'],
                    'name' => $t1['name'],
                    'color' => $t1['color'],
                    'theme' => $t1['theme'],
                    'sort_order' => $t1['sort_order'],
                    'scores' => [
                        'game1' => $g1Score,
                        'game2' => $g2Score,
                    ],
                    'score' => $total,
                    'total_score' => $total,
                ];
            }

            foreach ($g2Teams as $t2) {
                if (!in_array($t2['id'], $g2MatchedIds)) {
                    $g2Score = $t2['scores']['game2'] ?? $t2['score'] ?? 0;
                    $overall[] = [
                        'id' => $t2['id'],
                        'game_id' => $t2['game_id'],
                        'code' => $t2['code'],
                        'name' => $t2['name'],
                        'color' => $t2['color'],
                        'theme' => $t2['theme'],
                        'sort_order' => $t2['sort_order'],
                        'scores' => [
                            'game1' => 0,
                            'game2' => $g2Score,
                        ],
                        'score' => $g2Score,
                        'total_score' => $g2Score,
                    ];
                }
            }
        }

        return $overall;
    }

    /**
     * Configure dynamic teams (2, 3, 4, or more teams) with persistence in MySQL.
     */
    public function configureTeams(string|array $gameOrTeamsData, ?array $teamsData = null): array
    {
        if (is_array($gameOrTeamsData)) {
            $gameCode = 'game1';
            $teams = $gameOrTeamsData;
        } else {
            $gameCode = $gameOrTeamsData;
            $teams = $teamsData ?? [];
        }

        if (count($teams) < 2) {
            return ['success' => false, 'error' => 'A minimum of 2 teams is required for gameplay.'];
        }

        if ($gameCode === 'all') {
            $r1 = $this->configureTeams('game1', $teams);
            if (!$r1['success']) {
                return $r1;
            }
            $r2 = $this->configureTeams('game2', $teams);
            if (!$r2['success']) {
                return $r2;
            }
            return [
                'success' => true,
                'message' => 'Teams configured successfully across all games.',
                'teams' => $this->getTeamsWithScores(),
                'final_scores' => $this->getFinalScores(),
            ];
        }

        $palette = Team::COLOR_PALETTE;
        $seenNames = [];

        foreach ($teams as $idx => $t) {
            $name = trim($t['name'] ?? '');
            if ($name === '') {
                return ['success' => false, 'error' => "Team " . ($idx + 1) . " name cannot be empty."];
            }
            $lower = mb_strtolower($name);
            if (in_array($lower, $seenNames)) {
                return ['success' => false, 'error' => "Duplicate team name '{$name}'. Each team must have a unique name."];
            }
            $seenNames[] = $lower;
        }

        $game = $this->getGame($gameCode);

        return DB::transaction(function () use ($teams, $palette, $game, $gameCode) {
            $processedIds = [];
            $existingTeams = Team::where('game_id', $game->id)->orderBy('sort_order')->orderBy('id')->get();

            foreach ($teams as $idx => $t) {
                $id = isset($t['id']) && $t['id'] !== '' ? (int) $t['id'] : null;
                $name = trim($t['name']);
                $paletteItem = $palette[$idx % count($palette)];
                $color = !empty($t['color']) ? $t['color'] : $paletteItem['color'];
                $defaultCode = $paletteItem['code'];

                // 1. If explicit ID provided and belongs to this game
                $team = $id ? Team::where('game_id', $game->id)->find($id) : null;

                // 2. Otherwise reuse existing team of this game at this sort position to preserve stable identity
                if (!$team && isset($existingTeams[$idx])) {
                    $team = $existingTeams[$idx];
                }

                if ($team) {
                    $team->update([
                        'name' => $name,
                        'color' => $color,
                        'sort_order' => $idx,
                        'is_active' => true,
                    ]);
                    $processedIds[] = $team->id;
                } else {
                    $code = $defaultCode;
                    if (Team::where('game_id', $game->id)->where('code', $code)->exists()) {
                        $code = 'team_' . ($idx + 1) . '_' . uniqid();
                    }

                    $newTeam = Team::create([
                        'game_id' => $game->id,
                        'code' => $code,
                        'name' => $name,
                        'color' => $color,
                        'sort_order' => $idx,
                        'is_active' => true,
                    ]);

                    GameScore::firstOrCreate(['game_id' => $game->id, 'team_id' => $newTeam->id], ['score' => 0]);

                    $processedIds[] = $newTeam->id;
                }
            }

            // Deactivate only teams belonging to this specific game not in processedIds
            Team::where('game_id', $game->id)->whereNotIn('id', $processedIds)->update(['is_active' => false]);

            return [
                'success' => true,
                'message' => 'Teams configured successfully.',
                'teams' => $this->getTeamsWithScores($gameCode),
                'final_scores' => $this->getFinalScores(),
            ];
        });
    }

    /**
     * Calculate Final Scores from MySQL with dynamic teams and backward compatibility.
     */
    public function getFinalScores(): array
    {
        $g1Teams = $this->getTeamsWithScores('game1');
        $g2Teams = $this->getTeamsWithScores('game2');

        $g1Red = 0; $g1Blue = 0;
        $g2Red = 0; $g2Blue = 0;

        foreach ($g1Teams as $idx => $t) {
            if ($t['code'] === 'red' || $idx === 0) {
                if ($g1Red === 0) $g1Red = $t['score'];
            }
            if ($t['code'] === 'blue' || $idx === 1) {
                if ($g1Blue === 0) $g1Blue = $t['score'];
            }
        }

        foreach ($g2Teams as $idx => $t) {
            if ($t['code'] === 'red' || $idx === 0) {
                if ($g2Red === 0) $g2Red = $t['score'];
            }
            if ($t['code'] === 'blue' || $idx === 1) {
                if ($g2Blue === 0) $g2Blue = $t['score'];
            }
        }

        $totalRed = $g1Red + $g2Red;
        $totalBlue = $g1Blue + $g2Blue;

        return [
            'final_red' => $totalRed,
            'final_blue' => $totalBlue,
            'game1' => [
                'red' => $g1Red,
                'blue' => $g1Blue,
                'teams' => $g1Teams,
            ],
            'game2' => [
                'red' => $g2Red,
                'blue' => $g2Blue,
                'teams' => $g2Teams,
            ],
            'teams' => $this->getOverallTeamsWithScores(),
            'teams_by_game' => [
                'game1' => $g1Teams,
                'game2' => $g2Teams,
            ],
        ];
    }

    /**
     * Update scores for Game 1 or Game 2 in MySQL for any team.
     */
    public function updateScore(string $gameCode, string|int $teamIdentifier, int $amount, bool $isAbsolute = false): array
    {
        if (!in_array($gameCode, ['game1', 'game2'])) {
            return ['success' => false, 'error' => 'Invalid game code.'];
        }

        $game = $this->getGame($gameCode);

        // Find team by ID or by code strictly within this game
        if (is_numeric($teamIdentifier)) {
            $team = Team::where('game_id', $game->id)->find((int) $teamIdentifier);
        } else {
            $team = Team::where('game_id', $game->id)->where('is_active', true)->where('code', $teamIdentifier)->first();
            if (!$team) {
                if ($teamIdentifier === 'red') {
                    $team = Team::where('game_id', $game->id)->where('is_active', true)->orderBy('sort_order')->first();
                } elseif ($teamIdentifier === 'blue') {
                    $team = Team::where('game_id', $game->id)->where('is_active', true)->orderBy('sort_order')->skip(1)->first();
                }
            }
            if (!$team) {
                $team = Team::where('game_id', $game->id)->where('code', $teamIdentifier)->first();
            }
        }

        if (!$team) {
            return ['success' => false, 'error' => "Team '{$teamIdentifier}' not found for game '{$gameCode}'."];
        }

        $gameScore = GameScore::firstOrCreate(
            ['game_id' => $game->id, 'team_id' => $team->id],
            ['score' => 0]
        );

        if ($isAbsolute) {
            $newScore = max(0, $amount);
        } else {
            $newScore = max(0, (int) $gameScore->score + $amount);
        }

        $gameScore->update(['score' => $newScore]);

        $state = $this->getGameState();

        return [
            'success' => true,
            'team_id' => $team->id,
            'team_code' => $team->code,
            'new_score' => $newScore,
            'scores' => $state[$gameCode]['scores'],
            'teams' => $this->getTeamsWithScores($gameCode),
            'final_scores' => $this->getFinalScores(),
        ];
    }

    /**
     * Universal round point assignment: Exactly ONE team receives points for a completed round.
     * Moving recipient transfers points atomically and prevents duplicates.
     */
    public function assignRoundPoints(string $gameCode, int $roundId, ?int $teamId, ?int $pointsOverride = null): array
    {
        if (!in_array($gameCode, ['game1', 'game2'])) {
            return ['success' => false, 'error' => 'Invalid game code.'];
        }

        $game = $this->getGame($gameCode);
        $round = GameRound::where('game_id', $game->id)->find($roundId);

        if (!$round) {
            return ['success' => false, 'error' => 'Round not found for this game.'];
        }

        if ($teamId !== null) {
            $team = Team::where('is_active', true)->find($teamId);
            if (!$team) {
                return ['success' => false, 'error' => 'Target team not found or inactive.'];
            }
            if ((int) $team->game_id !== (int) $round->game_id) {
                return ['success' => false, 'error' => 'A round cannot award points to a team belonging to another game.'];
            }
        }

        // Determine points to award
        if ($gameCode === 'game2') {
            // Growth 100: points strictly derived from revealed survey answers
            $state = GameState::where('game_id', $game->id)->first();
            $revealedIdxs = $state ? ($state->state_data['revealed'][(string) $round->id] ?? []) : [];
            $sum = 0;
            foreach ($round->answers as $aIdx => $ans) {
                if (in_array($aIdx, $revealedIdxs)) {
                    $sum += (int) $ans->points;
                }
            }

            // Security: arbitrary client-provided score cannot bypass revealed-answer scoring
            if ($pointsOverride !== null && (int) $pointsOverride !== $sum) {
                return [
                    'success' => false,
                    'error' => 'Arbitrary scores cannot bypass revealed survey answer scoring in Growth 100.',
                ];
            }

            $points = $sum;
        } elseif ($pointsOverride !== null) {
            $points = max(0, $pointsOverride);
        } else {
            $points = (int) $round->score;
        }

        return DB::transaction(function () use ($game, $round, $teamId, $points, $gameCode) {
            $prevTeamId = $round->awarded_team_id;
            $prevPoints = (int) $round->awarded_points;

            // Case A: Clicking the already awarded team -> unassign (toggle off)
            if ($teamId !== null && $prevTeamId === $teamId) {
                if ($prevPoints > 0) {
                    $prevScore = GameScore::firstOrCreate(['game_id' => $game->id, 'team_id' => $prevTeamId], ['score' => 0]);
                    $prevScore->update(['score' => max(0, $prevScore->score - $prevPoints)]);
                }
                $round->update(['awarded_team_id' => null, 'awarded_points' => 0]);
                $action = 'unassigned';
                $awardedTeamId = null;
                $pointsAwarded = 0;
            }
            // Case B: Assigning to a new/different team -> transfer or assign
            elseif ($teamId !== null) {
                // If there was a previous recipient, deduct points
                if ($prevTeamId && $prevPoints > 0) {
                    $prevScore = GameScore::firstOrCreate(['game_id' => $game->id, 'team_id' => $prevTeamId], ['score' => 0]);
                    $prevScore->update(['score' => max(0, $prevScore->score - $prevPoints)]);
                }

                // Add to new recipient
                $newScore = GameScore::firstOrCreate(['game_id' => $game->id, 'team_id' => $teamId], ['score' => 0]);
                $newScore->update(['score' => $newScore->score + $points]);

                $round->update(['awarded_team_id' => $teamId, 'awarded_points' => $points]);
                $action = $prevTeamId ? 'transferred' : 'assigned';
                $awardedTeamId = $teamId;
                $pointsAwarded = $points;
            }
            // Case C: Explicit unassign ($teamId === null)
            else {
                if ($prevTeamId && $prevPoints > 0) {
                    $prevScore = GameScore::firstOrCreate(['game_id' => $game->id, 'team_id' => $prevTeamId], ['score' => 0]);
                    $prevScore->update(['score' => max(0, $prevScore->score - $prevPoints)]);
                }
                $round->update(['awarded_team_id' => null, 'awarded_points' => 0]);
                $action = 'unassigned';
                $awardedTeamId = null;
                $pointsAwarded = 0;
            }

            return [
                'success' => true,
                'action' => $action,
                'round_id' => (int) $round->id,
                'awarded_team_id' => $awardedTeamId,
                'awarded_points' => $pointsAwarded,
                'transferred_from_team_id' => ($action === 'transferred') ? $prevTeamId : null,
                'teams' => $this->getTeamsWithScores($game->code),
                'overall_teams' => $this->getTeamsWithScores(),
                'final_scores' => $this->getFinalScores(),
            ];
        });
    }
}
