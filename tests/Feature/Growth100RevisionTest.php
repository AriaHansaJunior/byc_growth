<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\Team;
use App\Models\User;
use App\Services\GameStorageService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Growth100RevisionTest extends TestCase
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

    /**
     * Requirement 1: Growth 100 round validates that all answers total exactly 100.
     */
    public function test_growth_100_round_validates_answers_total_exactly_100(): void
    {
        $this->actingAs($this->adminUser);

        // Invalid: sum is 90
        $resInvalid = $this->postJson('/game/growth-100/round', [
            'question' => 'Sample invalid survey question?',
            'answers' => [
                ['text' => 'Option A', 'score' => 40],
                ['text' => 'Option B', 'score' => 30],
                ['text' => 'Option C', 'score' => 20],
            ],
        ]);

        $resInvalid->assertStatus(422);
        $resInvalid->assertJsonPath('success', false);

        // Valid: sum is 100
        $resValid = $this->postJson('/game/growth-100/round', [
            'question' => 'What are your favorite Sunday activities?',
            'answers' => [
                ['text' => 'Family Fellowship', 'score' => 40],
                ['text' => 'Sports & Jogging', 'score' => 30],
                ['text' => 'Reading & Study', 'score' => 20],
                ['text' => 'Cooking & Baking', 'score' => 10],
            ],
        ]);

        $resValid->assertOk();
        $resValid->assertJsonPath('success', true);
    }

    /**
     * Requirement 2: Hidden answers contribute 0 to the revealed total.
     */
    public function test_hidden_answers_contribute_zero_to_revealed_total(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::where('game_id', $game2->id)->first();
        $this->assertNotNull($round);

        // Reset state so no answers are revealed
        GameState::updateOrCreate(
            ['game_id' => $game2->id],
            ['state_data' => ['current_round' => 0, 'revealed' => [(string) $round->id => []], 'crosses' => []]]
        );

        $team = Team::where('game_id', $game2->id)->first();
        $this->assertNotNull($team);

        // Award points with 0 answers revealed -> awards 0 points
        $res = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $round->id,
            'team_id' => $team->id,
        ]);

        $res->assertOk();
        $res->assertJsonPath('awarded_points', 0);
    }

    /**
     * Requirement 3: Revealed answer points contribute to the round total.
     */
    public function test_revealed_answer_points_contribute_to_round_total(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();

        // Create a known survey round: 40, 30, 20, 10
        $res = $this->postJson('/game/growth-100/round', [
            'question' => 'Testing Revealed Points Calculation',
            'answers' => [
                ['text' => 'First Answer', 'score' => 40],
                ['text' => 'Second Answer', 'score' => 30],
                ['text' => 'Third Answer', 'score' => 20],
                ['text' => 'Fourth Answer', 'score' => 10],
            ],
        ]);
        $res->assertOk();

        $round = GameRound::where('game_id', $game2->id)
            ->where('question', 'Testing Revealed Points Calculation')
            ->latest('id')
            ->first();
        $this->assertNotNull($round);

        // Set revealed to indexes 0 (40) and 2 (20) -> sum should be 60
        $currentState = GameState::where('game_id', $game2->id)->first();
        $data = $currentState ? $currentState->state_data : [];
        $data['revealed'] = $data['revealed'] ?? [];
        $data['revealed'][(string) $round->id] = [0, 2];

        GameState::updateOrCreate(
            ['game_id' => $game2->id],
            ['state_data' => $data]
        );

        $team = Team::where('game_id', $game2->id)->first();

        $assignRes = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $round->id,
            'team_id' => $team->id,
        ]);

        $assignRes->assertOk();
        $assignRes->assertJsonPath('awarded_points', 60);
    }

    /**
     * Requirement 4: Round total is correctly recalculated after reveal/hide operations.
     */
    public function test_round_total_is_recalculated_after_reveal_hide_operations(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $rounds = GameRound::where('game_id', $game2->id)->get();
        $round = $rounds->first();
        $this->assertNotNull($round);

        $teams = Team::where('game_id', $game2->id)->get();
        $team = $teams->first();

        // Reset state & score
        GameScore::updateOrCreate(['game_id' => $game2->id, 'team_id' => $team->id], ['score' => 0]);
        $round->update(['awarded_team_id' => null, 'awarded_points' => 0]);
        GameState::updateOrCreate(
            ['game_id' => $game2->id],
            ['state_data' => ['current_round' => 0, 'revealed' => [(string) $round->id => []], 'crosses' => []]]
        );

        // 1. Reveal first answer (index 0)
        $revealRes1 = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'answer_index' => 0,
        ]);
        $revealRes1->assertOk();
        $revealed = $revealRes1->json('state.revealed.' . $round->id);
        $this->assertContains(0, $revealed);

        // 2. Award round to team
        $ans0Points = (int) $round->answers[0]->points;
        $assignRes = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $round->id,
            'team_id' => $team->id,
        ]);
        $assignRes->assertOk();
        $assignRes->assertJsonPath('awarded_points', $ans0Points);

        // 3. Reveal second answer (index 1) while round is awarded -> score dynamically updates
        $ans1Points = (int) $round->answers[1]->points;
        $revealRes2 = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'answer_index' => 1,
        ]);
        $revealRes2->assertOk();

        // Check updated score in MySQL
        $score = GameScore::where('game_id', $game2->id)->where('team_id', $team->id)->first();
        $this->assertEquals($ans0Points + $ans1Points, (int) $score->score);

        // 4. Hide second answer (index 1) -> score decreases back
        $hideRes = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'answer_index' => 1,
        ]);
        $hideRes->assertOk();

        $scoreAfterHide = GameScore::where('game_id', $game2->id)->where('team_id', $team->id)->first();
        $this->assertEquals($ans0Points, (int) $scoreAfterHide->score);
    }

    /**
     * Requirement 5: Manual +5/-5 scoring is no longer available in Growth 100 UI,
     * and redundant team scoreboard is removed from team award cards.
     */
    public function test_manual_scoring_and_redundant_scoreboard_removed_from_growth_100(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/growth-100');
        $res->assertOk();

        // Must NOT contain arbitrary manual score adjustment buttons
        $res->assertDontSee('btn-score-action');
        $res->assertDontSee('+5');
        $res->assertDontSee('−5');

        // Must NOT contain redundant team score badge in team award card
        $res->assertDontSee('growth-team-score');

        // Universal Game System topbar scoreboard must remain the single source of truth
        $res->assertSee('scoreboard-team');
        $res->assertSee('id="modal-team-config"', false);

        // 0/100 Progress UI elements must be present
        $res->assertSee('id="round-progress-percent"', false);
        $res->assertSee('id="round-progress-bar"', false);
        $res->assertSee('/ 100 PTS');

        // Strikes reset must be present
        $res->assertSee('id="btn-reset-crosses"', false);
        $res->assertSee('Reset Strikes');
    }

    /**
     * Requirement 6: Arbitrary client-provided score cannot bypass revealed-answer scoring.
     */
    public function test_arbitrary_client_score_cannot_bypass_revealed_answers(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::where('game_id', $game2->id)->first();
        $team = Team::where('game_id', $game2->id)->first();

        // Set revealed to empty (sum = 0)
        GameState::updateOrCreate(
            ['game_id' => $game2->id],
            ['state_data' => ['current_round' => 0, 'revealed' => [(string) $round->id => []], 'crosses' => []]]
        );

        // Attempting to bypass revealed scoring by passing arbitrary points_override = 85
        $res = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $round->id,
            'team_id' => $team->id,
            'points_override' => 85,
        ]);

        $res->assertStatus(422);
        $res->assertJsonPath('success', false);
        $res->assertJsonPath('error', 'Arbitrary scores cannot bypass revealed survey answer scoring in Growth 100.');
    }

    /**
     * Requirement 7: Wrong-answer/cross state can be activated and reset without changing score.
     */
    public function test_wrong_answer_cross_can_be_activated_and_reset_without_changing_score(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $team = Team::where('game_id', $game2->id)->first();

        $initialScore = GameScore::where('game_id', $game2->id)->where('team_id', $team->id)->value('score') ?? 0;

        // Activate strike 2
        $res1 = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'crosses' => 2,
        ]);
        $res1->assertOk();

        $scoreAfterCross = GameScore::where('game_id', $game2->id)->where('team_id', $team->id)->value('score') ?? 0;
        $this->assertEquals($initialScore, $scoreAfterCross, 'Setting strikes must not alter team scores.');

        // Reset strikes
        $res2 = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'crosses' => 0,
        ]);
        $res2->assertOk();

        $scoreAfterReset = GameScore::where('game_id', $game2->id)->where('team_id', $team->id)->value('score') ?? 0;
        $this->assertEquals($initialScore, $scoreAfterReset, 'Resetting strikes must not alter team scores.');
    }

    /**
     * Requirement 8: Resetting wrong-answer/cross state does not reveal answers.
     */
    public function test_resetting_crosses_does_not_reveal_answers(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::where('game_id', $game2->id)->first();

        // Setup state with 1 revealed answer and 2 strikes
        GameState::updateOrCreate(
            ['game_id' => $game2->id],
            ['state_data' => ['current_round' => 0, 'revealed' => [(string) $round->id => [0]], 'crosses' => [(string) $round->id => 2]]]
        );

        // Reset strikes to 0
        $res = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'crosses' => 0,
        ]);
        $res->assertOk();

        $revealed = $res->json('state.revealed.' . $round->id);
        $this->assertEquals([0], $revealed, 'Resetting crosses must not touch or reveal other answers.');
    }

    /**
     * Requirement 9: Selected Growth 100 round state/highlight works in host tools.
     */
    public function test_selected_growth_100_round_highlight(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/growth-100');
        $res->assertOk();

        // Verify host editor rounds buttons container exists
        $res->assertSee('id="growth-round-buttons"', false);
        $res->assertSee('id="modal-growth-editor"', false);

        // Verify CSS contains the .active.is-selected earth-tone style rules for Growth 100 round buttons
        $cssContent = file_get_contents(resource_path('css/byc-growth/modals.css'));
        $this->assertStringContainsString('#growth-round-buttons button.is-selected', $cssContent);
        $this->assertStringContainsString('border-left: 4px solid var(--forest)', $cssContent);
    }

    /**
     * Requirement 10: Growth 100 batch save still works.
     */
    public function test_growth_100_batch_save_still_works(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'rounds' => [
                [
                    'question' => 'Batch Test Survey 1',
                    'answers' => [
                        ['text' => 'Ans 1A', 'score' => 60],
                        ['text' => 'Ans 1B', 'score' => 40],
                    ],
                ],
                [
                    'question' => 'Batch Test Survey 2',
                    'answers' => [
                        ['text' => 'Ans 2A', 'score' => 50],
                        ['text' => 'Ans 2B', 'score' => 30],
                        ['text' => 'Ans 2C', 'score' => 20],
                    ],
                ],
            ],
        ];

        $res = $this->postJson('/game/growth-100/batch', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $game2 = Game::where('code', 'game2')->first();
        $this->assertTrue(GameRound::where('game_id', $game2->id)->where('question', 'Batch Test Survey 1')->exists());
        $this->assertTrue(GameRound::where('game_id', $game2->id)->where('question', 'Batch Test Survey 2')->exists());
    }

    /**
     * Requirement 11: Growth 100 sorting still works (answers ordered by points descending).
     */
    public function test_growth_100_answers_sorted_descending(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->postJson('/game/growth-100/round', [
            'question' => 'Testing Sorted Answers',
            'answers' => [
                ['text' => 'Low Points', 'score' => 10],
                ['text' => 'High Points', 'score' => 50],
                ['text' => 'Mid Points', 'score' => 40],
            ],
        ]);
        $res->assertOk();

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::where('game_id', $game2->id)->where('question', 'Testing Sorted Answers')->latest('id')->first();
        $this->assertNotNull($round);

        $answers = $round->answers;
        $this->assertEquals(50, (int) $answers[0]->points);
        $this->assertEquals('High Points', $answers[0]->answer_text);
        $this->assertEquals(40, (int) $answers[1]->points);
        $this->assertEquals(10, (int) $answers[2]->points);
    }

    /**
     * Requirement 12: Universal dynamic team configuration still works.
     */
    public function test_universal_dynamic_team_configuration_works(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->postJson('/game/teams/configure', [
            'game' => 'game2',
            'teams' => [
                ['name' => 'Team Alpha', 'color' => '#bd4c42'],
                ['name' => 'Team Bravo', 'color' => '#315e89'],
                ['name' => 'Team Charlie', 'color' => '#284e3b'],
            ],
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);
        $this->assertCount(3, $res->json('teams'));

        $game2 = Game::where('code', 'game2')->first();
        $this->assertEquals(3, Team::where('game_id', $game2->id)->where('is_active', true)->count());
    }

    /**
     * Requirement 13: Team isolation between Guess Me and Growth 100 still works.
     */
    public function test_team_isolation_between_guess_me_and_growth_100(): void
    {
        $this->actingAs($this->adminUser);

        $game1 = Game::where('code', 'game1')->first();
        $game2 = Game::where('code', 'game2')->first();

        $game1InitialTeamCount = Team::where('game_id', $game1->id)->where('is_active', true)->count();

        // Configure 4 teams for Growth 100
        $this->postJson('/game/teams/configure', [
            'game' => 'game2',
            'teams' => [
                ['name' => 'G2 Team 1', 'color' => '#bd4c42'],
                ['name' => 'G2 Team 2', 'color' => '#315e89'],
                ['name' => 'G2 Team 3', 'color' => '#284e3b'],
                ['name' => 'G2 Team 4', 'color' => '#e7bd52'],
            ],
        ]);

        $this->assertEquals(4, Team::where('game_id', $game2->id)->where('is_active', true)->count());
        $this->assertEquals($game1InitialTeamCount, Team::where('game_id', $game1->id)->where('is_active', true)->count(), 'Guess Me teams must remain isolated.');
    }

    /**
     * Requirement 14: Universal round-point assignment still works (assign, transfer, unassign).
     */
    public function test_universal_round_point_assignment_workflow(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $teams = Team::where('game_id', $game2->id)->take(2)->get();
        $teamA = $teams[0];
        $teamB = $teams[1];

        // Create round with 70 and 30
        $this->postJson('/game/growth-100/round', [
            'question' => 'Workflow Assignment Survey',
            'answers' => [
                ['text' => 'Part 1', 'score' => 70],
                ['text' => 'Part 2', 'score' => 30],
            ],
        ]);

        $round = GameRound::where('game_id', $game2->id)->where('question', 'Workflow Assignment Survey')->latest('id')->first();

        // Reveal answer 0 (70 pts)
        $currentState = GameState::where('game_id', $game2->id)->first();
        $data = $currentState ? $currentState->state_data : [];
        $data['revealed'] = $data['revealed'] ?? [];
        $data['revealed'][(string) $round->id] = [0];

        GameState::updateOrCreate(
            ['game_id' => $game2->id],
            ['state_data' => $data]
        );

        // Reset scores
        GameScore::updateOrCreate(['game_id' => $game2->id, 'team_id' => $teamA->id], ['score' => 0]);
        GameScore::updateOrCreate(['game_id' => $game2->id, 'team_id' => $teamB->id], ['score' => 0]);

        // 1. Assign 70 points to Team A
        $resA = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $round->id,
            'team_id' => $teamA->id,
        ]);
        $resA->assertOk();
        $this->assertEquals(70, GameScore::where('game_id', $game2->id)->where('team_id', $teamA->id)->value('score'));
        $this->assertEquals(0, GameScore::where('game_id', $game2->id)->where('team_id', $teamB->id)->value('score'));

        // 2. Transfer points to Team B
        $resB = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $round->id,
            'team_id' => $teamB->id,
        ]);
        $resB->assertOk();
        $this->assertEquals(0, GameScore::where('game_id', $game2->id)->where('team_id', $teamA->id)->value('score'));
        $this->assertEquals(70, GameScore::where('game_id', $game2->id)->where('team_id', $teamB->id)->value('score'));

        // 3. Unassign points by sending same team ID
        $resUnassign = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $round->id,
            'team_id' => $teamB->id,
        ]);
        $resUnassign->assertOk();
        $this->assertNull($resUnassign->json('awarded_team_id'));
        $this->assertEquals(0, GameScore::where('game_id', $game2->id)->where('team_id', $teamB->id)->value('score'));
    }

    /**
     * Requirement 15: Existing authentication/authorization behavior still works.
     */
    public function test_public_user_cannot_access_growth_100_host_mutations(): void
    {
        // Public user (unauthenticated)
        $resRound = $this->postJson('/game/growth-100/round', [
            'question' => 'Unauthorized Survey',
            'answers' => [
                ['text' => 'Ans 1', 'score' => 50],
                ['text' => 'Ans 2', 'score' => 50],
            ],
        ]);
        $resRound->assertStatus(401);

        $resBatch = $this->postJson('/game/growth-100/batch', [
            'rounds' => [],
        ]);
        $resBatch->assertStatus(401);

        $resAssign = $this->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => 1,
            'team_id' => 1,
        ]);
        $resAssign->assertStatus(401);
    }

    /**
     * Requirement 16: Existing Guess Me functionality still passes.
     */
    public function test_existing_guess_me_functionality_remains_intact(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/guess-me');
        $res->assertOk();
        $res->assertSee('Game 01');
        $res->assertSee('<h1>Guess Me!</h1>', false);
        $res->assertSee('id="modal-team-config"', false);

        // Clue and answer uppercase normalization
        $saveRes = $this->postJson('/game/guess-me/round', [
            'correct_answer' => 'strawberry',
            'clue' => 's _ r _ w _ e _ r _',
            'score' => 30,
        ]);
        $saveRes->assertOk();

        $game1 = Game::where('code', 'game1')->first();
        $round = GameRound::where('game_id', $game1->id)->where('correct_answer', 'STRAWBERRY')->first();
        $this->assertNotNull($round);
        $this->assertEquals('S _ R _ W _ E _ R _', $round->clue);
    }

    /**
     * Requirement 17: Growth 100 answers return to hidden on page refresh, exit, or navigation.
     */
    public function test_growth_100_answers_return_to_hidden_on_page_refresh_or_exit(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::where('game_id', $game2->id)->first();
        $this->assertNotNull($round);

        // 1. Reveal answer 0 for this round during gameplay
        $revealRes = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'answer_index' => 0,
        ]);
        $revealRes->assertOk();
        $revealedInState = $revealRes->json('state.revealed.' . $round->id);
        $this->assertContains(0, $revealedInState);

        // 2. When user/admin refreshes or visits /growth-100, answers must return to all hidden
        $refreshRes = $this->get('/growth-100');
        $refreshRes->assertOk();

        // Must NOT render any answer tiles with 'open' class
        $refreshRes->assertDontSee('answer-tile open', false);

        // All answers must display default 'Click to reveal'
        $refreshRes->assertSee('Click to reveal');

        // Round revealed points must be 0
        $refreshRes->assertSee('id="round-revealed-points">0<', false);
        $refreshRes->assertSee('id="round-progress-percent">0%<', false);

        // State in database must be reset
        $dbState = GameState::where('game_id', $game2->id)->first();
        $this->assertEmpty($dbState->state_data['revealed'] ?? []);

        // 3. Explicit reset endpoint (used on pagehide/exit) also resets revealed state
        $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'answer_index' => 0,
        ]);
        $resetEndpointRes = $this->postJson('/game/growth-100/reset-revealed');
        $resetEndpointRes->assertOk();
        $resetEndpointRes->assertJsonPath('success', true);

        $dbStateAfter = GameState::where('game_id', $game2->id)->first();
        $this->assertEmpty($dbStateAfter->state_data['revealed'] ?? []);
    }
}
