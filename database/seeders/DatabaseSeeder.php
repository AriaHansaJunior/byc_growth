<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\MediaFile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial administrators, games, teams, and migrated JSON data.
     */
    public function run(): void
    {
        // 1. Initial Admin Accounts (Scope 2 Foundation)
        User::updateOrCreate(
            ['email' => 'admin_byc@gmail.com'],
            [
                'name' => 'Admin BYC',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // 2. Games
        $game1 = Game::updateOrCreate(
            ['code' => 'game1'],
            [
                'title' => 'Guess Me!',
                'description' => 'Guess the secret answer from image and character clues.',
                'is_active' => true,
            ]
        );

        $game2 = Game::updateOrCreate(
            ['code' => 'game2'],
            [
                'title' => 'BYC Growth 100',
                'description' => 'Top survey answers challenge to collect 100 points.',
                'is_active' => true,
            ]
        );

        // 3. Teams
        $teamRed = Team::updateOrCreate(
            ['code' => 'red'],
            [
                'name' => 'Red Team',
                'color' => '#bd4c42',
            ]
        );

        $teamBlue = Team::updateOrCreate(
            ['code' => 'blue'],
            [
                'name' => 'Blue Team',
                'color' => '#315e89',
            ]
        );

        // 4. Initial Game Scores (Default 0 or from existing state)
        foreach ([$game1, $game2] as $g) {
            foreach ([$teamRed, $teamBlue] as $t) {
                GameScore::firstOrCreate(
                    ['game_id' => $g->id, 'team_id' => $t->id],
                    ['score' => 0]
                );
            }
        }

        // 5. Initial Game States
        GameState::firstOrCreate(
            ['game_id' => $game1->id],
            [
                'current_round_index' => 0,
                'state_data' => ['revealed' => []],
            ]
        );

        GameState::firstOrCreate(
            ['game_id' => $game2->id],
            [
                'current_round_index' => 0,
                'state_data' => ['crosses' => [], 'revealed' => []],
            ]
        );

        // 6. Migrate Media Files from public/assets/images and storage
        $knownImages = [
            'BYC_Growth.jpg',
            'Screenshot_2026-09-28_144605.png',
            'Screenshot_2026-09-28_145555.png',
            'guess_1790655389_6abb3b9d9ff0b.png',
            'byc-logo.png',
            'group-photo-dummy.svg',
        ];

        $mediaMap = [];
        foreach ($knownImages as $img) {
            $publicPath = public_path('assets/images/' . $img);
            if (File::exists($publicPath)) {
                $media = MediaFile::firstOrCreate(
                    ['file_path' => 'assets/images/' . $img],
                    [
                        'disk' => 'public',
                        'original_name' => $img,
                        'mime_type' => File::mimeType($publicPath) ?: 'image/jpeg',
                        'file_size' => File::size($publicPath),
                    ]
                );
                $mediaMap[$img] = $media;
            }
        }

        // 7. Migrate Existing JSON Data
        $this->migrateExistingJsonData($game1, $game2, $teamRed, $teamBlue, $mediaMap);
    }

    /**
     * Migrate existing JSON files if they contain data
     */
    protected function migrateExistingJsonData(Game $game1, Game $game2, Team $teamRed, Team $teamBlue, array $mediaMap): void
    {
        $storageDir = storage_path('app/game');

        // Migrate Guess Me rounds
        $guessPath = $storageDir . '/guess-me.json';
        if (File::exists($guessPath)) {
            $guessData = json_decode(File::get($guessPath), true);
            if (is_array($guessData)) {
                foreach ($guessData as $item) {
                    $imgName = $item['image'] ?? 'BYC_Growth.jpg';
                    $mediaId = isset($mediaMap[$imgName]) ? $mediaMap[$imgName]->id : null;

                    GameRound::updateOrCreate(
                        [
                            'game_id' => $game1->id,
                            'round_number' => (int) ($item['id'] ?? 1),
                        ],
                        [
                            'correct_answer' => strtoupper($item['correct_answer'] ?? ''),
                            'clue' => $item['clue'] ?? '',
                            'score' => (int) ($item['score'] ?? 20),
                            'media_file_id' => $mediaId,
                            'image_path' => $imgName,
                        ]
                    );
                }
            }
        }

        // Migrate Growth 100 rounds & answers
        $growthPath = $storageDir . '/growth-100.json';
        if (File::exists($growthPath)) {
            $growthData = json_decode(File::get($growthPath), true);
            if (is_array($growthData)) {
                foreach ($growthData as $item) {
                    $round = GameRound::updateOrCreate(
                        [
                            'game_id' => $game2->id,
                            'round_number' => (int) ($item['id'] ?? 1),
                        ],
                        [
                            'question' => $item['question'] ?? '',
                            'score' => 100,
                        ]
                    );

                    if (isset($item['answers']) && is_array($item['answers'])) {
                        foreach ($item['answers'] as $sortIdx => $ans) {
                            GameAnswer::updateOrCreate(
                                [
                                    'game_round_id' => $round->id,
                                    'answer_text' => $ans['text'] ?? '',
                                ],
                                [
                                    'points' => (int) ($ans['score'] ?? $ans['points'] ?? 0),
                                    'sort_order' => $sortIdx,
                                    'is_revealed' => (bool) ($ans['revealed'] ?? false),
                                ]
                            );
                        }
                    }
                }
            }
        }

        // Migrate Game State & Scores
        $statePath = $storageDir . '/game-state.json';
        if (File::exists($statePath)) {
            $stateData = json_decode(File::get($statePath), true);
            if (is_array($stateData)) {
                // Game 1 scores
                if (isset($stateData['game1']['scores'])) {
                    GameScore::updateOrCreate(
                        ['game_id' => $game1->id, 'team_id' => $teamRed->id],
                        ['score' => (int) ($stateData['game1']['scores']['red'] ?? 0)]
                    );
                    GameScore::updateOrCreate(
                        ['game_id' => $game1->id, 'team_id' => $teamBlue->id],
                        ['score' => (int) ($stateData['game1']['scores']['blue'] ?? 0)]
                    );
                }

                // Game 2 scores
                if (isset($stateData['game2']['scores'])) {
                    GameScore::updateOrCreate(
                        ['game_id' => $game2->id, 'team_id' => $teamRed->id],
                        ['score' => (int) ($stateData['game2']['scores']['red'] ?? 0)]
                    );
                    GameScore::updateOrCreate(
                        ['game_id' => $game2->id, 'team_id' => $teamBlue->id],
                        ['score' => (int) ($stateData['game2']['scores']['blue'] ?? 0)]
                    );
                }

                // Game 1 state
                if (isset($stateData['game1'])) {
                    GameState::updateOrCreate(
                        ['game_id' => $game1->id],
                        [
                            'current_round_index' => (int) ($stateData['game1']['current_round'] ?? 0),
                            'state_data' => [
                                'revealed' => $stateData['game1']['revealed'] ?? [],
                            ],
                        ]
                    );
                }

                // Game 2 state
                if (isset($stateData['game2'])) {
                    GameState::updateOrCreate(
                        ['game_id' => $game2->id],
                        [
                            'current_round_index' => (int) ($stateData['game2']['current_round'] ?? 0),
                            'state_data' => [
                                'crosses' => $stateData['game2']['crosses'] ?? [],
                                'revealed' => $stateData['game2']['revealed'] ?? [],
                            ],
                        ]
                    );
                }
            }
        }
    }
}
