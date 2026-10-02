<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameRound;
use App\Models\GameState;
use App\Models\GameScore;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use App\Services\GameStorageService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Scope 15 â€” Final Verification Test Suite for BYC GROWTH 2.0.
 *
 * Verifies final project-wide architectural invariants, strict access control,
 * relational persistence integrity, member equality, and legacy elimination.
 */
class Scope15FinalVerificationTest extends TestCase
{
    protected User $adminUser;
    protected User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s15@bycgrowth.org'],
            [
                'name' => 'Admin S15',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->normalUser = User::firstOrCreate(
            ['email' => 'user_s15@bycgrowth.org'],
            [
                'name' => 'User S15',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    /**
     * 1. Core public architecture pages resolve cleanly with navigation and English UI.
     */
    public function test_01_core_public_architecture_pages_resolve_with_navigation(): void
    {
        $publicRoutes = [
            '/' => 'BYC GROWTH',
            '/about' => 'About BYC Growth',
            '/activity' => 'Activities & Events',
            '/members' => 'Members Directory',
            '/game-center' => 'Game Center',
        ];

        foreach ($publicRoutes as $uri => $expectedTitle) {
            $response = $this->get($uri);
            $response->assertStatus(200);
            $response->assertSee($expectedTitle, false);

            // Subpages must feature back navigation
            if ($uri !== '/') {
                $response->assertSee('Back to Home');
            }
        }

        // Legacy /contact redirect invariant
        $contactResponse = $this->get('/contact');
        $contactResponse->assertStatus(301);
        $contactResponse->assertRedirect('/about');
    }

    /**
     * 2. Interactive games resolve and enforce per-game team isolation and scoring rules.
     */
    public function test_02_game_routes_resolve_and_enforce_isolation_and_integrity(): void
    {
        $gameRoutes = [
            '/guess-me' => 'Guess Me!',
            '/growth-100' => 'BYC Growth 100',
            '/final' => 'Final Score',
        ];

        foreach ($gameRoutes as $uri => $expectedHeading) {
            $response = $this->get($uri);
            $response->assertStatus(200);
            $response->assertSee($expectedHeading);
        }

        // Team isolation: game 1 and game 2 teams are separate models with game_id foreign keys
        $game1 = Game::where('code', 'game1')->firstOrFail();
        $game2 = Game::where('code', 'game2')->firstOrFail();

        $game1Team = Team::where('game_id', $game1->id)->where('is_active', true)->firstOrFail();
        $game2Team = Team::where('game_id', $game2->id)->where('is_active', true)->firstOrFail();

        $this->assertNotEquals($game1Team->id, $game2Team->id);
        $this->assertEquals($game1->id, $game1Team->game_id);
        $this->assertEquals($game2->id, $game2Team->game_id);

        // Cross-game point assignment is rejected
        $storage = app(GameStorageService::class);
        $game1Round = GameRound::where('game_id', $game1->id)->first();
        if ($game1Round) {
            $crossGameResult = $storage->assignRoundPoints('game1', $game1Round->id, $game2Team->id);
            $this->assertFalse($crossGameResult['success']);
            $this->assertStringContainsString('cannot award points to a team belonging to another game', $crossGameResult['error']);
        }
    }

    /**
     * 3. Public Members page displays ONLY Photo + Full Name; no Position or DOB concept.
     */
    public function test_03_member_directory_enforces_photo_and_name_only_without_position_or_dob(): void
    {
        $testMember = Member::create([
            'full_name' => 'S15 Invariant Member',
            'position' => 'Prohibited Position Title',
            'date_of_birth' => '1998-05-15',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('S15 Invariant Member');

        // Verify Position and DOB are completely absent from public view
        $response->assertDontSee('Prohibited Position Title');
        $response->assertDontSee('1998-05-15');
        $response->assertDontSee('May 15, 1998');

        // Admin form in members page also does NOT have any position input
        $adminResponse = $this->actingAs($this->adminUser)->get('/members');
        $adminResponse->assertStatus(200);
        $adminResponse->assertDontSee('name="position"', false);

        $testMember->delete();
    }

    /**
     * 4. Admin boundaries and cash management financial privacy remain strictly enforced.
     */
    public function test_04_admin_and_cash_boundaries_strictly_enforced(): void
    {
        // Unauthenticated access redirects to login
        $unauthCash = $this->get('/cash-management');
        $unauthCash->assertRedirect('/admin-ganteng');

        $unauthDashboard = $this->get('/admin/dashboard');
        $unauthDashboard->assertRedirect('/admin-ganteng');

        // Normal authenticated user is forbidden (HTTP 403)
        $forbiddenCash = $this->actingAs($this->normalUser)->get('/cash-management');
        $forbiddenCash->assertStatus(403);

        $forbiddenDashboard = $this->actingAs($this->normalUser)->get('/admin/dashboard');
        $forbiddenDashboard->assertStatus(403);

        // Admin can access
        $adminCash = $this->actingAs($this->adminUser)->get('/cash-management');
        $adminCash->assertStatus(200);

        // Public homepage cash card is disabled with confidential amount never exposed
        $this->app['auth']->logout();
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertDontSee('30,000');
        $homeResponse->assertDontSee('30k');
        $homeResponse->assertSee('Contact the admin to view your cash contribution.');
    }

    /**
     * 5. Database relational integrity: all tables exist and all migrations are executed.
     */
    public function test_05_database_relational_integrity_and_migrations(): void
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
            $this->assertTrue(Schema::hasTable($table), "Table {$table} must exist in MySQL.");
        }

        Artisan::call('migrate:status');
        $output = Artisan::output();

        $this->assertStringNotContainsString('Pending', $output, 'No migration must be pending.');
        $this->assertStringContainsString('Ran', $output);
    }

    /**
     * 6. Complete absence of legacy JSON persistence files and mechanisms.
     */
    public function test_06_no_legacy_json_persistence_files_exist(): void
    {
        $legacyFiles = [
            storage_path('app/game/rounds.json'),
            storage_path('app/game/game_state.json'),
            storage_path('app/game/scores.json'),
            storage_path('app/rounds.json'),
            storage_path('app/scores.json'),
        ];

        foreach ($legacyFiles as $file) {
            $this->assertFalse(File::exists($file), "Legacy JSON file {$file} must not exist.");
        }
    }

    /**
     * 7. Custom error pages (403, 404, 500) exist with English UI and back navigation.
     */
    public function test_07_custom_error_pages_exist_with_english_ui(): void
    {
        $errorViews = [
            'errors/403.blade.php' => 'Access Forbidden',
            'errors/404.blade.php' => 'Resource Not Found',
            'errors/500.blade.php' => 'Something Went Wrong',
        ];

        foreach ($errorViews as $viewPath => $expectedString) {
            $fullPath = resource_path('views/' . $viewPath);
            $this->assertTrue(File::exists($fullPath), "Error view {$viewPath} must exist.");

            $content = File::get($fullPath);
            $this->assertStringContainsString($expectedString, $content);
            $this->assertStringContainsString('Back to Home', $content);
        }
    }
}
