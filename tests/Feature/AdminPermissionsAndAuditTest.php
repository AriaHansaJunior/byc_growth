<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPermissionsAndAuditTest extends TestCase
{
    protected function createSuperAdmin(): User
    {
        return User::create([
            'name' => 'Super Administrator',
            'username' => 'super_admin_' . uniqid(),
            'email' => 'super_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'permissions' => array_keys(User::AVAILABLE_PERMISSIONS),
        ]);
    }

    /**
     * Test newly created admin defaults to zero permissions (empty list).
     */
    public function test_newly_created_admin_defaults_to_zero_permissions(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $newAdminEmail = 'newadmin_' . uniqid() . '@bycgrowth.test';
        $response = $this->actingAs($superAdmin, 'admin')->post('/admin/roles', [
            'username' => 'admin_' . uniqid(),
            'name' => 'Blank Admin',
            'email' => $newAdminEmail,
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/roles');

        $created = User::where('email', $newAdminEmail)->first();
        $this->assertNotNull($created);
        $this->assertEquals('admin', $created->role);
        $this->assertEquals([], $created->permissions);
        $this->assertFalse($created->hasAnyPermission());
        $this->assertFalse($created->hasPermission('members'));
        $this->assertFalse($created->hasPermission('games'));
    }

    /**
     * Test admin with zero permissions can only access empty dashboard and restricted header.
     */
    public function test_blank_admin_can_access_dashboard_with_restricted_view_and_cannot_access_modules(): void
    {
        $blankAdmin = User::create([
            'name' => 'Restricted Admin',
            'username' => 'restricted_' . uniqid(),
            'email' => 'restricted_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'permissions' => [],
        ]);

        // Dashboard is accessible
        $response = $this->actingAs($blankAdmin, 'admin')->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Access Permissions Restricted');
        $response->assertSee('No Active Module Access Granted');
        $response->assertDontSee('Games');

        // Other modules are forbidden/redirected to dashboard
        $forbiddenModules = [
            '/admin/homepage',
            '/admin/activities',
            '/admin/members',
            '/admin/games',
            '/admin/birthday-wishes',
            '/admin/cash-management',
            '/admin/roles',
        ];

        foreach ($forbiddenModules as $url) {
            $resp = $this->actingAs($blankAdmin, 'admin')->get($url);
            $resp->assertRedirect('/admin/dashboard');
            $resp->assertSessionHas('error');
        }
    }

    /**
     * Test admin with games-only permission can only access games and see games in dashboard.
     */
    public function test_games_admin_can_only_access_games(): void
    {
        $gamesAdmin = User::create([
            'name' => 'Games Master',
            'username' => 'games_admin_' . uniqid(),
            'email' => 'games_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'permissions' => ['games'],
        ]);

        // Games access is allowed
        $response = $this->actingAs($gamesAdmin, 'admin')->get('/admin/games');
        $response->assertStatus(200);

        // Dashboard shows only games module card
        $dashResponse = $this->actingAs($gamesAdmin, 'admin')->get('/admin/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Authorized Management Modules (1)');
        $dashResponse->assertSee('Games');
        $dashResponse->assertDontSee('Members');
        $dashResponse->assertDontSee('Cash Management');

        // Members module is blocked
        $blockedResponse = $this->actingAs($gamesAdmin, 'admin')->get('/admin/members');
        $blockedResponse->assertRedirect('/admin/dashboard');
        $blockedResponse->assertSessionHas('error');
    }

    /**
     * Test updating permissions on an admin via roles update endpoint.
     */
    public function test_super_admin_can_update_permissions_for_admin_account(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $targetAdmin = User::create([
            'name' => 'Secretariat Admin',
            'username' => 'sekr_' . uniqid(),
            'email' => 'sekr_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'permissions' => [],
        ]);

        $response = $this->actingAs($superAdmin, 'admin')->put("/admin/roles/{$targetAdmin->id}", [
            'username' => $targetAdmin->username,
            'email' => $targetAdmin->email,
            'role' => 'admin',
            'permissions' => ['members', 'activities'],
        ]);

        $response->assertRedirect('/admin/roles');

        $targetAdmin->refresh();
        $this->assertEquals(['members', 'activities'], $targetAdmin->permissions);
        $this->assertTrue($targetAdmin->hasPermission('members'));
        $this->assertTrue($targetAdmin->hasPermission('activities'));
        $this->assertFalse($targetAdmin->hasPermission('cash_management'));
    }

    /**
     * Test audit trail tracking and formatted audit string on member edit.
     */
    public function test_member_edit_records_audit_and_shows_exact_formatted_audit_text(): void
    {
        $admin = $this->createSuperAdmin();

        $member = Member::create([
            'full_name' => 'Aria Hansa Junior',
            'date_of_birth' => '2000-01-01',
            'is_active' => true,
        ]);

        $fixedTime = Carbon::create(2026, 1, 1, 19, 20, 0, 'Asia/Jakarta');
        Carbon::setTestNow($fixedTime);

        $response = $this->actingAs($admin, 'admin')->put("/admin/members/{$member->id}", [
            'full_name' => 'Aria',
            'date_of_birth' => '2000-01-01',
        ]);

        $response->assertRedirect('/admin/members');

        $member->refresh();
        $this->assertEquals('Aria', $member->full_name);
        $this->assertEquals($admin->email, $member->last_action_by);
        $this->assertEquals('edited', $member->last_action_type);

        // Expected format: edited by {email} - Thursday, 1 January 2026 19.20 WIB
        $expectedAuditText = "edited by {$admin->email} - Thursday, 1 January 2026 19.20 WIB";
        $this->assertEquals($expectedAuditText, $member->audit_trail_text);

        // Verify member index page includes the audit trail
        $indexResponse = $this->actingAs($admin, 'admin')->get('/admin/members');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($admin->email);
        $indexResponse->assertSee('Thursday, 1 January 2026 19.20 WIB');

        // Verify audit_logs table
        $auditLog = AuditLog::where('entity_type', 'member')
            ->where('entity_id', $member->id)
            ->where('action', 'edited')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($admin->email, $auditLog->user_email);

        Carbon::setTestNow(); // reset
    }

    /**
     * Test admin members renders modals, quick user modal, and no plus icon on create new account.
     */
    public function test_admin_members_renders_modals_without_plus_icons_and_includes_quick_account_modal(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin, 'admin')->get('/admin/members');
        $response->assertStatus(200);

        // Verify modals are present
        $response->assertSee('id="modal-add-member"', false);
        $response->assertSee('id="modal-edit-member"', false);
        $response->assertSee('id="modal-quick-create-user"', false);

        // Verify "Create New Account" text exists without "+" icon
        $response->assertSee('Create New Account');
        $response->assertDontSee('+ Create New User Account');
        $response->assertDontSee('+ Create New Account');

        // Verify dedicated quick create account modal has fields
        $response->assertSee('id="quick-user-email"', false);
        $response->assertSee('id="quick-user-username"', false);
        $response->assertSee('id="quick-user-password"', false);
    }

    /**
     * Test quick account creation AJAX endpoint returns JSON user for linking.
     */
    public function test_quick_account_creation_ajax_endpoint(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $email = 'quick_' . uniqid() . '@bycgrowth.test';
        $username = 'quick_' . uniqid();

        $response = $this->actingAs($superAdmin, 'admin')
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('/admin/roles', [
                'email' => $email,
                'username' => $username,
                'name' => 'Quick Member Account',
                'password' => 'password123',
                'role' => 'user',
            ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('user.email', $email);
        $response->assertJsonPath('user.username', $username);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'username' => $username,
            'role' => 'user',
        ]);
    }

    /**
     * Test cash management ledger renders admin email in Recorded By column instead of username.
     */
    public function test_cash_management_displays_admin_email_for_recorded_by_column(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $tx = \App\Models\CashTransaction::create([
            'user_id' => $superAdmin->id,
            'contributor_name' => 'Testing Contributor',
            'amount' => 50000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Test Transaction',
            'transaction_date' => now()->toDateString(),
            'last_action_by' => $superAdmin->email,
            'last_action_type' => 'created',
            'last_action_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin, 'admin')->get('/admin/cash-management');
        $response->assertStatus(200);

        // Verify Recorded By column displays email
        $response->assertSee($superAdmin->email);
        $this->assertNotEquals($superAdmin->email, $superAdmin->username);
    }
}
