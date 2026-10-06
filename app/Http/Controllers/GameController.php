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
     * Game Center (/game-center) — Hub for interactive games, scoreboards, and rules
     */
    public function gameCenter()
    {
        // Reset Game 2 revealed answers when navigating to Game Center
        $this->storageService->resetGame2Revealed();

        $scores = $this->storageService->getFinalScores();
        $gameState = $this->storageService->getGameState();
        $teams = $this->storageService->getTeamsWithScores();
        $games = $this->storageService->getGames(true); // Visible games only

        return view('user.game-center', [
            'finalScores' => $scores,
            'gameState' => $gameState,
            'teams' => $teams,
            'games' => $games,
        ]);
    }

    /**
     * Game 1 — Guess Me! Page (Participant / Audience View)
     */
    public function guessMe()
    {
        $game1 = $this->storageService->getGame('game1');
        if ($game1->is_hidden) {
            return redirect()->route('game.center')->with('info', 'This game is currently not available.');
        }

        $rounds = $this->storageService->getGuessMeRounds(true); // Only visible rounds
        $gameState = $this->storageService->getGameState();
        $teams = $this->storageService->getTeamsWithScores('game1');

        return view('user.guess-me', [
            'rounds' => $rounds,
            'game1State' => $gameState['game1'],
            'teams' => $teams,
        ]);
    }

    /**
     * Game 2 — BYC Growth 100 Page (Participant / Audience View)
     */
    public function growth100()
    {
        $game2 = $this->storageService->getGame('game2');
        if ($game2->is_hidden) {
            return redirect()->route('game.center')->with('info', 'This game is currently not available.');
        }

        // Whenever a user enters or refreshes Growth 100, answers return to hidden state
        $this->storageService->resetGame2Revealed();

        $rounds = $this->storageService->getGrowth100Rounds(true); // Only visible rounds
        $gameState = $this->storageService->getGameState();
        $teams = $this->storageService->getTeamsWithScores('game2');

        return response()
            ->view('user.growth-100', [
                'rounds' => $rounds,
                'game2State' => $gameState['game2'],
                'teams' => $teams,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Final Score Screen
     */
    public function finalScore()
    {
        $scores = $this->storageService->getFinalScores();
        $teams = $this->storageService->getTeamsWithScores();

        return view('user.final', [
            'scores' => $scores,
            'teams' => $teams,
        ]);
    }

    /**
     * Configure dynamic teams (add, rename, update colors)
     */
    public function configureTeams(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game' => 'nullable|string|in:game1,game2,all',
            'teams' => 'required|array|min:2',
            'teams.*.id' => 'nullable|integer',
            'teams.*.name' => 'required|string|max:100',
            'teams.*.color' => 'nullable|string|max:50',
        ]);

        $gameCode = $validated['game'] ?? 'game1';
        $result = $this->storageService->configureTeams($gameCode, $validated['teams']);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Universal round point assignment: assign round points to one team, transferring from previous.
     */
    public function assignRoundPoints(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game' => 'required|in:game1,game2',
            'round_id' => 'required|integer',
            'team_id' => 'nullable|integer',
            'points_override' => 'nullable|integer|min:0',
        ]);

        $result = $this->storageService->assignRoundPoints(
            $validated['game'],
            (int) $validated['round_id'],
            isset($validated['team_id']) ? (int) $validated['team_id'] : null,
            isset($validated['points_override']) ? (int) $validated['points_override'] : null
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }


    /**
     * Update scores for Game 1 or Game 2 for any dynamic team
     */
    public function updateScore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game' => 'required|in:game1,game2',
            'team' => 'required',
            'amount' => 'required|integer',
            'is_absolute' => 'nullable|boolean',
        ]);

        $result = $this->storageService->updateScore(
            $validated['game'],
            $validated['team'],
            (int) $validated['amount'],
            (bool) ($validated['is_absolute'] ?? false)
        );

        if (!$result['success']) {
            return response()->json($result, 422);
        }

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
     * Explicitly reset Game 2 revealed state (e.g. called on pagehide/unload beacon)
     */
    public function resetGrowth100Revealed(): JsonResponse
    {
        $this->storageService->resetGame2Revealed();

        return response()->json([
            'success' => true,
            'message' => 'Growth 100 revealed answers reset.',
        ]);
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
     * Serve uploaded/game images safely with path traversal protection
     */
    public function getImage(string $filename)
    {
        $safeFilename = basename($filename);

        // Disallow path traversal sequences or invalid path characters
        if ($safeFilename !== $filename || str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(404, 'Image not found.');
        }

        $storagePath = storage_path('app/game/images/' . $safeFilename);
        $publicPath = public_path('assets/images/' . $safeFilename);
        $uploadsPath = public_path('assets/images/uploads/' . $safeFilename);

        if (File::exists($storagePath) && is_file($storagePath)) {
            return response()->file($storagePath);
        }

        if (File::exists($publicPath) && is_file($publicPath)) {
            return response()->file($publicPath);
        }

        if (File::exists($uploadsPath) && is_file($uploadsPath)) {
            return response()->file($uploadsPath);
        }

        abort(404, 'Image not found.');
    }
}
