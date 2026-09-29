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

    // ==========================================
    // 5. SCOPE 5 REVISION: TEAM GAME OWNERSHIP & ISOLATION TESTS
    // ==========================================

    public function test_guess_me_can_have_its_own_team_configuration(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'game' => 'game1',
            'teams' => [
                ['name' => 'GM Alpha', 'color' => '#bd4c42'],
                ['name' => 'GM Beta', 'color' => '#315e89'],
                ['name' => 'GM Gamma', 'color' => '#284e3b'],
            ],
        ];

        $res = $this->postJson('/game/teams/configure', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $g1Teams = $this->storage->getActiveTeams('game1');
        $this->assertCount(3, $g1Teams);
        $this->assertEquals('GM Alpha', $g1Teams[0]->name);
        $this->assertEquals('GM Beta', $g1Teams[1]->name);
        $this->assertEquals('GM Gamma', $g1Teams[2]->name);

        $game1 = Game::where('code', 'game1')->first();
        foreach ($g1Teams as $team) {
            $this->assertEquals($game1->id, $team->game_id);
        }
    }

    public function test_growth_100_can_have_a_different_team_configuration(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'game' => 'game2',
            'teams' => [
                ['name' => 'G100 Eagles', 'color' => '#bd4c42'],
                ['name' => 'G100 Falcons', 'color' => '#315e89'],
                ['name' => 'G100 Hawks', 'color' => '#284e3b'],
                ['name' => 'G100 Ravens', 'color' => '#c28b28'],
            ],
        ];

        $res = $this->postJson('/game/teams/configure', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $g2Teams = $this->storage->getActiveTeams('game2');
        $this->assertCount(4, $g2Teams);
        $this->assertEquals('G100 Eagles', $g2Teams[0]->name);
        $this->assertEquals('G100 Falcons', $g2Teams[1]->name);
        $this->assertEquals('G100 Hawks', $g2Teams[2]->name);
        $this->assertEquals('G100 Ravens', $g2Teams[3]->name);

        $game2 = Game::where('code', 'game2')->first();
        foreach ($g2Teams as $team) {
            $this->assertEquals($game2->id, $team->game_id);
        }
    }

    public function test_configuring_teams_for_guess_me_does_not_alter_growth_100_teams(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Establish distinct Game 2 teams
        $this->postJson('/game/teams/configure', [
            'game' => 'game2',
            'teams' => [
                ['name' => 'G2 Lions', 'color' => '#bd4c42'],
                ['name' => 'G2 Tigers', 'color' => '#315e89'],
                ['name' => 'G2 Bears', 'color' => '#284e3b'],
            ],
        ])->assertOk();

        $beforeG2Teams = $this->storage->getActiveTeams('game2')->pluck('name', 'id')->toArray();
        $this->assertCount(3, $beforeG2Teams);

        // 2. Configure Game 1 teams
        $this->postJson('/game/teams/configure', [
            'game' => 'game1',
            'teams' => [
                ['name' => 'G1 Wolves', 'color' => '#bd4c42'],
                ['name' => 'G1 Foxes', 'color' => '#315e89'],
            ],
        ])->assertOk();

        // 3. Verify Game 2 teams remain identical
        $afterG2Teams = $this->storage->getActiveTeams('game2')->pluck('name', 'id')->toArray();
        $this->assertEquals($beforeG2Teams, $afterG2Teams, 'Game 2 teams must remain untouched when configuring Game 1.');
    }

    public function test_configuring_teams_for_growth_100_does_not_alter_guess_me_teams(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Establish distinct Game 1 teams
        $this->postJson('/game/teams/configure', [
            'game' => 'game1',
            'teams' => [
                ['name' => 'G1 Sharks', 'color' => '#bd4c42'],
                ['name' => 'G1 Whales', 'color' => '#315e89'],
                ['name' => 'G1 Dolphins', 'color' => '#284e3b'],
            ],
        ])->assertOk();

        $beforeG1Teams = $this->storage->getActiveTeams('game1')->pluck('name', 'id')->toArray();
        $this->assertCount(3, $beforeG1Teams);

        // 2. Configure Game 2 teams
        $this->postJson('/game/teams/configure', [
            'game' => 'game2',
            'teams' => [
                ['name' => 'G2 Cobras', 'color' => '#bd4c42'],
                ['name' => 'G2 Vipers', 'color' => '#315e89'],
            ],
        ])->assertOk();

        // 3. Verify Game 1 teams remain identical
        $afterG1Teams = $this->storage->getActiveTeams('game1')->pluck('name', 'id')->toArray();
        $this->assertEquals($beforeG1Teams, $afterG1Teams, 'Game 1 teams must remain untouched when configuring Game 2.');
    }

    public function test_round_cannot_award_points_to_team_belonging_to_another_game(): void
    {
        $this->actingAs($this->adminUser);
        $this->storage->resetGame();

        // Ensure teams exist for both games
        $g1Teams = $this->storage->getActiveTeams('game1');
        $g2Teams = $this->storage->getActiveTeams('game2');

        $g1Team = $g1Teams->first();
        $g2Team = $g2Teams->first();

        $g1Rounds = $this->storage->getGuessMeRounds();
        $g1Round = $g1Rounds[0];

        $g2Rounds = $this->storage->getGrowth100Rounds();
        $g2Round = $g2Rounds[0];

        // Case 1: Attempt to award Game 1 round to Game 2 team
        $res1 = $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $g1Round['id'],
            'team_id' => $g2Team->id,
        ]);

        $res1->assertStatus(422);
        $res1->assertJsonPath('success', false);
        $this->assertStringContainsString('cannot award points to a team belonging to another game', $res1->json('error'));

        // Verify round awarded_team_id was NOT modified
        $dbG1Round = GameRound::find($g1Round['id']);
        $this->assertNotEquals($g2Team->id, $dbG1Round->awarded_team_id);

        // Case 2: Attempt to award Game 2 round to Game 1 team
        $res2 = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $g2Round['id'],
            'team_id' => $g1Team->id,
        ]);

        $res2->assertStatus(422);
        $res2->assertJsonPath('success', false);
        $this->assertStringContainsString('cannot award points to a team belonging to another game', $res2->json('error'));

        // Verify round awarded_team_id was NOT modified
        $dbG2Round = GameRound::find($g2Round['id']);
        $this->assertNotEquals($g1Team->id, $dbG2Round->awarded_team_id);
    }

    public function test_existing_migrated_game_data_remains_intact(): void
    {
        $game1 = Game::where('code', 'game1')->first();
        $game2 = Game::where('code', 'game2')->first();

        $this->assertNotNull($game1, 'Game 1 record must exist in MySQL.');
        $this->assertNotNull($game2, 'Game 2 record must exist in MySQL.');

        // Game 1 rounds and their relationship
        $g1Rounds = GameRound::where('game_id', $game1->id)->get();
        $this->assertNotEmpty($g1Rounds, 'Game 1 must have rounds in MySQL.');
        foreach ($g1Rounds as $round) {
            $this->assertEquals($game1->id, $round->game_id);
            $this->assertEquals($game1->id, $round->game->id);
            $this->assertNotEmpty($round->correct_answer);
        }

        // Game 2 rounds and their answers
        $g2Rounds = GameRound::where('game_id', $game2->id)->with('answers')->get();
        $this->assertNotEmpty($g2Rounds, 'Game 2 must have rounds in MySQL.');
        foreach ($g2Rounds as $round) {
            $this->assertEquals($game2->id, $round->game_id);
            $this->assertEquals($game2->id, $round->game->id);
            $this->assertNotEmpty($round->question);
            $this->assertNotEmpty($round->answers);
        }

        // Teams relational integrity: all teams must reference a valid game
        $allTeams = Team::all();
        $this->assertNotEmpty($allTeams);
        foreach ($allTeams as $team) {
            $this->assertNotNull($team->game_id, 'Every team must have a non-null game_id.');
            $this->assertNotNull($team->game, 'Team game relationship must resolve to a valid Game model.');
            $this->assertTrue(in_array($team->game_id, [$game1->id, $game2->id]));
        }

        // GameScores relational integrity: game_id and team.game_id must match
        $allScores = GameScore::with('team')->get();
        $this->assertNotEmpty($allScores);
        foreach ($allScores as $score) {
            $this->assertNotNull($score->team);
            $this->assertEquals($score->game_id, $score->team->game_id, 'GameScore game_id must match Team game_id.');
        }

        // Game model teams relationship
        $this->assertCount(Team::where('game_id', $game1->id)->count(), $game1->teams);
        $this->assertCount(Team::where('game_id', $game2->id)->count(), $game2->teams);
    }
}
