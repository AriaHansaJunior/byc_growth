<?php

namespace Tests\Feature;

use App\Http\Controllers\GameController;
use App\Http\Controllers\PageController;
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class Scope14MigrationCleanupTest extends TestCase
{
    protected GameStorageService $storage;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = app(GameStorageService::class);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s14@bycgrowth.org'],
            [
                'name' => 'Admin S14',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );
    }

    /**
     * 1. Relational schema: MySQL is the authoritative single source of truth for all entities.
     */
    public function test_01_mysql_is_single_source_of_truth_for_all_entities(): void
    {
        $requiredTables = [
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

        foreach ($requiredTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Database table '{$table}' must exist in MySQL.");
        }

        // Verify games exist and are linked to rounds and dynamic teams in MySQL
        $game1 = Game::where('code', 'game1')->first();
        $game2 = Game::where('code', 'game2')->first();

        $this->assertNotNull($game1, 'Game 1 (Guess Me!) must exist in MySQL.');
        $this->assertNotNull($game2, 'Game 2 (BYC Growth 100) must exist in MySQL.');

        $this->assertGreaterThan(0, Team::where('game_id', $game1->id)->count(), 'Game 1 must have persistent teams in MySQL.');
        $this->assertGreaterThan(0, Team::where('game_id', $game2->id)->count(), 'Game 2 must have persistent teams in MySQL.');
    }

    /**
     * 2. Absence of legacy JSON persistence files in storage and app.
     */
    public function test_02_no_legacy_json_persistence_files_exist(): void
    {
        $gameStoragePath = storage_path('app/game');
        $this->assertTrue(File::isDirectory($gameStoragePath), 'storage/app/game must exist for asset management.');

        // Verify no obsolete JSON files exist in storage/app/game
        $this->assertFalse(File::exists($gameStoragePath . '/rounds.json'), 'rounds.json must not exist.');
        $this->assertFalse(File::exists($gameStoragePath . '/game_state.json'), 'game_state.json must not exist.');
        $this->assertFalse(File::exists($gameStoragePath . '/scores.json'), 'scores.json must not exist.');

        // Verify no stray JSON files in storage/app/
        $storageAppFiles = File::files(storage_path('app'));
        foreach ($storageAppFiles as $file) {
            $this->assertNotEquals('json', strtolower($file->getExtension()), "storage/app/ must not contain JSON files: {$file->getFilename()}");
        }
    }

    /**
     * 3. Obsolete contact.blade.php template is removed, legacy route gracefully redirects.
     */
    public function test_03_obsolete_contact_view_is_removed_and_route_redirects(): void
    {
        $obsoleteContactView = resource_path('views/pages/contact.blade.php');
        $this->assertFalse(File::exists($obsoleteContactView), 'resources/views/pages/contact.blade.php must be removed.');
        $this->assertFalse(File::exists(resource_path('views/user/contact.blade.php')), 'resources/views/user/contact.blade.php must be removed.');

        // Legacy /contact route safely redirects to /about with HTTP 301
        $response = $this->get('/contact');
        $response->assertStatus(301);
        $response->assertRedirect('/about');

        // Combined About + Contact page renders properly
        $aboutResponse = $this->get('/about');
        $aboutResponse->assertStatus(200);
        $aboutResponse->assertSee('About BYC Growth');
        $aboutResponse->assertSee('bycgrowthbethany1@gmail.com');
    }

    /**
     * 4. GameController dead home() method is eliminated.
     */
    public function test_04_game_controller_dead_home_action_is_eliminated(): void
    {
        $reflection = new ReflectionClass(GameController::class);
        $this->assertFalse($reflection->hasMethod('home'), 'GameController::home() must be removed as dead code.');

        // Verify homepage is served by PageController
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('BYC GROWTH');
        $response->assertSee('Game Center');
    }

    /**
     * 5. Active media storage directories remain intact and functional.
     */
    public function test_05_active_media_storage_directories_functional(): void
    {
        $gameImagesPath = storage_path('app/game/images');
        $uploadsPath = public_path('assets/images/uploads');

        $this->assertTrue(File::isDirectory($gameImagesPath), 'storage/app/game/images directory must exist.');
        $this->assertTrue(File::isDirectory($uploadsPath), 'public/assets/images/uploads directory must exist.');

        // Test game image retrieval endpoint
        $firstImage = collect(File::files($gameImagesPath))->first();
        if ($firstImage) {
            $response = $this->get('/game/image/' . $firstImage->getFilename());
            $response->assertStatus(200);
        }
    }

    /**
     * 6. Migration history consistency: all migrations are executed, 0 pending.
     */
    public function test_06_all_migrations_are_executed_and_consistent(): void
    {
        Artisan::call('migrate:status');
        $output = Artisan::output();

        $this->assertStringNotContainsString('Pending', $output, 'No migrations should be pending.');
        $this->assertStringContainsString('Ran', $output, 'Migrations must be in Ran state.');
        $this->assertStringContainsString('create_users_table', $output);
        $this->assertStringContainsString('create_birthday_letters_table', $output);
    }

    /**
     * 7. Game score and state updates persist exclusively in MySQL.
     */
    public function test_07_game_persistence_mutations_persist_in_mysql_only(): void
    {
        $this->actingAs($this->adminUser);

        $game = Game::where('code', 'game1')->first();
        $team = Team::where('game_id', $game->id)->first();
        $this->assertNotNull($team);

        $initialScore = GameScore::where('game_id', $game->id)->where('team_id', $team->id)->value('score') ?? 0;

        // Mutate score via API (adds 10)
        $response = $this->postJson('/game/update-score', [
            'game' => 'game1',
            'team' => $team->id,
            'amount' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify MySQL updated
        $updatedScore = GameScore::where('game_id', $game->id)->where('team_id', $team->id)->value('score');
        $this->assertEquals($initialScore + 10, $updatedScore);

        // Verify no JSON files were created
        $this->assertFalse(File::exists(storage_path('app/game/scores.json')));
        $this->assertFalse(File::exists(storage_path('app/game/game_state.json')));
    }

    /**
     * 8. Public Members page remains strictly Photo + Full Name (No Position / Hierarchy).
     */
    public function test_08_public_members_page_equality_maintained(): void
    {
        $member = Member::create([
            'full_name' => 'S14 Equality Test Member',
            'position' => 'Special Senior Leadership Rank',
            'date_of_birth' => '1995-12-25',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Members Directory');
        $response->assertSee('S14 Equality Test Member');

        // Verify Position / Role / DOB are NOT rendered on public page
        $response->assertDontSee('Special Senior Leadership Rank');
        $response->assertDontSee('1995-12-25');
        $response->assertDontSee('December 25, 1995');
        $response->assertDontSee('member-position');

        $member->delete();
    }

    /**
     * 9. Cash Management security and privacy boundary remains strictly enforced.
     */
    public function test_09_cash_management_security_and_privacy_boundary_intact(): void
    {
        // Unauthenticated access rejected
        $guestResponse = $this->get('/cash-management');
        $guestResponse->assertRedirect('/admin-ganteng');

        // Admin can access
        $adminResponse = $this->actingAs($this->adminUser)->get('/cash-management');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Cash Management');

        // Homepage as unauthenticated visitor does not expose confidential 30k amount
        $this->app['auth']->logout();
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('30,000');
        $homeResponse->assertDontSee('30k');
        $homeResponse->assertSee('Contact the admin to view your cash contribution.');
    }
}
