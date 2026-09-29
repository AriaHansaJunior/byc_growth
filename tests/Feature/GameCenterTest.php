<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GameCenterTest extends TestCase
{
    /**
     * Test /game-center is publicly accessible without login.
     */
    public function test_game_center_is_accessible_without_login(): void
    {
        $response = $this->get('/game-center');

        $response->assertStatus(200);
        $response->assertViewIs('pages.game-center');
        $response->assertSee('BYC Game Center');
        $response->assertSee('Interactive Gaming Arena');
        $response->assertSee('Choose Your Challenge');
    }

    /**
     * Test Game Center renders game cards with clear play buttons, icons, and English descriptions.
     */
    public function test_game_center_renders_game_cards_with_play_buttons(): void
    {
        $response = $this->get('/game-center');

        $response->assertStatus(200);

        // Guess Me! Card
        $response->assertSee('Guess Me!');
        $response->assertSee('Visual Word Clues');
        $response->assertSee('Test your team speed and intuition');
        $response->assertSee('id="btn-play-guess-me"', false);
        $response->assertSee(route('game.guess-me'));

        // BYC GROWTH 100 Card
        $response->assertSee('BYC GROWTH 100');
        $response->assertSee('Survey Trivia');
        $response->assertSee('Discover the top survey answers');
        $response->assertSee('id="btn-play-growth-100"', false);
        $response->assertSee(route('game.growth-100'));
    }

    /**
     * Test Guess Me gameplay route is reachable from Game Center.
     */
    public function test_guess_me_route_is_reachable(): void
    {
        $response = $this->get(route('game.guess-me'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.guess-me');
        $response->assertSee('Guess Me!');
    }

    /**
     * Test BYC GROWTH 100 gameplay route is reachable from Game Center.
     */
    public function test_growth_100_route_is_reachable(): void
    {
        $response = $this->get(route('game.growth-100'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.growth-100');
        $response->assertSee('BYC Growth 100');
    }

    /**
     * Test authenticated admin user can access Game Center and play games.
     */
    public function test_admin_user_can_access_game_center(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_byc@gmail.com'],
            [
                'name' => 'Admin BYC',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $response = $this->actingAs($admin)->get('/game-center');

        $response->assertStatus(200);
        $response->assertSee('BYC Game Center');
        $response->assertSee('Guess Me!');
        $response->assertSee('BYC GROWTH 100');
    }

    /**
     * Test Game Center contains Back to Home navigation per global rules.
     */
    public function test_game_center_has_back_nav_to_home(): void
    {
        $response = $this->get('/game-center');

        $response->assertStatus(200);
        $response->assertSee('Back to Home');
        $response->assertSee(route('home'));
    }

    /**
     * Test How to Play guide has strictly English text without Indonesian remnants.
     */
    public function test_how_to_play_guide_is_purely_in_english(): void
    {
        $response = $this->get('/game-center');

        $response->assertStatus(200);
        $response->assertSee('How to Play');
        $response->assertSee('Official Game Guide');
        $response->assertSee('Ready, Let\'s Play', false);

        // Verify Indonesian phrases from old version are absent
        $response->assertDontSee('Cara Bermain');
        $response->assertDontSee('Panduan Resmi Permainan');
        $response->assertDontSee('Siap, mulai bermain');
        $response->assertDontSee('Rules Permainan');
    }

    /**
     * Test scoreboard and final standings are displayed on Game Center.
     */
    public function test_game_center_renders_team_scoreboard(): void
    {
        $response = $this->get('/game-center');

        $response->assertStatus(200);
        $response->assertSee('Team Scoreboard');
        $response->assertSee('Overall Standings');
        $response->assertSee('View Final Results');
        $response->assertSee(route('game.final'));
    }
}
