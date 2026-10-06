<?php

namespace App\Services\Games\Concerns;

use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GameRound;
use App\Models\GameState;
use Illuminate\Support\Facades\DB;

trait ManagesGrowth100
{
    /**
     * Get Growth 100 Rounds from MySQL with point assignment.
     */
    public function getGrowth100Rounds(bool $onlyVisible = false): array
    {
        $game2 = $this->getGame('game2');

        $query = GameRound::with(['answers' => function ($query) {
            $query->orderByDesc('points')->orderBy('sort_order');
        }])
            ->where('game_id', $game2->id)
            ->orderBy('round_number');

        if ($onlyVisible) {
            $query->where('is_hidden', false);
        }

        $rounds = $query->get();

        return $rounds->map(function (GameRound $round) {
            return [
                'id' => (int) $round->id,
                'round_number' => (int) $round->round_number,
                'question' => $round->question,
                'awarded_team_id' => $round->awarded_team_id ? (int) $round->awarded_team_id : null,
                'awarded_points' => (int) $round->awarded_points,
                'is_hidden' => (bool) $round->is_hidden,
                'answers' => $round->answers->map(function (GameAnswer $ans) {
                    return [
                        'id' => (int) $ans->id,
                        'text' => $ans->answer_text,
                        'score' => (int) $ans->points,
                        'revealed' => (bool) $ans->is_revealed,
                    ];
                })->toArray(),
            ];
        })->values()->toArray();
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
}
