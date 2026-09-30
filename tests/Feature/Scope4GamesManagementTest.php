<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\MediaFile;
use App\Models\Team;
use App\Models\User;
use App\Services\GameStorageService;
use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Scope S4 — Games Management
 *
 * Covers:
 * - Access Control (Admin allowed, Guest redirected, Normal user rejected)
 * - Game 1 — Guess Me! Management (View, Add, Edit, Delete, Clue validation, Nonexistent ID, Media Handling)
 * - Game 2 — BYC GROWTH 100 Management (View, Add, Edit, Delete, Answers, Exactly 100 Validation)
 * - Security (IDOR, Cross-game protection, Mass assignment, Traversal)
 * - Gameplay Regression (Guess Me scoring, Growth 100 scoring, Strikes, Reveal, Reset)
 * - S0-S3 Regressions (/admin-ganteng, logout redirect, username display, icons, Homepage, Members, Activities)
 */
class Scope4GamesManagementTest extends TestCase
{
    protected GameStorageService $storage;
    protected User $adminUser;
    protected User $normalUser;
    protected array $createdRoundIds = [];
    protected array $createdMediaIds = [];
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(GameStorageService::class);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s4@bycgrowth.org'],
            [
                'name' => 'Admin S4',
                'username' => 'admin_s4',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->normalUser = User::firstOrCreate(
            ['email' => 'user_s4@bycgrowth.org'],
            [
                'name' => 'User S4',
                'username' => 'user_s4',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        // Clean up rounds created in tests
        foreach ($this->createdRoundIds as $id) {
            $round = GameRound::find($id);
            if ($round) {
                if ($round->media_file_id) {
                    $media = MediaFile::find($round->media_file_id);
                    if ($media) {
                        $fullPath = public_path($media->file_path);
                        if (File::exists($fullPath)) {
                            File::delete($fullPath);
                        }
                        $media->delete();
                    }
                }
                $round->answers()->delete();
                $round->delete();
            }
        }

        // Clean up files and media
        foreach ($this->createdMediaIds as $id) {
            $media = MediaFile::find($id);
            if ($media) {
                $fullPath = public_path($media->file_path);
                if (File::exists($fullPath)) {
                    File::delete($fullPath);
                }
                $media->delete();
            }
        }

        foreach ($this->createdFiles as $filePath) {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        parent::tearDown();
    }

    // =========================================================================
    // 1. ACCESS CONTROL TESTS
    // =========================================================================

    public function test_admin_can_access_games_management(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/games');
        $res->assertOk();
        $res->assertSee('Games Management');
        $res->assertSee('Guess Me!');
        $res->assertSee('BYC GROWTH 100');
        $res->assertSee('Universal Game System');
    }

    public function test_guest_cannot_access_games_management(): void
    {
        Auth::logout();

        $res = $this->get('/admin/games');
        $this->assertTrue(in_array($res->status(), [302, 401]));
        if ($res->status() === 302) {
            $this->assertTrue($res->isRedirect(route('admin.login')) || $res->isRedirect(route('admin.ganteng')));
        }
    }

    public function test_normal_user_cannot_access_or_mutate_game_data(): void
    {
        $this->actingAs($this->normalUser);

        // Access to admin portal forbidden
        $this->get('/admin/games')->assertStatus(403);

        // Mutations forbidden
        $resGuess = $this->postJson('/admin/games/guess-me/round', [
            'correct_answer' => 'TESTING',
            'clue' => 'T _ S _ I _ G',
            'score' => 20,
        ]);
        $this->assertTrue(in_array($resGuess->status(), [403, 302]));

        $resGrowth = $this->postJson('/admin/games/growth-100/round', [
            'question' => 'Hacker survey?',
            'answers' => [
                ['text' => 'Ans A', 'score' => 60],
                ['text' => 'Ans B', 'score' => 40],
            ],
        ]);
        $this->assertTrue(in_array($resGrowth->status(), [403, 302]));
    }

    // =========================================================================
    // 2. GUESS ME! MANAGEMENT TESTS
    // =========================================================================

    public function test_admin_can_view_guess_me_rounds(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/games?tab=guess-me');
        $res->assertOk();
        $res->assertSee('Guess Me! Rounds');
        $res->assertSee('+ Add Round');
        $res->assertSee('Clue (Letter Slots)');
        $res->assertSee('Correct Answer');
    }

    public function test_admin_can_create_guess_me_round(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->postJson('/admin/games/guess-me/round', [
            'correct_answer' => 'DOLPHIN',
            'clue' => 'D _ L _ H _ N',
            'score' => 25,
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);

        $game1 = Game::where('code', 'game1')->first();
        $round = GameRound::where('game_id', $game1->id)->where('correct_answer', 'DOLPHIN')->first();

        $this->assertNotNull($round);
        $this->createdRoundIds[] = $round->id;
        $this->assertEquals('D _ L _ H _ N', $round->clue);
        $this->assertEquals(25, $round->score);
    }

    public function test_admin_can_edit_guess_me_round(): void
    {
        $this->actingAs($this->adminUser);

        $game1 = Game::where('code', 'game1')->first();
        $round = GameRound::create([
            'game_id' => $game1->id,
            'round_number' => 99,
            'correct_answer' => 'PENGUIN',
            'clue' => 'P _ N _ U _ N',
            'score' => 20,
        ]);
        $this->createdRoundIds[] = $round->id;

        $res = $this->postJson('/admin/games/guess-me/round', [
            'id' => $round->id,
            'correct_answer' => 'ELEPHANT',
            'clue' => 'E _ E _ H _ N T',
            'score' => 30,
        ]);

        $res->assertOk();
        $res->assertJsonPath('success', true);

        $round->refresh();
        $this->assertEquals('ELEPHANT', $round->correct_answer);
        $this->assertEquals('E _ E _ H _ N T', $round->clue);
        $this->assertEquals(30, $round->score);
    }

    public function test_admin_can_delete_guess_me_round(): void
    {
        $this->actingAs($this->adminUser);

        $game1 = Game::where('code', 'game1')->first();

        // Ensure at least 2 rounds exist so minimum 1 round rule allows deletion
        $round1 = GameRound::firstOrCreate(
            ['game_id' => $game1->id, 'correct_answer' => 'KEEPER'],
            ['round_number' => 1, 'clue' => 'K _ E _ E R', 'score' => 20]
        );
        $roundToDelete = GameRound::create([
            'game_id' => $game1->id,
            'round_number' => 888,
            'correct_answer' => 'DELETEGUESS',
            'clue' => 'D _ L _ T _ G _ _ S S',
            'score' => 20,
        ]);

        $res = $this->deleteJson('/admin/games/guess-me/round/' . $roundToDelete->id);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $this->assertNull(GameRound::find($roundToDelete->id));
    }

    public function test_invalid_guess_me_round_data_rejected(): void
    {
        $this->actingAs($this->adminUser);

        // Clue length does not match answer length
        $resMismatch = $this->postJson('/admin/games/guess-me/round', [
            'correct_answer' => 'TIGER',
            'clue' => 'T _ G', // 3 letters vs 5
            'score' => 20,
        ]);
        $resMismatch->assertStatus(422);

        // Clue letter mismatch
        $resLetterMismatch = $this->postJson('/admin/games/guess-me/round', [
            'correct_answer' => 'TIGER',
            'clue' => 'T _ X _ R', // X does not match G
            'score' => 20,
        ]);
        $resLetterMismatch->assertStatus(422);

        // Empty clue
        $resEmptyClue = $this->postJson('/admin/games/guess-me/round', [
            'correct_answer' => 'TIGER',
            'clue' => '',
            'score' => 20,
        ]);
        $resEmptyClue->assertStatus(422);
    }

    public function test_nonexistent_guess_me_round_id_handled_safely(): void
    {
        $this->actingAs($this->adminUser);

        // Edit nonexistent ID -> 404
        $resEdit = $this->postJson('/admin/games/guess-me/round', [
            'id' => 999999,
            'correct_answer' => 'NONEXIST',
            'clue' => 'N _ N _ X _ S T',
            'score' => 20,
        ]);
        $resEdit->assertStatus(404);

        // Delete nonexistent ID -> 404
        $resDelete = $this->deleteJson('/admin/games/guess-me/round/999999');
        $resDelete->assertStatus(404);
    }

    public function test_guess_me_media_handling_works(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create round with image upload via MediaUploadService
        $fakeImage = UploadedFile::fake()->image('guess_clue.jpg', 600, 600);
        $res = $this->post('/admin/games/guess-me/round', [
            'correct_answer' => 'SUNFLOWER',
            'clue' => 'S _ N _ L _ W _ R',
            'score' => 25,
            'image' => $fakeImage,
        ]);
        $res->assertRedirect();

        $game1 = Game::where('code', 'game1')->first();
        $round = GameRound::with('mediaFile')->where('game_id', $game1->id)->where('correct_answer', 'SUNFLOWER')->first();

        $this->assertNotNull($round);
        $this->createdRoundIds[] = $round->id;
        $this->assertNotNull($round->mediaFile);
        $this->assertEquals('guess_clue.jpg', $round->mediaFile->original_name);

        $initialMediaId = $round->media_file_id;
        $initialFilePath = public_path($round->mediaFile->file_path);
        $this->createdFiles[] = $initialFilePath;

        // 2. Replace image -> old MediaFile cleaned up safely
        $replacementImage = UploadedFile::fake()->image('guess_replacement.png', 600, 600);
        $resReplace = $this->post('/admin/games/guess-me/round', [
            'id' => $round->id,
            'correct_answer' => 'SUNFLOWER',
            'clue' => 'S _ N _ L _ W _ R',
            'score' => 30,
            'image' => $replacementImage,
        ]);
        $resReplace->assertRedirect();

        $round->refresh();
        $this->assertNotEquals($initialMediaId, $round->media_file_id);
        $this->assertNull(MediaFile::find($initialMediaId), 'Old MediaFile must be deleted upon image replacement.');
        $this->assertFalse(File::exists($initialFilePath), 'Old file on disk must be cleaned up.');

        $newMedia = MediaFile::find($round->media_file_id);
        $this->assertNotNull($newMedia);
        $this->createdFiles[] = public_path($newMedia->file_path);

        // 3. Delete round -> associated MediaFile cleaned up safely
        $mediaToDelete = $round->media_file_id;
        $filePathToDelete = public_path($newMedia->file_path);

        $this->deleteJson('/admin/games/guess-me/round/' . $round->id)->assertOk();
        $this->assertNull(MediaFile::find($mediaToDelete), 'MediaFile must be cleaned up upon round deletion.');
        $this->assertFalse(File::exists($filePathToDelete), 'Uploaded file on disk must be cleaned up.');
    }

    public function test_guess_me_rejects_dangerous_file_extensions(): void
    {
        $this->actingAs($this->adminUser);

        // Uploading a PHP script disguised as an image
        $phpFile = UploadedFile::fake()->create('malicious.php', 100, 'text/x-php');

        $res = $this->post('/admin/games/guess-me/round', [
            'correct_answer' => 'SECURITY',
            'clue' => 'S _ C _ R _ T Y',
            'score' => 20,
            'image' => $phpFile,
        ]);

        $res->assertSessionHasErrors('image');
    }

    // =========================================================================
    // 3. BYC GROWTH 100 MANAGEMENT TESTS
    // =========================================================================

    public function test_admin_can_view_growth_100_questions(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/games?tab=growth-100');
        $res->assertOk();
        $res->assertSee('BYC GROWTH 100 Questions');
        $res->assertSee('+ Add Question');
        $res->assertSee('Survey Answers');
    }

    public function test_admin_can_create_growth_100_question(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'question' => 'What are the top fellowship activities in BYC?',
            'answers' => [
                ['text' => 'Small Group Sharing', 'score' => 40],
                ['text' => 'Outdoor Sports & Games', 'score' => 30],
                ['text' => 'Cooking & Eating Together', 'score' => 20],
                ['text' => 'Music & Praise Session', 'score' => 10],
            ],
        ];

        $res = $this->postJson('/admin/games/growth-100/round', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::with('answers')
            ->where('game_id', $game2->id)
            ->where('question', 'What are the top fellowship activities in BYC?')
            ->first();

        $this->assertNotNull($round);
        $this->createdRoundIds[] = $round->id;
        $this->assertCount(4, $round->answers);
        $this->assertEquals(100, $round->answers->sum('points'));
    }

    public function test_admin_can_edit_growth_100_question(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::create([
            'game_id' => $game2->id,
            'round_number' => 88,
            'question' => 'Original Question?',
            'score' => 100,
        ]);
        $this->createdRoundIds[] = $round->id;

        GameAnswer::create(['game_round_id' => $round->id, 'answer_text' => 'Ans 1', 'points' => 60, 'sort_order' => 0]);
        GameAnswer::create(['game_round_id' => $round->id, 'answer_text' => 'Ans 2', 'points' => 40, 'sort_order' => 1]);

        // Edit question text and rebalance answers to 50 + 50 = 100
        $res = $this->postJson('/admin/games/growth-100/round', [
            'id' => $round->id,
            'question' => 'Updated Survey Question Title?',
            'answers' => [
                ['text' => 'Top Answer A', 'score' => 50],
                ['text' => 'Top Answer B', 'score' => 50],
            ],
        ]);

        $res->assertOk();
        $round->refresh();
        $this->assertEquals('Updated Survey Question Title?', $round->question);
        $this->assertCount(2, $round->answers);
        $this->assertEquals(100, $round->answers->sum('points'));
    }

    public function test_admin_can_delete_growth_100_question(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();

        // Ensure at least 2 rounds exist so deletion is allowed
        GameRound::firstOrCreate(
            ['game_id' => $game2->id, 'question' => 'Keeper Survey Question?'],
            ['round_number' => 1, 'score' => 100]
        );

        $roundToDelete = GameRound::create([
            'game_id' => $game2->id,
            'round_number' => 999,
            'question' => 'Question to be deleted?',
            'score' => 100,
        ]);
        $ans1 = GameAnswer::create(['game_round_id' => $roundToDelete->id, 'answer_text' => 'Ans A', 'points' => 70, 'sort_order' => 0]);
        $ans2 = GameAnswer::create(['game_round_id' => $roundToDelete->id, 'answer_text' => 'Ans B', 'points' => 30, 'sort_order' => 1]);

        $res = $this->deleteJson('/admin/games/growth-100/round/' . $roundToDelete->id);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        // Associated answers must NOT become orphans
        $this->assertNull(GameRound::find($roundToDelete->id));
        $this->assertNull(GameAnswer::find($ans1->id));
        $this->assertNull(GameAnswer::find($ans2->id));
    }

    public function test_growth_100_invalid_score_total_rejected_under_100(): void
    {
        $this->actingAs($this->adminUser);

        // 40 + 30 + 20 + 5 = 95 -> rejected!
        $payload = [
            'question' => 'Invalid Under 100 Question?',
            'answers' => [
                ['text' => 'Ans 1', 'score' => 40],
                ['text' => 'Ans 2', 'score' => 30],
                ['text' => 'Ans 3', 'score' => 20],
                ['text' => 'Ans 4', 'score' => 5], // Sum: 95
            ],
        ];

        $res = $this->postJson('/admin/games/growth-100/round', $payload);
        $res->assertStatus(422);
        $res->assertJsonPath('success', false);
        $this->assertStringContainsString('Current sum is 95', $res->json('error'));
    }

    public function test_growth_100_invalid_score_total_rejected_over_100(): void
    {
        $this->actingAs($this->adminUser);

        // 40 + 30 + 20 + 20 = 110 -> rejected!
        $payload = [
            'question' => 'Invalid Over 100 Question?',
            'answers' => [
                ['text' => 'Ans 1', 'score' => 40],
                ['text' => 'Ans 2', 'score' => 30],
                ['text' => 'Ans 3', 'score' => 20],
                ['text' => 'Ans 4', 'score' => 20], // Sum: 110
            ],
        ];

        $res = $this->postJson('/admin/games/growth-100/round', $payload);
        $res->assertStatus(422);
        $res->assertJsonPath('success', false);
        $this->assertStringContainsString('Current sum is 110', $res->json('error'));
    }

    public function test_growth_100_valid_total_exactly_100_accepted(): void
    {
        $this->actingAs($this->adminUser);

        // 40 + 30 + 20 + 10 = 100 -> valid!
        $payload = [
            'question' => 'Exactly 100 Question?',
            'answers' => [
                ['text' => 'Ans 1', 'score' => 40],
                ['text' => 'Ans 2', 'score' => 30],
                ['text' => 'Ans 3', 'score' => 20],
                ['text' => 'Ans 4', 'score' => 10], // Sum: 100
            ],
        ];

        $res = $this->postJson('/admin/games/growth-100/round', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);
    }

    public function test_growth_100_invalid_nested_data_rejected(): void
    {
        $this->actingAs($this->adminUser);

        // Empty answers array
        $this->postJson('/admin/games/growth-100/round', [
            'question' => 'No answers question?',
            'answers' => [],
        ])->assertStatus(422);

        // Empty answer text
        $this->postJson('/admin/games/growth-100/round', [
            'question' => 'Blank answer text question?',
            'answers' => [
                ['text' => '', 'score' => 100],
            ],
        ])->assertStatus(422);

        // Zero or negative score
        $this->postJson('/admin/games/growth-100/round', [
            'question' => 'Zero score question?',
            'answers' => [
                ['text' => 'Choice 1', 'score' => 100],
                ['text' => 'Choice 2', 'score' => 0],
            ],
        ])->assertStatus(422);
    }

    public function test_admin_can_add_edit_and_delete_individual_answers(): void
    {
        $this->actingAs($this->adminUser);

        $game2 = Game::where('code', 'game2')->first();
        $round = GameRound::create([
            'game_id' => $game2->id,
            'round_number' => 77,
            'question' => 'Survey for Answer CRUD?',
            'score' => 100,
        ]);
        $this->createdRoundIds[] = $round->id;

        $ans1 = GameAnswer::create(['game_round_id' => $round->id, 'answer_text' => 'Ans 1', 'points' => 60, 'sort_order' => 0]);
        $ans2 = GameAnswer::create(['game_round_id' => $round->id, 'answer_text' => 'Ans 2', 'points' => 40, 'sort_order' => 1]);

        // 1. Edit answer text (score unchanged, sum remains 100)
        $resEdit = $this->postJson('/admin/games/growth-100/answers/' . $ans1->id, [
            'text' => 'Updated Ans 1 Text',
        ]);
        $resEdit->assertOk();
        $this->assertEquals('Updated Ans 1 Text', $ans1->fresh()->answer_text);

        // 2. Add new answer choice deducting from ans1 so sum remains 100 (Ans 1: 50, Ans 2: 40, Ans 3: 10)
        $resAdd = $this->postJson('/admin/games/growth-100/round/' . $round->id . '/answers', [
            'text' => 'Ans 3',
            'score' => 10,
            'adjust_from_answer_id' => $ans1->id,
        ]);
        $resAdd->assertOk();
        $this->assertEquals(100, $round->answers()->sum('points'));

        // 3. Delete ans2 while transferring its 40 points to ans1 to keep sum == 100
        $resDelete = $this->deleteJson('/admin/games/growth-100/answers/' . $ans2->id, [
            'transfer_to_id' => $ans1->id,
        ]);
        $resDelete->assertOk();
        $this->assertNull(GameAnswer::find($ans2->id));
        $this->assertEquals(100, $round->answers()->sum('points'));
    }

    // =========================================================================
    // 4. SECURITY & IDOR TESTS
    // =========================================================================

    public function test_idor_cross_game_round_mutation_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $game1 = Game::where('code', 'game1')->first();
        $game2 = Game::where('code', 'game2')->first();

        $guessRound = GameRound::create([
            'game_id' => $game1->id,
            'round_number' => 55,
            'correct_answer' => 'GUESSIDOR',
            'clue' => 'G _ _ S S _ D _ R',
            'score' => 20,
        ]);
        $growthRound = GameRound::create([
            'game_id' => $game2->id,
            'round_number' => 56,
            'question' => 'GROWTH IDOR SURVEY?',
            'score' => 100,
        ]);
        $this->createdRoundIds[] = $guessRound->id;
        $this->createdRoundIds[] = $growthRound->id;

        // Attempting to mutate Game 2 round via Game 1 endpoint -> 403 Forbidden
        $resCross1 = $this->postJson('/admin/games/guess-me/round', [
            'id' => $growthRound->id,
            'correct_answer' => 'HACKED',
            'clue' => 'H _ C _ E D',
            'score' => 20,
        ]);
        $this->assertEquals(403, $resCross1->status());

        // Attempting to delete Game 2 round via Game 1 endpoint -> 403 Forbidden
        $resCrossDelete1 = $this->deleteJson('/admin/games/guess-me/round/' . $growthRound->id);
        $this->assertEquals(403, $resCrossDelete1->status());

        // Attempting to mutate Game 1 round via Game 2 endpoint -> 403 Forbidden
        $resCross2 = $this->postJson('/admin/games/growth-100/round', [
            'id' => $guessRound->id,
            'question' => 'HACKED QUESTION?',
            'answers' => [['text' => 'Ans', 'score' => 100]],
        ]);
        $this->assertEquals(403, $resCross2->status());

        // Attempting to delete Game 1 round via Game 2 endpoint -> 403 Forbidden
        $resCrossDelete2 = $this->deleteJson('/admin/games/growth-100/round/' . $guessRound->id);
        $this->assertEquals(403, $resCrossDelete2->status());
    }

    public function test_path_traversal_on_game_image_endpoint_rejected(): void
    {
        $this->get('/game/image/..%2F..%2F.env')->assertStatus(404);
        $this->get('/game/image/....//....//config.php')->assertStatus(404);
        $this->get('/game/image/some-folder/nonexistent.jpg')->assertStatus(404);
    }

    // =========================================================================
    // 5. GAMEPLAY REGRESSION TESTS
    // =========================================================================

    public function test_guess_me_scoring_navigation_and_reveal_behavior_intact(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Navigation & state update
        $resState = $this->postJson('/game/guess-me/state', [
            'round_index' => 0,
            'revealed' => true,
        ]);
        $resState->assertOk();
        $this->assertEquals(0, $resState->json('state.current_round'));

        // 2. Public view sees gameplay intact
        Auth::logout();
        $resPublic = $this->get('/guess-me');
        $resPublic->assertOk();
        $resPublic->assertSee('Guess Me!');
        $resPublic->assertSee('Round');
    }

    public function test_growth_100_scoring_strikes_and_reveal_intact(): void
    {
        $this->actingAs($this->adminUser);

        // Set strikes to 2
        $resStrike = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'crosses' => 2,
        ]);
        $resStrike->assertOk();

        // Reset strikes
        $resResetStrikes = $this->postJson('/game/growth-100/state', [
            'round_index' => 0,
            'crosses' => 0,
        ]);
        $resResetStrikes->assertOk();

        // Public gameplay accessible
        Auth::logout();
        $res = $this->get('/growth-100');
        $res->assertOk();
        $res->assertSee('BYC Growth');
    }

    public function test_dynamic_teams_configuration_and_scores_intact(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'game' => 'game1',
            'teams' => [
                ['name' => 'Eagles Team', 'color' => 'red'],
                ['name' => 'Lions Team', 'color' => 'blue'],
            ],
        ];

        $res = $this->postJson('/game/teams/configure', $payload);
        $res->assertOk();
        $res->assertJsonPath('success', true);

        // Score update
        $resScore = $this->postJson('/game/update-score', [
            'game' => 'game1',
            'team' => 'red',
            'amount' => 50,
        ]);
        $resScore->assertOk();

        // Final score screen
        $resFinal = $this->get('/final');
        $resFinal->assertOk();
        $resFinal->assertSee('Final Score');
    }

    // =========================================================================
    // 6. S0–S3 REGRESSION TESTS
    // =========================================================================

    public function test_s0_authentication_and_admin_ganteng_intact(): void
    {
        Auth::logout();

        $res = $this->get('/admin-ganteng');
        $res->assertOk();
        $res->assertSee('Admin Sign In');
    }

    public function test_s0_admin_logout_redirects_to_admin_ganteng(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->post('/admin/logout');
        $res->assertRedirect(route('admin.ganteng'));
    }

    public function test_s1_admin_shell_username_display_and_icons(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/dashboard');
        $res->assertOk();
        $res->assertSee('admin_s4');
        $res->assertSee('Dashboard');
        $res->assertSee('Roles / Accounts');
    }

    public function test_s2_homepage_management_accessible(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/homepage');
        $res->assertOk();
        $res->assertSee('Homepage Management');
    }

    public function test_s3_members_management_accessible(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/members');
        $res->assertOk();
        $res->assertSee('Members Management');
    }

    public function test_s3_activities_management_accessible(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/activities');
        $res->assertOk();
        $res->assertSee('Activities Management');
    }
}
