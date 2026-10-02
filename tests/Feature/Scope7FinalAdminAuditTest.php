<?php

namespace Tests\Feature;

use App\Models\BirthdayLetter;
use App\Models\CashTransaction;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope7FinalAdminAuditTest extends TestCase
{
    protected User $admin1;
    protected User $admin2;
    protected User $normalUser;
    protected array $cleanupUserIds = [];
    protected array $cleanupMemberIds = [];
    protected array $cleanupCashIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Required Primary Admin: admin@gmail.com / admin_utama / admin123
        $this->admin1 = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Utama',
                'username' => 'admin_utama',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // 2. Required Secondary Admin: jojo_ganteng@gmail.com / rilbiezzz / jojo123
        $this->admin2 = User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
            ]
        );

        // 3. Regular Fellowship Member User
        $this->normalUser = User::updateOrCreate(
            ['email' => 'audit_normal_user@bycgrowth.org'],
            [
                'name' => 'Audit Normal User',
                'username' => 'audit_normal_user',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        if (!empty($this->cleanupCashIds)) {
            CashTransaction::whereIn('id', $this->cleanupCashIds)->delete();
        }

        if (!empty($this->cleanupUserIds)) {
            User::whereIn('id', $this->cleanupUserIds)->delete();
        }

        if (!empty($this->cleanupMemberIds)) {
            Member::whereIn('id', $this->cleanupMemberIds)->delete();
        }

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | 1. ADMIN ACCESS ARCHITECTURE & ENTRANCE AUDIT
    |--------------------------------------------------------------------------
    */

    public function test_01_hidden_admin_entrance_accessible_and_not_leaked_to_public(): void
    {
        // Hidden entrance must be accessible
        $entranceResponse = $this->get('/admin-ganteng');
        $entranceResponse->assertStatus(200);
        $entranceResponse->assertSee('Admin Sign In');

        // Legacy /admin/login route must return 404
        $legacyGet = $this->get('/admin/login');
        $legacyGet->assertStatus(404);

        $legacyPost = $this->post('/admin/login', [
            'login' => 'admin@gmail.com',
            'password' => 'admin123',
        ]);
        $legacyPost->assertStatus(404);

        // Public pages must NOT leak the hidden entrance
        $publicRoutes = ['/', '/about', '/activity', '/members', '/game-center'];
        foreach ($publicRoutes as $route) {
            $resp = $this->get($route);
            $resp->assertStatus(200);
            $resp->assertDontSee('/admin-ganteng');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 2. REQUIRED ADMIN ACCOUNTS AUDIT
    |--------------------------------------------------------------------------
    */

    public function test_02_required_final_admin_accounts_exist_and_authenticate(): void
    {
        // Admin 1: admin_utama / admin123 / admin@gmail.com
        $this->assertTrue(Hash::check('admin123', $this->admin1->password));
        $this->assertEquals('admin', $this->admin1->role);
        $this->assertStringStartsWith('$2y$', $this->admin1->password);

        // Test login with username
        $login1 = $this->post('/admin-ganteng', [
            'login' => 'admin_utama',
            'password' => 'admin123',
        ]);
        $login1->assertRedirect('/admin/dashboard');
        $this->assertTrue(Auth::check());
        $this->assertEquals('admin_utama', Auth::user()->username);
        Auth::logout();

        // Admin 2: rilbiezzz / jojo123 / jojo_ganteng@gmail.com
        $this->assertTrue(Hash::check('jojo123', $this->admin2->password));
        $this->assertEquals('admin', $this->admin2->role);
        $this->assertStringStartsWith('$2y$', $this->admin2->password);

        // Test login with email
        $login2 = $this->post('/admin-ganteng', [
            'login' => 'jojo_ganteng@gmail.com',
            'password' => 'jojo123',
        ]);
        $login2->assertRedirect('/admin/dashboard');
        $this->assertTrue(Auth::check());
        $this->assertEquals('rilbiezzz', Auth::user()->username);
        Auth::logout();
    }

    /*
    |--------------------------------------------------------------------------
    | 3. ADMIN HEADER / IDENTITY AUDIT
    |--------------------------------------------------------------------------
    */

    public function test_03_admin_header_displays_username_only_and_logout_redirects_to_admin_ganteng(): void
    {
        $this->actingAs($this->admin2); // rilbiezzz

        // 1. Header displays username in public header
        $publicResp = $this->get('/');
        $publicResp->assertStatus(200);
        $publicResp->assertSee('rilbiezzz');
        $publicResp->assertDontSee('Jojo Admin (rilbiezzz)');
        $publicResp->assertDontSee('Edit Profile');

        // 2. Admin Portal header shows username
        $adminResp = $this->get('/admin/dashboard');
        $adminResp->assertStatus(200);
        $adminResp->assertSee('rilbiezzz');

        // 3. Admin logout redirects to /admin-ganteng
        $logoutResp = $this->post('/admin/logout');
        $logoutResp->assertRedirect('/admin-ganteng');
        $this->assertFalse(Auth::check());
    }

    /*
    |--------------------------------------------------------------------------
    | 4. ADMIN NAVIGATION AUDIT (8 PORTALS)
    |--------------------------------------------------------------------------
    */

    public function test_04_admin_navigation_contains_all_8_portals_with_active_icons(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        $portals = [
            'Dashboard' => '/admin/dashboard',
            'Homepage' => '/admin/homepage',
            'Members' => '/admin/members',
            'Activities' => '/admin/activities',
            'Games' => '/admin/games',
            'Birthday Wishes' => '/admin/birthday-wishes',
            'Cash Management' => '/admin/cash-management',
            'Roles / Accounts' => '/admin/roles',
        ];

        foreach ($portals as $name => $path) {
            $response->assertSee($name);
            $portalResp = $this->get($path);
            $portalResp->assertStatus(200);
        }

        // Normal user must NOT see Admin navigation
        $this->actingAs($this->normalUser);
        $userHome = $this->get('/');
        $userHome->assertDontSee('Admin Portal');
        $userHome->assertDontSee('Roles / Accounts');
    }

    /*
    |--------------------------------------------------------------------------
    | 5. AUTHORIZATION AUDIT (SERVER-SIDE ENFORCEMENT)
    |--------------------------------------------------------------------------
    */

    public function test_05_all_admin_portals_strictly_protected_against_guest_and_normal_user(): void
    {
        $adminEndpoints = [
            '/admin/dashboard',
            '/admin/homepage',
            '/admin/members',
            '/admin/activities',
            '/admin/games',
            '/admin/birthday-wishes',
            '/admin/cash-management',
            '/admin/roles',
        ];

        // 1. Guests redirected
        foreach ($adminEndpoints as $url) {
            $resp = $this->get($url);
            $resp->assertRedirect('/admin-ganteng');
        }

        // 2. Normal user gets 403 Forbidden
        $this->actingAs($this->normalUser);
        foreach ($adminEndpoints as $url) {
            $resp = $this->get($url);
            $resp->assertStatus(403);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 6. MEMBERS AUDIT (PHOTO + FULL NAME ONLY, NO POSITION)
    |--------------------------------------------------------------------------
    */

    public function test_06_public_members_privacy_photo_and_fullname_only_no_position(): void
    {
        $member = Member::create([
            'full_name' => 'Audited Elder Member',
            'position' => 'Secret Core Ministry Lead',
            'date_of_birth' => '1990-11-20',
            'is_active' => true,
        ]);
        $this->cleanupMemberIds[] = $member->id;

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Audited Elder Member');

        // MUST NOT display position
        $response->assertDontSee('Secret Core Ministry Lead');

        // MUST NOT display date of birth publicly
        $response->assertDontSee('1990-11-20');
        $response->assertDontSee('November 20');
    }

    /*
    |--------------------------------------------------------------------------
    | 7. BIRTHDAY WISHES AUDIT (ANONYMOUS PRIVACY & INVARIANTS)
    |--------------------------------------------------------------------------
    */

    public function test_07_anonymous_birthday_privacy_preserved_admin_sees_identity_public_sees_anonymous(): void
    {
        $today = Carbon::now('Asia/Jakarta');
        $celebrant = Member::create([
            'full_name' => 'Birthday Celebrant S7',
            'date_of_birth' => $today->copy()->subYears(20)->toDateString(),
            'is_active' => true,
        ]);
        $this->cleanupMemberIds[] = $celebrant->id;

        // Anonymous letter by normalUser
        $letter = BirthdayLetter::create([
            'user_id' => $this->normalUser->id,
            'member_id' => $celebrant->id,
            'author_name' => 'Anonymous',
            'message' => 'Happy Birthday Secret Blessing Message!',
            'birthday_year' => $today->year,
            'is_anonymous' => true,
        ]);

        // 1. Admin viewing archive: CAN see real sender identity
        $this->actingAs($this->admin1);
        $adminView = $this->get('/admin/birthday-wishes');
        $adminView->assertStatus(200);
        $adminView->assertSee('audit_normal_user');
        $adminView->assertSee('Anonymous');

        // 2. Public / Recipient: sees Anonymous ONLY
        $this->actingAs($this->normalUser);
        $publicView = $this->get('/birthday-wishes');
        $publicView->assertStatus(200);
        $publicView->assertSee('Anonymous');

        $letter->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | 8. CASH MANAGEMENT AUDIT (MIME WHITELIST & SERVER TIMESTAMP)
    |--------------------------------------------------------------------------
    */

    public function test_08_cash_management_proof_restrictions_and_server_side_timestamp(): void
    {
        $this->actingAs($this->admin1);

        $donorMember = Member::create([
            'full_name' => 'Verified Audit Donor ' . time(),
            'date_of_birth' => '1996-01-01',
            'is_active' => true,
        ]);
        $this->cleanupMemberIds[] = $donorMember->id;

        // 1. Reject dangerous executable proof files
        $dangerousFile = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');
        $rejectResp = $this->post('/admin/cash-management', [
            'member_id' => $donorMember->id,
            'account_type' => 'BCA Transfer',
            'amount' => 100000,
            'proof' => $dangerousFile,
        ]);
        $rejectResp->assertSessionHasErrors(['proof']);

        // 2. Reject PDF proof files (image only whitelist: jpg, jpeg, png, webp)
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $pdfResp = $this->post('/admin/cash-management', [
            'member_id' => $donorMember->id,
            'account_type' => 'BCA Transfer',
            'amount' => 100000,
            'proof' => $pdfFile,
        ]);
        $pdfResp->assertSessionHasErrors(['proof']);

        // 3. Valid JPG proof accepted with server-side system input time
        $validFile = UploadedFile::fake()->image('transfer_receipt.jpg');
        $successResp = $this->post('/admin/cash-management', [
            'member_id' => $donorMember->id,
            'account_type' => 'BCA Transfer',
            'amount' => 75000,
            'proof' => $validFile,
        ]);
        $successResp->assertRedirect();

        $tx = CashTransaction::where('member_id', $donorMember->id)->firstOrFail();
        $this->cleanupCashIds[] = $tx->id;

        // Server-side input time authority (Asia/Jakarta timezone)
        $this->assertNotNull($tx->created_at);
        $this->assertEquals(Carbon::now('Asia/Jakarta')->toDateString(), $tx->transaction_date->toDateString());
        $this->assertTrue(Carbon::parse($tx->created_at)->diffInMinutes(Carbon::now('Asia/Jakarta')) < 5);
    }

    /*
    |--------------------------------------------------------------------------
    | 9. GAMES AUDIT (GROWTH 100 SUM == 100 VALIDATION)
    |--------------------------------------------------------------------------
    */

    public function test_09_growth_100_answer_sum_strictly_enforced_to_100(): void
    {
        $this->actingAs($this->admin1);

        // Attempt score sum == 90 (rejected)
        $underResp = $this->postJson('/admin/games/growth-100/round', [
            'round_number' => 999,
            'question' => 'Under 100 Survey Question?',
            'answers' => [
                ['text' => 'Option 1', 'score' => 50],
                ['text' => 'Option 2', 'score' => 40],
            ],
        ]);
        $underResp->assertStatus(422);

        // Attempt score sum == 110 (rejected)
        $overResp = $this->postJson('/admin/games/growth-100/round', [
            'round_number' => 999,
            'question' => 'Over 100 Survey Question?',
            'answers' => [
                ['text' => 'Option 1', 'score' => 60],
                ['text' => 'Option 2', 'score' => 50],
            ],
        ]);
        $overResp->assertStatus(422);

        // Valid score sum == 100 (accepted)
        $validResp = $this->postJson('/admin/games/growth-100/round', [
            'round_number' => 999,
            'question' => 'Exact 100 Survey Question?',
            'answers' => [
                ['text' => 'Top Answer', 'score' => 60],
                ['text' => 'Second Answer', 'score' => 40],
            ],
        ]);
        $validResp->assertStatus(200);

        // Clean up
        $round = GameRound::where('round_number', 999)->first();
        if ($round) {
            $round->answers()->delete();
            $round->delete();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | 10. ROLES & ACCOUNTS AUDIT (SELF-DELETE & ROLE SANITIZATION)
    |--------------------------------------------------------------------------
    */

    public function test_10_roles_accounts_self_delete_protection_and_role_whitelist(): void
    {
        $this->actingAs($this->admin1);

        // 1. Self-delete is strictly rejected
        $selfDeleteResp = $this->delete('/admin/roles/' . $this->admin1->id);
        $selfDeleteResp->assertRedirect('/admin/roles');
        $selfDeleteResp->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin1->id]);

        $selfDeleteJson = $this->deleteJson('/admin/roles/' . $this->admin1->id);
        $selfDeleteJson->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->admin1->id]);

        // 2. Role whitelist rejection (superadmin, god_mode, root)
        $badRoleResp = $this->post('/admin/roles', [
            'username' => 'hacker_role_' . time(),
            'email' => 'badrole_' . time() . '@bycgrowth.org',
            'password' => 'secret123',
            'role' => 'god_mode',
        ]);
        $badRoleResp->assertSessionHasErrors(['role']);

        // 3. Normal user cannot mutate accounts
        $this->actingAs($this->normalUser);
        $unauthorizedCreate = $this->post('/admin/roles', [
            'username' => 'unauth_user',
            'email' => 'unauth@bycgrowth.org',
            'password' => 'secret123',
            'role' => 'admin',
        ]);
        $unauthorizedCreate->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | 11. DATA INTEGRITY & RELATIONSHIP PRESERVATION AUDIT
    |--------------------------------------------------------------------------
    */

    public function test_11_deleting_user_or_member_safely_preserves_historical_data(): void
    {
        $this->actingAs($this->admin1);

        // Member
        $member = Member::create([
            'full_name' => 'Preserved Historic Member ' . time(),
            'date_of_birth' => '1995-03-25',
            'is_active' => true,
        ]);

        // Linked User
        $user = User::create([
            'name' => 'Temp Historic User',
            'username' => 'temp_user_' . time(),
            'email' => 'temp_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $member->id,
        ]);

        // Cash contribution
        $cash = CashTransaction::create([
            'user_id' => $user->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 125000,
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'notes' => 'Preserved Fellowship Donation',
        ]);

        // Delete user account -> member and cash transaction MUST remain!
        $this->delete('/admin/roles/' . $user->id)->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
        $this->assertDatabaseHas('cash_transactions', ['id' => $cash->id, 'amount' => 125000]);

        // Delete member profile -> cash transaction MUST remain with nulled member_id!
        $this->delete('/admin/members/' . $member->id)->assertRedirect();
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseHas('cash_transactions', [
            'id' => $cash->id,
            'member_id' => null,
            'contributor_name' => $member->full_name,
            'amount' => 125000,
        ]);

        // Cleanup
        $cash->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | 12. ERROR PAGES & NONEXISTENT RESOURCE AUDIT
    |--------------------------------------------------------------------------
    */

    public function test_12_custom_error_pages_render_without_sensitive_leaks(): void
    {
        // 404 Page Not Found
        $response404 = $this->get('/nonexistent-page-route-12345');
        $response404->assertStatus(404);
        $response404->assertSee('Resource Not Found');
        $response404->assertSee('Return to Homepage');
        $response404->assertDontSee('SQLSTATE');
        $response404->assertDontSee('Stack trace');

        // 404 for invalid admin resource ID
        $this->actingAs($this->admin1);
        $badIdResp = $this->get('/admin/roles/888888');
        $badIdResp->assertStatus(404);
    }
}
