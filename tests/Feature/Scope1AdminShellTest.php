<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Scope S1 — Admin Shell & Page-Based Management Architecture
 *
 * Verifies:
 * - Admin Access (Dashboard, all 8 domain routes, rejection of non-admins and guests, server-side authorization)
 * - Navigation Architecture (all 8 admin domains in topbar, absence in public navigation)
 * - UI & Layout Structure (reusable admin shell, view site action, confirmation modal foundation, clean public UI)
 */
class Scope1AdminShellTest extends TestCase
{
    protected User $adminUser;
    protected User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure required Admin account
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Utama',
                'username' => 'admin_utama',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        if ($this->adminUser->role !== 'admin' || $this->adminUser->username !== 'admin_utama') {
            $this->adminUser->update(['role' => 'admin', 'username' => 'admin_utama']);
        }

        // Normal User
        $this->normalUser = User::firstOrCreate(
            ['email' => 'member_s1@example.com'],
            [
                'name' => 'Fellowship Member S1',
                'username' => 'member_s1',
                'password' => Hash::make('secret123'),
                'role' => 'user',
            ]
        );
    }

    /**
     * List of all 8 core Admin domain routes established in S1
     */
    protected function getAdminDomainRoutes(): array
    {
        return [
            '/admin/dashboard',
            '/admin/homepage',
            '/admin/members',
            '/admin/activities',
            '/admin/games',
            '/admin/birthday-wishes',
            '/admin/cash-management',
            '/admin/roles',
        ];
    }

    // ==========================================
    // ADMIN ACCESS (1 - 5)
    // ==========================================

    /**
     * 1. Admin can access Admin Dashboard
     */
    public function test_01_admin_can_access_admin_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Portal');
        $response->assertSee('Dashboard');
        $response->assertSee('Protected Management Modules');
        $response->assertSee($this->adminUser->name);
    }

    /**
     * 2. Admin can access each established Admin navigation destination
     */
    public function test_02_admin_can_access_each_established_admin_navigation_destination(): void
    {
        $routes = $this->getAdminDomainRoutes();

        foreach ($routes as $route) {
            $response = $this->actingAs($this->adminUser)->get($route);
            $response->assertStatus(200, "Failed accessing admin route: {$route}");
        }
    }

    /**
     * 3. Normal authenticated user cannot access Admin routes
     */
    public function test_03_normal_authenticated_user_cannot_access_admin_routes(): void
    {
        $routes = $this->getAdminDomainRoutes();

        foreach ($routes as $route) {
            $response = $this->actingAs($this->normalUser)->get($route);
            $response->assertStatus(403, "Normal user was not forbidden on route: {$route}");
        }
    }

    /**
     * 4. Unauthenticated user cannot access protected Admin routes
     */
    public function test_04_unauthenticated_user_cannot_access_protected_admin_routes(): void
    {
        $routes = $this->getAdminDomainRoutes();

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/admin/login', "Guest was not redirected to login on route: {$route}");
        }
    }

    /**
     * 5. Admin authorization remains server-side
     */
    public function test_05_admin_authorization_remains_server_side(): void
    {
        // Untrusted user with crafted session data
        $untrusted = User::firstOrCreate(
            ['email' => 'untrusted_s1@example.com'],
            [
                'name' => 'Untrusted User',
                'username' => 'untrusted_s1',
                'password' => Hash::make('secret123'),
                'role' => 'user',
            ]
        );

        $response = $this->actingAs($untrusted)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    // ==========================================
    // NAVIGATION (6 - 9)
    // ==========================================

    /**
     * 6. Admin navigation contains all intended Admin domains
     */
    public function test_06_admin_navigation_contains_all_intended_admin_domains(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Homepage');
        $response->assertSee('Members');
        $response->assertSee('Activities');
        $response->assertSee('Games');
        $response->assertSee('Birthday Wishes');
        $response->assertSee('Cash Management');
        $response->assertSee('Roles / Accounts');
    }

    /**
     * 7. Public/user navigation does not expose Admin management links
     */
    public function test_07_public_user_navigation_does_not_expose_admin_management_links(): void
    {
        $publicPages = ['/', '/about', '/activity', '/members', '/game-center'];

        foreach ($publicPages as $page) {
            $response = $this->get($page);
            $response->assertStatus(200);

            // Public navigation must not show admin management links
            $response->assertDontSee('/admin/dashboard');
            $response->assertDontSee('/admin/roles');
            $response->assertDontSee('/admin-ganteng');
        }
    }

    /**
     * 8. Birthday Wishes remains available in Admin navigation
     */
    public function test_08_birthday_wishes_remains_available_in_admin_navigation(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Birthday Wishes');
        $response->assertSee(route('admin.birthday-wishes'));
    }

    /**
     * 9. Roles / Accounts remains available in Admin navigation
     */
    public function test_09_roles_accounts_remains_available_in_admin_navigation(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Roles / Accounts');
        $response->assertSee(route('admin.roles'));
    }

    // ==========================================
    // UI & STRUCTURE (10 - 13)
    // ==========================================

    /**
     * 10. Admin layout is shared consistently
     */
    public function test_10_admin_layout_is_shared_consistently(): void
    {
        $pagesToCheck = [
            '/admin/dashboard',
            '/admin/homepage',
            '/admin/members',
            '/admin/activities',
            '/admin/games',
            '/admin/birthday-wishes',
            '/admin/cash-management',
            '/admin/roles',
        ];

        foreach ($pagesToCheck as $path) {
            $res = $this->actingAs($this->adminUser)->get($path);
            $res->assertStatus(200);
            $res->assertSee('admin-shell', false);
            $res->assertSee('admin-topbar', false);
            $res->assertSee('Admin Portal');
            $res->assertSee('id="btn-admin-logout"', false);
            $res->assertSee('id="btn-admin-view-site"', false);
        }
    }

    /**
     * 11. Admin pages use the Admin shell and include confirmation modal foundation
     */
    public function test_11_admin_pages_include_confirmation_modal_foundation(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/members');

        $response->assertStatus(200);
        $response->assertSee('id="admin-confirm-modal"', false);
        $response->assertSee('Confirmation Required');
        $response->assertSee('id="admin-confirm-submit-btn"', false);
    }

    /**
     * 12. View Site points to the public website
     */
    public function test_12_view_site_points_to_public_website(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="btn-admin-view-site"', false);
        $response->assertSee('View Site');
        $response->assertSee(route('home'));
    }

    /**
     * 13. Public website does not receive Admin editing controls
     */
    public function test_13_public_website_does_not_receive_admin_editing_controls(): void
    {
        // Unauthenticated visitor on public pages
        $homeRes = $this->get('/');
        $homeRes->assertStatus(200);
        $homeRes->assertDontSee('id="admin-confirm-modal"', false);
        $homeRes->assertDontSee('btn-admin-edit', false);

        $aboutRes = $this->get('/about');
        $aboutRes->assertStatus(200);
        $aboutRes->assertDontSee('id="admin-confirm-modal"', false);
    }
}
