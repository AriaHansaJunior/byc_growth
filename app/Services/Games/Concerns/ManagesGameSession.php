<?php

namespace App\Services\Games\Concerns;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;

trait ManagesGameSession
{
    /**
     * Get persistent game state from MySQL with dynamic teams.
     */
    public function getGameState(): array
    {
        $game1 = $this->getGame('game1');
        $game2 = $this->getGame('game2');

        $g1Teams = $this->getTeamsWithScores('game1');
        $g2Teams = $this->getTeamsWithScores('game2');

        $g1State = GameState::firstOrCreate(
            ['game_id' => $game1->id],
            ['current_round_index' => 0, 'state_data' => ['revealed' => []]]
        );

        $g2State = GameState::firstOrCreate(
            ['game_id' => $game2->id],
            ['current_round_index' => 0, 'state_data' => ['crosses' => [], 'revealed' => []]]
        );

        $g1Scores = [];
        foreach ($g1Teams as $t) {
            $g1Scores[$t['code']] = $t['score'];
            $g1Scores[(string) $t['id']] = $t['score'];
        }
        if (!isset($g1Scores['red']) && isset($g1Teams[0])) $g1Scores['red'] = $g1Teams[0]['score'];
        if (!isset($g1Scores['blue']) && isset($g1Teams[1])) $g1Scores['blue'] = $g1Teams[1]['score'];
        if (!isset($g1Scores['red'])) $g1Scores['red'] = 0;
        if (!isset($g1Scores['blue'])) $g1Scores['blue'] = 0;

        $g2Scores = [];
        foreach ($g2Teams as $t) {
            $g2Scores[$t['code']] = $t['score'];
            $g2Scores[(string) $t['id']] = $t['score'];
        }
        if (!isset($g2Scores['red']) && isset($g2Teams[0])) $g2Scores['red'] = $g2Teams[0]['score'];
        if (!isset($g2Scores['blue']) && isset($g2Teams[1])) $g2Scores['blue'] = $g2Teams[1]['score'];
        if (!isset($g2Scores['red'])) $g2Scores['red'] = 0;
        if (!isset($g2Scores['blue'])) $g2Scores['blue'] = 0;

        return [
            'teams' => array_merge($g1Teams, $g2Teams),
            'game1' => [
                'scores' => $g1Scores,
                'teams' => $g1Teams,
                'current_round' => (int) $g1State->current_round_index,
                'revealed' => (array) ($g1State->state_data['revealed'] ?? []),
            ],
            'game2' => [
                'scores' => $g2Scores,
                'teams' => $g2Teams,
                'current_round' => (int) $g2State->current_round_index,
                'crosses' => (array) ($g2State->state_data['crosses'] ?? []),
                'revealed' => (array) ($g2State->state_data['revealed'] ?? []),
            ],
        ];
    }

    /**
     * Get Game definitions with database visibility status.
     */
    public function getGames(bool $onlyVisible = false): array
    {
        $g1 = $this->getGame('game1');
        $g2 = $this->getGame('game2');

        $games = [
            [
                'id' => 'guess-me',
                'code' => 'game1',
                'order' => '01',
                'title' => 'Guess Me!',
                'tag' => 'Visual Word Clues',
                'description' => 'Test your team speed and intuition by decoding secret words from custom visual clues and letter slot hints.',
                'icon' => '?',
                'user_route' => 'game.guess-me',
                'admin_route' => 'admin.games.guess-me.host',
                'route' => 'game.guess-me',
                'theme' => 'forest',
                'status' => 'available',
                'features' => ['Picture Clues', 'Letter Slots', 'Team vs Team'],
                'is_hidden' => (bool) $g1->is_hidden,
                'db_id' => $g1->id,
            ],
            [
                'id' => 'growth-100',
                'code' => 'game2',
                'order' => '02',
                'title' => 'BYC GROWTH 100',
                'tag' => 'Survey Trivia',
                'description' => 'Discover the top survey answers, rack up to 100 points per round, and steal points when the opposing team strikes out.',
                'icon' => '100',
                'user_route' => 'game.growth-100',
                'admin_route' => 'admin.games.growth-100.host',
                'route' => 'game.growth-100',
                'theme' => 'cream',
                'status' => 'available',
                'features' => ['Top Survey Answers', 'Card Reveal', '3-Strike Steal'],
                'is_hidden' => (bool) $g2->is_hidden,
                'db_id' => $g2->id,
            ],
        ];

        if ($onlyVisible) {
            $games = array_values(array_filter($games, fn($g) => !$g['is_hidden']));
        }

        return $games;
    }

    /**
     * Toggle visibility of an entire game.
     */
    public function toggleGameVisibility(string|int $gameIdOrCode): array
    {
        if (is_numeric($gameIdOrCode)) {
            $game = Game::find((int) $gameIdOrCode);
        } else {
            $code = in_array($gameIdOrCode, ['guess-me', 'game1'], true) ? 'game1' : 'game2';
            $game = $this->getGame($code);
        }

        if (!$game) {
            return ['success' => false, 'error' => 'Game not found.'];
        }

        $game->is_hidden = !$game->is_hidden;
        $game->save();

        return [
            'success' => true,
            'game_id' => $game->id,
            'code' => $game->code,
            'is_hidden' => (bool) $game->is_hidden,
        ];
    }

    /**
     * Toggle visibility of an individual round.
     */
    public function toggleRoundVisibility(int $roundId): array
    {
        $round = GameRound::find($roundId);
        if (!$round) {
            return ['success' => false, 'error' => 'Round not found.'];
        }

        $round->is_hidden = !$round->is_hidden;
        $round->save();

        return [
            'success' => true,
            'round_id' => $round->id,
            'is_hidden' => (bool) $round->is_hidden,
        ];
    }

    /**
     * Update Game 1 active round or reveal state in MySQL.
     */
    public function updateGame1State(int $roundIndex, ?bool $revealed = null): array
    {
        $game1 = $this->getGame('game1');
        $rounds = $this->getGuessMeRounds();

        $roundIndex = max(0, min(max(0, count($rounds) - 1), $roundIndex));
        $roundId = (string) ($rounds[$roundIndex]['id'] ?? ($roundIndex + 1));

        $state = GameState::firstOrCreate(
            ['game_id' => $game1->id],
            ['current_round_index' => 0, 'state_data' => ['revealed' => []]]
        );

        $data = $state->state_data ?: [];
        $data['revealed'] = $data['revealed'] ?? [];

        if ($revealed !== null) {
            $data['revealed'][$roundId] = $revealed;
        }

        $state->update([
            'current_round_index' => $roundIndex,
            'state_data' => $data,
        ]);

        return [
            'success' => true,
            'state' => $this->getGameState()['game1'],
        ];
    }

    /**
     * Update Game 2 active round, revealed answers, or crosses in MySQL.
     */
    public function updateGame2State(int $roundIndex, ?int $answerIndex = null, ?bool $revealed = null, ?int $crosses = null, ?bool $revealAll = null): array
    {
        $game2 = $this->getGame('game2');
        $rounds = $this->getGrowth100Rounds();

        $roundIndex = max(0, min(max(0, count($rounds) - 1), $roundIndex));
        $roundId = (string) ($rounds[$roundIndex]['id'] ?? ($roundIndex + 1));

        $state = GameState::firstOrCreate(
            ['game_id' => $game2->id],
            ['current_round_index' => 0, 'state_data' => ['crosses' => [], 'revealed' => []]]
        );

        $data = $state->state_data ?: [];
        $data['revealed'] = $data['revealed'] ?? [];
        $data['crosses'] = $data['crosses'] ?? [];

        if (!isset($data['revealed'][$roundId])) {
            $data['revealed'][$roundId] = [];
        }
        if (!isset($data['crosses'][$roundId])) {
            $data['crosses'][$roundId] = 0;
        }

        if ($crosses !== null) {
            $data['crosses'][$roundId] = max(0, min(3, $crosses));
        }

        if ($revealAll === true) {
            $totalAnswers = count($rounds[$roundIndex]['answers'] ?? []);
            $data['revealed'][$roundId] = range(0, max(0, $totalAnswers - 1));
        } elseif ($revealAll === false) {
            $data['revealed'][$roundId] = [];
        } elseif ($answerIndex !== null) {
            $currentRevealed = $data['revealed'][$roundId] ?? [];
            if ($revealed === true && !in_array($answerIndex, $currentRevealed)) {
                $currentRevealed[] = $answerIndex;
            } elseif ($revealed === false) {
                $currentRevealed = array_values(array_filter($currentRevealed, fn($idx) => $idx !== $answerIndex));
            } elseif ($revealed === null) {
                if (in_array($answerIndex, $currentRevealed)) {
                    $currentRevealed = array_values(array_filter($currentRevealed, fn($idx) => $idx !== $answerIndex));
                } else {
                    $currentRevealed[] = $answerIndex;
                }
            }
            $data['revealed'][$roundId] = array_values(array_unique($currentRevealed));
        }

        $state->update([
            'current_round_index' => $roundIndex,
            'state_data' => $data,
        ]);

        // If an answer was revealed or hidden and this round was already awarded to a team, sync awarded points
        $dbRound = GameRound::where('game_id', $game2->id)->find($roundId);
        if (($answerIndex !== null || $revealAll !== null) && $dbRound && $dbRound->awarded_team_id) {
            $newPoints = 0;
            $activeRevealed = $data['revealed'][$roundId] ?? [];
            foreach ($dbRound->answers as $aIdx => $ans) {
                if (in_array($aIdx, $activeRevealed)) {
                    $newPoints += (int) $ans->points;
                }
            }
            $prevAwarded = (int) $dbRound->awarded_points;
            $diff = $newPoints - $prevAwarded;
            if ($diff !== 0) {
                $score = GameScore::firstOrCreate(['game_id' => $game2->id, 'team_id' => $dbRound->awarded_team_id], ['score' => 0]);
                $score->update(['score' => max(0, (int) $score->score + $diff)]);
                $dbRound->update(['awarded_points' => $newPoints]);
            }
        }

        return [
            'success' => true,
            'state' => $this->getGameState()['game2'],
        ];
    }

    /**
     * Reset Game 2 revealed answers and crosses when entering, refreshing, or leaving the page.
     * Round questions, configurations, and team scores remain intact.
     */
    public function resetGame2Revealed(): void
    {
        $game2 = $this->getGame('game2');
        $state = GameState::firstOrCreate(
            ['game_id' => $game2->id],
            ['current_round_index' => 0, 'state_data' => ['crosses' => [], 'revealed' => []]]
        );

        $data = $state->state_data ?: [];
        $data['revealed'] = [];
        $data['crosses'] = [];

        $state->update([
            'state_data' => $data,
        ]);
    }

    /**
     * Reset Game State to initial in MySQL.
     * Scores = 0, revealed = [], crosses = [], current_round_index = 0, round awards = null.
     * Questions and image records are PRESERVED.
     */
    public function resetGame(): array
    {
        $game1 = $this->getGame('game1');
        $game2 = $this->getGame('game2');

        // Reset all team scores to 0
        GameScore::query()->update(['score' => 0]);

        // Reset point awards on rounds
        GameRound::query()->update([
            'awarded_team_id' => null,
            'awarded_points' => 0,
        ]);

        // Reset game states
        GameState::where('game_id', $game1->id)->update([
            'current_round_index' => 0,
            'state_data' => ['revealed' => []],
        ]);

        GameState::where('game_id', $game2->id)->update([
            'current_round_index' => 0,
            'state_data' => ['crosses' => [], 'revealed' => []],
        ]);

        return [
            'success' => true,
            'message' => 'Game state reset successfully in MySQL.',
            'state' => $this->getGameState(),
            'final_scores' => $this->getFinalScores(),
        ];
    }
}
