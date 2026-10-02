<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the two required initial admin accounts exist
        User::updateOrCreate(
            ['email' => 'admin_byc@gmail.com'],
            [
                'name' => 'Admin BYC',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
            ]
        );
    }

    /**
     * Test admin can access login page at /admin-ganteng and /admin/login is removed (404)
     */
    public function test_admin_can_access_login_page(): void
    {
        $response = $this->get('/admin-ganteng');

        $response->assertStatus(200);
        $response->assertViewIs('admin.login');
        $response->assertSee('Admin Sign In');
        $response->assertSee('Email / Username');
        $response->assertSee('Password');

        // Legacy /admin/login route must return 404
        $legacy = $this->get('/admin/login');
        $legacy->assertStatus(404);

        // /admin root route must return 404 to avoid leaking hidden /admin-ganteng URL
        $adminRoot = $this->get('/admin');
        $adminRoot->assertStatus(404);
    }

    /**
     * Test valid admin credentials can authenticate through /admin-ganteng
     */
    public function test_valid_admin_credentials_can_authenticate(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'admin_byc@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(Auth::user()->isAdmin());
        $response->assertCookie(Auth::guard()->getRecallerName());
    }

    /**
     * Test admin login UI has Return to Website, Admin Portal, and Remember Me removed, and logo centered
     */
    public function test_admin_login_ui_elements_cleaned_up(): void
    {
        $response = $this->get('/admin-ganteng');
        $response->assertStatus(200);

        // "Return to Website" is absent
        $response->assertDontSee('Return to Website');

        // "ADMIN PORTAL" is absent
        $response->assertDontSee('Admin Portal');
        $response->assertDontSee('ADMIN PORTAL');

        // "Remember this device" is absent
        $response->assertDontSee('Remember this device');
        $response->assertDontSee('name="remember"');

        // BYC Growth logo is centered horizontally
        $response->assertSee('justify-content: center');
        $response->assertSee('byc-logo.png');
    }

    /**
     * Test authentication persists across subsequent requests and by default without remember checkbox
     */
    public function test_authentication_is_persistent_by_default_without_remember_checkbox(): void
    {
        $loginResponse = $this->post('/admin-ganteng', [
            'login' => 'admin_byc@gmail.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();

        // The remember recaller cookie is queued automatically
        $loginResponse->assertCookie(Auth::guard()->getRecallerName());

        $admin = User::where('email', 'admin_byc@gmail.com')->first();
        $this->assertNotNull($admin->remember_token);

        // Subsequent requests remain authenticated
        $dashboardResponse = $this->get('/admin/dashboard');
        $dashboardResponse->assertStatus(200);
    }

    /**
     * Test visiting /admin-ganteng while already authenticated redirects to dashboard
     */
    public function test_visiting_admin_ganteng_while_authenticated_redirects_to_dashboard(): void
    {
        $admin = User::where('email', 'admin_byc@gmail.com')->first();
        $response = $this->actingAs($admin)->get('/admin-ganteng');
        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * Test logout invalidates authentication and protected routes require auth again
     */
    public function test_logout_invalidates_auth_and_protected_routes_require_login(): void
    {
        $admin = User::where('email', 'admin_byc@gmail.com')->first();
        $this->actingAs($admin);

        $logoutResponse = $this->post('/admin/logout');
        $logoutResponse->assertRedirect('/admin-ganteng');
        $this->assertGuest();

        // Protected route redirects back to login
        $protectedResponse = $this->get('/admin/dashboard');
        $protectedResponse->assertRedirect('/admin-ganteng');
    }

    /**
     * Test second seeded admin account can authenticate through /admin-ganteng
     */
    public function test_second_seeded_admin_can_authenticate(): void
    {
        $response = $this->post('/admin-ganteng', [
            'login' => 'jojo_ganteng@gmail.com',
            'password' => 'jojo123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertEquals('jojo_ganteng@gmail.com', Auth::user()->email);
    }

    /**
     * Test invalid credentials are rejected with validation errors
     */
    public function test_invalid_credentials_are_rejected(): void
    {
        $response = $this->from('/admin-ganteng')->post('/admin-ganteng', [
            'login' => 'admin_byc@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/admin-ganteng');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test non-existent user credentials are rejected
     */
    public function test_non_existent_user_is_rejected(): void
    {
        $response = $this->from('/admin-ganteng')->post('/admin-ganteng', [
            'login' => 'unknown@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin-ganteng');
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /**
     * Test authenticated admin can access protected admin route
     */
    public function test_authenticated_admin_can_access_protected_admin_route(): void
    {
        $admin = User::where('email', 'admin_byc@gmail.com')->first();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Admin Portal');
        $response->assertSee($admin->username ?? $admin->name);
        $response->assertSee('Protected Management Modules');
    }

    /**
     * Test unauthenticated user cannot access protected admin route
     */
    public function test_unauthenticated_user_cannot_access_protected_admin_route(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/admin-ganteng');
        $this->assertGuest();
    }

    /**
     * Test non-admin authenticated user is denied access to protected admin route
     */
    public function test_non_admin_user_cannot_access_protected_admin_route(): void
    {
        $regularUser = User::firstOrCreate(
            ['email' => 'regular_member@gmail.com'],
            [
                'name' => 'Regular Member',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );

        $response = $this->actingAs($regularUser)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test public routes remain accessible without login
     */
    public function test_public_routes_remain_accessible_without_login(): void
    {
        $publicRoutes = [
            '/',
            '/about',
            '/activity',
            '/members',
            '/game-center',
            '/guess-me',
            '/growth-100',
        ];

        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }

        // Old contact route redirects safely to combined about page without 404
        $this->get('/contact')->assertRedirect('/about');
    }


    /**
     * Test logout invalidates the authenticated session
     */
    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $admin = User::where('email', 'admin_byc@gmail.com')->first();

        $loginResponse = $this->actingAs($admin)->post('/admin/logout');

        $loginResponse->assertRedirect('/admin-ganteng');
        $this->assertGuest();
    }

    /**
     * Test passwords are stored hashed and never in plaintext
     */
    public function test_passwords_are_stored_hashed(): void
    {
        $admin = User::where('email', 'admin_byc@gmail.com')->first();

        $this->assertNotNull($admin);
        // Password in DB must NOT equal the plaintext 'password123'
        $this->assertNotEquals('password123', $admin->password);
        // Must verify against Hash check
        $this->assertTrue(Hash::check('password123', $admin->password));
    }

    /**
     * Test normal user authentication persists indefinitely by default without remember option
     */
    public function test_normal_user_authentication_persists_by_default_without_remember_option(): void
    {
        $regularUser = User::firstOrCreate(
            ['email' => 'regular_member@gmail.com'],
            [
                'name' => 'Regular Member',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );

        // 1. Log in without passing any 'remember' parameter
        $loginResponse = $this->post('/login', [
            'login' => 'regular_member@gmail.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/');
        $this->assertAuthenticatedAs($regularUser);

        // 2. Persistent recaller cookie is queued automatically
        $loginResponse->assertCookie(Auth::guard()->getRecallerName());
        $this->assertNotNull($regularUser->fresh()->remember_token);

        // 3. Subsequent requests remain authenticated
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);

        // 4. Visiting /login while authenticated redirects to home
        $loginPageResponse = $this->get('/login');
        $loginPageResponse->assertRedirect('/');

        // 5. Explicit logout terminates the session
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/');
        $this->assertGuest();

        // 6. After logout, user is unauthenticated
        $this->assertNull(Auth::user());
    }
}
