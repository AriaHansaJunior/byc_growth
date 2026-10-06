<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemVerificationTest extends TestCase
{
    protected function createAdminUser(): User
    {
        return User::create([
            'name' => 'System Auditor Admin',
            'username' => 'auditor_admin_' . uniqid(),
            'email' => 'admin_' . uniqid() . '@bycgrowth.test',
            'password' => Hash::make('Secret123!'),
            'role' => 'admin',
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
        $this->assertAuthenticatedAs($admin);
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
}
