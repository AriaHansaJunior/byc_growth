<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GameStorageService;
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
     * Manages Guess Me!, BYC GROWTH 100, team setups, rounds, and live scoring.
     */
    public function index(): View
    {
        $scores = $this->storageService->getFinalScores();
        $gameState = $this->storageService->getGameState();
        $teams = $this->storageService->getTeamsWithScores();

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
                'rounds_count' => 10,
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
                'rounds_count' => 10,
            ],
        ];

        return view('admin.games', [
            'finalScores' => $scores,
            'gameState' => $gameState,
            'teams' => $teams,
            'games' => $games,
        ]);
    }
}
