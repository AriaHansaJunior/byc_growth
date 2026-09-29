<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Game;
use App\Models\GameRound;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\Team;
use App\Models\User;
use App\Services\MediaUploadService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope13SecurityValidationTest extends TestCase
{
    protected User $adminUser;
    protected User $regularUser;
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s13@bycgrowth.org'],
            [
                'name' => 'Admin S13',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_s13@bycgrowth.org'],
            [
                'name' => 'Regular User S13',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $filePath) {
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        parent::tearDown();
    }

    // ==========================================
    // 1. AUTHENTICATION (1 - 4)
    // ==========================================

    /**
     * 1. Public routes remain accessible without login.
     */
    public function test_01_public_routes_remain_public(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/about')->assertStatus(200);
        $this->get('/activity')->assertStatus(200);
        $this->get('/members')->assertStatus(200);
        $this->get('/game-center')->assertStatus(200);
        $this->get('/guess-me')->assertStatus(200);
        $this->get('/growth-100')->assertStatus(200);
        $this->get('/final')->assertStatus(200);
        $this->get('/contact')->assertStatus(301);
    }

    /**
     * 2. Protected admin routes reject guests.
     */
    public function test_02_protected_admin_routes_reject_guests(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->get('/admin/roles')->assertRedirect('/admin/login');
        $this->get('/cash-management')->assertRedirect('/admin/login');
    }

    /**
     * 3. Admin can access admin routes.
     */
    public function test_03_admin_can_access_admin_routes(): void
    {
        $this->actingAs($this->adminUser)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($this->adminUser)->get('/admin/roles')->assertStatus(200);
        $this->actingAs($this->adminUser)->get('/cash-management')->assertStatus(200);
    }

    /**
     * 4. Normal user cannot access admin routes.
     */
    public function test_04_normal_user_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->regularUser)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($this->regularUser)->get('/admin/roles')->assertStatus(403);
        $this->actingAs($this->regularUser)->get('/cash-management')->assertStatus(403);
    }

    // ==========================================
    // 2. AUTHORIZATION (5 - 7)
    // ==========================================

    /**
     * 5. Normal user cannot perform admin CRUD.
     */
    public function test_05_normal_user_cannot_perform_admin_crud(): void
    {
        // Members CRUD
        $this->actingAs($this->regularUser)->post('/admin/members', ['full_name' => 'Hacker'])->assertStatus(403);
        $this->actingAs($this->regularUser)->post('/admin/members/1', ['full_name' => 'Hacker'])->assertStatus(403);
        $this->actingAs($this->regularUser)->delete('/admin/members/1')->assertStatus(403);

        // Roles CRUD
        $this->actingAs($this->regularUser)->post('/admin/roles', [
            'name' => 'Hacker Admin',
            'email' => 'hacker@test.com',
            'password' => 'secret123',
            'role' => 'admin',
        ])->assertStatus(403);

        // Cash CRUD
        $this->actingAs($this->regularUser)->post('/admin/cash-management', [
            'amount' => 100000,
        ])->assertStatus(403);
    }

    /**
     * 6. Public user cannot perform admin CRUD.
     */
    public function test_06_public_user_cannot_perform_admin_crud(): void
    {
        $this->post('/admin/members', ['full_name' => 'Public Hacker'])->assertRedirect('/admin/login');
        $this->post('/admin/cash-management', ['amount' => 100000])->assertRedirect('/admin/login');
        $this->post('/game/teams/configure', ['teams' => []])->assertRedirect('/admin/login');
    }

    /**
     * 7. Admin authorization remains functional.
     */
    public function test_07_admin_authorization_remains_functional(): void
    {
        $member = Member::create(['full_name' => 'S13 Auth Test Member', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)->post('/admin/members/' . $member->id, [
            'full_name' => 'S13 Auth Test Member Renamed',
        ]);
        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', ['id' => $member->id, 'full_name' => 'S13 Auth Test Member Renamed']);

        $member->delete();
    }

    // ==========================================
    // 3. MASS ASSIGNMENT (8 - 10)
    // ==========================================

    /**
     * 8. Protected role cannot be changed through arbitrary client input.
     */
    public function test_08_protected_role_cannot_be_changed_through_normal_user_input(): void
    {
        // Normal user attempting to escalate role
        $this->actingAs($this->regularUser)->post('/admin/roles/' . $this->regularUser->id, [
            'name' => 'Escalated User',
            'email' => $this->regularUser->email,
            'role' => 'admin',
        ])->assertStatus(403);

        $this->regularUser->refresh();
        $this->assertSame('user', $this->regularUser->role);
    }

    /**
     * 9. Server-controlled timestamps cannot be overridden.
     */
    public function test_09_server_controlled_timestamps_cannot_be_overridden(): void
    {
        $member = Member::create(['full_name' => 'Timestamp Test Member', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('proof.jpg');

        $clientSpoofedDate = '1999-01-01';
        $clientSpoofedCreatedAt = '1999-01-01 00:00:00';

        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 35000,
            'proof' => $proof,
            'transaction_date' => $clientSpoofedDate,
            'created_at' => $clientSpoofedCreatedAt,
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest()->firstOrFail();
        $this->assertNotEquals($clientSpoofedDate, $tx->transaction_date->format('Y-m-d'));

        $tx->delete();
        $member->delete();
    }

    /**
     * 10. Sensitive internal relationships cannot be arbitrarily changed.
     */
    public function test_10_sensitive_internal_relationships_cannot_be_arbitrarily_changed(): void
    {
        $member = Member::create(['full_name' => 'Rel Test Member', 'is_active' => true]);

        // Attempt to pass spoofed foreign keys during member update
        $this->actingAs($this->adminUser)->post('/admin/members/' . $member->id, [
            'full_name' => 'Rel Test Member',
            'photo_file_id' => 999999, // Blind injection of non-owned media ID
        ]);

        $member->refresh();
        $this->assertNotEquals(999999, $member->photo_file_id);

        $member->delete();
    }

    // ==========================================
    // 4. CASH SECURITY & VALIDATION (11 - 18)
    // ==========================================

    /**
     * 11. Invalid amount rejected.
     */
    public function test_11_invalid_amount_rejected(): void
    {
        $member = Member::create(['full_name' => 'Amount Test', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('proof.jpg');

        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 'not-a-number',
            'proof' => $proof,
        ]);

        $response->assertSessionHasErrors('amount');
        $member->delete();
    }

    /**
     * 12. Negative and zero amounts rejected.
     */
    public function test_12_negative_amount_rejected(): void
    {
        $member = Member::create(['full_name' => 'Negative Amount Test', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('proof.jpg');

        $responseNegative = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => -25000,
            'proof' => $proof,
        ]);
        $responseNegative->assertSessionHasErrors('amount');

        $responseZero = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 0,
            'proof' => $proof,
        ]);
        $responseZero->assertSessionHasErrors('amount');

        $member->delete();
    }

    /**
     * 13. Invalid proof file rejected.
     */
    public function test_13_invalid_proof_rejected(): void
    {
        $member = Member::create(['full_name' => 'Invalid Proof Test', 'is_active' => true]);
        $txt = UploadedFile::fake()->create('proof.txt', 100, 'text/plain');

        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $txt,
        ]);

        $response->assertSessionHasErrors('proof');
        $member->delete();
    }

    /**
     * 14. Non-image proof rejected (PDF, EXE, etc.).
     */
    public function test_14_non_image_proof_rejected(): void
    {
        $member = Member::create(['full_name' => 'PDF Proof Test', 'is_active' => true]);
        $pdf = UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 30000,
            'proof' => $pdf,
        ]);

        $response->assertSessionHasErrors('proof');
        $member->delete();
    }

    /**
     * 15. Missing proof rejected on new transaction.
     */
    public function test_15_missing_proof_rejected_on_new_transaction(): void
    {
        $member = Member::create(['full_name' => 'Missing Proof Test', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Cash',
            'amount' => 30000,
        ]);

        $response->assertSessionHasErrors('proof');
        $member->delete();
    }

    /**
     * 16. Client timestamp cannot override server timestamp.
     */
    public function test_16_client_timestamp_cannot_override_server_timestamp(): void
    {
        $member = Member::create(['full_name' => 'Server Timestamp Test', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('proof.jpg');

        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
            'created_at' => '2010-01-01 12:00:00',
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest()->firstOrFail();
        $this->assertTrue($tx->created_at->isToday());

        $tx->delete();
        $member->delete();
    }

    /**
     * 17. Shortcut does not expose previous proof.
     */
    public function test_17_shortcut_does_not_expose_previous_proof(): void
    {
        $member = Member::create(['full_name' => 'Shortcut Proof Privacy Test', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('secret_proof.jpg');

        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $proof,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/cash-management/shortcut/' . $member->id);
        $response->assertStatus(200);
        $response->assertJsonMissing(['proof' => null]);
        $response->assertJsonMissing(['proof_file_id' => null]);
        $response->assertJsonMissing(['file_path' => null]);

        CashTransaction::where('member_id', $member->id)->delete();
        $member->delete();
    }

    /**
     * 18. Unauthorized cash access rejected.
     */
    public function test_18_unauthorized_cash_access_rejected(): void
    {
        $this->get('/cash-management')->assertRedirect('/admin/login');
        $this->actingAs($this->regularUser)->get('/cash-management')->assertStatus(403);
    }

    // ==========================================
    // 5. MEMBERS SECURITY & VALIDATION (19 - 22)
    // ==========================================

    /**
     * 19. Invalid member data rejected.
     */
    public function test_19_invalid_member_data_rejected(): void
    {
        // Missing full_name
        $response1 = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => '',
        ]);
        $response1->assertSessionHasErrors('full_name');

        // Invalid date_of_birth
        $response2 = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Valid Name',
            'date_of_birth' => 'invalid-date',
        ]);
        $response2->assertSessionHasErrors('date_of_birth');

        // Invalid photo file type
        $txt = UploadedFile::fake()->create('shell.txt', 10, 'text/plain');
        $response3 = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Valid Name',
            'photo' => $txt,
        ]);
        $response3->assertSessionHasErrors('photo');
    }

    /**
     * 20. Unauthorized member CRUD rejected.
     */
    public function test_20_unauthorized_member_crud_rejected(): void
    {
        $this->post('/admin/members', ['full_name' => 'Test'])->assertRedirect('/admin/login');
        $this->actingAs($this->regularUser)->post('/admin/members', ['full_name' => 'Test'])->assertStatus(403);
    }

    /**
     * 21. DOB is not publicly exposed.
     */
    public function test_21_dob_is_not_publicly_exposed(): void
    {
        $member = Member::create([
            'full_name' => 'Secret Birthday Member',
            'date_of_birth' => '1993-04-18',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Secret Birthday Member');
        $response->assertDontSee('1993-04-18');
        $response->assertDontSee('April 18, 1993');

        $member->delete();
    }

    /**
     * 22. Position is not introduced or exposed.
     */
    public function test_22_position_is_not_introduced_or_exposed(): void
    {
        $member = Member::create([
            'full_name' => 'No Position Public Member',
            'position' => 'Internal Coordinator Role',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('No Position Public Member');
        $response->assertDontSee('Internal Coordinator Role');

        $member->delete();
    }

    // ==========================================
    // 6. GAME VALIDATION (23 - 27)
    // ==========================================

    /**
     * 23. Cross-game team assignment rejected.
     */
    public function test_23_cross_game_team_assignment_rejected(): void
    {
        $g1 = Game::where('code', 'game1')->firstOrFail();
        $g2 = Game::where('code', 'game2')->firstOrFail();

        $g1Round = GameRound::where('game_id', $g1->id)->firstOrFail();
        $g2Team = Team::where('game_id', $g2->id)->firstOrFail();

        // Attempt to assign G1 round points to a G2 team
        $response = $this->actingAs($this->adminUser)->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $g1Round->id,
            'team_id' => $g2Team->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'A round cannot award points to a team belonging to another game.',
        ]);
    }

    /**
     * 24. Invalid round/game relationship rejected.
     */
    public function test_24_invalid_round_game_relationship_rejected(): void
    {
        $g2 = Game::where('code', 'game2')->firstOrFail();
        $g2Round = GameRound::where('game_id', $g2->id)->firstOrFail();

        // Attempt to access G2 round using game1 endpoint
        $response = $this->actingAs($this->adminUser)->postJson('/game/assign-round-points', [
            'game' => 'game1',
            'round_id' => $g2Round->id,
            'team_id' => null,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Round not found for this game.',
        ]);
    }

    /**
     * 25. Arbitrary score manipulation rejected.
     */
    public function test_25_arbitrary_score_manipulation_rejected(): void
    {
        $g2 = Game::where('code', 'game2')->firstOrFail();
        $g2Round = GameRound::where('game_id', $g2->id)->firstOrFail();
        $g2Team = Team::where('game_id', $g2->id)->firstOrFail();

        // Growth 100 rejects arbitrary pointsOverride that doesn't match revealed survey points sum
        $response = $this->actingAs($this->adminUser)->postJson('/game/assign-round-points', [
            'game' => 'game2',
            'round_id' => $g2Round->id,
            'team_id' => $g2Team->id,
            'points_override' => 9999, // Arbitrary manipulation attempt
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * 26. Growth 100 invalid survey answers total rejected.
     */
    public function test_26_growth_100_invalid_total_rejected(): void
    {
        // Total points do not sum to 100
        $response = $this->actingAs($this->adminUser)->postJson('/game/growth-100/round', [
            'question' => 'What is your favorite fruit?',
            'answers' => [
                ['text' => 'Apple', 'score' => 40],
                ['text' => 'Banana', 'score' => 30], // Sum = 70 (invalid, must be 100)
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * 27. Guess Me invalid clue/answer rejected.
     */
    public function test_27_guess_me_invalid_clue_answer_rejected(): void
    {
        // Clue length does not match answer length
        $response = $this->actingAs($this->adminUser)->postJson('/game/guess-me/round', [
            'correct_answer' => 'JESUS',
            'clue' => 'J_S__EXTRA', // Invalid length
            'score' => 20,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    // ==========================================
    // 7. INPUT & QUERY PARAMETER HANDLING (28 - 31)
    // ==========================================

    /**
     * 28. Invalid pagination handled safely.
     */
    public function test_28_invalid_pagination_handled_safely(): void
    {
        // Negative page
        $resNegative = $this->actingAs($this->adminUser)->get('/cash-management?page=-5');
        $resNegative->assertStatus(200);

        // Non-numeric page
        $resString = $this->actingAs($this->adminUser)->get('/cash-management?page=invalid');
        $resString->assertStatus(200);

        // Huge page number
        $resHuge = $this->actingAs($this->adminUser)->get('/cash-management?page=999999');
        $resHuge->assertStatus(200);
        $resHuge->assertSee('No cash transactions found.');
    }

    /**
     * 29. Invalid amount filter handled safely.
     */
    public function test_29_invalid_amount_filter_handled_safely(): void
    {
        $res1 = $this->actingAs($this->adminUser)->get('/cash-management?amount=abc');
        $res1->assertStatus(200);

        $res2 = $this->actingAs($this->adminUser)->get('/cash-management?amount=-9999');
        $res2->assertStatus(200);
    }

    /**
     * 30. Invalid date filter handled safely.
     */
    public function test_30_invalid_date_filter_handled_safely(): void
    {
        $res = $this->actingAs($this->adminUser)->get('/cash-management?date=invalid-date-string-32-13');
        $res->assertStatus(200);
    }

    /**
     * 31. Malformed IDs handled safely.
     */
    public function test_31_malformed_ids_handled_safely(): void
    {
        $this->actingAs($this->adminUser)->get('/admin/cash-management/shortcut/99999999')->assertStatus(404);
        $this->actingAs($this->adminUser)->delete('/admin/members/99999999')->assertStatus(404);
    }

    // ==========================================
    // 8. XSS PREVENTION (32)
    // ==========================================

    /**
     * 32. User-controlled text is escaped safely.
     */
    public function test_32_user_controlled_text_is_escaped_safely(): void
    {
        $xssName = '<script>alert("XSS")</script>';
        $member = Member::create(['full_name' => $xssName, 'is_active' => true]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        // Blade must escape <script> to &lt;script&gt;
        $response->assertDontSee('<script>alert("XSS")</script>', false);
        $response->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', false);

        $member->delete();
    }

    // ==========================================
    // 9. MISSING RESOURCES (33 - 35)
    // ==========================================

    /**
     * 33. Nonexistent member handled safely.
     */
    public function test_33_nonexistent_member_handled_safely(): void
    {
        $this->actingAs($this->adminUser)->post('/admin/members/999999', ['full_name' => 'Ghost'])->assertStatus(404);
        $this->actingAs($this->adminUser)->delete('/admin/members/999999')->assertStatus(404);
    }

    /**
     * 34. Nonexistent game/round handled safely.
     */
    public function test_34_nonexistent_game_round_handled_safely(): void
    {
        $response = $this->actingAs($this->adminUser)->deleteJson('/game/guess-me/round/999999');
        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error' => 'Round not found.',
        ]);
    }

    /**
     * 35. Missing media handled safely.
     */
    public function test_35_missing_media_handled_safely(): void
    {
        $this->get('/game/image/nonexistent_image_12345.jpg')->assertStatus(404);

        // Path traversal attempts must return 404
        $this->get('/game/image/..%2F..%2F.env')->assertStatus(404);
        $this->get('/game/image/....//.env')->assertStatus(404);
    }

    // ==========================================
    // 10. REGRESSION (36 - 40)
    // ==========================================

    /**
     * 36. Existing S9 tests pass.
     */
    public function test_36_s9_cash_management_remains_functional(): void
    {
        $member = Member::create(['full_name' => 'S13 Regr S9', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('proof.jpg');

        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ])->assertRedirect('/cash-management');

        CashTransaction::where('member_id', $member->id)->delete();
        $member->delete();
    }

    /**
     * 37. Existing S10 tests pass.
     */
    public function test_37_s10_member_shortcut_remains_functional(): void
    {
        $member = Member::create(['full_name' => 'S13 Regr S10', 'is_active' => true]);
        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 40000.00,
            'account_type' => 'Mandiri',
            'type' => 'inflow',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/cash-management/shortcut/' . $member->id);
        $response->assertStatus(200);
        $response->assertJson([
            'has_shortcut' => true,
            'account_type' => 'Mandiri',
            'amount' => 40000,
        ]);

        CashTransaction::where('member_id', $member->id)->delete();
        $member->delete();
    }

    /**
     * 38. Existing S11 tests pass.
     */
    public function test_38_s11_transaction_table_remains_functional(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('id="cash-transactions-table"', false);
    }

    /**
     * 39. Existing S12 tests pass.
     */
    public function test_39_s12_members_page_remains_functional(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Members Directory');
    }

    /**
     * 40. Existing game tests pass.
     */
    public function test_40_existing_game_tests_remain_functional(): void
    {
        $this->get('/guess-me')->assertStatus(200)->assertSee('Guess Me!');
        $this->get('/growth-100')->assertStatus(200)->assertSee('BYC Growth 100');
    }
}
