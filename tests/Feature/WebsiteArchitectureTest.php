<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebsiteArchitectureTest extends TestCase
{
    /**
     * Test Homepage renders properly as the main information hub
     */
    public function test_homepage_information_hub_structure(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Header & Brand Navigation (Scope 8)
        $response->assertSee('BYC Growth');
        $response->assertSee('About');
        $response->assertSee('Activity');
        $response->assertSee('Members');
        $response->assertSee('Game Center');

        // Hero Section & Group Photo Area
        $response->assertSee('Growing together in faith');
        $response->assertSee('hero-group-photo');
        $response->assertSee('group-photo-dummy.svg');

        // Scripture Section
        $response->assertSee('For we walk by faith, not by sight');
        $response->assertSee('2 Corinthians 5:7');

        // Destinations Section & Portal Cards
        $response->assertSee('What do you want to go through?');
        $response->assertSee('Game Center');
        $response->assertSee('Cash Management');
        $response->assertSee('Contact the admin to view your cash contribution.');
        $response->assertSee('Members');

        // Global Footer
        $response->assertSee('Want to join us?');
        $response->assertSee('Contact Person');
        $response->assertSee('2026');
        $response->assertSee('Part of Successful Bethany Families');
        $response->assertSee('Gunung Anyar, Surabaya');
    }

    /**
     * Test About Us page renders properly without custom back button and with real contact info
     */
    public function test_about_page_renders_with_back_nav(): void
    {
        $response = $this->get('/about');
        $response->assertStatus(200);
        $response->assertSee('About BYC Growth');
        $response->assertDontSee('Back to Home');
        $response->assertSee('Part of Successful Bethany Families');
        $response->assertSee('Gunung Anyar, Surabaya');
        $response->assertSee('+62 838-5750-9420');
        $response->assertSee('bycgrowthbethany1@gmail.com');
    }

    /**
     * Test Public Activity page renders without custom back button
     */
    public function test_activity_page_renders_with_back_nav(): void
    {
        $response = $this->get('/activity');
        $response->assertStatus(200);
        $response->assertSee('Activities');
        $response->assertDontSee('Back to Home');
    }


    /**
     * Test Members Directory page renders without custom back button
     */
    public function test_members_page_renders_with_back_nav(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Members Directory');
        $response->assertDontSee('Back to Home');
    }

    /**
     * Test Game Center page renders without custom back button and with simplified options
     */
    public function test_game_center_page_renders_with_back_nav(): void
    {
        $response = $this->get('/game-center');
        $response->assertStatus(200);
        $response->assertSee('BYC Game Center');
        $response->assertDontSee('Back to Home');
        $response->assertSee('Guess Me!');
        $response->assertSee('BYC GROWTH 100');
        $response->assertSee('Team Scoreboard');
    }

    /**
     * Test Cash Management portal requires admin authorization
     */
    public function test_cash_management_page_renders_with_back_nav(): void
    {
        // Unauthenticated access must redirect to login
        $unauthRes = $this->get('/cash-management');
        $unauthRes->assertRedirect('/admin-ganteng');

        // Authenticated admin can view Cash Management
        $admin = User::firstOrCreate(
            ['email' => 'admin_byc@gmail.com'],
            ['name' => 'Admin BYC', 'password' => Hash::make('password123'), 'role' => 'admin']
        );

        $response = $this->actingAs($admin)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Cash Management');
        $response->assertDontSee('Back to Home');
        $response->assertSee('Record Contribution');
    }

    /**
     * Test old Contact route safely redirects to combined About & Contact page without 404
     */
    public function test_contact_route_safely_redirects_to_combined_about_page(): void
    {
        $response = $this->get('/contact');
        $response->assertRedirect('/about');

        $followResponse = $this->get('/about');
        $followResponse->assertStatus(200);
        $followResponse->assertSee('About BYC Growth');
        $followResponse->assertSee('Part of Successful Bethany Families');
    }
}
