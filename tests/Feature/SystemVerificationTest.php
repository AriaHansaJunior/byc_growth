<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemVerificationTest extends TestCase
{
    protected function createAdminUser(array $permissions = null): User
    {
        return User::create([
            'name' => 'System Auditor Admin',
            'username' => 'auditor_admin_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
            'permissions' => $permissions ?? array_keys(User::AVAILABLE_PERMISSIONS),
        ]);
    }

    protected function createNormalUser(): User
    {
        return User::create([
            'name' => 'Fellowship Member',
            'username' => 'member_' . uniqid(),
            'email' => 'member_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'member',
        ]);
    }

    /**
     * Test public pages respond with 200 OK without requiring authentication.
     */
    public function test_public_pages_are_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response = $this->get('/about');
        $response->assertStatus(200);

        $response = $this->get('/activity');
        $response->assertStatus(200);

        $response = $this->get('/members');
        $response->assertStatus(200);

        $response = $this->get('/game-center');
        $response->assertStatus(200);

        $response = $this->get('/guess-me');
        $response->assertStatus(200);

        $response = $this->get('/growth-100');
        $response->assertStatus(200);

        $response = $this->get('/final');
        $response->assertStatus(200);

        // Contact redirects to combined About page
        $response = $this->get('/contact');
        $response->assertRedirect('/about');
    }

    /**
     * Test authentication pages exist and resolve.
     */
    public function test_auth_routes_resolve(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);

        // Sole hidden admin entrance
        $response = $this->get('/admin-ganteng');
        $response->assertStatus(200);
    }

    /**
     * Test guest is redirected from admin portals to hidden admin entrance.
     */
    public function test_guest_is_redirected_from_admin_portals(): void
    {
        $adminRoutes = [
            '/admin/dashboard',
            '/admin/homepage',
            '/admin/activities',
            '/admin/members',
            '/admin/games',
            '/admin/birthday-wishes',
            '/admin/cash-management',
            '/admin/roles',
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->get($route);
            // Admin auth middleware redirects guests to the sole admin login entrance
            $response->assertRedirect('/admin-ganteng');
        }
    }

    /**
     * Test non-admin authenticated users cannot access admin portals.
     */
    public function test_normal_member_forbidden_from_admin_portals(): void
    {
        $user = $this->createNormalUser();

        $response = $this->actingAs($user)->get('/admin/dashboard');
        // Admin middleware aborts with 403 or redirects to home
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));

        $response = $this->actingAs($user)->get('/admin/roles');
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));
    }

    /**
     * Test administrator can access all admin management portals.
     */
    public function test_admin_can_access_all_management_portals(): void
    {
        $admin = $this->createAdminUser();

        $routes = [
            '/admin/dashboard',
            '/admin/homepage',
            '/admin/activities',
            '/admin/members',
            '/admin/games',
            '/admin/birthday-wishes',
            '/admin/cash-management',
            '/admin/roles',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            $response->assertStatus(200);
        }
    }

    /**
     * Test public gameplay state update endpoints.
     */
    public function test_game_state_endpoints_work(): void
    {
        $response = $this->postJson('/game/guess-me/state', [
            'round_index' => 1,
            'is_revealed' => true,
        ]);
        $response->assertStatus(200);

        $response = $this->postJson('/game/growth-100/state', [
            'round_index' => 1,
            'strikes' => 2,
        ]);
        $response->assertStatus(200);

        $response = $this->postJson('/game/growth-100/reset-revealed');
        $response->assertStatus(200);
    }

    /**
     * Test member validation and creation via admin controller.
     */
    public function test_admin_member_crud_validation(): void
    {
        $admin = $this->createAdminUser();

        // Validation error on empty name
        $response = $this->actingAs($admin)->post('/admin/members', [
            'full_name' => '',
        ]);
        $response->assertSessionHasErrors(['full_name']);

        // Successful creation
        $name = 'Verification Member ' . uniqid();
        $response = $this->actingAs($admin)->post('/admin/members', [
            'full_name' => $name,
            'date_of_birth' => '2000-05-15',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('members', ['full_name' => $name]);
    }

    /**
     * Test cash management validation and transaction persistence with proof upload.
     */
    public function test_cash_management_transaction_persistence(): void
    {
        $admin = $this->createAdminUser();
        $member = Member::create([
            'full_name' => 'Cash Test Member ' . uniqid(),
            'date_of_birth' => '1998-03-20',
            'is_active' => true,
        ]);

        // Missing required fields
        $response = $this->actingAs($admin)->post('/admin/cash-management', []);
        $response->assertSessionHasErrors(['member_id', 'account_type', 'amount', 'proof']);

        // Successful transaction creation with fake image upload
        $fakeProof = \Illuminate\Http\UploadedFile::fake()->image('receipt.jpg');
        $response = $this->actingAs($admin)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Bank Transfer (BCA)',
            'amount' => 50000,
            'proof' => $fakeProof,
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
            'amount' => 50000,
            'account_type' => 'Bank Transfer (BCA)',
        ]);
    }

    /**
     * Test normal user login and authentication flow.
     */
    public function test_user_can_login_with_credentials(): void
    {
        $password = 'SecretPass123!';
        $user = User::create([
            'name' => 'Login User Test',
            'username' => 'testuser_' . uniqid(),
            'email' => 'testuser_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make($password),
            'role' => 'user',
        ]);

        $response = $this->post('/login', [
            'login' => $user->username,
            'password' => $password,
        ]);
        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test admin login at hidden /admin-ganteng entrance.
     */
    public function test_admin_can_login_at_admin_ganteng(): void
    {
        $password = 'AdminPass123!';
        $admin = User::create([
            'name' => 'Admin Gate Test',
            'username' => 'testadmin_' . uniqid(),
            'email' => 'testadmin_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $response = $this->post('/admin-ganteng', [
            'login' => $admin->email,
            'password' => $password,
        ]);
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    /**
     * Test admin cannot login via normal user portal.
     */
    public function test_admin_cannot_login_at_user_portal(): void
    {
        $password = 'AdminPass123!';
        $admin = User::create([
            'name' => 'Admin Separation Test',
            'username' => 'admin_sep_' . uniqid(),
            'email' => 'admin_sep_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'login' => $admin->username,
            'password' => $password,
        ]);
        $response->assertSessionHasErrors([
            'login' => 'Administrator accounts cannot log in to the User portal.',
        ]);
        $errors = session('errors')->get('login');
        $this->assertCount(1, $errors);
        $this->assertGuest('web');
    }

    /**
     * Test standard user cannot login via admin entrance.
     */
    public function test_user_cannot_login_at_admin_ganteng(): void
    {
        $password = 'UserPass123!';
        $user = User::create([
            'name' => 'User Gate Test',
            'username' => 'user_sep_' . uniqid(),
            'email' => 'user_sep_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make($password),
            'role' => 'user',
        ]);

        $response = $this->post('/admin-ganteng', [
            'login' => $user->username,
            'password' => $password,
        ]);
        $response->assertSessionHasErrors([
            'login' => 'Standard user accounts do not have administrator access.',
        ]);
        $errors = session('errors')->get('login');
        $this->assertCount(1, $errors);
        $this->assertGuest('admin');
    }

    /**
     * Test admin self-delete protection.
     */
    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->delete("/admin/roles/{$admin->id}");
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /**
     * Test birthday today endpoint.
     */
    public function test_birthday_today_endpoint(): void
    {
        $response = $this->getJson('/birthday/today');
        $response->assertStatus(200);
        $response->assertJsonStructure(['date', 'count', 'members']);
    }

    /**
     * Test creating a member with a newly created user account.
     */
    public function test_member_creation_with_new_user_account(): void
    {
        $admin = $this->createAdminUser();
        $name = 'Member Account Test ' . uniqid();
        $email = 'newmember_' . uniqid() . '@bycgrowth.test';

        $response = $this->actingAs($admin, 'admin')->post('/admin/members', [
            'full_name' => $name,
            'date_of_birth' => '2001-08-20',
            'user_id' => '__new__',
            'new_user_email' => $email,
            'new_user_password' => 'password123',
        ]);

        $response->assertSessionHasNoErrors();
        $member = Member::where('full_name', $name)->first();
        $this->assertNotNull($member);

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals($member->id, $user->member_id);
        $this->assertEquals('user', $user->role);
    }

    /**
     * Test updating a member to link an existing user account.
     */
    public function test_member_update_linking_existing_user_account(): void
    {
        $admin = $this->createAdminUser();
        $user = User::create([
            'name' => 'Standalone User',
            'username' => 'stand_user_' . uniqid(),
            'email' => 'stand_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $member = Member::create([
            'full_name' => 'Member To Link ' . uniqid(),
            'date_of_birth' => '1999-11-12',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put("/admin/members/{$member->id}", [
            'full_name' => $member->full_name,
            'user_id' => $user->id,
        ]);

        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertEquals($member->id, $user->member_id);
    }

    /**
     * Test birthday recipient with linked user account can access wishes.
     */
    public function test_birthday_recipient_can_access_wishes(): void
    {
        $member = Member::create([
            'full_name' => 'Aria Celebrant ' . uniqid(),
            'date_of_birth' => '1995-10-07',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => $member->full_name,
            'username' => 'aria_' . uniqid(),
            'email' => 'aria_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $member->id,
        ]);

        $response = $this->actingAs($user, 'web')->get('/birthday-wishes');
        $response->assertStatus(200);
        $response->assertSee('Birthday Wishes for ' . $member->full_name);
    }

    /**
     * Test one-letter limit per recipient per year, multi-recipient capability, and letter update.
     */
    public function test_letter_limits_and_update_flow(): void
    {
        $sender = User::create([
            'name' => 'Sender User',
            'username' => 'sender_' . uniqid(),
            'email' => 'sender_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $today = \Carbon\Carbon::now('Asia/Jakarta');
        $memberA = Member::create([
            'full_name' => 'Celebrant A ' . uniqid(),
            'date_of_birth' => $today->copy()->subYears(20)->format('Y-m-d'),
            'is_active' => true,
        ]);
        $memberB = Member::create([
            'full_name' => 'Celebrant B ' . uniqid(),
            'date_of_birth' => $today->copy()->subYears(22)->format('Y-m-d'),
            'is_active' => true,
        ]);

        // 1. Send to Member A -> Success
        $resA = $this->actingAs($sender, 'web')->postJson('/birthday/letter', [
            'member_id' => $memberA->id,
            'message' => 'Happy birthday Celebrant A!',
        ]);
        $resA->assertStatus(200);
        $resA->assertJson(['success' => true]);
        $letterIdA = $resA->json('letter_id');

        // 2. Send again to Member A in same year -> Fails (422)
        $resA2 = $this->actingAs($sender, 'web')->postJson('/birthday/letter', [
            'member_id' => $memberA->id,
            'message' => 'Another wish for A',
        ]);
        $resA2->assertStatus(422);
        $resA2->assertJson(['success' => false]);

        // 3. Send to Member B -> Success (different person celebrating)
        $resB = $this->actingAs($sender, 'web')->postJson('/birthday/letter', [
            'member_id' => $memberB->id,
            'message' => 'Happy birthday Celebrant B!',
        ]);
        $resB->assertStatus(200);
        $resB->assertJson(['success' => true]);

        // 4. Update wish for Member A on birthday -> Success
        $resUpdate = $this->actingAs($sender, 'web')->postJson("/birthday/letter/{$letterIdA}", [
            'message' => 'Updated heartfelt blessing for A!',
            'is_anonymous' => 1,
        ]);
        $resUpdate->assertStatus(200);
        $resUpdate->assertJson(['success' => true]);
        $this->assertDatabaseHas('birthday_letters', [
            'id' => $letterIdA,
            'message' => 'Updated heartfelt blessing for A!',
            'is_anonymous' => 1,
        ]);
    }
}
