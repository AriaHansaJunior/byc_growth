<?php

namespace Tests\Feature;

use App\Models\BirthdayLetter;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Scope R2 — Birthday Popup + Members Integration Test Suite
 *
 * Verifies:
 * - Birthday detection (Surabaya Asia/Jakarta timezone)
 * - Conditional rendering: ZERO modal HTML when no birthdays exist
 * - Multi-member sequencing data and lock duration formula (N × 5 seconds)
 * - Single-member lock duration (5 seconds)
 * - Members page presentation ordering (celebrants first, non-celebrants follow deterministically)
 * - Birthday visual state on Members page (only on birthday date, presentation only, no DOB/role leaks)
 * - Interaction persistence after popup dismissal via Members page
 * - Auth session independence from popup cooldown
 * - Login redirect preservation of birthday context
 * - Full R1 access control, invariant, and authorization preservation
 */
class ScopeR2BirthdayPopupTest extends TestCase
{
    protected Carbon $now;
    protected User $adminUser;
    protected User $senderUser;
    protected Member $senderMember;
    protected Member $celebrantA;
    protected Member $celebrantB;
    protected Member $celebrantC;
    protected Member $nonCelebrantA;
    protected Member $nonCelebrantB;
    protected Member $inactiveCelebrant;

    protected function setUp(): void
    {
        parent::setUp();

        // Use 2026-07-07 (July 7) which has zero preexisting DB collisions
        $this->now = Carbon::create(2026, 7, 7, 10, 0, 0, 'Asia/Jakarta');
        Carbon::setTestNow($this->now);

        $todayMonthDay = $this->now->format('m-d'); // 07-07

        // 3 Celebrants for today
        $this->celebrantA = Member::create([
            'full_name' => 'Aria Celebrant ' . uniqid(),
            'date_of_birth' => '1995-' . $todayMonthDay,
            'is_active' => true,
        ]);

        $this->celebrantB = Member::create([
            'full_name' => 'Bintang Celebrant ' . uniqid(),
            'date_of_birth' => '1998-' . $todayMonthDay,
            'is_active' => true,
        ]);

        $this->celebrantC = Member::create([
            'full_name' => 'Chandra Celebrant ' . uniqid(),
            'date_of_birth' => '1996-' . $todayMonthDay,
            'is_active' => true,
        ]);

        // 2 Non-Celebrants
        $this->nonCelebrantA = Member::create([
            'full_name' => 'Daniel NonCelebrant ' . uniqid(),
            'date_of_birth' => '1992-05-15',
            'is_active' => true,
        ]);

        $this->nonCelebrantB = Member::create([
            'full_name' => 'Eka NonCelebrant ' . uniqid(),
            'date_of_birth' => '1994-11-20',
            'is_active' => true,
        ]);

        // 1 Inactive Celebrant (must NOT appear)
        $this->inactiveCelebrant = Member::create([
            'full_name' => 'Inactive Celebrant ' . uniqid(),
            'date_of_birth' => '1993-' . $todayMonthDay,
            'is_active' => false,
        ]);

        // Sender Member & User
        $this->senderMember = Member::create([
            'full_name' => 'Sender Fellowship ' . uniqid(),
            'date_of_birth' => '1999-01-01',
            'is_active' => true,
        ]);

        $this->senderUser = User::create([
            'name' => 'Sender Account',
            'email' => 'sender_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->senderMember->id,
        ]);

        // Admin User
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_r2@bycgrowth.org'],
            [
                'name' => 'Admin R2',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        $this->senderUser->delete();
        $this->senderMember->delete();

        $this->celebrantA->delete();
        $this->celebrantB->delete();
        $this->celebrantC->delete();
        $this->nonCelebrantA->delete();
        $this->nonCelebrantB->delete();
        $this->inactiveCelebrant->delete();

        parent::tearDown();
    }

    /**
     * 1. No birthday -> no birthday popup data/render
     */
    public function test_01_no_birthday_no_birthday_popup_data_render(): void
    {
        // Move to July 8 where zero members celebrate birthdays
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 10, 0, 0, 'Asia/Jakarta'));

        $response = $this->get('/');
        $response->assertStatus(200);

        // View composer shares empty collection
        $birthdayMembers = $response->viewData('birthdayMembers');
        $this->assertNotNull($birthdayMembers);
        $this->assertTrue($birthdayMembers->isEmpty());

        // Zero modal HTML rendered
        $response->assertDontSee('modal-birthday-popup');
        $response->assertDontSee('Special Birthday Greeting!');
        $response->assertDontSee('birthday-modal-card');
    }

    /**
     * 2. One birthday -> correct celebrant
     */
    public function test_02_one_birthday_correct_celebrant(): void
    {
        // Deactivate celebrant B and C so only celebrant A has a birthday on July 7
        $this->celebrantB->update(['is_active' => false]);
        $this->celebrantC->update(['is_active' => false]);

        $response = $this->get('/');
        $response->assertStatus(200);

        $birthdayMembers = $response->viewData('birthdayMembers');
        $this->assertCount(1, $birthdayMembers);
        $this->assertEquals($this->celebrantA->id, $birthdayMembers->first()->id);

        $response->assertSee('modal-birthday-popup');
        $response->assertSee($this->celebrantA->full_name);
        // Single celebrant lock duration = 1 * 5 = 5 seconds
        $response->assertSee('data-lock-duration="5"', false);
        // Multi-member controls must NOT be rendered for single celebrant
        $response->assertDontSee('id="birthday-nav-controls"', false);
    }

    /**
     * 3. Multiple birthdays -> all detected
     */
    public function test_03_multiple_birthdays_all_detected(): void
    {
        $response = $this->getJson('/birthday/today');
        $response->assertStatus(200);

        $members = collect($response->json('members'));
        $memberIds = $members->pluck('id')->all();

        $this->assertContains($this->celebrantA->id, $memberIds);
        $this->assertContains($this->celebrantB->id, $memberIds);
        $this->assertContains($this->celebrantC->id, $memberIds);

        // Inactive member must NOT be detected
        $this->assertNotContains($this->inactiveCelebrant->id, $memberIds);
        // Non-celebrants must NOT be detected
        $this->assertNotContains($this->nonCelebrantA->id, $memberIds);
        $this->assertNotContains($this->nonCelebrantB->id, $memberIds);
    }

    /**
     * 4. Birthday members appear first on Members page
     */
    public function test_04_birthday_members_appear_first_on_members_page(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);

        /** @var \Illuminate\Database\Eloquent\Collection $members */
        $members = $response->viewData('members');
        $this->assertNotNull($members);

        // The first 3 members must all be celebrants
        $firstThree = $members->take(3);
        $this->assertCount(3, $firstThree);
        foreach ($firstThree as $m) {
            $this->assertTrue($m->isBirthdayToday(), "Member {$m->full_name} should be celebrating today.");
        }
    }

    /**
     * 5. Non-birthday members remain after birthday members
     */
    public function test_05_non_birthday_members_remain_after_birthday_members(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);

        /** @var \Illuminate\Database\Eloquent\Collection $members */
        $members = $response->viewData('members');

        $celebrantIds = [$this->celebrantA->id, $this->celebrantB->id, $this->celebrantC->id];
        $nonCelebrantIds = [$this->nonCelebrantA->id, $this->nonCelebrantB->id, $this->senderMember->id];

        $celebrantIndices = [];
        $nonCelebrantIndices = [];

        foreach ($members->values() as $idx => $m) {
            if (in_array($m->id, $celebrantIds)) {
                $celebrantIndices[] = $idx;
            } elseif (in_array($m->id, $nonCelebrantIds)) {
                $nonCelebrantIndices[] = $idx;
            }
        }

        // Maximum celebrant index must be smaller than minimum non-celebrant index
        $maxCelebrantIndex = max($celebrantIndices);
        $minNonCelebrantIndex = min($nonCelebrantIndices);

        $this->assertLessThan($minNonCelebrantIndex, $maxCelebrantIndex);
    }

    /**
     * 6. Birthday visual state exists for today's birthday members
     */
    public function test_06_birthday_visual_state_exists_for_todays_birthday_members(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);

        // Check celebratory card classes and Send Wishes button
        $response->assertSee('member-card-birthday');
        $response->assertSee('birthday-badge-pin');
        $response->assertSee('btn-member-send-wishes');
        $response->assertSee('data-id="' . $this->celebrantA->id . '"', false);
    }

    /**
     * 7. Birthday visual state absent on non-birthday date
     */
    public function test_07_birthday_visual_state_absent_on_non_birthday_date(): void
    {
        // Switch to July 8 where zero celebrants exist
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 10, 0, 0, 'Asia/Jakarta'));

        $response = $this->get('/members');
        $response->assertStatus(200);

        $response->assertDontSee('member-card-birthday');
        $response->assertDontSee('birthday-badge-pin');
        $response->assertDontSee('btn-member-send-wishes');
    }

    /**
     * 8. Birthday ordering is not permanently stored in DB
     */
    public function test_08_birthday_ordering_is_not_permanently_stored_in_db(): void
    {
        // Fetch original IDs and verify no permanent mutation
        $originalDbOrder = Member::where('is_active', true)->orderBy('id')->pluck('id')->all();

        // Visit Members page
        $this->get('/members');

        $afterVisitDbOrder = Member::where('is_active', true)->orderBy('id')->pluck('id')->all();
        $this->assertEquals($originalDbOrder, $afterVisitDbOrder);

        // Visit on non-birthday date: returns to pure alphabetical order
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 10, 0, 0, 'Asia/Jakarta'));
        $response = $this->get('/members');
        $members = $response->viewData('members');

        $names = $members->pluck('full_name')->all();
        $sortedNames = $names;
        sort($sortedNames, SORT_STRING | SORT_FLAG_CASE);

        $this->assertEquals($sortedNames, $names, "Members must be strictly alphabetical when no birthdays exist.");
    }

    /**
     * 9. Popup endpoint exposes only today's celebrants
     */
    public function test_09_popup_endpoint_exposes_only_todays_celebrants(): void
    {
        $response = $this->getJson('/birthday/today');
        $response->assertStatus(200);

        $members = $response->json('members');
        foreach ($members as $m) {
            $memberModel = Member::find($m['id']);
            $this->assertNotNull($memberModel);
            $this->assertTrue($memberModel->isBirthdayToday());
            $this->assertTrue($memberModel->is_active);
        }
    }

    /**
     * 10. Ordinary navigation does not require repeated popup
     */
    public function test_10_ordinary_navigation_does_not_require_repeated_popup(): void
    {
        // Routes load cleanly without forcing modal reappearance on ordinary navigation
        $routes = ['/', '/about', '/members', '/game-center'];
        foreach ($routes as $route) {
            $res = $this->get($route);
            $res->assertStatus(200);
            // Script contains cooldown key and duration check
            if ($res->viewData('birthdayMembers') && $res->viewData('birthdayMembers')->isNotEmpty()) {
                $res->assertSee('byc_birthday_popup_last_shown');
                $res->assertSee('3 * 60 * 60 * 1000', false);
            }
        }
    }

    /**
     * 11. Authenticated user remains authenticated independently of birthday cooldown
     */
    public function test_11_authenticated_user_remains_authenticated_independently_of_birthday_cooldown(): void
    {
        $this->actingAs($this->senderUser);

        // Simulating visiting with no cooldown state or deleted cookie
        $response = $this->get('/members');
        $response->assertStatus(200);

        $this->assertAuthenticated();
        $this->assertEquals($this->senderUser->id, Auth::id());
    }

    /**
     * 12. Unauthenticated Write a Letter redirects to login
     */
    public function test_12_unauthenticated_write_a_letter_redirects_to_login(): void
    {
        Auth::logout();

        // Non-JSON letter submission redirects to login
        $response = $this->post('/birthday/letter', [
            'member_id' => $this->celebrantA->id,
            'message' => 'Happy Birthday!',
        ]);

        $response->assertRedirect('/admin-ganteng');
    }

    /**
     * 13. Authenticated Write a Letter preserves birthday context
     */
    public function test_13_authenticated_write_a_letter_preserves_birthday_context(): void
    {
        // Test redirect query preservation on login form
        $redirectUrl = '/members?birthday_letter=1&member_id=' . $this->celebrantA->id;
        $loginPageRes = $this->get('/admin-ganteng?redirect=' . urlencode($redirectUrl));
        $loginPageRes->assertStatus(200);
        $loginPageRes->assertSee('value="' . e($redirectUrl) . '"', false);

        // Test login redirects directly back to the birthday context
        $loginPostRes = $this->post('/admin-ganteng', [
            'email' => $this->senderUser->email,
            'password' => 'password123',
            'redirect' => $redirectUrl,
        ]);

        $loginPostRes->assertRedirect($redirectUrl);
    }

    /**
     * 14. Popup data contains enough information for multi-member sequencing
     */
    public function test_14_popup_data_contains_enough_information_for_multi_member_sequencing(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // With 3 celebrants: duration is 3 * 5 = 15 seconds
        $response->assertSee('data-celebrants-count="3"', false);
        $response->assertSee('data-lock-duration="15"', false);
        $response->assertSee('1 of 3 celebrating today');
        $response->assertSee('id="btn-birthday-prev"', false);
        $response->assertSee('id="btn-birthday-next"', false);
    }

    /**
     * 15. No birthday popup exists when there are zero celebrants
     */
    public function test_15_no_birthday_popup_exists_when_there_are_zero_celebrants(): void
    {
        // Switch to a date with zero celebrants (July 8)
        Carbon::setTestNow(Carbon::create(2026, 7, 8, 10, 0, 0, 'Asia/Jakarta'));

        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertDontSee('modal-birthday-popup');
        $response->assertDontSee('Special Birthday Greeting!');
    }

    /**
     * 16. Current birthday interaction remains available after popup closure
     */
    public function test_16_current_birthday_interaction_remains_available_after_popup_closure(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);

        // Celebrant cards continue exposing Send Wishes action even if popup closed
        $response->assertSee('btn-member-send-wishes');
        $response->assertSee('data-id="' . $this->celebrantA->id . '"', false);
        $response->assertSee('window.openBirthdayLetter');
    }

    /**
     * 17. Existing R1 letter authorization remains intact
     */
    public function test_17_existing_r1_letter_authorization_remains_intact(): void
    {
        // Unauthenticated sender is rejected
        Auth::logout();
        $unauthRes = $this->postJson('/birthday/letter', [
            'member_id' => $this->celebrantA->id,
            'message' => 'Unauthenticated letter',
        ]);
        $unauthRes->assertStatus(401);

        // Self-wishing is rejected
        $this->actingAs($this->senderUser);
        $selfWishRes = $this->postJson('/birthday/letter', [
            'member_id' => $this->senderMember->id,
            'message' => 'Wishing myself happy birthday',
        ]);
        $selfWishRes->assertStatus(422);
        $selfWishRes->assertJson([
            'success' => false,
            'message' => 'You cannot send a birthday letter to yourself.',
        ]);
    }

    /**
     * 18. Existing one-letter-per-year constraint remains intact
     */
    public function test_18_existing_one_letter_per_year_constraint_remains_intact(): void
    {
        $this->actingAs($this->senderUser);

        // First letter succeeds
        $res1 = $this->postJson('/birthday/letter', [
            'member_id' => $this->celebrantA->id,
            'message' => 'First message for 2026',
        ]);
        $res1->assertStatus(200);
        $letterId = $res1->json('letter_id');

        // Duplicate letter rejected
        $res2 = $this->postJson('/birthday/letter', [
            'member_id' => $this->celebrantA->id,
            'message' => 'Second duplicate message attempt',
        ]);
        $res2->assertStatus(422);
        $res2->assertJson([
            'success' => false,
            'message' => 'You have already sent a birthday letter to this member for this year.',
        ]);

        BirthdayLetter::destroy($letterId);
    }

    /**
     * 19. Existing anonymous sender behavior remains intact
     */
    public function test_19_existing_anonymous_sender_behavior_remains_intact(): void
    {
        $this->actingAs($this->senderUser);

        $res = $this->postJson('/birthday/letter', [
            'member_id' => $this->celebrantA->id,
            'is_anonymous' => 1,
            'message' => 'Secret blessing message',
        ]);
        $res->assertStatus(200);
        $letterId = $res->json('letter_id');

        $letter = BirthdayLetter::findOrFail($letterId);
        $this->assertTrue($letter->is_anonymous);
        $this->assertEquals('Anonymous', $letter->display_name);
        $this->assertEquals($this->senderUser->id, $letter->user_id);

        $letter->delete();
    }

    /**
     * 20. Existing admin access remains intact
     */
    public function test_20_existing_admin_access_remains_intact(): void
    {
        $this->actingAs($this->adminUser);

        $res = $this->get('/admin/dashboard');
        $res->assertStatus(200);
        $res->assertSee('Admin Portal');

        $rolesRes = $this->get('/admin/roles');
        $rolesRes->assertStatus(200);
    }
}
