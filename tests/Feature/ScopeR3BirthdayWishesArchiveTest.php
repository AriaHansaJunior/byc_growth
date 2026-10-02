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
 * Scope R3 — Birthday Wishes & Archive Test Suite
 *
 * Verifies:
 * - Access control between Admin, Birthday Recipient, Senders, Normal Users, and Guests
 * - Automatic year-based archiving (no manual flags, strict boundary on future years)
 * - Filtering by year and member (server-side enforced, no client-side trust)
 * - Sender self-review and edit capabilities during recipient's birthday
 * - Privacy protection (anonymity preserved for recipient, full audit trail for admin)
 * - No unauthorized letter exposure
 * - Full preservation of R1 foundation and R2 popup features
 */
class ScopeR3BirthdayWishesArchiveTest extends TestCase
{
    protected Carbon $now;
    protected User $adminUser;
    protected User $celebrantUser;
    protected Member $celebrantMember;
    protected User $nonCelebrantUser;
    protected Member $nonCelebrantMember;
    protected User $sender1User;
    protected Member $sender1Member;
    protected User $sender2User;
    protected Member $sender2Member;
    protected User $normalUser;
    protected Member $normalMember;

    protected BirthdayLetter $letter2026Named;
    protected BirthdayLetter $letter2026Anon;
    protected BirthdayLetter $letter2025Archive;
    protected BirthdayLetter $letter2024Archive;
    protected BirthdayLetter $letterOtherRecipient;

    protected function setUp(): void
    {
        parent::setUp();

        // Use 2026-07-15 which has zero preexisting DB collisions
        $this->now = Carbon::create(2026, 7, 15, 10, 0, 0, 'Asia/Jakarta');
        Carbon::setTestNow($this->now);

        // 1. Celebrant Member & User (Birthday is July 15)
        $this->celebrantMember = Member::create([
            'full_name' => 'Celebrant Person ' . uniqid(),
            'date_of_birth' => '1995-07-15',
            'is_active' => true,
        ]);
        $this->celebrantUser = User::create([
            'name' => 'Celebrant Account',
            'email' => 'celebrant_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->celebrantMember->id,
        ]);

        // 2. Non-Celebrant Member & User (Birthday is January 20)
        $this->nonCelebrantMember = Member::create([
            'full_name' => 'Other Member ' . uniqid(),
            'date_of_birth' => '1993-01-20',
            'is_active' => true,
        ]);
        $this->nonCelebrantUser = User::create([
            'name' => 'Other Member Account',
            'email' => 'other_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->nonCelebrantMember->id,
        ]);

        // 3. Sender 1 Member & User
        $this->sender1Member = Member::create([
            'full_name' => 'Sender One ' . uniqid(),
            'date_of_birth' => '1998-03-10',
            'is_active' => true,
        ]);
        $this->sender1User = User::create([
            'name' => 'Sender 1 Account',
            'email' => 'sender1_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->sender1Member->id,
        ]);

        // 4. Sender 2 Member & User
        $this->sender2Member = Member::create([
            'full_name' => 'Sender Two ' . uniqid(),
            'date_of_birth' => '1997-09-05',
            'is_active' => true,
        ]);
        $this->sender2User = User::create([
            'name' => 'Sender 2 Account',
            'email' => 'sender2_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->sender2Member->id,
        ]);

        // 5. Normal User with no letters
        $this->normalMember = Member::create([
            'full_name' => 'Bystander Member ' . uniqid(),
            'date_of_birth' => '1994-11-12',
            'is_active' => true,
        ]);
        $this->normalUser = User::create([
            'name' => 'Bystander Account',
            'email' => 'bystander_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->normalMember->id,
        ]);

        // 6. Admin User
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_r3@bycgrowth.org'],
            [
                'name' => 'Admin R3',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Seed Letters:
        // Letter 1: Sender 1 -> Celebrant (2026, Named)
        $this->letter2026Named = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->sender1User->id,
            'birthday_year' => 2026,
            'is_anonymous' => false,
            'sender_name' => $this->sender1Member->full_name,
            'message' => 'Happy 2026 Birthday Celebrant from Sender One!',
        ]);

        // Letter 2: Sender 2 -> Celebrant (2026, Anonymous)
        $this->letter2026Anon = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->sender2User->id,
            'birthday_year' => 2026,
            'is_anonymous' => true,
            'sender_name' => 'Anonymous',
            'message' => 'Secret Anonymous Blessings for 2026!',
        ]);

        // Letter 3: Sender 1 -> Celebrant (2025 Archive)
        $this->letter2025Archive = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->sender1User->id,
            'birthday_year' => 2025,
            'is_anonymous' => false,
            'sender_name' => $this->sender1Member->full_name,
            'message' => 'Archived 2025 Wish: God bless you abundantly!',
        ]);

        // Letter 4: Sender 2 -> Celebrant (2024 Archive)
        $this->letter2024Archive = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->sender2User->id,
            'birthday_year' => 2024,
            'is_anonymous' => true,
            'sender_name' => 'Anonymous',
            'message' => 'Archived 2024 Wish: Grace upon grace!',
        ]);

        // Letter 5: Sender 1 -> Other Member (2026)
        $this->letterOtherRecipient = BirthdayLetter::create([
            'member_id' => $this->nonCelebrantMember->id,
            'user_id' => $this->sender1User->id,
            'birthday_year' => 2026,
            'is_anonymous' => false,
            'sender_name' => $this->sender1Member->full_name,
            'message' => 'Wish to other member for January.',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        BirthdayLetter::whereIn('id', [
            $this->letter2026Named->id,
            $this->letter2026Anon->id,
            $this->letter2025Archive->id,
            $this->letter2024Archive->id,
            $this->letterOtherRecipient->id,
        ])->delete();

        $this->celebrantUser->delete();
        $this->celebrantMember->delete();
        $this->nonCelebrantUser->delete();
        $this->nonCelebrantMember->delete();
        $this->sender1User->delete();
        $this->sender1Member->delete();
        $this->sender2User->delete();
        $this->sender2Member->delete();
        $this->normalUser->delete();
        $this->normalMember->delete();

        parent::tearDown();
    }

    // ==========================================
    // ACCESS CONTROL (1 - 7)
    // ==========================================

    /**
     * 1. Unauthenticated user cannot access Birthday Wishes
     */
    public function test_01_unauthenticated_user_cannot_access_birthday_wishes(): void
    {
        Auth::logout();

        $response = $this->get('/birthday-wishes');
        $response->assertRedirectContains('/admin-ganteng');
    }

    /**
     * 2. Admin can access anytime
     */
    public function test_02_admin_can_access_anytime(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(200);
        $response->assertViewIs('user.birthday-wishes');
        $response->assertSee('Birthday Wishes Directory');
        $response->assertSee('Administrator Full Archive Access');
    }

    /**
     * 3. Birthday recipient can access on birthday
     */
    public function test_03_birthday_recipient_can_access_on_birthday(): void
    {
        // Today is July 15, which is celebrant's birthday
        $this->actingAs($this->celebrantUser);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(200);
        $response->assertSee('Birthday Wishes for ' . $this->celebrantMember->full_name);
        $response->assertSee('Happy 2026 Birthday Celebrant from Sender One!');
    }

    /**
     * 4. Birthday recipient cannot access outside birthday
     */
    public function test_04_birthday_recipient_cannot_access_outside_birthday(): void
    {
        // Change date to July 16 (day after birthday)
        Carbon::setTestNow(Carbon::create(2026, 7, 16, 10, 0, 0, 'Asia/Jakarta'));

        $this->actingAs($this->celebrantUser);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(403);
    }

    /**
     * 5. Non-birthday user cannot browse received wishes
     */
    public function test_05_non_birthday_user_cannot_browse_received_wishes(): void
    {
        $this->actingAs($this->normalUser);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(403);
    }

    /**
     * 6. Sender can see only their own wish for today's birthday recipient
     */
    public function test_06_sender_can_see_only_their_own_wish_for_todays_birthday_recipient(): void
    {
        $this->actingAs($this->sender1User);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(200);
        $response->assertSee('My Birthday Wishes');
        $response->assertSee('Happy 2026 Birthday Celebrant from Sender One!');
        $response->assertSee('To:');
        $response->assertSee($this->celebrantMember->full_name);
    }

    /**
     * 7. Sender cannot see another sender's wish
     */
    public function test_07_sender_cannot_see_another_senders_wish(): void
    {
        $this->actingAs($this->sender1User);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(200);

        // Sender 1 must NOT see Sender 2's wish
        $response->assertDontSee('Secret Anonymous Blessings for 2026!');
    }

    // ==========================================
    // ARCHIVE SYSTEM (8 - 12)
    // ==========================================

    /**
     * 8. Current birthday year is visible
     */
    public function test_08_current_birthday_year_is_visible(): void
    {
        $this->actingAs($this->celebrantUser);

        $response = $this->get('/birthday-wishes?year=2026');
        $response->assertStatus(200);
        $response->assertSee('Happy 2026 Birthday Celebrant from Sender One!');
        $response->assertSee('Secret Anonymous Blessings for 2026!');
    }

    /**
     * 9. Previous birthday years are visible to recipient
     */
    public function test_09_previous_birthday_years_are_visible_to_recipient(): void
    {
        $this->actingAs($this->celebrantUser);

        $response2025 = $this->get('/birthday-wishes?year=2025');
        $response2025->assertStatus(200);
        $response2025->assertSee('Archived 2025 Wish: God bless you abundantly!');
        $response2025->assertSee('Archived Year');

        $response2024 = $this->get('/birthday-wishes?year=2024');
        $response2024->assertStatus(200);
        $response2024->assertSee('Archived 2024 Wish: Grace upon grace!');
    }

    /**
     * 10. Future years are not visible
     */
    public function test_10_future_years_are_not_visible(): void
    {
        $this->actingAs($this->celebrantUser);

        // Attempting to query future year 2027 or 2030 is forbidden
        $response = $this->get('/birthday-wishes?year=2027');
        $response->assertStatus(403);

        $responseFarFuture = $this->get('/birthday-wishes?year=2099');
        $responseFarFuture->assertStatus(403);
    }

    /**
     * 11. Year filtering works
     */
    public function test_11_year_filtering_works(): void
    {
        $this->actingAs($this->celebrantUser);

        $response2025 = $this->get('/birthday-wishes?year=2025');
        $response2025->assertStatus(200);
        $response2025->assertSee('Archived 2025 Wish');
        // 2026 messages must NOT appear on 2025 filter
        $response2025->assertDontSee('Happy 2026 Birthday Celebrant from Sender One!');
    }

    /**
     * 12. Recipient cannot use year/member parameters to access another member's archive
     */
    public function test_12_recipient_cannot_use_parameters_to_access_another_members_archive(): void
    {
        $this->actingAs($this->celebrantUser);

        // Celebrant passes member_id parameter attempting to view nonCelebrantMember's letters
        $response = $this->get('/birthday-wishes?member_id=' . $this->nonCelebrantMember->id);
        $response->assertStatus(200);

        // Server strictly scopes query to celebrantMember: must NOT contain nonCelebrantMember's letters
        $response->assertDontSee('Wish to other member for January.');
    }

    // ==========================================
    // ADMIN CAPABILITIES (13 - 16)
    // ==========================================

    /**
     * 13. Admin can view all members
     */
    public function test_13_admin_can_view_all_members(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(200);
        // Can see wishes addressed to Celebrant
        $response->assertSee('Happy 2026 Birthday Celebrant from Sender One!');
        // Can also see wishes addressed to Other Member
        $response->assertSee('Wish to other member for January.');
    }

    /**
     * 14. Admin can filter by member
     */
    public function test_14_admin_can_filter_by_member(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/birthday-wishes?member_id=' . $this->celebrantMember->id);
        $response->assertStatus(200);
        $response->assertSee('Happy 2026 Birthday Celebrant from Sender One!');
        $response->assertDontSee('Wish to other member for January.');
    }

    /**
     * 15. Admin can filter by year
     */
    public function test_15_admin_can_filter_by_year(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/birthday-wishes?year=2025');
        $response->assertStatus(200);
        $response->assertSee('Archived 2025 Wish: God bless you abundantly!');
        $response->assertDontSee('Happy 2026 Birthday Celebrant from Sender One!');
    }

    /**
     * 16. Admin can edit any wish
     */
    public function test_16_admin_can_edit_any_wish(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->putJson('/birthday/letter/' . $this->letter2026Named->id, [
            'message' => 'Admin moderated wish message',
            'is_anonymous' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertEquals('Admin moderated wish message', $this->letter2026Named->fresh()->message);
    }

    // ==========================================
    // SENDER PRIVILEGES & RULES (17 - 20)
    // ==========================================

    /**
     * 17. Sender can view own current wish
     */
    public function test_17_sender_can_view_own_current_wish(): void
    {
        $this->actingAs($this->sender1User);

        $response = $this->get('/birthday-wishes');
        $response->assertStatus(200);
        $response->assertSee($this->sender1Member->full_name);
        $response->assertSee('Happy 2026 Birthday Celebrant from Sender One!');
    }

    /**
     * 18. Sender can edit own wish during recipient birthday
     */
    public function test_18_sender_can_edit_own_wish_during_recipient_birthday(): void
    {
        // Recipient's birthday is July 15, which is today
        $this->actingAs($this->sender1User);

        $response = $this->putJson('/birthday/letter/' . $this->letter2026Named->id, [
            'message' => 'Updated message by Sender 1 with extra prayers!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertEquals('Updated message by Sender 1 with extra prayers!', $this->letter2026Named->fresh()->message);
    }

    /**
     * 19. Sender cannot edit after birthday
     */
    public function test_19_sender_cannot_edit_after_birthday(): void
    {
        // Move to July 16 (after birthday)
        Carbon::setTestNow(Carbon::create(2026, 7, 16, 10, 0, 0, 'Asia/Jakarta'));

        $this->actingAs($this->sender1User);

        $response = $this->putJson('/birthday/letter/' . $this->letter2026Named->id, [
            'message' => 'Attempted late edit after birthday passed',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * 20. Sender cannot edit another sender's wish
     */
    public function test_20_sender_cannot_edit_another_senders_wish(): void
    {
        $this->actingAs($this->sender1User);

        // Sender 1 attempts to edit Sender 2's letter
        $response = $this->putJson('/birthday/letter/' . $this->letter2026Anon->id, [
            'message' => 'Hacked message from other sender',
        ]);

        $response->assertStatus(403);
    }

    // ==========================================
    // PRIVACY & AUDITING (21 - 23)
    // ==========================================

    /**
     * 21. Anonymous sender is shown as Anonymous to recipient
     */
    public function test_21_anonymous_sender_is_shown_as_anonymous_to_recipient(): void
    {
        $this->actingAs($this->celebrantUser);

        $response = $this->get('/birthday-wishes?year=2026');
        $response->assertStatus(200);

        // Recipient sees "Anonymous"
        $response->assertSee('Anonymous');
        // Recipient must NOT see Sender 2's real account name or member full name
        $response->assertDontSee($this->sender2Member->full_name);
        $response->assertDontSee($this->sender2User->name);
    }

    /**
     * 22. Real sender identity remains available to admin
     */
    public function test_22_real_sender_identity_remains_available_to_admin(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/birthday-wishes?year=2026');
        $response->assertStatus(200);

        // Admin sees Anonymous badge
        $response->assertSee('Anonymous');
        // Admin also sees real audit identity
        $response->assertSee('Audit:');
        $response->assertSee($this->sender2Member->full_name);
    }

    /**
     * 23. No unauthorized birthday_letters data is exposed
     */
    public function test_23_no_unauthorized_birthday_letters_data_is_exposed(): void
    {
        // Unauthenticated access to single letter show endpoint returns 401
        Auth::logout();
        $this->getJson('/birthday/letter/' . $this->letter2026Anon->id)->assertStatus(401);

        // Bystander access returns 403
        $this->actingAs($this->normalUser);
        $this->getJson('/birthday/letter/' . $this->letter2026Anon->id)->assertStatus(403);

        // Recipient access reveals display name Anonymous and null sender account
        $this->actingAs($this->celebrantUser);
        $res = $this->getJson('/birthday/letter/' . $this->letter2026Anon->id);
        $res->assertStatus(200);
        $res->assertJsonPath('sender_name', 'Anonymous');
        $this->assertNull($res->json('sender'));
    }

    // ==========================================
    // REGRESSION CHECKS (24 - 25)
    // ==========================================

    /**
     * 24. Existing R1 policy tests remain passing
     */
    public function test_24_existing_r1_policy_tests_remain_passing(): void
    {
        // Verify R1 self-wish prevention
        $this->actingAs($this->celebrantUser);
        $res = $this->postJson('/birthday/letter', [
            'member_id' => $this->celebrantMember->id,
            'message' => 'Self wish attempt',
        ]);
        $res->assertStatus(422);

        // Verify R1 one-letter-per-year constraint
        $this->actingAs($this->sender1User);
        $dupRes = $this->postJson('/birthday/letter', [
            'member_id' => $this->celebrantMember->id,
            'message' => 'Duplicate letter for 2026',
        ]);
        $dupRes->assertStatus(422);
    }

    /**
     * 25. Existing R2 birthday popup tests remain passing
     */
    public function test_25_existing_r2_birthday_popup_tests_remain_passing(): void
    {
        // Celebrant visits home: popup is present with lock duration and celebrants
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('modal-birthday-popup');
        $response->assertSee($this->celebrantMember->full_name);
    }
}
