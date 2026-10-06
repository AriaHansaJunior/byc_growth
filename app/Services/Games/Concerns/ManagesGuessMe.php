<?php

namespace App\Services\Games\Concerns;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameState;
use App\Models\MediaFile;
use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

trait ManagesGuessMe
{
    /**
     * Get Guess Me Rounds from MySQL with point assignment.
     */
    public function getGuessMeRounds(bool $onlyVisible = false): array
    {
        $game1 = $this->getGame('game1');

        $query = GameRound::with('mediaFile')
            ->where('game_id', $game1->id)
            ->orderBy('round_number');

        if ($onlyVisible) {
            $query->where('is_hidden', false);
        }

        $rounds = $query->get();

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
                'is_hidden' => (bool) $round->is_hidden,
            ];
        })->values()->toArray();
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
}
