<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\GameStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GameController extends Controller
{
    protected GameStorageService $storageService;

    public function __construct(GameStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Display the Admin Games Management portal.
     * Manages Guess Me! rounds, BYC GROWTH 100 questions, answers, and scores.
     */
    public function index(Request $request): View
    {
        $scores = $this->storageService->getFinalScores();
        $gameState = $this->storageService->getGameState();
        $teams = $this->storageService->getTeamsWithScores();
        $guessMeRounds = $this->storageService->getGuessMeRounds(false); // All rounds
        $growth100Rounds = $this->storageService->getGrowth100Rounds(false); // All rounds

        $activeTab = $request->query('tab', 'guess-me');
        if (!in_array($activeTab, ['guess-me', 'growth-100', 'overview'], true)) {
            $activeTab = 'guess-me';
        }

        $games = $this->storageService->getGames(false); // All games with is_hidden flag
        foreach ($games as &$g) {
            $g['rounds_count'] = $g['code'] === 'game1' ? count($guessMeRounds) : count($growth100Rounds);
        }
        unset($g);

        return view('admin.games', [
            'finalScores' => $scores,
            'gameState' => $gameState,
            'teams' => $teams,
            'games' => $games,
            'guessMeRounds' => $guessMeRounds,
            'growth100Rounds' => $growth100Rounds,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Admin Host Session for Game 1 — Guess Me!
     */
    public function guessMeHost(): View
    {
        $rounds = $this->storageService->getGuessMeRounds(false); // All rounds (with is_hidden status)
        $gameState = $this->storageService->getGameState();
        $teams = $this->storageService->getTeamsWithScores('game1');

        return view('admin.game-guess-me', [
            'rounds' => $rounds,
            'game1State' => $gameState['game1'],
            'teams' => $teams,
        ]);
    }

    /**
     * Admin Host Session for Game 2 — BYC Growth 100
     */
    public function growth100Host(): View
    {
        $this->storageService->resetGame2Revealed();
        $rounds = $this->storageService->getGrowth100Rounds(false); // All rounds (with is_hidden status)
        $gameState = $this->storageService->getGameState();
        $teams = $this->storageService->getTeamsWithScores('game2');

        return view('admin.game-growth-100', [
            'rounds' => $rounds,
            'game2State' => $gameState['game2'],
            'teams' => $teams,
        ]);
    }

    /**
     * Toggle visibility of an entire game from Admin Game Center.
     */
    public function toggleGameVisibility(string|int $id): JsonResponse
    {
        $result = $this->storageService->toggleGameVisibility($id);
        return response()->json($result);
    }

    /**
     * Toggle visibility of a round from within the round's admin page.
     */
    public function toggleRoundVisibility(int $id): JsonResponse
    {
        $result = $this->storageService->toggleRoundVisibility($id);
        return response()->json($result);
    }

    /**
     * Create or update a Guess Me round.
     */
    public function saveGuessMeRound(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'correct_answer' => 'required|string|max:100',
            'clue' => 'required|string|max:100',
            'score' => 'required|integer|min:1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
        ]);

        $imageFile = $request->file('image');
        $result = $this->storageService->saveGuessMeRound($validated, $imageFile);

        if (!$result['success']) {
            $status = $result['status'] ?? 422;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($result, $status);
            }
            return redirect()->route('admin.games', ['tab' => 'guess-me'])
                ->withErrors(['error' => $result['error']])
                ->withInput();
        }

        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        $action = !empty($validated['id']) ? 'edited' : 'created';
        AuditLog::record($adminUser, $action, 'game', $validated['id'] ?? null, "Saved Guess Me round");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        $message = !empty($validated['id']) ? 'Guess Me round updated successfully.' : 'New Guess Me round created successfully.';
        return redirect()->route('admin.games', ['tab' => 'guess-me'])->with('success', $message);
    }

    /**
     * Delete a Guess Me round.
     */
    public function deleteGuessMeRound(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $result = $this->storageService->deleteGuessMeRound($id);

        if (!$result['success']) {
            $status = $result['status'] ?? 422;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($result, $status);
            }
            return redirect()->route('admin.games', ['tab' => 'guess-me'])->with('error', $result['error']);
        }

        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        AuditLog::record($adminUser, 'deleted', 'game', $id, "Deleted Guess Me round #{$id}");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.games', ['tab' => 'guess-me'])->with('success', 'Guess Me round deleted successfully.');
    }

    /**
     * Reorder Guess Me rounds.
     */
    public function reorderGuessMeRounds(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'round_ids' => 'required|array|min:1',
            'round_ids.*' => 'required|integer',
        ]);

        $result = $this->storageService->reorderGuessMeRounds($validated['round_ids']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.games', ['tab' => 'guess-me'])->with('success', 'Rounds reordered successfully.');
    }

    /**
     * Create or update a BYC Growth 100 question round.
     */
    public function saveGrowth100Round(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'question' => 'required|string|max:500',
            'answers' => 'required|array|min:1',
            'answers.*.text' => 'required|string|max:200',
            'answers.*.score' => 'required|integer|min:1',
        ]);

        $result = $this->storageService->saveGrowth100Round($validated);

        if (!$result['success']) {
            $status = $result['status'] ?? 422;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($result, $status);
            }
            return redirect()->route('admin.games', ['tab' => 'growth-100'])
                ->withErrors(['error' => $result['error']])
                ->withInput();
        }

        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        $action = !empty($validated['id']) ? 'edited' : 'created';
        AuditLog::record($adminUser, $action, 'game', $validated['id'] ?? null, "Saved Growth 100 round '{$validated['question']}'");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        $message = !empty($validated['id']) ? 'Survey question updated successfully.' : 'New survey question created successfully.';
        return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('success', $message);
    }

    /**
     * Delete a BYC Growth 100 round.
     */
    public function deleteGrowth100Round(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $result = $this->storageService->deleteGrowth100Round($id);

        if (!$result['success']) {
            $status = $result['status'] ?? 422;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($result, $status);
            }
            return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('error', $result['error']);
        }

        $adminUser = Auth::guard('admin')->user() ?? Auth::guard('web')->user() ?? Auth::user();
        AuditLog::record($adminUser, 'deleted', 'game', $id, "Deleted Growth 100 round #{$id}");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('success', 'Survey question deleted successfully.');
    }

    /**
     * Reorder Growth 100 questions.
     */
    public function reorderGrowth100Rounds(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'round_ids' => 'required|array|min:1',
            'round_ids.*' => 'required|integer',
        ]);

        $result = $this->storageService->reorderGrowth100Rounds($validated['round_ids']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('success', 'Questions reordered successfully.');
    }

    /**
     * Add single answer to a Growth 100 question.
     */
    public function addGrowth100Answer(Request $request, int $roundId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:200',
            'score' => 'required|integer|min:1',
            'adjust_from_answer_id' => 'nullable|integer',
        ]);

        $result = $this->storageService->addGrowth100Answer($roundId, $validated);

        if (!$result['success']) {
            $status = $result['status'] ?? 422;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($result, $status);
            }
            return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('error', $result['error']);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('success', 'Answer added successfully.');
    }

    /**
     * Edit single answer in Growth 100.
     */
    public function updateGrowth100Answer(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'text' => 'nullable|string|max:200',
            'score' => 'nullable|integer|min:1',
            'adjust_answer_id' => 'nullable|integer',
        ]);

        $result = $this->storageService->updateGrowth100Answer($id, $validated);

        if (!$result['success']) {
            $status = $result['status'] ?? 422;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($result, $status);
            }
            return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('error', $result['error']);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('success', 'Answer updated successfully.');
    }

    /**
     * Delete single answer in Growth 100.
     */
    public function deleteGrowth100Answer(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $transferToId = $request->input('transfer_to_id') ? (int) $request->input('transfer_to_id') : null;
        $result = $this->storageService->deleteGrowth100Answer($id, $transferToId);

        if (!$result['success']) {
            $status = $result['status'] ?? 422;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($result, $status);
            }
            return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('error', $result['error']);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('admin.games', ['tab' => 'growth-100'])->with('success', 'Answer removed successfully.');
    }

    /**
     * Batch save multiple Guess Me rounds in one atomic transaction.
     */
    public function saveGuessMeBatchRounds(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rounds' => 'required|array|min:1',
            'rounds.*.id' => 'nullable|integer',
            'rounds.*.correct_answer' => 'required|string',
            'rounds.*.clue' => 'required|string',
            'rounds.*.score' => 'required|integer|min:1',
            'rounds.*.image' => 'nullable|string',
        ]);

        $result = $this->storageService->saveGuessMeBatchRounds($validated['rounds']);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * Batch save multiple Growth 100 rounds in one atomic transaction.
     */
    public function saveGrowth100BatchRounds(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rounds' => 'required|array|min:1',
            'rounds.*.id' => 'nullable|integer',
            'rounds.*.question' => 'required|string',
            'rounds.*.answers' => 'required|array|min:1',
            'rounds.*.answers.*.text' => 'required|string',
            'rounds.*.answers.*.score' => 'required|integer|min:1',
        ]);

        $result = $this->storageService->saveGrowth100BatchRounds($validated['rounds']);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }
}
