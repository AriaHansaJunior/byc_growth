<?php

namespace Tests\Feature;

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

        // Header & Brand
        $response->assertSee('BYC Growth');
        $response->assertSee('About');
        $response->assertSee('Members');
        $response->assertSee('Game Center');
        $response->assertSee('Contact Us');

        // Hero Section & Group Photo Area
        $response->assertSee('Growing together in faith');
        $response->assertSee('hero-group-photo');
        $response->assertSee('group-photo-dummy.svg');

        // Scripture Section
        $response->assertSee('For we walk by faith, not by sight');
        $response->assertSee('2 Corinthians 5:7');

        // Destinations Section & 3 Portal Cards
        $response->assertSee('What do you want to go through?');
        $response->assertSee('Game Center');
        $response->assertSee('Cash Management');
        $response->assertSee('Members');

        // Global Footer
        $response->assertSee('Want to join us?');
        $response->assertSee('Contact Person');
        $response->assertSee('2026');
        $response->assertSee('Part of Successful Bethany Families');
        $response->assertSee('Gunung Anyar, Surabaya');
    }

    /**
     * Test About Us page renders with back navigation
     */
    public function test_about_page_renders_with_back_nav(): void
    {
        $response = $this->get('/about');
        $response->assertStatus(200);
        $response->assertSee('About BYC Growth');
        $response->assertSee('Back to Home');
        $response->assertSee('Successful Bethany Families');
    }

    /**
     * Test Members Directory structural page renders with back navigation
     */
    public function test_members_page_renders_with_back_nav(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Members Directory');
        $response->assertSee('Back to Home');
        $response->assertSee('Structural Foundation');
    }

    /**
     * Test Game Center page renders with back navigation and game options
     */
    public function test_game_center_page_renders_with_back_nav(): void
    {
        $response = $this->get('/game-center');
        $response->assertStatus(200);
        $response->assertSee('BYC Game Center');
        $response->assertSee('Back to Home');
        $response->assertSee('Guess Me!');
        $response->assertSee('BYC Growth 100');
        $response->assertSee('Team Scoreboard');
    }

    /**
     * Test Cash Management structural page renders with back navigation
     */
    public function test_cash_management_page_renders_with_back_nav(): void
    {
        $response = $this->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Cash Management');
        $response->assertSee('Back to Home');
        $response->assertSee('Structural Foundation');
    }

    /**
     * Test Contact page renders with back navigation and church details
     */
    public function test_contact_page_renders_with_back_nav(): void
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Contact BYC Growth');
        $response->assertSee('Back to Home');
        $response->assertSee('Gunung Anyar, Surabaya');
        $response->assertSee('+62 812-3456-7890');
    }
}
