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
        $response->assertSee($this->adminUser->username);
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

    // ==========================================
    // S1 REVISION TESTS: VIEW & CSS SEPARATION + UI ALIGNMENT
    // ==========================================

    /**
     * 14. User pages resolve from resources/views/user/ and old pages/ directory is removed
     */
    public function test_14_user_views_resolve_from_user_directory(): void
    {
        $this->assertDirectoryDoesNotExist(resource_path('views/pages'));
        $this->assertDirectoryExists(resource_path('views/user'));

        $this->get('/about')->assertStatus(200)->assertViewIs('user.about');
        $this->get('/members')->assertStatus(200)->assertViewIs('user.members');
        $this->get('/activity')->assertStatus(200)->assertViewIs('user.activities');
        $this->get('/game-center')->assertStatus(200)->assertViewIs('user.game-center');
        $this->get('/guess-me')->assertStatus(200)->assertViewIs('user.guess-me');
        $this->get('/growth-100')->assertStatus(200)->assertViewIs('user.growth-100');
    }

    /**
     * 15. Members Admin page uses the User Members visual foundation with administrative controls
     */
    public function test_15_admin_members_uses_user_visual_foundation_with_controls(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/members');

        $response->assertStatus(200);
        // Shared User visual elements
        $response->assertSee('members-grid', false);
        $response->assertSee('member-card', false);
        $response->assertSee('member-photo-frame', false);
        $response->assertSee('member-name', false);
        // Admin controls
        $response->assertSee('id="btn-open-add-member"', false);
        $response->assertSee('btn-edit-member', false);
        $response->assertSee('data-admin-confirm', false);
        $response->assertSee('id="modal-add-member"', false);
        $response->assertSee('id="modal-edit-member"', false);
    }

    /**
     * 16. Activities Admin page uses the User Activities visual foundation with administrative controls
     */
    public function test_16_admin_activities_uses_user_visual_foundation_with_controls(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/activities');

        $response->assertStatus(200);
        // Shared User visual elements
        $response->assertSee('activities-list', false);
        $response->assertSee('activity-card', false);
        $response->assertSee('activity-header', false);
        $response->assertSee('activity-date', false);
        // Admin controls
        $response->assertSee('id="btn-open-add-activity"', false);
        $response->assertSee('btn-edit-activity', false);
        $response->assertSee('id="modal-add-activity"', false);
        $response->assertSee('id="modal-edit-activity"', false);
    }

    /**
     * 17. Games Admin page uses the User Game Center visual foundation with administrative controls
     */
    public function test_17_admin_games_uses_user_visual_foundation_with_controls(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/games');

        $response->assertStatus(200);
        // Shared User visual elements
        $response->assertSee('game-center-hero', false);
        $response->assertSee('game-center-selection', false);
        $response->assertSee('gc-scoreboard', false);
        $response->assertSee('Team Scoreboard', false);
        // Admin controls
        $response->assertSee('data-admin-confirm="Are you sure you want to reset all game scores and active round states?"', false);
    }

    /**
     * 18. User and Admin CSS stylesheets exist and have clear ownership
     */
    public function test_18_user_and_admin_css_stylesheets_exist_with_clear_ownership(): void
    {
        $this->assertFileExists(resource_path('css/user/user.css'));
        $this->assertFileExists(resource_path('css/admin/admin.css'));

        $appCss = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('./user/user.css', $appCss);
        $this->assertStringContainsString('./admin/admin.css', $appCss);
    }

    /**
     * 19. Admin logout redirects to /admin-ganteng (never /admin/login)
     */
    public function test_19_admin_logout_redirects_to_admin_ganteng(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/logout');

        $response->assertRedirect('/admin-ganteng');
        $this->assertGuest();
    }

    /**
     * 20. Dashboard and Roles / Accounts module cards render valid SVG icons
     */
    public function test_20_admin_dashboard_and_roles_icons_render_svg_paths(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertStatus(200);
        // Chart icon for Dashboard card is rendered with SVG path
        $response->assertSee('M3 13.125C3 12.504 3.504 12', false);
        // Shield icon for Roles / Accounts card is rendered with SVG path
        $response->assertSee('M9 12.75 11.25 15 15 9.75', false);
    }

    /**
     * 21. Admin account display and greeting strictly use username
     */
    public function test_21_admin_username_display_uses_username_not_name(): void
    {
        $jojoAdmin = User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
            ]
        );

        $response = $this->actingAs($jojoAdmin)->get('/admin/dashboard');

        $response->assertStatus(200);
        // Username is primary display in header
        $response->assertSee('<strong>rilbiezzz</strong>', false);
        $response->assertDontSee('Jojo Admin (rilbiezzz)');
        // Greeting uses username
        $response->assertSee('Welcome back, rilbiezzz');
        $response->assertDontSee('Welcome back, Jojo Admin');
        // Email and role badge remain intact
        $response->assertSee('jojo_ganteng@gmail.com');
        $response->assertSee('Admin');
    }
}

