<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Game;
use App\Models\GameAnswer;
use App\Models\GameRound;
use App\Models\GameScore;
use App\Models\GameState;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use App\Services\GameStorageService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabasePersistenceTest extends TestCase
{
    /**
     * Test all required relational tables exist in MySQL
     */
    public function test_all_required_database_tables_exist(): void
    {
        $tables = [
            'users',
            'media_files',
            'games',
            'teams',
            'game_rounds',
            'game_answers',
            'game_states',
            'game_scores',
            'members',
            'cash_transactions',
            'activities',
            'birthday_letters',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Database table '{$table}' must exist in MySQL.");
        }

    }

    /**
     * Test Game to Rounds and Answers model relationships
     */
    public function test_game_rounds_and_answers_relationships(): void
    {
        $game = Game::where('code', 'game2')->first();
        $this->assertNotNull($game);
        $this->assertNotEmpty($game->rounds);

        $round = $game->rounds->first();
        $this->assertInstanceOf(GameRound::class, $round);
        $this->assertEquals($game->id, $round->game_id);
        $this->assertNotEmpty($round->answers);

        $answer = $round->answers->first();
        $this->assertInstanceOf(GameAnswer::class, $answer);
        $this->assertEquals($round->id, $answer->round->id);
    }

    /**
     * Test Teams and Scores relationships
     */
    public function test_team_and_game_score_relationships(): void
    {
        $teamRed = Team::where('code', 'red')->first();
        $game1 = Game::where('code', 'game1')->first();

        $this->assertNotNull($teamRed);
        $this->assertNotNull($game1);

        $score = GameScore::where('game_id', $game1->id)->where('team_id', $teamRed->id)->first();
        $this->assertNotNull($score);
        $this->assertEquals($teamRed->id, $score->team->id);
        $this->assertEquals($game1->id, $score->game->id);
    }

    /**
     * Test MediaFile metadata persistence and relationship with GameRound
     */
    public function test_media_file_persistence_and_game_round_relationship(): void
    {
        $media = MediaFile::create([
            'disk' => 'public',
            'file_path' => 'assets/images/test_image.jpg',
            'original_name' => 'test_image.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 12345,
        ]);

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'original_name' => 'test_image.jpg',
            'mime_type' => 'image/jpeg',
        ]);
        $this->assertTrue($media->isImage());

        $game = Game::where('code', 'game1')->first();
        $round = GameRound::create([
            'game_id' => $game->id,
            'round_number' => 999,
            'correct_answer' => 'TEST',
            'clue' => 'T _ S T',
            'score' => 20,
            'media_file_id' => $media->id,
        ]);

        $this->assertEquals($media->id, $round->mediaFile->id);

        // Cleanup
        $round->delete();
        $media->delete();
    }

    /**
     * Test Member photo architecture and relationship
     */
    public function test_member_photo_architecture_and_relationship(): void
    {
        $photo = MediaFile::create([
            'disk' => 'public',
            'file_path' => 'members/john_doe.png',
            'original_name' => 'john_doe.png',
            'mime_type' => 'image/png',
            'file_size' => 54321,
        ]);

        $member = Member::create([
            'full_name' => 'John Doe',
            'position' => 'Youth Leader',
            'photo_file_id' => $photo->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'full_name' => 'John Doe',
            'position' => 'Youth Leader',
        ]);

        $this->assertNotNull($member->photo);
        $this->assertEquals('john_doe.png', $member->photo->original_name);
        $this->assertTrue($member->photo->isImage());

        // Cleanup
        $member->delete();
        $photo->delete();
    }

    /**
     * Test Cash Transaction proof image architecture and relationship
     */
    public function test_cash_transaction_proof_image_architecture(): void
    {
        $admin = User::where('role', 'admin')->first();

        $proof = MediaFile::create([
            'disk' => 'local',
            'file_path' => 'cash_proofs/receipt_march_2026.jpg',
            'original_name' => 'receipt_march_2026.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 67890,
        ]);

        $transaction = CashTransaction::create([
            'user_id' => $admin->id,
            'contributor_name' => 'Youth Fellowship Offering',
            'amount' => 500000.00,
            'type' => 'inflow',
            'description' => 'March youth gathering fellowship offering',
            'proof_file_id' => $proof->id,
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('cash_transactions', [
            'id' => $transaction->id,
            'amount' => 500000.00,
            'type' => 'inflow',
        ]);

        $this->assertNotNull($transaction->proof);
        $this->assertTrue($transaction->hasValidImageProof());
        $this->assertEquals($admin->id, $transaction->user->id);

        // Cleanup
        $transaction->delete();
        $proof->delete();
    }

    /**
     * Test GameStorageService reads from and writes to MySQL directly
     */
    public function test_game_storage_service_persists_to_mysql(): void
    {
        $service = app(GameStorageService::class);

        // Update score
        $service->updateScore('game1', 'red', 35, true);

        $redTeam = Team::where('code', 'red')->first();
        $game1 = Game::where('code', 'game1')->first();

        $scoreRecord = GameScore::where('game_id', $game1->id)->where('team_id', $redTeam->id)->first();
        $this->assertEquals(35, $scoreRecord->score);

        // Read through service
        $scores = $service->getFinalScores();
        $this->assertEquals(35, $scores['game1']['red']);

        // Reset
        $service->resetGame();
        $this->assertEquals(0, GameScore::where('game_id', $game1->id)->where('team_id', $redTeam->id)->value('score'));
    }
}
