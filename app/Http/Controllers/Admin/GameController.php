<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GameStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $guessMeRounds = $this->storageService->getGuessMeRounds();
        $growth100Rounds = $this->storageService->getGrowth100Rounds();

        $activeTab = $request->query('tab', 'guess-me');
        if (!in_array($activeTab, ['guess-me', 'growth-100', 'overview'], true)) {
            $activeTab = 'guess-me';
        }

        $games = [
            [
                'id' => 'guess-me',
                'order' => '01',
                'title' => 'Guess Me!',
                'tag' => 'Visual Word Clues',
                'description' => 'Test speed and teamwork by decoding secret words from custom visual clues and letter slot hints.',
                'route' => 'game.guess-me',
                'theme' => 'forest',
                'status' => 'active',
                'rounds_count' => count($guessMeRounds),
            ],
            [
                'id' => 'growth-100',
                'order' => '02',
                'title' => 'BYC GROWTH 100',
                'tag' => 'Survey Trivia',
                'description' => 'Uncover top survey answers, rack up points, and manage card reveals with 3-strike point steals.',
                'route' => 'game.growth-100',
                'theme' => 'cream',
                'status' => 'active',
                'rounds_count' => count($growth100Rounds),
            ],
        ];

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
}
