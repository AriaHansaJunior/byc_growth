<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class GameStorageService
{
    protected string $storageDir;
    protected string $imagesDir;
    protected string $publicImagesDir;
    protected string $gameStatePath;
    protected string $guessMePath;
    protected string $growth100Path;

    public function __construct()
    {
        $this->storageDir = storage_path('app/game');
        $this->imagesDir = storage_path('app/game/images');
        $this->publicImagesDir = public_path('assets/images');

        $this->gameStatePath = $this->storageDir . '/game-state.json';
        $this->guessMePath = $this->storageDir . '/guess-me.json';
        $this->growth100Path = $this->storageDir . '/growth-100.json';

        $this->ensureDirectoriesAndFilesExist();
    }

    /**
     * Ensure storage directories and JSON files exist with proper permissions.
     */
    protected function ensureDirectoriesAndFilesExist(): void
    {
        if (!File::isDirectory($this->storageDir)) {
            File::makeDirectory($this->storageDir, 0755, true);
        }
        if (!File::isDirectory($this->imagesDir)) {
            File::makeDirectory($this->imagesDir, 0755, true);
        }
        if (!File::isDirectory($this->publicImagesDir)) {
            File::makeDirectory($this->publicImagesDir, 0755, true);
        }

        if (!File::exists($this->gameStatePath)) {
            $this->saveGameState($this->getDefaultGameState());
        }

        if (!File::exists($this->guessMePath)) {
            $this->saveGuessMeRounds($this->getDefaultGuessMeRounds());
        }

        if (!File::exists($this->growth100Path)) {
            $this->saveGrowth100Rounds($this->getDefaultGrowth100Rounds());
        }
    }

    /**
     * Default initial game state (Red: 0, Blue: 0).
     */
    public function getDefaultGameState(): array
    {
        return [
            'game1' => [
                'scores' => ['red' => 0, 'blue' => 0],
                'current_round' => 0,
                'revealed' => [],
            ],
            'game2' => [
                'scores' => ['red' => 0, 'blue' => 0],
                'current_round' => 0,
                'crosses' => [],
                'revealed' => [],
            ],
        ];
    }

    public function getDefaultGuessMeRounds(): array
    {
        return [
            [
                'id' => 1,
                'image' => 'BYC_Growth.jpg',
                'correct_answer' => 'GROOT',
                'clue' => 'G _ O _ T',
                'score' => 20,
            ],
            [
                'id' => 2,
                'image' => 'Screenshot_2026-09-28_144605.png',
                'correct_answer' => 'TUMBUH',
                'clue' => 'T _ M _ U H',
                'score' => 25,
            ],
            [
                'id' => 3,
                'image' => 'Screenshot_2026-09-28_145555.png',
                'correct_answer' => 'KOMPAK',
                'clue' => 'K O _ P _ K',
                'score' => 30,
            ],
        ];
    }

    public function getDefaultGrowth100Rounds(): array
    {
        return [
            [
                'id' => 1,
                'question' => 'Apa hal yang membuat sebuah tim terus bertumbuh?',
                'answers' => [
                    ['text' => 'Komunikasi yang jujur', 'score' => 30, 'revealed' => false],
                    ['text' => 'Saling percaya', 'score' => 24, 'revealed' => false],
                    ['text' => 'Tujuan yang sama', 'score' => 18, 'revealed' => false],
                    ['text' => 'Mau belajar', 'score' => 12, 'revealed' => false],
                    ['text' => 'Saling mendukung', 'score' => 10, 'revealed' => false],
                    ['text' => 'Evaluasi rutin', 'score' => 6, 'revealed' => false],
                ],
            ],
            [
                'id' => 2,
                'question' => 'Kebiasaan apa yang dilakukan pemimpin yang baik?',
                'answers' => [
                    ['text' => 'Mendengarkan tim', 'score' => 28, 'revealed' => false],
                    ['text' => 'Memberi teladan', 'score' => 23, 'revealed' => false],
                    ['text' => 'Memberi arahan jelas', 'score' => 19, 'revealed' => false],
                    ['text' => 'Mengapresiasi', 'score' => 14, 'revealed' => false],
                    ['text' => 'Menerima masukan', 'score' => 10, 'revealed' => false],
                    ['text' => 'Konsisten', 'score' => 6, 'revealed' => false],
                ],
            ],
            [
                'id' => 3,
                'question' => 'Apa yang membuat suasana kerja terasa menyenangkan?',
                'answers' => [
                    ['text' => 'Rekan yang suportif', 'score' => 31, 'revealed' => false],
                    ['text' => 'Komunikasi terbuka', 'score' => 22, 'revealed' => false],
                    ['text' => 'Apresiasi', 'score' => 17, 'revealed' => false],
                    ['text' => 'Lingkungan nyaman', 'score' => 13, 'revealed' => false],
                    ['text' => 'Pekerjaan bermakna', 'score' => 10, 'revealed' => false],
                    ['text' => 'Humor yang sehat', 'score' => 7, 'revealed' => false],
                ],
            ],
        ];
    }

    /**
     * Read Game State
     */
    public function getGameState(): array
    {
        if (!File::exists($this->gameStatePath)) {
            $default = $this->getDefaultGameState();
            $this->saveGameState($default);
            return $default;
        }

        $content = File::get($this->gameStatePath);
        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['game1']) || !isset($data['game2'])) {
            $default = $this->getDefaultGameState();
            $this->saveGameState($default);
            return $default;
        }

        return $data;
    }

    /**
     * Save Game State to storage/app/game/game-state.json
     */
    public function saveGameState(array $state): void
    {
        File::put($this->gameStatePath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Get Guess Me Rounds
     */
    public function getGuessMeRounds(): array
    {
        if (!File::exists($this->guessMePath)) {
            $default = $this->getDefaultGuessMeRounds();
            $this->saveGuessMeRounds($default);
            return $default;
        }

        $content = File::get($this->guessMePath);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Save Guess Me Rounds to storage/app/game/guess-me.json
     */
    public function saveGuessMeRounds(array $rounds): void
    {
        File::put($this->guessMePath, json_encode(array_values($rounds), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Get Growth 100 Rounds
     */
    public function getGrowth100Rounds(): array
    {
        if (!File::exists($this->growth100Path)) {
            $default = $this->getDefaultGrowth100Rounds();
            $this->saveGrowth100Rounds($default);
            return $default;
        }

        $content = File::get($this->growth100Path);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Save Growth 100 Rounds to storage/app/game/growth-100.json
     */
    public function saveGrowth100Rounds(array $rounds): void
    {
        File::put($this->growth100Path, json_encode(array_values($rounds), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Calculate Final Scores
     * Final Red = Game 1 Red + Game 2 Red
     * Final Blue = Game 1 Blue + Game 2 Blue
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
     * Validate Clue vs Answer
     * - jumlah karakter clue = jumlah karakter jawaban
     * - posisi huruf yang terlihat harus sama
     */
    public function validateClue(string $answer, string $clue): array
    {
        $cleanAnswer = strtoupper(trim($answer));
        $trimmedClue = trim($clue);

        if ($cleanAnswer === '') {
            return ['valid' => false, 'error' => 'Jawaban tidak boleh kosong.'];
        }
        if ($trimmedClue === '') {
            return ['valid' => false, 'error' => 'Clue tidak boleh kosong.'];
        }

        // Support both "_ A _ A _" and "_A_A_"
        $answerChars = mb_str_split($cleanAnswer);
        $answerLen = count($answerChars);

        // Check if clue has spaces separating every character/wildcard
        $clueCharsDirect = mb_str_split($trimmedClue);
        $clueNoSpaces = mb_str_split(str_replace(' ', '', $trimmedClue));

        if (count($clueCharsDirect) === $answerLen) {
            $clueTokens = $clueCharsDirect;
        } elseif (count($clueNoSpaces) === $answerLen) {
            $clueTokens = $clueNoSpaces;
        } else {
            // Also check space-split tokens (e.g. "_ A _ A _")
            $tokens = array_values(array_filter(explode(' ', $trimmedClue), fn($c) => $c !== ''));
            if (count($tokens) === $answerLen) {
                $clueTokens = $tokens;
            } else {
                return [
                    'valid' => false,
                    'error' => "Jumlah karakter clue (" . count($clueNoSpaces) . ") harus sama dengan jumlah karakter jawaban ({$answerLen}).",
                ];
            }
        }

        for ($i = 0; $i < $answerLen; $i++) {
            $c = strtoupper($clueTokens[$i]);
            $a = $answerChars[$i];

            // If clue has wildcard character: _ or - or .
            if ($c === '_' || $c === '-' || $c === '.') {
                continue;
            }

            if ($c !== $a) {
                return [
                    'valid' => false,
                    'error' => "Karakter posisi ke-" . ($i + 1) . " ('{$c}') berbeda dengan huruf pada jawaban ('{$a}').",
                ];
            }
        }

        return ['valid' => true, 'error' => null];
    }

    /**
     * Validate Growth 100 Answers
     * - Total score must be exactly 100
     * - Auto-sort descending by score
     */
    public function validateGrowthAnswers(array $answers): array
    {
        if (empty($answers)) {
            return ['valid' => false, 'error' => 'Daftar jawaban tidak boleh kosong.'];
        }

        $totalScore = 0;
        $sanitized = [];

        foreach ($answers as $index => $ans) {
            $text = trim($ans['text'] ?? '');
            $score = (int) ($ans['score'] ?? 0);

            if ($text === '') {
                return ['valid' => false, 'error' => "Jawaban nomor " . ($index + 1) . " tidak boleh kosong."];
            }
            if ($score < 1) {
                return ['valid' => false, 'error' => "Poin untuk jawaban '{$text}' harus minimal 1."];
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
                'error' => "Total score seluruh jawaban harus tepat 100. Saat ini berjumlah {$totalScore}.",
            ];
        }

        // Auto sort ranking: highest score -> rank 1
        usort($sanitized, fn($a, $b) => $b['score'] <=> $a['score']);

        return ['valid' => true, 'answers' => $sanitized, 'error' => null];
    }

    /**
     * Add or update Guess Me round
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

        $rounds = $this->getGuessMeRounds();
        $targetIndex = null;
        $existingImage = 'BYC_Growth.jpg';

        if ($id !== null) {
            foreach ($rounds as $idx => $round) {
                if ((int) $round['id'] === $id) {
                    $targetIndex = $idx;
                    $existingImage = $round['image'] ?? 'BYC_Growth.jpg';
                    break;
                }
            }
        }

        // Handle uploaded image
        $imageName = $existingImage;
        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $extension = $imageFile->getClientOriginalExtension();
            $imageName = 'guess_' . time() . '_' . uniqid() . '.' . $extension;
            $imageFile->move($this->imagesDir, $imageName);
            // Also copy to public directory for immediate serving
            File::copy($this->imagesDir . '/' . $imageName, $this->publicImagesDir . '/' . $imageName);
        }

        if ($targetIndex !== null) {
            $rounds[$targetIndex] = [
                'id' => $id,
                'image' => $imageName,
                'correct_answer' => strtoupper($answer),
                'clue' => $clue,
                'score' => $score,
            ];
        } else {
            $newId = empty($rounds) ? 1 : (max(array_column($rounds, 'id')) + 1);
            $rounds[] = [
                'id' => $newId,
                'image' => $imageName,
                'correct_answer' => strtoupper($answer),
                'clue' => $clue,
                'score' => $score,
            ];
        }

        $this->saveGuessMeRounds($rounds);

        return ['success' => true, 'rounds' => $rounds];
    }

    /**
     * Delete Guess Me round
     */
    public function deleteGuessMeRound(int $id): array
    {
        $rounds = $this->getGuessMeRounds();
        $rounds = array_values(array_filter($rounds, fn($r) => (int) $r['id'] !== $id));

        if (empty($rounds)) {
            return ['success' => false, 'error' => 'Minimal harus menyisakan 1 ronde.'];
        }

        $this->saveGuessMeRounds($rounds);

        // Adjust state if current_round is out of bounds
        $state = $this->getGameState();
        if ($state['game1']['current_round'] >= count($rounds)) {
            $state['game1']['current_round'] = max(0, count($rounds) - 1);
            $this->saveGameState($state);
        }

        return ['success' => true, 'rounds' => $rounds];
    }

    /**
     * Add or update Growth 100 round
     */
    public function saveGrowth100Round(array $data): array
    {
        $question = trim($data['question'] ?? '');
        $answers = $data['answers'] ?? [];
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        if ($question === '') {
            return ['success' => false, 'error' => 'Pertanyaan tidak boleh kosong.'];
        }

        $validation = $this->validateGrowthAnswers($answers);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        $rounds = $this->getGrowth100Rounds();
        $targetIndex = null;

        if ($id !== null) {
            foreach ($rounds as $idx => $round) {
                if ((int) $round['id'] === $id) {
                    $targetIndex = $idx;
                    break;
                }
            }
        }

        if ($targetIndex !== null) {
            $rounds[$targetIndex] = [
                'id' => $id,
                'question' => $question,
                'answers' => $validation['answers'],
            ];
        } else {
            $newId = empty($rounds) ? 1 : (max(array_column($rounds, 'id')) + 1);
            $rounds[] = [
                'id' => $newId,
                'question' => $question,
                'answers' => $validation['answers'],
            ];
        }

        $this->saveGrowth100Rounds($rounds);

        return ['success' => true, 'rounds' => $rounds];
    }

    /**
     * Delete Growth 100 round
     */
    public function deleteGrowth100Round(int $id): array
    {
        $rounds = $this->getGrowth100Rounds();
        $rounds = array_values(array_filter($rounds, fn($r) => (int) $r['id'] !== $id));

        if (empty($rounds)) {
            return ['success' => false, 'error' => 'Minimal harus menyisakan 1 ronde.'];
        }

        $this->saveGrowth100Rounds($rounds);

        // Adjust state if current_round is out of bounds
        $state = $this->getGameState();
        if ($state['game2']['current_round'] >= count($rounds)) {
            $state['game2']['current_round'] = max(0, count($rounds) - 1);
            $this->saveGameState($state);
        }

        return ['success' => true, 'rounds' => $rounds];
    }

    /**
     * Update scores for Game 1 or Game 2
     */
    public function updateScore(string $game, string $team, int $amount, bool $isAbsolute = false): array
    {
        if (!in_array($game, ['game1', 'game2']) || !in_array($team, ['red', 'blue'])) {
            return ['success' => false, 'error' => 'Invalid game or team.'];
        }

        $state = $this->getGameState();
        $currentScore = (int) ($state[$game]['scores'][$team] ?? 0);

        if ($isAbsolute) {
            $newScore = max(0, $amount);
        } else {
            $newScore = max(0, $currentScore + $amount);
        }

        $state[$game]['scores'][$team] = $newScore;
        $this->saveGameState($state);

        return [
            'success' => true,
            'scores' => $state[$game]['scores'],
            'final_scores' => $this->getFinalScores(),
        ];
    }

    /**
     * Update Game 1 active round or reveal state
     */
    public function updateGame1State(int $roundIndex, ?bool $revealed = null): array
    {
        $state = $this->getGameState();
        $rounds = $this->getGuessMeRounds();

        $roundIndex = max(0, min(count($rounds) - 1, $roundIndex));
        $state['game1']['current_round'] = $roundIndex;

        $roundId = (string) ($rounds[$roundIndex]['id'] ?? ($roundIndex + 1));

        if ($revealed !== null) {
            $state['game1']['revealed'][$roundId] = $revealed;
        }

        $this->saveGameState($state);

        return [
            'success' => true,
            'state' => $state['game1'],
        ];
    }

    /**
     * Update Game 2 active round, revealed answers, or crosses
     */
    public function updateGame2State(int $roundIndex, ?int $answerIndex = null, ?bool $revealed = null, ?int $crosses = null, ?bool $revealAll = null): array
    {
        $state = $this->getGameState();
        $rounds = $this->getGrowth100Rounds();

        $roundIndex = max(0, min(count($rounds) - 1, $roundIndex));
        $state['game2']['current_round'] = $roundIndex;

        $roundId = (string) ($rounds[$roundIndex]['id'] ?? ($roundIndex + 1));

        if (!isset($state['game2']['revealed'][$roundId])) {
            $state['game2']['revealed'][$roundId] = [];
        }
        if (!isset($state['game2']['crosses'][$roundId])) {
            $state['game2']['crosses'][$roundId] = 0;
        }

        if ($crosses !== null) {
            $state['game2']['crosses'][$roundId] = max(0, min(3, $crosses));
        }

        if ($revealAll === true) {
            $totalAnswers = count($rounds[$roundIndex]['answers'] ?? []);
            $state['game2']['revealed'][$roundId] = range(0, max(0, $totalAnswers - 1));
        } elseif ($revealAll === false) {
            $state['game2']['revealed'][$roundId] = [];
        } elseif ($answerIndex !== null) {
            $currentRevealed = $state['game2']['revealed'][$roundId] ?? [];
            if ($revealed === true && !in_array($answerIndex, $currentRevealed)) {
                $currentRevealed[] = $answerIndex;
            } elseif ($revealed === false) {
                $currentRevealed = array_values(array_filter($currentRevealed, fn($idx) => $idx !== $answerIndex));
            } elseif ($revealed === null) {
                // Toggle
                if (in_array($answerIndex, $currentRevealed)) {
                    $currentRevealed = array_values(array_filter($currentRevealed, fn($idx) => $idx !== $answerIndex));
                } else {
                    $currentRevealed[] = $answerIndex;
                }
            }
            $state['game2']['revealed'][$roundId] = array_values(array_unique($currentRevealed));
        }

        $this->saveGameState($state);

        return [
            'success' => true,
            'state' => $state['game2'],
        ];
    }

    /**
     * Reset Game State to initial
     * Scores = 0, revealed = false, crosses = 0, current_round = 0
     * Questions and images are NOT deleted!
     */
    public function resetGame(): array
    {
        $defaultState = $this->getDefaultGameState();
        $this->saveGameState($defaultState);

        return [
            'success' => true,
            'message' => 'Game state berhasil di-reset ke kondisi awal.',
            'state' => $defaultState,
            'final_scores' => $this->getFinalScores(),
        ];
    }
}
