<?php

namespace Tests\Feature;

use App\Services\GameStorageService;
use Tests\TestCase;

class GameTest extends TestCase
{
    protected GameStorageService $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(GameStorageService::class);
    }

    /**
     * Test Homepage and Game Center render properly under Scope 1 architecture
     */
    public function test_homepage_renders_and_calculates_final_scores(): void
    {
        $this->storage->resetGame();

        // Homepage as Main Information Hub
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertViewIs('welcome');
        $response->assertSee('BYC GROWTH');
        $response->assertSee('For we walk by faith, not by sight');
        $response->assertSee('What do you want to go through?');
        $response->assertSee('Game Center');

        // Game Center Hub
        $gcRes = $this->get('/game-center');
        $gcRes->assertStatus(200);
        $gcRes->assertViewIs('pages.game-center');
        $gcRes->assertSee('BYC Game Center');
        $gcRes->assertSee('Team Scoreboard');
    }

    /**
     * Test Clue validation logic:
     * - MAKAN + "_ A _ A _" => valid
     * - MAKAN + "_ _ A _ _" => rejected (character at pos 3 differs)
     * - length mismatch => rejected
     */
    public function test_clue_validation(): void
    {
        // Valid case from specification: MAKAN with _ A _ A _
        $validRes = $this->storage->validateClue('MAKAN', '_ A _ A _');
        $this->assertTrue($validRes['valid']);
        $this->assertNull($validRes['error']);

        // Another valid format without spaces: _A_A_
        $validNoSpaces = $this->storage->validateClue('MAKAN', '_A_A_');
        $this->assertTrue($validNoSpaces['valid']);

        // Invalid case from specification: MAKAN with _ _ A _ _ (pos 3 is A, but in MAKAN pos 3 is K)
        $invalidPos = $this->storage->validateClue('MAKAN', '_ _ A _ _');
        $this->assertFalse($invalidPos['valid']);
        $this->assertStringContainsString('3', $invalidPos['error']);

        // Length mismatch
        $invalidLen = $this->storage->validateClue('MAKAN', '_ A _');
        $this->assertFalse($invalidLen['valid']);
    }

    /**
     * Test Growth 100 validation:
     * - Total score must be exactly 100
     * - Automatic sorting from highest to lowest score
     */
    public function test_growth_100_validation_and_sorting(): void
    {
        // Total score 90 (invalid)
        $invalidAnswers = [
            ['text' => 'A', 'score' => 50],
            ['text' => 'B', 'score' => 40],
        ];
        $invRes = $this->storage->validateGrowthAnswers($invalidAnswers);
        $this->assertFalse($invRes['valid']);
        $this->assertStringContainsString('100', $invRes['error']);

        // Total score 100 (valid) and unsorted input: 10, 50, 40
        $validUnsorted = [
            ['text' => 'C', 'score' => 10],
            ['text' => 'A', 'score' => 50],
            ['text' => 'B', 'score' => 40],
        ];
        $valRes = $this->storage->validateGrowthAnswers($validUnsorted);
        $this->assertTrue($valRes['valid']);
        
        // Assert automatic sorting: 50 -> 40 -> 10
        $this->assertEquals(50, $valRes['answers'][0]['score']);
        $this->assertEquals('A', $valRes['answers'][0]['text']);
        $this->assertEquals(40, $valRes['answers'][1]['score']);
        $this->assertEquals(10, $valRes['answers'][2]['score']);
    }

    /**
     * Test Score separation and final calculation:
     * Game 1 (Red: 20, Blue: 0)
     * Game 2 (Red: 30, Blue: 25)
     * Final: Red = 50, Blue = 25
     */
    public function test_score_separation_and_final_calculation(): void
    {
        $this->storage->resetGame();

        // Update Game 1
        $this->postJson('/game/update-score', [
            'game' => 'game1',
            'team' => 'red',
            'amount' => 20,
        ])->assertOk();

        // Update Game 2
        $this->postJson('/game/update-score', [
            'game' => 'game2',
            'team' => 'red',
            'amount' => 30,
        ])->assertOk();

        $this->postJson('/game/update-score', [
            'game' => 'game2',
            'team' => 'blue',
            'amount' => 25,
        ])->assertOk();

        $final = $this->storage->getFinalScores();
        $this->assertEquals(20, $final['game1']['red']);
        $this->assertEquals(0, $final['game1']['blue']);
        $this->assertEquals(30, $final['game2']['red']);
        $this->assertEquals(25, $final['game2']['blue']);
        $this->assertEquals(50, $final['final_red']);
        $this->assertEquals(25, $final['final_blue']);

        // Verify on Game Center Hub
        $gcRes = $this->get('/game-center');
        $gcRes->assertStatus(200);
        $gcRes->assertSee('50');
        $gcRes->assertSee('25');
    }

    /**
     * Test Game 1 CRUD endpoints
     */
    public function test_game1_crud_endpoints(): void
    {
        // Add new round with invalid clue (should fail)
        $this->postJson('/game/guess-me/round', [
            'correct_answer' => 'POHON',
            'clue' => '_ _ O _ _', // pos 3 is O, but POHON pos 3 is H!
            'score' => 15,
        ])->assertStatus(422);

        // Add new round with valid clue
        $addRes = $this->postJson('/game/guess-me/round', [
            'correct_answer' => 'POHON',
            'clue' => 'P _ H _ N',
            'score' => 15,
        ]);
        $addRes->assertOk();

        $rounds = $this->storage->getGuessMeRounds();
        $newRound = end($rounds);
        $this->assertEquals('POHON', $newRound['correct_answer']);
        $this->assertEquals(15, $newRound['score']);

        // Delete round
        $this->deleteJson('/game/guess-me/round/' . $newRound['id'])->assertOk();
    }

    /**
     * Test Game 2 CRUD endpoints
     */
    public function test_game2_crud_endpoints(): void
    {
        // Add new survey round with sum != 100 (should fail)
        $this->postJson('/game/growth-100/round', [
            'question' => 'Makanan favorit saat rapat?',
            'answers' => [
                ['text' => 'Gorengan', 'score' => 60],
                ['text' => 'Kopi', 'score' => 30],
            ],
        ])->assertStatus(422);

        // Add new survey round with sum == 100
        $addRes = $this->postJson('/game/growth-100/round', [
            'question' => 'Makanan favorit saat rapat?',
            'answers' => [
                ['text' => 'Kopi', 'score' => 40],
                ['text' => 'Gorengan', 'score' => 60],
            ],
        ]);
        $addRes->assertOk();

        $rounds = $this->storage->getGrowth100Rounds();
        $newRound = end($rounds);
        $this->assertEquals('Makanan favorit saat rapat?', $newRound['question']);
        // Assert auto-sorted: Gorengan (60) then Kopi (40)
        $this->assertEquals('Gorengan', $newRound['answers'][0]['text']);
        $this->assertEquals(60, $newRound['answers'][0]['score']);

        // Delete round
        $this->deleteJson('/game/growth-100/round/' . $newRound['id'])->assertOk();
    }

    /**
     * Test Reset Game resets scores and state to 0 without deleting questions
     */
    public function test_reset_game_preserves_questions(): void
    {
        // Set some scores first
        $this->storage->updateScore('game1', 'red', 40);
        $this->storage->updateScore('game2', 'blue', 50);

        $initialG1Count = count($this->storage->getGuessMeRounds());
        $initialG2Count = count($this->storage->getGrowth100Rounds());

        // Perform reset
        $resetRes = $this->postJson('/game/reset');
        $resetRes->assertOk();

        // Scores must be 0
        $final = $this->storage->getFinalScores();
        $this->assertEquals(0, $final['final_red']);
        $this->assertEquals(0, $final['final_blue']);
        $this->assertEquals(0, $final['game1']['red']);
        $this->assertEquals(0, $final['game2']['blue']);

        // Questions count must remain intact
        $this->assertEquals($initialG1Count, count($this->storage->getGuessMeRounds()));
        $this->assertEquals($initialG2Count, count($this->storage->getGrowth100Rounds()));
    }
}
