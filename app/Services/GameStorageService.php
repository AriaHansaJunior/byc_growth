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
    protected function getTeam(string $code): Team
    {
        return Team::firstOrCreate(
            ['code' => $code],
            [
                'name' => ucfirst($code) . ' Team',
                'color' => $code === 'red' ? '#bd4c42' : '#315e89',
            ]
        );
    }

    /**
     * Get persistent game state from MySQL.
     */
    public function getGameState(): array
    {
        $game1 = $this->getGame('game1');
        $game2 = $this->getGame('game2');
        $red = $this->getTeam('red');
        $blue = $this->getTeam('blue');

        $g1State = GameState::firstOrCreate(
            ['game_id' => $game1->id],
            ['current_round_index' => 0, 'state_data' => ['revealed' => []]]
        );

        $g2State = GameState::firstOrCreate(
            ['game_id' => $game2->id],
            ['current_round_index' => 0, 'state_data' => ['crosses' => [], 'revealed' => []]]
        );

        $g1RedScore = GameScore::firstOrCreate(['game_id' => $game1->id, 'team_id' => $red->id], ['score' => 0]);
        $g1BlueScore = GameScore::firstOrCreate(['game_id' => $game1->id, 'team_id' => $blue->id], ['score' => 0]);

        $g2RedScore = GameScore::firstOrCreate(['game_id' => $game2->id, 'team_id' => $red->id], ['score' => 0]);
        $g2BlueScore = GameScore::firstOrCreate(['game_id' => $game2->id, 'team_id' => $blue->id], ['score' => 0]);

        return [
            'game1' => [
                'scores' => [
                    'red' => (int) $g1RedScore->score,
                    'blue' => (int) $g1BlueScore->score,
                ],
                'current_round' => (int) $g1State->current_round_index,
                'revealed' => (array) ($g1State->state_data['revealed'] ?? []),
            ],
            'game2' => [
                'scores' => [
                    'red' => (int) $g2RedScore->score,
                    'blue' => (int) $g2BlueScore->score,
                ],
                'current_round' => (int) $g2State->current_round_index,
                'crosses' => (array) ($g2State->state_data['crosses'] ?? []),
                'revealed' => (array) ($g2State->state_data['revealed'] ?? []),
            ],
        ];
    }

    /**
     * Get Guess Me Rounds from MySQL.
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
            ];
        })->toArray();
    }

    /**
     * Get Growth 100 Rounds from MySQL.
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
     * Calculate Final Scores from MySQL.
     */
    public function getFinalScores(): array
    {
        $state = $this->getGameState();

        $game1Red = (int) ($state['game1']['scores']['red'] ?? 0);
        $game1Blue = (int) ($state['game1']['scores']['blue'] ?? 0);

        $game2Red = (int) ($state['game2']['scores']['red'] ?? 0);
        $game2Blue = (int) ($state['game2']['scores']['blue'] ?? 0);

        return [
            'final_red' => $game1Red + $game2Red,
            'final_blue' => $game1Blue + $game2Blue,
            'game1' => [
                'red' => $game1Red,
                'blue' => $game1Blue,
            ],
            'game2' => [
                'red' => $game2Red,
                'blue' => $game2Blue,
            ],
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
     * Update scores for Game 1 or Game 2 in MySQL.
     */
    public function updateScore(string $gameCode, string $teamCode, int $amount, bool $isAbsolute = false): array
    {
        if (!in_array($gameCode, ['game1', 'game2']) || !in_array($teamCode, ['red', 'blue'])) {
            return ['success' => false, 'error' => 'Invalid game or team.'];
        }

        $game = $this->getGame($gameCode);
        $team = $this->getTeam($teamCode);

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
            'scores' => $state[$gameCode]['scores'],
            'final_scores' => $this->getFinalScores(),
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

        return [
            'success' => true,
            'state' => $this->getGameState()['game2'],
        ];
    }

    /**
     * Reset Game State to initial in MySQL.
     * Scores = 0, revealed = [], crosses = [], current_round_index = 0.
     * Questions and image records are PRESERVED.
     */
    public function resetGame(): array
    {
        $game1 = $this->getGame('game1');
        $game2 = $this->getGame('game2');

        // Reset all team scores to 0
        GameScore::query()->update(['score' => 0]);

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
