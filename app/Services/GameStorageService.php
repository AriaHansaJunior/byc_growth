<?php

namespace App\Services;

use App\Models\Game;
use App\Services\Games\Concerns\ManagesGameSession;
use App\Services\Games\Concerns\ManagesGrowth100;
use App\Services\Games\Concerns\ManagesGuessMe;
use App\Services\Games\Concerns\ManagesTeams;
use Illuminate\Support\Facades\File;

class GameStorageService
{
    use ManagesTeams,
        ManagesGuessMe,
        ManagesGrowth100,
        ManagesGameSession;

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
    public function getGame(string $code): Game
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
}
