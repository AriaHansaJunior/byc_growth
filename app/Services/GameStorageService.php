<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\MediaFile;
use App\Models\Team;
use App\Services\MediaUploadService;
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

            foreach ($teams as $idx => $team) {
                $score = GameScore::firstOrCreate(['game_id' => $game->id, 'team_id' => $team->id], ['score' => 0]);
                $theme = $team->color ?: ($palette[$idx % count($palette)]['theme'] ?? 'forest');

                $result[] = [
                    'id' => (int) $team->id,
                    'game_id' => (int) $team->game_id,
                    'code' => $team->code,
                    'name' => $team->name,
                    'color' => $team->color ?: ($palette[$idx % count($palette)]['color'] ?? '#284e3b'),
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

        // Combined for both games
        $g1Teams = $this->getTeamsWithScores('game1');
        $g2Teams = $this->getTeamsWithScores('game2');

        return array_merge($g1Teams, $g2Teams);
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
                'image_url' => $round->image_url,
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
            'teams' => array_merge($g1Teams, $g2Teams),
            'teams_by_game' => [
                'game1' => $g1Teams,
                'game2' => $g2Teams,
            ],
        ];
    }

    /**
     * Validate Clue vs Answer format and character matching.
     */
    public function validateClue(string $answer, string $clue): array
    {
        $cleanAnswer = strtoupper(trim($answer));
        $trimmedClue = strtoupper(trim($clue));

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
        $answer = strtoupper(trim($data['correct_answer'] ?? ''));
        $clue = strtoupper(trim($data['clue'] ?? ''));
        $score = (int) ($data['score'] ?? 20);
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $validation = $this->validateClue($answer, $clue);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error'], 'status' => 422];
        }

        $game1 = $this->getGame('game1');
        $existingImage = 'BYC_Growth.jpg';
        $mediaFileId = null;
        $existingRound = null;

        if ($id !== null) {
            $existingRound = GameRound::find($id);
            if (!$existingRound) {
                return ['success' => false, 'error' => 'Round not found.', 'status' => 404];
            }
            if ((int) $existingRound->game_id !== (int) $game1->id) {
                return ['success' => false, 'error' => 'Round does not belong to Guess Me.', 'status' => 403];
            }
            $existingImage = $existingRound->image_path ?: 'BYC_Growth.jpg';
            $mediaFileId = $existingRound->media_file_id;
        }

        // Handle uploaded image safely via MediaUploadService
        $imageName = $existingImage;
        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $mediaUploadService = app(MediaUploadService::class);

            // Clean up old media file when replacing
            if ($existingRound && $existingRound->mediaFile) {
                $mediaUploadService->deleteMediaFile($existingRound->mediaFile);
            }

            $media = $mediaUploadService->storeImage(
                $imageFile,
                'guess',
                GameRound::class,
                $existingRound?->id
            );
            $mediaFileId = $media->id;
            $imageName = basename($media->file_path);
        }

        if ($existingRound) {
            $existingRound->update([
                'correct_answer' => $answer,
                'clue' => $clue,
                'score' => $score,
                'image_path' => $imageName,
                'media_file_id' => $mediaFileId,
            ]);
            $round = $existingRound;
        } else {
            $maxRound = (int) GameRound::where('game_id', $game1->id)->max('round_number');
            $round = GameRound::create([
                'game_id' => $game1->id,
                'round_number' => $maxRound + 1,
                'correct_answer' => $answer,
                'clue' => $clue,
                'score' => $score,
                'image_path' => $imageName,
                'media_file_id' => $mediaFileId,
            ]);
            if ($mediaFileId) {
                MediaFile::where('id', $mediaFileId)->update(['fileable_id' => $round->id]);
            }
        }

        return ['success' => true, 'round' => $round, 'rounds' => $this->getGuessMeRounds()];
    }

    /**
     * Delete Guess Me round from MySQL.
     */
    public function deleteGuessMeRound(int $id): array
    {
        $game1 = $this->getGame('game1');
        $round = GameRound::find($id);

        if (!$round) {
            return ['success' => false, 'error' => 'Round not found.', 'status' => 404];
        }

        if ((int) $round->game_id !== (int) $game1->id) {
            return ['success' => false, 'error' => 'Round does not belong to Guess Me.', 'status' => 403];
        }

        $totalCount = GameRound::where('game_id', $game1->id)->count();
        if ($totalCount <= 1) {
            return ['success' => false, 'error' => 'At least 1 round must remain.', 'status' => 422];
        }

        if ($round->mediaFile) {
            app(MediaUploadService::class)->deleteMediaFile($round->mediaFile);
        }

        $round->delete();

        // Reorder remaining rounds sequentially
        $remaining = GameRound::where('game_id', $game1->id)->orderBy('round_number')->orderBy('id')->get();
        foreach ($remaining as $idx => $r) {
            $r->update(['round_number' => $idx + 1]);
        }

        // Adjust state if current_round is out of bounds
        $rounds = $this->getGuessMeRounds();
        $state = GameState::where('game_id', $game1->id)->first();
        if ($state && $state->current_round_index >= count($rounds)) {
            $state->update(['current_round_index' => max(0, count($rounds) - 1)]);
        }

        return ['success' => true, 'rounds' => $rounds];
    }

    /**
     * Reorder Guess Me rounds by ID sequence.
     */
    public function reorderGuessMeRounds(array $roundIds): array
    {
        $game1 = $this->getGame('game1');
        foreach ($roundIds as $index => $roundId) {
            GameRound::where('game_id', $game1->id)
                ->where('id', (int) $roundId)
                ->update(['round_number' => $index + 1]);
        }

        return ['success' => true, 'rounds' => $this->getGuessMeRounds()];
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
            return ['success' => false, 'error' => 'Question cannot be empty.', 'status' => 422];
        }

        $validation = $this->validateGrowthAnswers($answers);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error'], 'status' => 422];
        }

        $game2 = $this->getGame('game2');

        if ($id !== null) {
            $existingRound = GameRound::find($id);
            if (!$existingRound) {
                return ['success' => false, 'error' => 'Round not found.', 'status' => 404];
            }
            if ((int) $existingRound->game_id !== (int) $game2->id) {
                return ['success' => false, 'error' => 'Round does not belong to Growth 100.', 'status' => 403];
            }
        }

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

            return ['success' => true, 'round' => $round, 'rounds' => $this->getGrowth100Rounds()];
        });
    }

    /**
     * Delete Growth 100 round from MySQL.
     */
    public function deleteGrowth100Round(int $id): array
    {
        $game2 = $this->getGame('game2');
        $round = GameRound::find($id);

        if (!$round) {
            return ['success' => false, 'error' => 'Round not found.', 'status' => 404];
        }

        if ((int) $round->game_id !== (int) $game2->id) {
            return ['success' => false, 'error' => 'Round does not belong to Growth 100.', 'status' => 403];
        }

        $totalCount = GameRound::where('game_id', $game2->id)->count();
        if ($totalCount <= 1) {
            return ['success' => false, 'error' => 'At least 1 round must remain.', 'status' => 422];
        }

        // Clean up answers to prevent orphans
        $round->answers()->delete();
        $round->delete();

        // Reorder remaining rounds sequentially
        $remaining = GameRound::where('game_id', $game2->id)->orderBy('round_number')->orderBy('id')->get();
        foreach ($remaining as $idx => $r) {
            $r->update(['round_number' => $idx + 1]);
        }

        // Adjust state if current_round is out of bounds
        $rounds = $this->getGrowth100Rounds();
        $state = GameState::where('game_id', $game2->id)->first();
        if ($state && $state->current_round_index >= count($rounds)) {
            $state->update(['current_round_index' => max(0, count($rounds) - 1)]);
        }

        return ['success' => true, 'rounds' => $rounds];
    }

    /**
     * Reorder Growth 100 rounds by ID sequence.
     */
    public function reorderGrowth100Rounds(array $roundIds): array
    {
        $game2 = $this->getGame('game2');
        foreach ($roundIds as $index => $roundId) {
            GameRound::where('game_id', $game2->id)
                ->where('id', (int) $roundId)
                ->update(['round_number' => $index + 1]);
        }

        return ['success' => true, 'rounds' => $this->getGrowth100Rounds()];
    }

    /**
     * Add single answer to a Growth 100 round while enforcing sum == 100.
     */
    public function addGrowth100Answer(int $roundId, array $data): array
    {
        $game2 = $this->getGame('game2');
        $round = GameRound::find($roundId);

        if (!$round || (int) $round->game_id !== (int) $game2->id) {
            return ['success' => false, 'error' => 'Round not found.', 'status' => 404];
        }

        $text = trim($data['text'] ?? '');
        $score = (int) ($data['score'] ?? $data['points'] ?? 0);

        if ($text === '') {
            return ['success' => false, 'error' => 'Answer text cannot be empty.', 'status' => 422];
        }
        if ($score < 1) {
            return ['success' => false, 'error' => 'Answer score must be at least 1.', 'status' => 422];
        }

        // If adjust_from_answer_id is provided, deduct the points from that answer
        $adjustFromId = isset($data['adjust_from_answer_id']) ? (int) $data['adjust_from_answer_id'] : null;

        return DB::transaction(function () use ($round, $text, $score, $adjustFromId) {
            if ($adjustFromId) {
                $target = GameAnswer::where('game_round_id', $round->id)->find($adjustFromId);
                if ($target && $target->points > $score) {
                    $target->decrement('points', $score);
                }
            }

            $currentSum = (int) $round->answers()->sum('points');
            if ($currentSum + $score !== 100) {
                return [
                    'success' => false,
                    'error' => "Total score of all answers must equal exactly 100. Adding this answer results in " . ($currentSum + $score) . ".",
                    'status' => 422,
                ];
            }

            $maxSort = (int) $round->answers()->max('sort_order');
            $newAnswer = GameAnswer::create([
                'game_round_id' => $round->id,
                'answer_text' => $text,
                'points' => $score,
                'sort_order' => $maxSort + 1,
                'is_revealed' => false,
            ]);

            return ['success' => true, 'answer' => $newAnswer, 'rounds' => $this->getGrowth100Rounds()];
        });
    }

    /**
     * Edit single answer in Growth 100 while enforcing sum == 100.
     */
    public function updateGrowth100Answer(int $answerId, array $data): array
    {
        $answer = GameAnswer::with('round')->find($answerId);
        if (!$answer || !$answer->round) {
            return ['success' => false, 'error' => 'Answer not found.', 'status' => 404];
        }

        $game2 = $this->getGame('game2');
        if ((int) $answer->round->game_id !== (int) $game2->id) {
            return ['success' => false, 'error' => 'Answer does not belong to Growth 100.', 'status' => 403];
        }

        $text = isset($data['text']) ? trim((string) $data['text']) : $answer->answer_text;
        $newScore = isset($data['score']) || isset($data['points'])
            ? (int) ($data['score'] ?? $data['points'])
            : (int) $answer->points;

        if ($text === '') {
            return ['success' => false, 'error' => 'Answer text cannot be empty.', 'status' => 422];
        }
        if ($newScore < 1) {
            return ['success' => false, 'error' => 'Answer score must be at least 1.', 'status' => 422];
        }

        $adjustAnswerId = isset($data['adjust_answer_id']) ? (int) $data['adjust_answer_id'] : null;

        return DB::transaction(function () use ($answer, $text, $newScore, $adjustAnswerId) {
            $diff = $newScore - (int) $answer->points;
            if ($diff !== 0 && $adjustAnswerId) {
                $target = GameAnswer::where('game_round_id', $answer->game_round_id)->find($adjustAnswerId);
                if ($target && ($target->points - $diff) >= 1) {
                    $target->decrement('points', $diff);
                }
            }

            $currentOtherSum = (int) GameAnswer::where('game_round_id', $answer->game_round_id)
                ->where('id', '!=', $answer->id)
                ->sum('points');

            if ($currentOtherSum + $newScore !== 100) {
                return [
                    'success' => false,
                    'error' => "Total score of all answers must equal exactly 100. Current sum with update would be " . ($currentOtherSum + $newScore) . ".",
                    'status' => 422,
                ];
            }

            $answer->update([
                'answer_text' => $text,
                'points' => $newScore,
            ]);

            return ['success' => true, 'answer' => $answer, 'rounds' => $this->getGrowth100Rounds()];
        });
    }

    /**
     * Delete single answer in Growth 100 with optional points transfer to keep sum == 100.
     */
    public function deleteGrowth100Answer(int $answerId, ?int $transferToId = null): array
    {
        $answer = GameAnswer::with('round')->find($answerId);
        if (!$answer || !$answer->round) {
            return ['success' => false, 'error' => 'Answer not found.', 'status' => 404];
        }

        $game2 = $this->getGame('game2');
        if ((int) $answer->round->game_id !== (int) $game2->id) {
            return ['success' => false, 'error' => 'Answer does not belong to Growth 100.', 'status' => 403];
        }

        $roundId = $answer->game_round_id;
        $points = (int) $answer->points;

        return DB::transaction(function () use ($answer, $roundId, $points, $transferToId) {
            if ($transferToId) {
                $target = GameAnswer::where('game_round_id', $roundId)->find($transferToId);
                if ($target) {
                    $target->increment('points', $points);
                }
            }

            $remainingSum = (int) GameAnswer::where('game_round_id', $roundId)
                ->where('id', '!=', $answer->id)
                ->sum('points');

            if ($remainingSum !== 100) {
                return [
                    'success' => false,
                    'error' => "Deleting this answer would cause total score to be {$remainingSum} instead of 100. Please transfer points or update question.",
                    'status' => 422,
                ];
            }

            $answer->delete();

            return ['success' => true, 'rounds' => $this->getGrowth100Rounds()];
        });
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
            $answer = strtoupper(trim($r['correct_answer'] ?? ''));
            $clue = strtoupper(trim($r['clue'] ?? ''));
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
                $answer = strtoupper(trim($r['correct_answer'] ?? ''));
                $clue = strtoupper(trim($r['clue'] ?? ''));
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
