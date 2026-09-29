<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\Team;
use App\Models\User;
use App\Services\GameStorageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GuessMeRevisionTest extends TestCase
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
     * Requirement 1: Guess Me clue is persisted uppercase.
     */
    public function test_guess_me_clue_is_persisted_uppercase(): void
    {
        $this->actingAs($this->adminUser);

        // Send lowercase clue to single round save endpoint
        $res = $this->postJson('/game/guess-me/round', [
            'correct_answer' => 'ORANGE',
            'clue' => 'o _ a _ g e', // lowercase
            'score' => 25,
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);

        // Verify in MySQL
        $game1 = Game::where('code', 'game1')->first();
        $round = GameRound::where('game_id', $game1->id)->where('correct_answer', 'ORANGE')->latest('id')->first();

        $this->assertNotNull($round);
        $this->assertEquals('O _ A _ G E', $round->clue, 'Clue must be normalized and persisted uppercase in MySQL.');
    }

    /**
     * Requirement 2: Guess Me correct answer is persisted uppercase.
     */
    public function test_guess_me_correct_answer_is_persisted_uppercase(): void
    {
        $this->actingAs($this->adminUser);

        // Send lowercase correct_answer to single round save endpoint
        $res = $this->postJson('/game/guess-me/round', [
            'correct_answer' => 'watermelon', // lowercase
            'clue' => 'W _ T _ R _ E _ O _',
            'score' => 30,
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);

        // Verify in MySQL
        $game1 = Game::where('code', 'game1')->first();
        $round = GameRound::where('game_id', $game1->id)->where('clue', 'W _ T _ R _ E _ O _')->latest('id')->first();

        $this->assertNotNull($round);
        $this->assertEquals('WATERMELON', $round->correct_answer, 'Correct answer must be normalized and persisted uppercase in MySQL.');
    }

    /**
     * Requirement 3: Uppercase normalization also works for batch question saving.
     */
    public function test_uppercase_normalization_works_for_batch_question_saving(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'rounds' => [
                [
                    'correct_answer' => 'cherry', // lowercase
                    'clue' => 'c _ e _ r _',     // lowercase
                    'score' => 20,
                    'image' => 'BYC_Growth.jpg',
                ],
                [
                    'correct_answer' => 'mango',  // lowercase
                    'clue' => 'm _ n _ o',       // lowercase
                    'score' => 25,
                    'image' => 'BYC_Growth.jpg',
                ],
            ],
        ];

        $res = $this->postJson('/game/guess-me/batch', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        // Verify both rounds in MySQL
        $game1 = Game::where('code', 'game1')->first();
        $rounds = GameRound::where('game_id', $game1->id)->orderBy('round_number')->get();

        $this->assertCount(2, $rounds);
        $this->assertEquals('CHERRY', $rounds[0]->correct_answer);
        $this->assertEquals('C _ E _ R _', $rounds[0]->clue);
        $this->assertEquals('MANGO', $rounds[1]->correct_answer);
        $this->assertEquals('M _ N _ O', $rounds[1]->clue);
    }

    /**
     * Requirement 4: Round selection produces the expected selected/highlighted state where testable.
     */
    public function test_round_selection_produces_the_expected_selected_highlighted_state(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Ensure at least 3 rounds exist
        $this->postJson('/game/guess-me/batch', [
            'rounds' => [
                ['correct_answer' => 'ROUNDONE', 'clue' => 'R _ _ _ _ _ _ E', 'score' => 10],
                ['correct_answer' => 'ROUNDTWO', 'clue' => 'R _ _ _ _ _ _ O', 'score' => 20],
                ['correct_answer' => 'ROUNDTHR', 'clue' => 'R _ _ _ _ _ _ R', 'score' => 30],
            ],
        ])->assertOk();

        // 2. Select Round 2 (index 1) via state update
        $res = $this->postJson('/game/guess-me/state', [
            'round_index' => 1,
        ]);
        $res->assertOk();

        // 3. Render Guess Me page
        $page = $this->get('/guess-me');
        $page->assertOk();

        // Round indicator displays active round (Round 02 / 03)
        $page->assertSee('02');
        $page->assertSee('/ 03');

        // Active round content is rendered
        $page->assertSee('R _ _ _ _ _ _ O');
        $page->assertSee('20'); // round score badge
    }

    /**
     * Requirement 5: Guess Me host modal has only one close/cancel control where testable.
     */
    public function test_guess_me_host_modal_has_only_one_close_cancel_action(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/guess-me');
        $res->assertOk();

        $content = $res->getContent();

        // Extract the editor modal HTML
        $this->assertStringContainsString('id="modal-guess-editor"', $content);
        $modalStart = strpos($content, 'id="modal-guess-editor"');
        $modalEnd = strpos($content, '</section>', $modalStart);
        $modalHtml = substr($content, $modalStart, $modalEnd - $modalStart);

        // Must contain the dedicated X close button
        $this->assertStringContainsString('id="btn-close-guess-editor"', $modalHtml);
        $this->assertStringContainsString('aria-label="Close editor"', $modalHtml);

        // Must NOT contain redundant duplicate Cancel button
        $this->assertStringNotContainsString('id="btn-cancel-guess-editor"', $modalHtml);
        $this->assertStringNotContainsString('>Cancel<', $modalHtml);
    }

    /**
     * Requirement 6: Existing Guess Me functionality still passes.
     */
    public function test_existing_guess_me_functionality_still_passes(): void
    {
        $this->actingAs($this->adminUser);

        // Public user access
        Auth::logout();
        $this->get('/guess-me')->assertOk()->assertSee('Guess Me!');

        // Admin reveal answer toggling
        $this->actingAs($this->adminUser);
        $toggleRes = $this->postJson('/game/guess-me/state', [
            'round_index' => 0,
            'revealed' => true,
        ]);
        $toggleRes->assertOk();
    }

    /**
     * Requirement 7: Universal team configuration still works.
     */
    public function test_universal_team_configuration_still_works(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->postJson('/game/teams/configure', [
            'game' => 'game1',
            'teams' => [
                ['name' => 'Fellowship Gold', 'color' => '#e7bd52'],
                ['name' => 'Fellowship Forest', 'color' => '#284e3b'],
            ],
        ]);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $teams = $this->storage->getActiveTeams('game1');
        $this->assertEquals('Fellowship Gold', $teams[0]->name);
        $this->assertEquals('Fellowship Forest', $teams[1]->name);
    }

    /**
     * Requirement 8: Team isolation between Guess Me and BYC GROWTH 100 still works.
     */
    public function test_team_isolation_between_guess_me_and_growth_100_still_works(): void
    {
        $this->actingAs($this->adminUser);

        // Establish Growth 100 teams
        $this->postJson('/game/teams/configure', [
            'game' => 'game2',
            'teams' => [
                ['name' => 'G100 Phoenix', 'color' => '#bd4c42'],
                ['name' => 'G100 Griffin', 'color' => '#315e89'],
            ],
        ])->assertOk();

        $beforeG2 = $this->storage->getActiveTeams('game2')->pluck('name')->toArray();

        // Reconfigure Guess Me teams
        $this->postJson('/game/teams/configure', [
            'game' => 'game1',
            'teams' => [
                ['name' => 'GM Dragon', 'color' => '#bd4c42'],
                ['name' => 'GM Tiger', 'color' => '#315e89'],
            ],
        ])->assertOk();

        // Growth 100 teams must be completely untouched
        $afterG2 = $this->storage->getActiveTeams('game2')->pluck('name')->toArray();
        $this->assertEquals($beforeG2, $afterG2);

        // Cross-game point award is rejected
        $g1Round = $this->storage->getGuessMeRounds()[0];
        $g2Team = $this->storage->getActiveTeams('game2')->first();

        $crossRes = $this->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $g1Round['id'],
            'team_id' => $g2Team->id,
        ]);
        $crossRes->assertStatus(422);
    }

    /**
     * Requirement 9: Existing authentication/authorization behavior still passes.
     */
    public function test_existing_authentication_authorization_behavior_still_passes(): void
    {
        Auth::logout();

        // Guest cannot save round
        $res1 = $this->postJson('/game/guess-me/round', [
            'correct_answer' => 'TEST',
            'clue' => 'T _ S T',
            'score' => 20,
        ]);
        $this->assertTrue(in_array($res1->status(), [401, 302, 403]));

        // Guest cannot batch save
        $res2 = $this->postJson('/game/guess-me/batch', [
            'rounds' => [
                ['correct_answer' => 'TEST', 'clue' => 'T _ S T', 'score' => 20],
            ],
        ]);
        $this->assertTrue(in_array($res2->status(), [401, 302, 403]));
    }
}
