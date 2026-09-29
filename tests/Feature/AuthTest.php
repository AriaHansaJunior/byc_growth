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
        User::firstOrCreate(
            ['email' => 'admin_byc@gmail.com'],
            [
                'name' => 'Admin BYC',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );
    }

    /**
     * Test admin can access login page
     */
    public function test_admin_can_access_login_page(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertViewIs('admin.login');
        $response->assertSee('Sign In');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
    }

    /**
     * Test valid admin credentials can authenticate
     */
    public function test_valid_admin_credentials_can_authenticate(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin_byc@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(Auth::user()->isAdmin());
    }

    /**
     * Test second seeded admin account can authenticate
     */
    public function test_second_seeded_admin_can_authenticate(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'jojo_ganteng@gmail.com',
            'password' => 'password123',
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
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin_byc@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test non-existent user credentials are rejected
     */
    public function test_non_existent_user_is_rejected(): void
    {
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'unknown@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
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
        $response->assertSee($admin->name);
        $response->assertSee('Protected Management Modules');
    }

    /**
     * Test unauthenticated user cannot access protected admin route
     */
    public function test_unauthenticated_user_cannot_access_protected_admin_route(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/admin/login');
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

        $loginResponse->assertRedirect('/admin/login');
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
}
