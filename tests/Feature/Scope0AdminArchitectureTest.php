<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope0AdminArchitectureTest extends TestCase
{
    protected User $admin1;
    protected User $admin2;
    protected User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure required Admin 1 exists: admin@gmail.com / admin_utama / admin123
        $this->admin1 = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Utama',
                'username' => 'admin_utama',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Ensure required Admin 2 exists: jojo_ganteng@gmail.com / rilbiezzz / jojo123
        $this->admin2 = User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
            ]
        );

        // Ensure normal user exists: regular_member@gmail.com / regular_member / password123
        $this->normalUser = User::updateOrCreate(
            ['email' => 'regular_member@gmail.com'],
            [
                'name' => 'Regular Member',
                'username' => 'regular_member',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    /**
     * 1. /admin-ganteng displays Admin Login page
     */
    public function test_01_admin_ganteng_displays_admin_login_page(): void
    {
        $response = $this->get('/admin-ganteng');

        $response->assertStatus(200);
        $response->assertViewIs('admin.login');
        $response->assertSee('WARNING! THIS IS THE ADMIN AREA.');
        $response->assertSee('Email / Username');
        $response->assertSee('Password');
        $response->assertSee('Login');

        // Legacy /admin/login route must return 404
        $this->get('/admin/login')->assertStatus(404);
    }

    /**
     * 2. Unauthenticated visitor can see the Admin Login page
     */
    public function test_02_unauthenticated_visitor_can_see_admin_login_page(): void
    {
        $this->assertGuest();
        $response = $this->get('/admin-ganteng');
        $response->assertStatus(200);
    }

    /**
     * 3. Normal user cannot enter Admin Area
     */
    public function test_03_normal_user_cannot_enter_admin_area(): void
    {
        $response = $this->actingAs($this->normalUser)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    /**
     * 4. Admin can authenticate through email
     */
    public function test_04_admin_can_authenticate_through_email(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'admin@gmail.com',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($this->admin1);
        $this->assertTrue(Auth::user()->isAdmin());
    }

    /**
     * 5. Admin can authenticate through username
     */
    public function test_05_admin_can_authenticate_through_username(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'admin_utama',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($this->admin1);
    }

    /**
     * 6. Normal user can authenticate through email
     */
    public function test_06_normal_user_can_authenticate_through_email(): void
    {
        $response = $this->post('/login', [
            'login' => 'regular_member@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($this->normalUser);
    }

    /**
     * 7. Normal user can authenticate through username
     */
    public function test_07_normal_user_can_authenticate_through_username(): void
    {
        $response = $this->post('/login', [
            'login' => 'regular_member',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($this->normalUser);
    }

    /**
     * 8. Incorrect password is rejected
     */
    public function test_08_incorrect_password_is_rejected(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'admin_utama',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /**
     * 9. Unknown email/username is rejected
     */
    public function test_09_unknown_email_or_username_is_rejected(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'completely_unknown_user',
            'password' => 'somepassword',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    /**
     * 10. Non-admin role cannot access Admin Dashboard
     */
    public function test_10_non_admin_role_cannot_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->normalUser)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    /**
     * 11. Admin role can access Admin Dashboard
     */
    public function test_11_admin_role_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin1)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Admin Portal');
        $response->assertSee('Dashboard');
        $response->assertSee('Roles / Accounts');
    }

    /**
     * 12. Username is stored in database
     */
    public function test_12_username_is_stored_in_database(): void
    {
        $user = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($user->username);
        $this->assertEquals('admin_utama', $user->username);
    }

    /**
     * 13. Username is unique
     */
    public function test_13_username_is_unique(): void
    {
        $count = User::where('username', 'admin_utama')->count();
        $this->assertEquals(1, $count);
    }

    /**
     * 14. Duplicate username is rejected
     */
    public function test_14_duplicate_username_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert([
            'name' => 'Duplicate User',
            'username' => 'admin_utama', // already taken by admin1
            'email' => 'duplicate_test@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 15. Authenticated header displays username
     */
    public function test_15_authenticated_header_displays_username(): void
    {
        $response = $this->actingAs($this->admin1)->get('/');
        $response->assertStatus(200);
        $response->assertSee('admin_utama');
    }

    /**
     * 16. Login button disappears after authentication
     */
    public function test_16_login_button_disappears_after_authentication(): void
    {
        $guestResponse = $this->get('/');
        $guestResponse->assertSee('id="btn-header-login"', false);

        $authResponse = $this->actingAs($this->admin1)->get('/');
        $authResponse->assertDontSee('id="btn-header-login"', false);
    }

    /**
     * 17. Username dropdown contains Logout only
     */
    public function test_17_username_dropdown_contains_logout_only(): void
    {
        $response = $this->actingAs($this->admin1)->get('/');
        $response->assertStatus(200);
        $response->assertSee('id="btn-header-logout"', false);
        $response->assertSee('Logout');
        $response->assertDontSee('Edit Profile');
        $response->assertDontSee('Edit Account');
        $response->assertDontSee('Settings');
    }

    /**
     * 18. Logout works
     */
    public function test_18_logout_works(): void
    {
        $this->actingAs($this->admin1);
        $this->assertAuthenticated();

        $response = $this->post('/logout');
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * 19. Successful login displays Welcome, {username}
     */
    public function test_19_successful_login_displays_welcome_username(): void
    {
        $response = $this->post('/login', [
            'login' => 'rilbiezzz',
            'password' => 'jojo123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('welcome_user', 'rilbiezzz');

        // On next request, user sees the welcome toast
        $nextResponse = $this->get('/');
        $nextResponse->assertSee('Welcome, rilbiezzz!');
    }

    /**
     * 20. Welcome notification is not repeatedly triggered by ordinary page refreshes
     */
    public function test_20_welcome_notification_not_repeated_on_refreshes(): void
    {
        // 1. Initial login sets flash
        $this->post('/login', [
            'login' => 'admin_utama',
            'password' => 'admin123',
        ]);

        // 2. First request consumes the flash
        $first = $this->get('/');
        $first->assertSee('Welcome, admin_utama!');

        // 3. Second request (refresh) does not see it anymore
        $second = $this->get('/');
        $second->assertDontSee('Welcome, admin_utama!');
    }

    /**
     * 21. admin@gmail.com / admin123 works
     */
    public function test_21_admin_at_gmail_works(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'admin@gmail.com',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('admin_utama', Auth::user()->username);
    }

    /**
     * 22. jojo_ganteng@gmail.com / jojo123 works
     */
    public function test_22_jojo_ganteng_works(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'jojo_ganteng@gmail.com',
            'password' => 'jojo123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('rilbiezzz', Auth::user()->username);
    }

    /**
     * 23. Admin usernames are admin_utama and rilbiezzz
     */
    public function test_23_admin_usernames_are_admin_utama_and_rilbiezzz(): void
    {
        $u1 = User::where('email', 'admin@gmail.com')->first();
        $u2 = User::where('email', 'jojo_ganteng@gmail.com')->first();

        $this->assertEquals('admin_utama', $u1->username);
        $this->assertEquals('rilbiezzz', $u2->username);
    }

    /**
     * 24. /admin-ganteng is not exposed in public navigation
     */
    public function test_24_admin_ganteng_not_exposed_in_public_navigation(): void
    {
        $publicPages = ['/', '/about', '/activity', '/members', '/game-center'];

        foreach ($publicPages as $page) {
            $response = $this->get($page);
            $response->assertStatus(200);
            $response->assertDontSee('admin-ganteng');
        }
    }

    /**
     * 25. Login Host no longer appears on Game UI
     */
    public function test_25_login_host_no_longer_appears_on_game_ui(): void
    {
        $guessMe = $this->get('/guess-me');
        $guessMe->assertStatus(200);
        $guessMe->assertDontSee('Login Host');

        $growth100 = $this->get('/growth-100');
        $growth100->assertStatus(200);
        $growth100->assertDontSee('Login Host');
    }

    /**
     * 26. Admin routes require admin authorization
     */
    public function test_26_admin_routes_require_admin_authorization(): void
    {
        // Unauthenticated visitor is redirected
        $this->get('/admin/dashboard')->assertRedirect('/admin-ganteng');
        $this->get('/admin/roles')->assertRedirect('/admin-ganteng');

        // Authenticated non-admin is forbidden (403)
        $this->actingAs($this->normalUser)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($this->normalUser)->get('/admin/roles')->assertStatus(403);
    }

    /**
     * 27. Admin cannot delete own authenticated account
     */
    public function test_27_admin_cannot_delete_own_authenticated_account(): void
    {
        $this->actingAs($this->admin1);

        $response = $this->delete("/admin/roles/{$this->admin1->id}");

        $response->assertRedirect('/admin/roles');
        $response->assertSessionHas('error', 'Cannot delete your own active administrator account.');

        // User must still exist in the database
        $this->assertDatabaseHas('users', [
            'id' => $this->admin1->id,
            'email' => 'admin@gmail.com',
        ]);
    }

    /**
     * 28. Admin can manage other users through authorized routes
     */
    public function test_28_admin_can_manage_other_users_through_authorized_routes(): void
    {
        $this->actingAs($this->admin1);

        // Create new account
        $createRes = $this->post('/admin/roles', [
            'name' => 'Managed User',
            'username' => 'managed_user_s0',
            'email' => 'managed_user_s0@example.com',
            'password' => 'password123',
            'role' => 'user',
        ]);

        $createRes->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('users', [
            'username' => 'managed_user_s0',
            'email' => 'managed_user_s0@example.com',
        ]);

        $created = User::where('username', 'managed_user_s0')->first();

        // Update account
        $updateRes = $this->post("/admin/roles/{$created->id}", [
            'name' => 'Managed User Updated',
            'username' => 'managed_user_s0_upd',
            'email' => 'managed_user_s0@example.com',
            'role' => 'admin',
        ]);

        $updateRes->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('users', [
            'id' => $created->id,
            'username' => 'managed_user_s0_upd',
            'role' => 'admin',
        ]);

        // Delete account
        $deleteRes = $this->delete("/admin/roles/{$created->id}");
        $deleteRes->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('users', [
            'id' => $created->id,
        ]);
    }

    /**
     * 29. Existing authentication tests pass (admin can access login, reject invalid)
     */
    public function test_29_existing_auth_flows_work(): void
    {
        $this->get('/admin/login')->assertStatus(404);
        $this->get('/admin-ganteng')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
    }

    /**
     * 30. Existing Birthday R1–R3 access integrity preserved
     */
    public function test_30_existing_birthday_wishes_route_accessible_to_admin(): void
    {
        $this->actingAs($this->admin1);
        $response = $this->get('/birthday-wishes');
        $response->assertStatus(200);
    }

    /**
     * 31. Existing Game tests pass
     */
    public function test_31_game_center_accessible(): void
    {
        $response = $this->get('/game-center');
        $response->assertStatus(200);
    }

    /**
     * 32. Existing Cash tests pass (only admin can access)
     */
    public function test_32_cash_management_protected(): void
    {
        $this->get('/cash-management')->assertRedirect('/admin-ganteng');
        $this->actingAs($this->admin1)->get('/cash-management')->assertStatus(200);
    }

    /**
     * 33. Existing Members/Activities tests pass
     */
    public function test_33_members_and_activities_publicly_viewable(): void
    {
        $this->get('/members')->assertStatus(200);
        $this->get('/activity')->assertStatus(200);
    }
}
