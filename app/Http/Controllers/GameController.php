<?php

namespace App\Http\Controllers;

use App\Services\GameStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GameController extends Controller
{
    protected GameStorageService $storageService;

    public function __construct(GameStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Homepage (/) — Shows Final Red & Final Blue calculated from Game 1 + Game 2
     */
    public function home()
    {
        $scores = $this->storageService->getFinalScores();
        $gameState = $this->storageService->getGameState();

        return view('welcome', [
            'finalScores' => $scores,
            'gameState' => $gameState,
        ]);
    }

    /**
     * Game 1 — Guess Me! Page
     */
    public function guessMe()
    {
        $rounds = $this->storageService->getGuessMeRounds();
        $gameState = $this->storageService->getGameState();

        return view('pages.guess-me', [
            'rounds' => $rounds,
            'game1State' => $gameState['game1'],
        ]);
    }

    /**
     * Game 2 — BYC Growth 100 Page
     */
    public function growth100()
    {
        $rounds = $this->storageService->getGrowth100Rounds();
        $gameState = $this->storageService->getGameState();

        return view('pages.growth-100', [
            'rounds' => $rounds,
            'game2State' => $gameState['game2'],
        ]);
    }

    /**
     * Final Score Screen
     */
    public function finalScore()
    {
        $scores = $this->storageService->getFinalScores();

        return view('pages.final', [
            'scores' => $scores,
        ]);
    }

    /**
     * Update scores for Game 1 or Game 2
     */
    public function updateScore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game' => 'required|in:game1,game2',
            'team' => 'required|in:red,blue',
            'amount' => 'required|integer',
            'is_absolute' => 'nullable|boolean',
        ]);

        $result = $this->storageService->updateScore(
            $validated['game'],
            $validated['team'],
            (int) $validated['amount'],
            (bool) ($validated['is_absolute'] ?? false)
        );

        return response()->json($result);
    }

    /**
     * Update Game 1 active round and/or revealed answer status
     */
    public function updateGame1State(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'round_index' => 'required|integer|min:0',
            'revealed' => 'nullable|boolean',
        ]);

        $result = $this->storageService->updateGame1State(
            (int) $validated['round_index'],
            isset($validated['revealed']) ? (bool) $validated['revealed'] : null
        );

        return response()->json($result);
    }

    /**
     * Add or edit Guess Me round
     */
    public function saveGuessMeRound(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'correct_answer' => 'required|string',
            'clue' => 'required|string',
            'score' => 'required|integer|min:1',
            'image' => 'nullable|image|max:5120',
        ]);

        $imageFile = $request->file('image');
        $result = $this->storageService->saveGuessMeRound($validated, $imageFile);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Delete Guess Me round
     */
    public function deleteGuessMeRound(int $id): JsonResponse
    {
        $result = $this->storageService->deleteGuessMeRound($id);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Update Game 2 active round, revealed answer status, crosses, or revealAll
     */
    public function updateGame2State(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'round_index' => 'required|integer|min:0',
            'answer_index' => 'nullable|integer|min:0',
            'revealed' => 'nullable|boolean',
            'crosses' => 'nullable|integer|min:0|max:3',
            'reveal_all' => 'nullable|boolean',
        ]);

        $result = $this->storageService->updateGame2State(
            (int) $validated['round_index'],
            isset($validated['answer_index']) ? (int) $validated['answer_index'] : null,
            isset($validated['revealed']) ? (bool) $validated['revealed'] : null,
            isset($validated['crosses']) ? (int) $validated['crosses'] : null,
            isset($validated['reveal_all']) ? (bool) $validated['reveal_all'] : null
        );

        return response()->json($result);
    }

    /**
     * Add or edit Growth 100 round
     */
    public function saveGrowth100Round(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'question' => 'required|string',
            'answers' => 'required|array|min:1',
            'answers.*.text' => 'required|string',
            'answers.*.score' => 'required|integer|min:1',
        ]);

        $result = $this->storageService->saveGrowth100Round($validated);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Delete Growth 100 round
     */
    public function deleteGrowth100Round(int $id): JsonResponse
    {
        $result = $this->storageService->deleteGrowth100Round($id);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Reset Game state to initial
     */
    public function resetGame(): JsonResponse
    {
        $result = $this->storageService->resetGame();
        return response()->json($result);
    }

    /**
     * Serve uploaded/game images safely
     */
    public function getImage(string $filename)
    {
        $storagePath = storage_path('app/game/images/' . $filename);
        $publicPath = public_path('assets/images/' . $filename);

        if (File::exists($storagePath)) {
            return response()->file($storagePath);
        }

        if (File::exists($publicPath)) {
            return response()->file($publicPath);
        }

        abort(404, 'Image not found.');
    }
}
