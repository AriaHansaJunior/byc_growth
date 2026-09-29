<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\MediaFile;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GameStorageService
{
    protected string $publicImagesDir;

    public function __construct()
    {
        $this->publicImagesDir = public_path('assets/images');

        if (!File::isDirectory($this->publicImagesDir)) {
            File::makeDirectory($this->publicImagesDir, 0755, true);
        }
    }

    /**
     * Get or create Game model by code.
     */
    protected function getGame(string $code): Game
    {
        return Game::firstOrCreate(
            ['code' => $code],
            [
                'title' => $code === 'game1' ? 'Guess Me!' : 'BYC Growth 100',
                'description' => $code === 'game1' ? 'Guess secret answer from clues' : 'Top survey answers challenge',
                'is_active' => true,
            ]
        );
    }

    /**
     * Get or create Team model by code.
     */
    public function getTeam(string $code): Team
    {
        return Team::firstOrCreate(
            ['code' => $code],
            [
                'name' => ucfirst($code) . ' Team',
                'color' => $code === 'red' ? '#bd4c42' : ($code === 'blue' ? '#315e89' : '#284e3b'),
                'sort_order' => $code === 'red' ? 0 : ($code === 'blue' ? 1 : 2),
                'is_active' => true,
            ]
        );
    }

    /**
     * Get all active teams ordered by sort_order.
     */
    public function getActiveTeams()
    {
        // Ensure at least Red and Blue exist as initial default teams
        if (Team::where('is_active', true)->count() < 2) {
            $this->getTeam('red');
            $this->getTeam('blue');
        }

        return Team::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get dynamic teams along with their scores in each game and total score.
     */
    public function getTeamsWithScores(?string $gameCode = null): array
    {
        $game1 = $this->getGame('game1');
        $game2 = $this->getGame('game2');
        $teams = $this->getActiveTeams();

        $palette = Team::COLOR_PALETTE;
        $result = [];

        foreach ($teams as $idx => $team) {
            $g1Score = GameScore::firstOrCreate(['game_id' => $game1->id, 'team_id' => $team->id], ['score' => 0]);
            $g2Score = GameScore::firstOrCreate(['game_id' => $game2->id, 'team_id' => $team->id], ['score' => 0]);

            $theme = $team->code;
            if (!in_array($theme, ['red', 'blue', 'forest', 'gold', 'purple', 'teal'])) {
                $paletteItem = $palette[$idx % count($palette)];
                $theme = $paletteItem['theme'] ?? 'forest';
            }

            $result[] = [
                'id' => (int) $team->id,
                'code' => $team->code,
                'name' => $team->name,
                'color' => $team->color ?: ($palette[$idx % count($palette)]['color'] ?? '#284e3b'),
                'theme' => $theme,
                'sort_order' => (int) $team->sort_order,
                'scores' => [
                    'game1' => (int) $g1Score->score,
                    'game2' => (int) $g2Score->score,
                ],
                'score' => $gameCode ? (int) ($gameCode === 'game1' ? $g1Score->score : $g2Score->score) : ((int) $g1Score->score + (int) $g2Score->score),
                'total_score' => (int) $g1Score->score + (int) $g2Score->score,
            ];
        }

        return $result;
    }

    /**
     * Configure dynamic teams (2, 3, 4, or more teams) with persistence in MySQL.
     */
    public function configureTeams(array $teamsData): array
    {
        if (count($teamsData) < 2) {
            return ['success' => false, 'error' => 'A minimum of 2 teams is required for gameplay.'];
        }

        $palette = Team::COLOR_PALETTE;
        $seenNames = [];

        foreach ($teamsData as $idx => $t) {
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

        $game1 = $this->getGame('game1');
        $game2 = $this->getGame('game2');

        return DB::transaction(function () use ($teamsData, $palette, $game1, $game2) {
            $processedIds = [];
            $existingTeams = Team::orderBy('sort_order')->orderBy('id')->get();

            foreach ($teamsData as $idx => $t) {
                $id = isset($t['id']) && $t['id'] !== '' ? (int) $t['id'] : null;
                $name = trim($t['name']);
                $paletteItem = $palette[$idx % count($palette)];
                $color = !empty($t['color']) ? $t['color'] : $paletteItem['color'];
                $defaultCode = $paletteItem['code'];

                // 1. If explicit ID provided and exists
                $team = $id ? Team::find($id) : null;

                // 2. Otherwise reuse existing team at this sort position to preserve stable identity
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
                    if (Team::where('code', $code)->exists()) {
                        $code = 'team_' . ($idx + 1) . '_' . uniqid();
                    }

                    $newTeam = Team::create([
                        'code' => $code,
                        'name' => $name,
                        'color' => $color,
                        'sort_order' => $idx,
                        'is_active' => true,
                    ]);

                    GameScore::firstOrCreate(['game_id' => $game1->id, 'team_id' => $newTeam->id], ['score' => 0]);
                    GameScore::firstOrCreate(['game_id' => $game2->id, 'team_id' => $newTeam->id], ['score' => 0]);

                    $processedIds[] = $newTeam->id;
                }
            }

            // Deactivate any teams not in processedIds
            Team::whereNotIn('id', $processedIds)->update(['is_active' => false]);

            return [
                'success' => true,
                'message' => 'Teams configured successfully.',
                'teams' => $this->getTeamsWithScores(),
                'final_scores' => $this->getFinalScores(),
            ];
        });
    }

    /**
     * Get persistent game state from MySQL with dynamic teams.
     */
    public function getGameState(): array
    {
        $game1 = $this->getGame('game1');
        $game2 = $this->getGame('game2');
        $teams = $this->getTeamsWithScores();

        $g1State = GameState::firstOrCreate(
            ['game_id' => $game1->id],
            ['current_round_index' => 0, 'state_data' => ['revealed' => []]]
        );

        $g2State = GameState::firstOrCreate(
            ['game_id' => $game2->id],
            ['current_round_index' => 0, 'state_data' => ['crosses' => [], 'revealed' => []]]
        );

        $g1Scores = [];
        $g2Scores = [];
        foreach ($teams as $t) {
            $g1Scores[$t['code']] = $t['scores']['game1'];
            $g2Scores[$t['code']] = $t['scores']['game2'];
            $g1Scores[(string) $t['id']] = $t['scores']['game1'];
            $g2Scores[(string) $t['id']] = $t['scores']['game2'];
        }

        // Backward compatibility fallbacks
        if (!isset($g1Scores['red'])) $g1Scores['red'] = 0;
        if (!isset($g1Scores['blue'])) $g1Scores['blue'] = 0;
        if (!isset($g2Scores['red'])) $g2Scores['red'] = 0;
        if (!isset($g2Scores['blue'])) $g2Scores['blue'] = 0;

        return [
            'teams' => $teams,
            'game1' => [
                'scores' => $g1Scores,
                'current_round' => (int) $g1State->current_round_index,
                'revealed' => (array) ($g1State->state_data['revealed'] ?? []),
            ],
            'game2' => [
                'scores' => $g2Scores,
                'current_round' => (int) $g2State->current_round_index,
                'crosses' => (array) ($g2State->state_data['crosses'] ?? []),
                'revealed' => (array) ($g2State->state_data['revealed'] ?? []),
            ],
        ];
    }

    /**
     * Get Guess Me Rounds from MySQL with point assignment.
     */
    public function getGuessMeRounds(): array
    {
        $game1 = $this->getGame('game1');

        $rounds = GameRound::with('mediaFile')
            ->where('game_id', $game1->id)
            ->orderBy('round_number')
            ->get();

        return $rounds->map(function (GameRound $round) {
            $imageName = $round->image_path ?: ($round->mediaFile ? $round->mediaFile->original_name : 'BYC_Growth.jpg');

            return [
                'id' => (int) $round->id,
                'round_number' => (int) $round->round_number,
                'image' => $imageName,
                'correct_answer' => $round->correct_answer,
                'clue' => $round->clue,
                'score' => (int) $round->score,
                'awarded_team_id' => $round->awarded_team_id ? (int) $round->awarded_team_id : null,
                'awarded_points' => (int) $round->awarded_points,
            ];
        })->toArray();
    }

    /**
     * Get Growth 100 Rounds from MySQL with point assignment.
     */
    public function getGrowth100Rounds(): array
    {
        $game2 = $this->getGame('game2');

        $rounds = GameRound::with(['answers' => function ($query) {
            $query->orderByDesc('points')->orderBy('sort_order');
        }])
            ->where('game_id', $game2->id)
            ->orderBy('round_number')
            ->get();

        return $rounds->map(function (GameRound $round) {
            return [
                'id' => (int) $round->id,
                'round_number' => (int) $round->round_number,
                'question' => $round->question,
                'awarded_team_id' => $round->awarded_team_id ? (int) $round->awarded_team_id : null,
                'awarded_points' => (int) $round->awarded_points,
                'answers' => $round->answers->map(function (GameAnswer $ans) {
                    return [
                        'id' => (int) $ans->id,
                        'text' => $ans->answer_text,
                        'score' => (int) $ans->points,
                        'revealed' => (bool) $ans->is_revealed,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    /**
     * Calculate Final Scores from MySQL with dynamic teams and backward compatibility.
     */
    public function getFinalScores(): array
    {
        $teams = $this->getTeamsWithScores();

        $totalRed = 0;
        $totalBlue = 0;
        $g1Red = 0;
        $g1Blue = 0;
        $g2Red = 0;
        $g2Blue = 0;

        foreach ($teams as $idx => $t) {
            if ($t['code'] === 'red' || $idx === 0) {
                if ($totalRed === 0 && $g1Red === 0 && $g2Red === 0) {
                    $totalRed = $t['total_score'];
                    $g1Red = $t['scores']['game1'];
                    $g2Red = $t['scores']['game2'];
                }
            }
            if ($t['code'] === 'blue' || $idx === 1) {
                if ($totalBlue === 0 && $g1Blue === 0 && $g2Blue === 0) {
                    $totalBlue = $t['total_score'];
                    $g1Blue = $t['scores']['game1'];
                    $g2Blue = $t['scores']['game2'];
                }
            }
        }

        return [
            'final_red' => $totalRed,
            'final_blue' => $totalBlue,
            'game1' => [
                'red' => $g1Red,
                'blue' => $g1Blue,
            ],
            'game2' => [
                'red' => $g2Red,
                'blue' => $g2Blue,
            ],
            'teams' => $teams,
        ];
    }

    /**
     * Validate Clue vs Answer format and character matching.
     */
    public function validateClue(string $answer, string $clue): array
    {
        $cleanAnswer = strtoupper(trim($answer));
        $trimmedClue = trim($clue);

        if ($cleanAnswer === '') {
            return ['valid' => false, 'error' => 'Answer cannot be empty.'];
        }
        if ($trimmedClue === '') {
            return ['valid' => false, 'error' => 'Clue cannot be empty.'];
        }

        $answerChars = mb_str_split($cleanAnswer);
        $answerLen = count($answerChars);

        $clueCharsDirect = mb_str_split($trimmedClue);
        $clueNoSpaces = mb_str_split(str_replace(' ', '', $trimmedClue));

        if (count($clueCharsDirect) === $answerLen) {
            $clueTokens = $clueCharsDirect;
        } elseif (count($clueNoSpaces) === $answerLen) {
            $clueTokens = $clueNoSpaces;
        } else {
            $tokens = array_values(array_filter(explode(' ', $trimmedClue), fn($c) => $c !== ''));
            if (count($tokens) === $answerLen) {
                $clueTokens = $tokens;
            } else {
                return [
                    'valid' => false,
                    'error' => "Clue length (" . count($clueNoSpaces) . ") must match answer length ({$answerLen}).",
                ];
            }
        }

        for ($i = 0; $i < $answerLen; $i++) {
            $c = strtoupper($clueTokens[$i]);
            $a = $answerChars[$i];

            if ($c === '_' || $c === '-' || $c === '.') {
                continue;
            }

            if ($c !== $a) {
                return [
                    'valid' => false,
                    'error' => "Character at position " . ($i + 1) . " ('{$c}') does not match letter in answer ('{$a}').",
                ];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate Growth 100 Answers.
     */
    public function validateGrowthAnswers(array $answers): array
    {
        if (empty($answers)) {
            return ['valid' => false, 'error' => 'Answer list cannot be empty.'];
        }

        $totalScore = 0;
        $sanitized = [];

        foreach ($answers as $index => $ans) {
            $text = trim($ans['text'] ?? '');
            $score = (int) ($ans['score'] ?? $ans['points'] ?? 0);

            if ($text === '') {
                return ['valid' => false, 'error' => "Answer number " . ($index + 1) . " cannot be empty."];
            }
            if ($score < 1) {
                return ['valid' => false, 'error' => "Points for answer '{$text}' must be at least 1."];
            }

            $totalScore += $score;
            $sanitized[] = [
                'text' => $text,
                'score' => $score,
                'revealed' => (bool) ($ans['revealed'] ?? false),
            ];
        }

        if ($totalScore !== 100) {
            return [
                'valid' => false,
                'error' => "Total score of all answers must equal exactly 100. Current sum is {$totalScore}.",
            ];
        }

        usort($sanitized, fn($a, $b) => $b['score'] <=> $a['score']);

        return ['valid' => true, 'answers' => $sanitized, 'error' => null];
    }

    /**
     * Add or update Guess Me round in MySQL.
     */
    public function saveGuessMeRound(array $data, ?UploadedFile $imageFile = null): array
    {
        $answer = trim($data['correct_answer'] ?? '');
        $clue = trim($data['clue'] ?? '');
        $score = (int) ($data['score'] ?? 20);
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $validation = $this->validateClue($answer, $clue);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        $game1 = $this->getGame('game1');
        $existingImage = 'BYC_Growth.jpg';
        $mediaFileId = null;

        if ($id !== null) {
            $existingRound = GameRound::where('game_id', $game1->id)->find($id);
            if ($existingRound) {
                $existingImage = $existingRound->image_path ?: 'BYC_Growth.jpg';
                $mediaFileId = $existingRound->media_file_id;
            }
        }

        // Handle uploaded image
        $imageName = $existingImage;
        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = 'guess_' . time() . '_' . uniqid() . '.' . $extension;
            $imageFile->move($this->publicImagesDir, $imageName);

            $media = MediaFile::create([
                'disk' => 'public',
                'file_path' => 'assets/images/' . $imageName,
                'original_name' => $imageFile->getClientOriginalName(),
                'mime_type' => $imageFile->getClientMimeType() ?: 'image/jpeg',
                'file_size' => File::size($this->publicImagesDir . '/' . $imageName),
            ]);
            $mediaFileId = $media->id;
        }

        if ($id !== null && $existingRound = GameRound::where('game_id', $game1->id)->find($id)) {
            $existingRound->update([
                'correct_answer' => strtoupper($answer),
                'clue' => $clue,
                'score' => $score,
                'image_path' => $imageName,
                'media_file_id' => $mediaFileId,
            ]);
        } else {
            $maxRound = (int) GameRound::where('game_id', $game1->id)->max('round_number');
            GameRound::create([
                'game_id' => $game1->id,
                'round_number' => $maxRound + 1,
                'correct_answer' => strtoupper($answer),
                'clue' => $clue,
                'score' => $score,
                'image_path' => $imageName,
                'media_file_id' => $mediaFileId,
            ]);
        }

        return ['success' => true, 'rounds' => $this->getGuessMeRounds()];
    }

    /**
     * Delete Guess Me round from MySQL.
     */
    public function deleteGuessMeRound(int $id): array
    {
        $game1 = $this->getGame('game1');
        $round = GameRound::where('game_id', $game1->id)->find($id);

        if (!$round) {
            return ['success' => false, 'error' => 'Round not found.'];
        }

        $totalCount = GameRound::where('game_id', $game1->id)->count();
        if ($totalCount <= 1) {
            return ['success' => false, 'error' => 'At least 1 round must remain.'];
        }

        $round->delete();

        // Adjust state if current_round is out of bounds
        $rounds = $this->getGuessMeRounds();
        $state = GameState::where('game_id', $game1->id)->first();
        if ($state && $state->current_round_index >= count($rounds)) {
            $state->update(['current_round_index' => max(0, count($rounds) - 1)]);
        }

        return ['success' => true, 'rounds' => $rounds];
    }

    /**
     * Add or update Growth 100 round in MySQL.
     */
    public function saveGrowth100Round(array $data): array
    {
        $question = trim($data['question'] ?? '');
        $answers = $data['answers'] ?? [];
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        if ($question === '') {
            return ['success' => false, 'error' => 'Question cannot be empty.'];
        }

        $validation = $this->validateGrowthAnswers($answers);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        $game2 = $this->getGame('game2');

        return DB::transaction(function () use ($game2, $id, $question, $validation) {
            if ($id !== null && $round = GameRound::where('game_id', $game2->id)->find($id)) {
                $round->update([
                    'question' => $question,
                ]);
                $round->answers()->delete();
            } else {
                $maxRound = (int) GameRound::where('game_id', $game2->id)->max('round_number');
                $round = GameRound::create([
                    'game_id' => $game2->id,
                    'round_number' => $maxRound + 1,
                    'question' => $question,
                    'score' => 100,
                ]);
            }

            foreach ($validation['answers'] as $sortIdx => $ans) {
                GameAnswer::create([
                    'game_round_id' => $round->id,
                    'answer_text' => $ans['text'],
                    'points' => (int) $ans['score'],
                    'sort_order' => $sortIdx,
                    'is_revealed' => (bool) ($ans['revealed'] ?? false),
                ]);
            }

            return ['success' => true, 'rounds' => $this->getGrowth100Rounds()];
        });
    }

    /**
     * Delete Growth 100 round from MySQL.
     */
    public function deleteGrowth100Round(int $id): array
    {
        $game2 = $this->getGame('game2');
        $round = GameRound::where('game_id', $game2->id)->find($id);

        if (!$round) {
            return ['success' => false, 'error' => 'Round not found.'];
        }

        $totalCount = GameRound::where('game_id', $game2->id)->count();
        if ($totalCount <= 1) {
            return ['success' => false, 'error' => 'At least 1 round must remain.'];
        }

        $round->delete();

        // Adjust state if current_round is out of bounds
        $rounds = $this->getGrowth100Rounds();
        $state = GameState::where('game_id', $game2->id)->first();
        if ($state && $state->current_round_index >= count($rounds)) {
            $state->update(['current_round_index' => max(0, count($rounds) - 1)]);
        }

        return ['success' => true, 'rounds' => $rounds];
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

        // Find team by ID or by code
        if (is_numeric($teamIdentifier)) {
            $team = Team::find((int) $teamIdentifier);
        } else {
            $team = Team::where('is_active', true)->where('code', $teamIdentifier)->first();
            if (!$team) {
                if ($teamIdentifier === 'red') {
                    $team = Team::where('is_active', true)->orderBy('sort_order')->first();
                } elseif ($teamIdentifier === 'blue') {
                    $team = Team::where('is_active', true)->orderBy('sort_order')->skip(1)->first();
                }
            }
            if (!$team) {
                $team = Team::where('code', $teamIdentifier)->first();
            }
        }

        if (!$team) {
            return ['success' => false, 'error' => "Team '{$teamIdentifier}' not found."];
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
            'teams' => $this->getTeamsWithScores(),
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
        }

        // Determine points to award
        if ($pointsOverride !== null) {
            $points = max(0, $pointsOverride);
        } elseif ($gameCode === 'game1') {
            $points = (int) $round->score;
        } else {
            // Growth 100: sum of revealed answers for this round, or round score
            $state = GameState::where('game_id', $game->id)->first();
            $revealedIdxs = $state ? ($state->state_data['revealed'][(string) $round->id] ?? []) : [];
            $sum = 0;
            foreach ($round->answers as $aIdx => $ans) {
                if (in_array($aIdx, $revealedIdxs)) {
                    $sum += (int) $ans->points;
                }
            }
            $points = $sum > 0 ? $sum : (int) $round->score;
        }

        return DB::transaction(function () use ($game, $round, $teamId, $points) {
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
                'teams' => $this->getTeamsWithScores(),
                'final_scores' => $this->getFinalScores(),
            ];
        });
    }

    /**
     * Batch save multiple Guess Me rounds in a single atomic transaction.
     */
    public function saveGuessMeBatchRounds(array $roundsData): array
    {
        if (empty($roundsData)) {
            return ['success' => false, 'error' => 'Rounds list cannot be empty. At least 1 round is required.'];
        }

        // Pre-validate all rounds before any database modification
        foreach ($roundsData as $idx => $r) {
            $answer = trim($r['correct_answer'] ?? '');
            $clue = trim($r['clue'] ?? '');
            $score = (int) ($r['score'] ?? 20);

            if ($answer === '') {
                return ['success' => false, 'error' => "Round " . ($idx + 1) . ": Answer cannot be empty."];
            }
            if ($clue === '') {
                return ['success' => false, 'error' => "Round " . ($idx + 1) . ": Clue cannot be empty."];
            }
            if ($score < 1) {
                return ['success' => false, 'error' => "Round " . ($idx + 1) . ": Score must be at least 1."];
            }

            $validation = $this->validateClue($answer, $clue);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => "Round " . ($idx + 1) . ": " . $validation['error']];
            }
        }

        $game1 = $this->getGame('game1');

        return DB::transaction(function () use ($game1, $roundsData) {
            $keptIds = [];

            foreach ($roundsData as $idx => $r) {
                $id = isset($r['id']) && $r['id'] !== '' ? (int) $r['id'] : null;
                $answer = strtoupper(trim($r['correct_answer']));
                $clue = trim($r['clue']);
                $score = (int) ($r['score'] ?? 20);
                $imagePath = $r['image'] ?? 'BYC_Growth.jpg';

                if ($id && $existing = GameRound::where('game_id', $game1->id)->find($id)) {
                    $existing->update([
                        'round_number' => $idx + 1,
                        'correct_answer' => $answer,
                        'clue' => $clue,
                        'score' => $score,
                    ]);
                    $keptIds[] = $existing->id;
                } else {
                    $newRound = GameRound::create([
                        'game_id' => $game1->id,
                        'round_number' => $idx + 1,
                        'correct_answer' => $answer,
                        'clue' => $clue,
                        'score' => $score,
                        'image_path' => $imagePath,
                    ]);
                    $keptIds[] = $newRound->id;
                }
            }

            // Remove rounds omitted from batch
            GameRound::where('game_id', $game1->id)->whereNotIn('id', $keptIds)->delete();

            // Adjust active round index if out of range
            $rounds = $this->getGuessMeRounds();
            $state = GameState::where('game_id', $game1->id)->first();
            if ($state && $state->current_round_index >= count($rounds)) {
                $state->update(['current_round_index' => max(0, count($rounds) - 1)]);
            }

            return [
                'success' => true,
                'message' => 'All rounds saved successfully.',
                'rounds' => $rounds,
            ];
        });
    }

    /**
     * Batch save multiple Growth 100 rounds with validation and atomic transaction.
     */
    public function saveGrowth100BatchRounds(array $roundsData): array
    {
        if (empty($roundsData)) {
            return ['success' => false, 'error' => 'Rounds list cannot be empty. At least 1 round is required.'];
        }

        // Validate all rounds before touching database
        foreach ($roundsData as $idx => $r) {
            $question = trim($r['question'] ?? '');
            $answers = $r['answers'] ?? [];

            if ($question === '') {
                return ['success' => false, 'error' => "Round " . ($idx + 1) . ": Question cannot be empty."];
            }

            $validation = $this->validateGrowthAnswers($answers);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => "Round " . ($idx + 1) . ": " . $validation['error']];
            }
        }

        $game2 = $this->getGame('game2');

        return DB::transaction(function () use ($game2, $roundsData) {
            $keptIds = [];

            foreach ($roundsData as $idx => $r) {
                $id = isset($r['id']) && $r['id'] !== '' ? (int) $r['id'] : null;
                $question = trim($r['question']);
                $validation = $this->validateGrowthAnswers($r['answers'] ?? []);

                if ($id && $round = GameRound::where('game_id', $game2->id)->find($id)) {
                    $round->update([
                        'round_number' => $idx + 1,
                        'question' => $question,
                    ]);
                    $round->answers()->delete();
                    $keptIds[] = $round->id;
                } else {
                    $round = GameRound::create([
                        'game_id' => $game2->id,
                        'round_number' => $idx + 1,
                        'question' => $question,
                        'score' => 100,
                    ]);
                    $keptIds[] = $round->id;
                }

                foreach ($validation['answers'] as $sortIdx => $ans) {
                    GameAnswer::create([
                        'game_round_id' => $round->id,
                        'answer_text' => $ans['text'],
                        'points' => (int) $ans['score'],
                        'sort_order' => $sortIdx,
                        'is_revealed' => (bool) ($ans['revealed'] ?? false),
                    ]);
                }
            }

            // Delete omitted rounds
            GameRound::where('game_id', $game2->id)->whereNotIn('id', $keptIds)->delete();

            // Adjust active round index if out of range
            $rounds = $this->getGrowth100Rounds();
            $state = GameState::where('game_id', $game2->id)->first();
            if ($state && $state->current_round_index >= count($rounds)) {
                $state->update(['current_round_index' => max(0, count($rounds) - 1)]);
            }

            return [
                'success' => true,
                'message' => 'All survey rounds saved successfully.',
                'rounds' => $rounds,
            ];
        });
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

        return [
            'success' => true,
            'state' => $this->getGameState()['game2'],
        ];
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
