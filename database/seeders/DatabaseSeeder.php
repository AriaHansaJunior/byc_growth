<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\HomepageSlide;
use App\Models\MediaFile;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial administrators, games, teams, and default homepage slides.
     */
    public function run(): void
    {
        // 1. Initial Admin Accounts (S0 Foundation)
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Utama',
                'username' => 'admin_utama',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'permissions' => ['homepage', 'activities', 'members', 'games', 'birthday_wishes', 'cash_management', 'roles'],
            ]
        );

        User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'rilbiezzz',
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'super_admin',
                'permissions' => ['homepage', 'activities', 'members', 'games', 'birthday_wishes', 'cash_management', 'roles'],
            ]
        );

        User::updateOrCreate(
            ['email' => 'mega@gmail.com'],
            [
                'name' => 'mega',
                'username' => 'meme_imup',
                'password' => '$2y$10$29NiAeimSeQpN81k/oJoceOjvpSmX1eAgLwiDmiL7y1cCsQIMugyy',
                'role' => 'super_admin',
                'permissions' => ['homepage', 'activities', 'members', 'games', 'birthday_wishes', 'cash_management', 'roles'],
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

        // 3. Teams (Isolated per Game)
        $g1TeamRed = Team::updateOrCreate(
            ['game_id' => $game1->id, 'code' => 'red'],
            [
                'name' => 'Red Team',
                'color' => '#bd4c42',
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        $g1TeamBlue = Team::updateOrCreate(
            ['game_id' => $game1->id, 'code' => 'blue'],
            [
                'name' => 'Blue Team',
                'color' => '#315e89',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $g2TeamRed = Team::updateOrCreate(
            ['game_id' => $game2->id, 'code' => 'red'],
            [
                'name' => 'Red Team',
                'color' => '#bd4c42',
                'sort_order' => 0,
                'is_active' => true,
            ]
        );

        $g2TeamBlue = Team::updateOrCreate(
            ['game_id' => $game2->id, 'code' => 'blue'],
            [
                'name' => 'Blue Team',
                'color' => '#315e89',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        // 4. Initial Game Scores
        foreach ([$g1TeamRed, $g1TeamBlue] as $t) {
            GameScore::firstOrCreate(
                ['game_id' => $game1->id, 'team_id' => $t->id],
                ['score' => 0]
            );
        }
        foreach ([$g2TeamRed, $g2TeamBlue] as $t) {
            GameScore::firstOrCreate(
                ['game_id' => $game2->id, 'team_id' => $t->id],
                ['score' => 0]
            );
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

        // 6. Seed Default Homepage Slides if empty
        if (HomepageSlide::count() === 0) {
            $defaultSlides = [
                [
                    'file' => 'assets/images/hero-slide-1.jpg',
                    'title' => 'Growing in Faith & Fellowship',
                    'caption' => 'A vibrant youth fellowship rooted in faith, love, and spiritual unity.',
                    'sort_order' => 1,
                ],
                [
                    'file' => 'assets/images/hero-slide-2.jpg',
                    'title' => 'Sunday Service & Worship',
                    'caption' => 'Connecting hearts through passionate worship, prayer, and God\'s Word.',
                    'sort_order' => 2,
                ],
                [
                    'file' => 'assets/images/hero-slide-3.jpg',
                    'title' => 'Community Outreach & Service',
                    'caption' => 'Sharing Christ\'s love through active service and genuine community care.',
                    'sort_order' => 3,
                ],
            ];

            foreach ($defaultSlides as $item) {
                $fullPath = public_path($item['file']);
                $mediaId = null;

                if (File::exists($fullPath)) {
                    $media = MediaFile::firstOrCreate(
                        ['file_path' => $item['file']],
                        [
                            'disk' => 'public',
                            'original_name' => basename($item['file']),
                            'mime_type' => 'image/jpeg',
                            'file_size' => File::size($fullPath),
                        ]
                    );
                    $mediaId = $media->id;
                }

                $slide = HomepageSlide::create([
                    'media_file_id' => $mediaId,
                    'title' => $item['title'],
                    'caption' => $item['caption'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]);

                if ($mediaId) {
                    MediaFile::where('id', $mediaId)->update([
                        'fileable_type' => HomepageSlide::class,
                        'fileable_id' => $slide->id,
                    ]);
                }
            }
        }
    }
}
