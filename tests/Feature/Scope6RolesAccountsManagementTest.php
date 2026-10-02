<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope6RolesAccountsManagementTest extends TestCase
{
    protected User $admin1;
    protected User $admin2;
    protected User $normalUser;
    protected array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Required primary Admin: admin@gmail.com / admin_utama / admin123
        $this->admin1 = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Utama',
                'username' => 'admin_utama',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // 2. Required secondary Admin: jojo_ganteng@gmail.com / rilbiezzz / jojo123
        $this->admin2 = User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Ganteng',
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
            ]
        );

        // 3. Regular fellowship user
        $this->normalUser = User::updateOrCreate(
            ['email' => 's6_normal_user@bycgrowth.org'],
            [
                'name' => 'Normal User S6',
                'username' => 's6_normal_user',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdUserIds)) {
            User::whereIn('id', $this->createdUserIds)->delete();
        }

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESS CONTROL
    |--------------------------------------------------------------------------
    */

    public function test_01_admin_can_access_roles_accounts_management_page(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->get('/admin/roles');

        $response->assertStatus(200);
        $response->assertSee('Role & Account Management');
        $response->assertSee('Registered Accounts');
        $response->assertSee('Total Accounts');
        $response->assertSee('Administrators');
        $response->assertSee('Standard Users');
        $response->assertSee('@admin_utama');
        $response->assertSee('admin@gmail.com');
        $response->assertSee('@rilbiezzz');
    }

    public function test_02_guest_cannot_access_roles_accounts_management_page(): void
    {
        $response = $this->get('/admin/roles');

        $response->assertRedirect('/admin-ganteng');
    }

    public function test_03_normal_user_cannot_access_roles_accounts_management_page(): void
    {
        $this->actingAs($this->normalUser);

        $response = $this->get('/admin/roles');

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function test_04_admin_can_create_standard_user_account(): void
    {
        $this->actingAs($this->admin1);

        $unique = time() . '_' . rand(100, 999);
        $username = 'new_fellowship_user_' . $unique;
        $email = 'user_' . $unique . '@bycgrowth.org';

        $response = $this->post('/admin/roles', [
            'username' => $username,
            'name' => 'New Fellowship User',
            'email' => $email,
            'password' => 'securepass123',
            'role' => 'user',
        ]);

        $response->assertRedirect('/admin/roles');

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->createdUserIds[] = $user->id;

        $this->assertEquals($username, $user->username);
        $this->assertEquals('user', $user->role);
        $this->assertTrue(Hash::check('securepass123', $user->password));
    }

    public function test_05_admin_can_create_administrator_account(): void
    {
        $this->actingAs($this->admin1);

        $unique = time() . '_' . rand(100, 999);
        $username = 'new_admin_' . $unique;
        $email = 'admin_' . $unique . '@bycgrowth.org';

        $response = $this->postJson('/admin/roles', [
            'username' => $username,
            'name' => 'New Administrator',
            'email' => $email,
            'password' => 'adminsecret123',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Account created successfully.',
        ]);

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->createdUserIds[] = $user->id;

        $this->assertEquals('admin', $user->role);
        $this->assertTrue(Hash::check('adminsecret123', $user->password));
    }

    public function test_06_duplicate_username_is_rejected(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->post('/admin/roles', [
            'username' => 'admin_utama', // already taken
            'email' => 'unique_' . time() . '@bycgrowth.org',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors(['username']);
    }

    public function test_07_duplicate_email_is_rejected(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->post('/admin/roles', [
            'username' => 'unique_user_' . time(),
            'email' => 'admin@gmail.com', // already taken
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_08_invalid_role_injection_is_rejected(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->post('/admin/roles', [
            'username' => 'hacker_' . time(),
            'email' => 'hacker_' . time() . '@bycgrowth.org',
            'password' => 'password123',
            'role' => 'superadmin_god_mode', // invalid role
        ]);

        $response->assertSessionHasErrors(['role']);
    }

    public function test_09_invalid_email_format_is_rejected(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->post('/admin/roles', [
            'username' => 'bad_email_' . time(),
            'email' => 'not-a-valid-email-address',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_10_missing_required_fields_rejected(): void
    {
        $this->actingAs($this->admin1);

        // Missing email, password, role
        $response = $this->post('/admin/roles', [
            'name' => 'Incomplete User',
        ]);

        $response->assertSessionHasErrors(['email', 'password', 'role']);

        // Empty username when provided
        $response2 = $this->post('/admin/roles', [
            'username' => '',
            'email' => 'incomplete_' . time() . '@bycgrowth.org',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response2->assertSessionHasErrors(['username']);
    }

    public function test_11_created_user_password_is_properly_hashed(): void
    {
        $this->actingAs($this->admin1);

        $email = 'plaintext_audit_' . time() . '@bycgrowth.org';
        $plainPassword = 'superSecretPassword987';

        $this->post('/admin/roles', [
            'username' => 'pwd_check_' . time(),
            'name' => 'Password Check',
            'email' => $email,
            'password' => $plainPassword,
            'role' => 'user',
        ]);

        $user = User::where('email', $email)->firstOrFail();
        $this->createdUserIds[] = $user->id;

        // Never store plaintext
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT & ROLE MUTATIONS
    |--------------------------------------------------------------------------
    */

    public function test_12_admin_can_edit_username(): void
    {
        $this->actingAs($this->admin1);

        $user = User::create([
            'username' => 'before_edit_' . time(),
            'name' => 'Before Edit',
            'email' => 'edit_user_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
        $this->createdUserIds[] = $user->id;

        $newUsername = 'after_edit_' . time();
        $response = $this->put('/admin/roles/' . $user->id, [
            'username' => $newUsername,
            'name' => 'After Edit',
            'email' => $user->email,
            'role' => 'user',
        ]);

        $response->assertRedirect('/admin/roles');

        $user->refresh();
        $this->assertEquals($newUsername, $user->username);
        $this->assertEquals('After Edit', $user->name);
    }

    public function test_13_admin_can_edit_email(): void
    {
        $this->actingAs($this->admin1);

        $user = User::create([
            'username' => 'email_edit_' . time(),
            'name' => 'Email Edit User',
            'email' => 'old_email_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
        $this->createdUserIds[] = $user->id;

        $newEmail = 'new_email_' . time() . '@bycgrowth.org';
        $response = $this->put('/admin/roles/' . $user->id, [
            'username' => $user->username,
            'email' => $newEmail,
            'role' => 'user',
        ]);

        $response->assertRedirect('/admin/roles');

        $user->refresh();
        $this->assertEquals($newEmail, $user->email);
    }

    public function test_14_admin_can_change_role_from_user_to_admin(): void
    {
        $this->actingAs($this->admin1);

        $targetUser = User::create([
            'username' => 'promote_me_' . time(),
            'name' => 'Promote Target',
            'email' => 'promote_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
        $this->createdUserIds[] = $targetUser->id;

        $response = $this->put('/admin/roles/' . $targetUser->id, [
            'username' => $targetUser->username,
            'email' => $targetUser->email,
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/roles');

        $targetUser->refresh();
        $this->assertEquals('admin', $targetUser->role);
        $this->assertTrue($targetUser->isAdmin());
    }

    public function test_15_admin_can_change_role_from_admin_to_user(): void
    {
        $this->actingAs($this->admin1);

        $targetAdmin = User::create([
            'username' => 'demote_me_' . time(),
            'name' => 'Demote Target',
            'email' => 'demote_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
        $this->createdUserIds[] = $targetAdmin->id;

        $response = $this->put('/admin/roles/' . $targetAdmin->id, [
            'username' => $targetAdmin->username,
            'email' => $targetAdmin->email,
            'role' => 'user',
        ]);

        $response->assertRedirect('/admin/roles');

        $targetAdmin->refresh();
        $this->assertEquals('user', $targetAdmin->role);
        $this->assertFalse($targetAdmin->isAdmin());
    }

    public function test_16_optional_password_update_works_safely(): void
    {
        $this->actingAs($this->admin1);

        $user = User::create([
            'username' => 'pw_update_' . time(),
            'name' => 'PW Update User',
            'email' => 'pw_update_' . time() . '@bycgrowth.org',
            'password' => Hash::make('originalPassword123'),
            'role' => 'user',
        ]);
        $this->createdUserIds[] = $user->id;

        $response = $this->put('/admin/roles/' . $user->id, [
            'username' => $user->username,
            'email' => $user->email,
            'password' => 'brandNewPassword456',
            'role' => 'user',
        ]);

        $response->assertRedirect('/admin/roles');

        $user->refresh();
        $this->assertFalse(Hash::check('originalPassword123', $user->password));
        $this->assertTrue(Hash::check('brandNewPassword456', $user->password));
    }

    public function test_17_blank_password_on_edit_does_not_erase_existing_password(): void
    {
        $this->actingAs($this->admin1);

        $originalHash = Hash::make('keepThisPassword123');
        $user = User::create([
            'username' => 'pw_keep_' . time(),
            'name' => 'PW Keep User',
            'email' => 'pw_keep_' . time() . '@bycgrowth.org',
            'password' => $originalHash,
            'role' => 'user',
        ]);
        $this->createdUserIds[] = $user->id;

        // Submit blank password
        $response = $this->put('/admin/roles/' . $user->id, [
            'username' => $user->username,
            'email' => $user->email,
            'password' => '',
            'role' => 'user',
        ]);

        $response->assertRedirect('/admin/roles');

        $user->refresh();
        $this->assertTrue(Hash::check('keepThisPassword123', $user->password));
        $this->assertNotEmpty($user->password);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE & SELF-DELETE PROTECTION
    |--------------------------------------------------------------------------
    */

    public function test_18_admin_can_delete_another_user_account(): void
    {
        $this->actingAs($this->admin1);

        $userToDelete = User::create([
            'username' => 'to_delete_' . time(),
            'name' => 'To Delete',
            'email' => 'delete_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->delete('/admin/roles/' . $userToDelete->id);

        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    public function test_19_admin_cannot_delete_themselves_via_endpoint(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->delete('/admin/roles/' . $this->admin1->id);

        // Web request redirects back with error notification
        $response->assertRedirect('/admin/roles');
        $response->assertSessionHas('error');

        // Admin account MUST still exist in database
        $this->assertDatabaseHas('users', ['id' => $this->admin1->id]);
    }

    public function test_20_direct_json_self_delete_attempt_returns_403_forbidden(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->deleteJson('/admin/roles/' . $this->admin1->id);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'Cannot delete your own active administrator account.',
        ]);

        // Admin account MUST still exist in database
        $this->assertDatabaseHas('users', ['id' => $this->admin1->id]);
    }

    public function test_21_deleting_user_does_not_accidentally_delete_unrelated_member_or_history_data(): void
    {
        $this->actingAs($this->admin1);

        // Create member
        $member = Member::create([
            'full_name' => 'Protected Member ' . time(),
            'date_of_birth' => '1998-05-15',
            'is_active' => true,
        ]);

        // Create user linked to member
        $user = User::create([
            'username' => 'linked_user_' . time(),
            'name' => 'Linked User',
            'email' => 'linked_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $member->id,
        ]);

        // Create cash transaction for member
        $cashTx = CashTransaction::create([
            'user_id' => $user->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 50000,
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'notes' => 'Preserved Fellowship Contribution',
        ]);

        // Delete user
        $response = $this->delete('/admin/roles/' . $user->id);
        $response->assertRedirect('/admin/roles');

        // User is deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        // Member profile MUST STILL EXIST!
        $this->assertDatabaseHas('members', ['id' => $member->id, 'full_name' => $member->full_name]);

        // Cash transaction MUST STILL EXIST with transaction history intact!
        $this->assertDatabaseHas('cash_transactions', [
            'id' => $cashTx->id,
            'amount' => 50000,
            'contributor_name' => $member->full_name,
        ]);

        // Clean up
        $cashTx->delete();
        $member->delete();
    }

    /*
    |--------------------------------------------------------------------------
    | SECURITY & PERMISSIONS ENFORCEMENT
    |--------------------------------------------------------------------------
    */

    public function test_22_normal_user_cannot_call_create_endpoint(): void
    {
        $this->actingAs($this->normalUser);

        $response = $this->post('/admin/roles', [
            'username' => 'exploit_user_' . time(),
            'email' => 'exploit_' . time() . '@bycgrowth.org',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(403);
    }

    public function test_23_normal_user_cannot_call_update_endpoint(): void
    {
        $this->actingAs($this->normalUser);

        $response = $this->put('/admin/roles/' . $this->normalUser->id, [
            'username' => 'self_promote_' . time(),
            'email' => $this->normalUser->email,
            'role' => 'admin',
        ]);

        $response->assertStatus(403);
    }

    public function test_24_normal_user_cannot_call_delete_endpoint(): void
    {
        $this->actingAs($this->normalUser);

        $response = $this->delete('/admin/roles/' . $this->admin1->id);

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $this->admin1->id]);
    }

    public function test_25_nonexistent_user_returns_404(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->get('/admin/roles/999999');
        $response->assertStatus(404);

        $response2 = $this->put('/admin/roles/999999', [
            'username' => 'nonexistent',
            'email' => 'nonexistent@bycgrowth.org',
            'role' => 'user',
        ]);
        $response2->assertStatus(404);

        $response3 = $this->delete('/admin/roles/999999');
        $response3->assertStatus(404);
    }

    public function test_26_sensitive_password_data_is_never_exposed(): void
    {
        $this->actingAs($this->admin1);

        // 1. JSON inspect endpoint
        $response = $this->get('/admin/roles/' . $this->admin1->id);
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayNotHasKey('password', $json);
        $this->assertArrayNotHasKey('password_hash', $json);
        $this->assertArrayNotHasKey('remember_token', $json);

        // 2. HTML Ledger Page
        $pageResponse = $this->get('/admin/roles');
        $pageResponse->assertStatus(200);

        // Never expose bcrypt hash on the page
        $pageResponse->assertDontSee($this->admin1->password);
        $pageResponse->assertDontSee('$2y$');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE 0â€“5 REGRESSION VERIFICATION
    |--------------------------------------------------------------------------
    */

    public function test_27_s0_authentication_compatibility(): void
    {
        // Login with Username + Password
        $response1 = $this->post('/login', [
            'login' => 'admin_utama',
            'password' => 'admin123',
        ]);
        $response1->assertRedirect();
        $this->assertTrue(Auth::check());
        $this->assertEquals('admin_utama', Auth::user()->username);
        Auth::logout();

        // Login with Email + Password
        $response2 = $this->post('/login', [
            'login' => 'jojo_ganteng@gmail.com',
            'password' => 'jojo123',
        ]);
        $response2->assertRedirect();
        $this->assertTrue(Auth::check());
        $this->assertEquals('rilbiezzz', Auth::user()->username);
        Auth::logout();

        // Hidden admin login entry (/admin-ganteng)
        $response3 = $this->get('/admin-ganteng');
        $response3->assertStatus(200);
    }

    public function test_28_s1_admin_shell_and_navigation_integrity(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Admin Portal');
        $response->assertSee('Dashboard');
        $response->assertSee('Homepage');
        $response->assertSee('Members');
        $response->assertSee('Activities');
        $response->assertSee('Games');
        $response->assertSee('Birthday Wishes');
        $response->assertSee('Cash Management');
        $response->assertSee('Roles / Accounts');

        // Public page must not expose /admin-ganteng
        $publicResponse = $this->get('/');
        $publicResponse->assertStatus(200);
        $publicResponse->assertDontSee('/admin-ganteng');
    }

    public function test_29_s2_homepage_management_accessible(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->get('/admin/homepage');
        $response->assertStatus(200);
    }

    public function test_30_s3_members_activities_accessible_and_no_position_field(): void
    {
        $this->actingAs($this->admin1);

        $this->get('/admin/members')->assertStatus(200);
        $this->get('/admin/activities')->assertStatus(200);

        // Verify public members has no position displayed
        $testMember = Member::create([
            'full_name' => 'S6 Position Exclusion Member',
            'position' => 'Secret Coordinator Role S6',
            'is_active' => true,
        ]);

        $publicMembers = $this->get('/members');
        $publicMembers->assertStatus(200);
        $publicMembers->assertSee('S6 Position Exclusion Member');
        $publicMembers->assertDontSee('Secret Coordinator Role S6');

        $testMember->delete();
    }

    public function test_31_s4_games_management_accessible(): void
    {
        $this->actingAs($this->admin1);

        $this->get('/admin/games')->assertStatus(200);
    }

    public function test_32_s5_birthday_wishes_and_cash_management_accessible(): void
    {
        $this->actingAs($this->admin1);

        $this->get('/admin/birthday-wishes')->assertStatus(200);
        $this->get('/admin/cash-management')->assertStatus(200);
    }
}
