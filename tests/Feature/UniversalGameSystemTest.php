<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\Team;
use App\Models\User;
use App\Services\GameStorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UniversalGameSystemTest extends TestCase
{
    protected GameStorageService $storage;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(GameStorageService::class);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_byc@gmail.com'],
            [
                'name' => 'Admin BYC',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );
    }

    // ==========================================
    // 1. TEAM SYSTEM TESTS
    // ==========================================

    public function test_can_configure_2_teams(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'teams' => [
                ['name' => 'Alpha Team', 'color' => 'red'],
                ['name' => 'Beta Team', 'color' => 'blue'],
            ],
        ];

        $res = $this->postJson('/game/teams/configure', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $activeTeams = $this->storage->getActiveTeams();
        $this->assertCount(2, $activeTeams);
        $this->assertEquals('Alpha Team', $activeTeams[0]['name']);
        $this->assertEquals('Beta Team', $activeTeams[1]['name']);
    }

    public function test_can_configure_3_teams(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'teams' => [
                ['name' => 'Alpha', 'color' => 'red'],
                ['name' => 'Beta', 'color' => 'blue'],
                ['name' => 'Gamma', 'color' => 'forest'],
            ],
        ];

        $res = $this->postJson('/game/teams/configure', $payload);
        $res->assertOk();

        $activeTeams = $this->storage->getActiveTeams();
        $this->assertCount(3, $activeTeams);
        $this->assertEquals('Gamma', $activeTeams[2]['name']);
        $this->assertEquals('forest', $activeTeams[2]['color']);
    }

    public function test_can_configure_4_teams(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'teams' => [
                ['name' => 'Alpha', 'color' => 'red'],
                ['name' => 'Beta', 'color' => 'blue'],
                ['name' => 'Gamma', 'color' => 'forest'],
                ['name' => 'Delta', 'color' => 'gold'],
            ],
        ];

        $res = $this->postJson('/game/teams/configure', $payload);
        $res->assertOk();

        $activeTeams = $this->storage->getActiveTeams();
        $this->assertCount(4, $activeTeams);
        $this->assertEquals('Delta', $activeTeams[3]['name']);
        $this->assertEquals('gold', $activeTeams[3]['color']);
    }

    public function test_team_names_persist_in_mysql_and_can_be_edited(): void
    {
        $this->actingAs($this->adminUser);

        // Configure initial
        $this->postJson('/game/teams/configure', [
            'teams' => [
                ['name' => 'Original A', 'color' => 'red'],
                ['name' => 'Original B', 'color' => 'blue'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('teams', ['name' => 'Original A', 'is_active' => true]);
        $this->assertDatabaseHas('teams', ['name' => 'Original B', 'is_active' => true]);

        // Edit names
        $this->postJson('/game/teams/configure', [
            'teams' => [
                ['name' => 'Renamed Falcon', 'color' => 'red'],
                ['name' => 'Renamed Eagle', 'color' => 'blue'],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('teams', ['name' => 'Renamed Falcon', 'is_active' => true]);
        $this->assertDatabaseHas('teams', ['name' => 'Renamed Eagle', 'is_active' => true]);
    }

    public function test_team_configuration_requires_minimum_2_teams(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->postJson('/game/teams/configure', [
            'teams' => [
                ['name' => 'Solo Team', 'color' => 'red'],
            ],
        ]);

        $res->assertStatus(422);
    }

    // ==========================================
    // 2. SCORE SYSTEM & ATOMIC TRANSFER TESTS
    // ==========================================

    public function test_round_points_awarded_to_only_one_team(): void
    {
        $this->actingAs($this->adminUser);
        $this->storage->resetGame();

        // Ensure 3 active teams
        $this->storage->configureTeams([
            ['name' => 'Alpha', 'color' => 'red'],
            ['name' => 'Beta', 'color' => 'blue'],
            ['name' => 'Gamma', 'color' => 'forest'],
        ]);

        $teams = $this->storage->getActiveTeams();
        $teamAlpha = $teams[0];
        $teamBeta = $teams[1];

        $rounds = $this->storage->getGuessMeRounds();
        $round = $rounds[0];
        $roundScore = $round['score'];

        // Assign round points to Alpha
        $res = $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $round['id'],
            'team_id' => $teamAlpha['id'],
        ]);

        $res->assertOk();
        $res->assertJsonPath('awarded_team_id', $teamAlpha['id']);
        $res->assertJsonPath('action', 'assigned');

        // Check scores: Alpha has roundScore, Beta has 0
        $scores = $this->storage->getTeamsWithScores('game1');
        $alphaScore = collect($scores)->firstWhere('id', $teamAlpha['id'])['scores']['game1'];
        $betaScore = collect($scores)->firstWhere('id', $teamBeta['id'])['scores']['game1'];

        $this->assertEquals($roundScore, $alphaScore);
        $this->assertEquals(0, $betaScore);
    }

    public function test_round_points_transfer_atomically_deducts_from_previous_recipient(): void
    {
        $this->actingAs($this->adminUser);
        $this->storage->resetGame();

        $this->storage->configureTeams([
            ['name' => 'Alpha', 'color' => 'red'],
            ['name' => 'Beta', 'color' => 'blue'],
            ['name' => 'Gamma', 'color' => 'forest'],
        ]);

        $teams = $this->storage->getActiveTeams();
        $teamAlpha = $teams[0];
        $teamBeta = $teams[1];

        $rounds = $this->storage->getGuessMeRounds();
        $round = $rounds[0];
        $roundScore = (int) $round['score'];

        // 1. Award to Alpha
        $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $round['id'],
            'team_id' => $teamAlpha['id'],
        ])->assertOk();

        // 2. Transfer recipient to Beta
        $transferRes = $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $round['id'],
            'team_id' => $teamBeta['id'],
        ]);

        $transferRes->assertOk();
        $transferRes->assertJsonPath('action', 'transferred');
        $transferRes->assertJsonPath('awarded_team_id', $teamBeta['id']);
        $transferRes->assertJsonPath('transferred_from_team_id', $teamAlpha['id']);

        // Check scores: Alpha must lose the points (0), Beta must have them (roundScore)
        $scores = $this->storage->getTeamsWithScores('game1');
        $alphaScore = collect($scores)->firstWhere('id', $teamAlpha['id'])['scores']['game1'];
        $betaScore = collect($scores)->firstWhere('id', $teamBeta['id'])['scores']['game1'];

        $this->assertEquals(0, $alphaScore, 'Previous recipient must NOT retain round points.');
        $this->assertEquals($roundScore, $betaScore, 'Newly selected recipient must receive round points.');

        // Round in MySQL must only reference Beta
        $dbRound = GameRound::find($round['id']);
        $this->assertEquals($teamBeta['id'], $dbRound->awarded_team_id);
        $this->assertEquals($roundScore, $dbRound->awarded_points);
    }

    public function test_clicking_awarded_team_unassigns_points_cleanly(): void
    {
        $this->actingAs($this->adminUser);
        $this->storage->resetGame();

        $teams = $this->storage->getActiveTeams();
        $teamA = $teams[0];

        $rounds = $this->storage->getGuessMeRounds();
        $round = $rounds[0];

        // Award points
        $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $round['id'],
            'team_id' => $teamA['id'],
        ])->assertOk();

        // Click again -> should unassign
        $unassignRes = $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $round['id'],
            'team_id' => $teamA['id'],
        ]);

        $unassignRes->assertOk();
        $unassignRes->assertJsonPath('action', 'unassigned');
        $unassignRes->assertJsonPath('awarded_team_id', null);

        // Score must return to 0
        $scores = $this->storage->getTeamsWithScores('game1');
        $aScore = collect($scores)->firstWhere('id', $teamA['id'])['scores']['game1'];
        $this->assertEquals(0, $aScore);
    }

    public function test_score_persists_in_mysql_after_fresh_query(): void
    {
        $this->actingAs($this->adminUser);
        $this->storage->resetGame();

        $teams = $this->storage->getActiveTeams();
        $teamA = $teams[0];

        $this->postJson('/game/update-score', [
            'game' => 'game1',
            'team' => $teamA['id'],
            'amount' => 45,
        ])->assertOk();

        // Direct query without service cache
        $game = Game::where('code', 'game1')->first();
        $scoreRecord = GameScore::where('game_id', $game->id)->where('team_id', $teamA['id'])->first();

        $this->assertNotNull($scoreRecord);
        $this->assertEquals(45, $scoreRecord->score);
    }

    // ==========================================
    // 3. BATCH QUESTION SAVE TESTS
    // ==========================================

    public function test_guess_me_batch_save_multiple_rounds(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'rounds' => [
                [
                    'correct_answer' => 'APPLE',
                    'clue' => 'A _ _ L E',
                    'score' => 25,
                    'image' => 'BYC_Growth.jpg',
                ],
                [
                    'correct_answer' => 'BANANA',
                    'clue' => 'B _ N _ N _',
                    'score' => 30,
                    'image' => 'BYC_Growth.jpg',
                ],
            ],
        ];

        $res = $this->postJson('/game/guess-me/batch', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $rounds = $this->storage->getGuessMeRounds();
        $this->assertCount(2, $rounds);
        $this->assertEquals('APPLE', $rounds[0]['correct_answer']);
        $this->assertEquals('BANANA', $rounds[1]['correct_answer']);
    }

    public function test_guess_me_batch_save_invalid_round_is_rejected_and_rolls_back(): void
    {
        $this->actingAs($this->adminUser);

        $initialRounds = $this->storage->getGuessMeRounds();
        $initialCount = count($initialRounds);

        $payload = [
            'rounds' => [
                [
                    'correct_answer' => 'APPLE',
                    'clue' => 'A _ _ L E',
                    'score' => 25,
                ],
                [
                    'correct_answer' => 'BANANA',
                    'clue' => 'B _ _ _', // invalid: length mismatch
                    'score' => 30,
                ],
            ],
        ];

        $res = $this->postJson('/game/guess-me/batch', $payload);
        $res->assertStatus(422);
        $res->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('Round 2', $res->json('error'));

        // Database must remain unaffected
        $currentRounds = $this->storage->getGuessMeRounds();
        $this->assertCount($initialCount, $currentRounds);
    }

    public function test_growth_100_batch_save_multiple_rounds(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'rounds' => [
                [
                    'question' => 'Batch Survey 1?',
                    'answers' => [
                        ['text' => 'Top Answer', 'score' => 60],
                        ['text' => 'Second Answer', 'score' => 40],
                    ],
                ],
                [
                    'question' => 'Batch Survey 2?',
                    'answers' => [
                        ['text' => 'Option A', 'score' => 50],
                        ['text' => 'Option B', 'score' => 30],
                        ['text' => 'Option C', 'score' => 20],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/game/growth-100/batch', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $rounds = $this->storage->getGrowth100Rounds();
        $this->assertCount(2, $rounds);
        $this->assertEquals('Batch Survey 1?', $rounds[0]['question']);
        $this->assertEquals('Batch Survey 2?', $rounds[1]['question']);
    }

    public function test_growth_100_batch_save_invalid_sum_is_rejected_and_rolls_back(): void
    {
        $this->actingAs($this->adminUser);

        $initialCount = count($this->storage->getGrowth100Rounds());

        $payload = [
            'rounds' => [
                [
                    'question' => 'Valid Question',
                    'answers' => [
                        ['text' => 'Ans 1', 'score' => 60],
                        ['text' => 'Ans 2', 'score' => 40],
                    ],
                ],
                [
                    'question' => 'Invalid Question Sum 90',
                    'answers' => [
                        ['text' => 'Ans 1', 'score' => 50],
                        ['text' => 'Ans 2', 'score' => 40], // Sum = 90
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/game/growth-100/batch', $payload);
        $res->assertStatus(422);
        $res->assertJsonFragment(['success' => false]);
        $this->assertStringContainsString('Round 2', $res->json('error'));

        // Database must remain unaffected
        $this->assertCount($initialCount, $this->storage->getGrowth100Rounds());
    }

    // ==========================================
    // 4. AUTHORIZATION TESTS
    // ==========================================

    public function test_public_user_can_access_and_view_games(): void
    {
        Auth::logout();

        $this->get('/guess-me')->assertOk()->assertSee('Guess Me!');
        $this->get('/growth-100')->assertOk()->assertSee('BYC Growth');
        $this->get('/final')->assertOk()->assertSee('Final Score');
    }

    public function test_public_user_cannot_configure_teams(): void
    {
        Auth::logout();

        $res = $this->postJson('/game/teams/configure', [
            'teams' => [
                ['name' => 'Hacker 1', 'color' => 'red'],
                ['name' => 'Hacker 2', 'color' => 'blue'],
            ],
        ]);

        // Must be unauthenticated (401 or redirect to login)
        $this->assertTrue(in_array($res->status(), [401, 302, 403]));
    }

    public function test_public_user_cannot_update_scores(): void
    {
        Auth::logout();

        $res = $this->postJson('/game/update-score', [
            'game' => 'game1',
            'team' => 'red',
            'amount' => 50,
        ]);

        $this->assertTrue(in_array($res->status(), [401, 302, 403]));
    }

    public function test_public_user_cannot_assign_round_points(): void
    {
        Auth::logout();

        $res = $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => 1,
            'team_id' => 1,
        ]);

        $this->assertTrue(in_array($res->status(), [401, 302, 403]));
    }

    public function test_public_user_cannot_batch_save(): void
    {
        Auth::logout();

        $res = $this->postJson('/game/guess-me/batch', [
            'rounds' => [
                ['correct_answer' => 'TEST', 'clue' => 'T _ S T', 'score' => 20],
            ],
        ]);

        $this->assertTrue(in_array($res->status(), [401, 302, 403]));
    }
}
