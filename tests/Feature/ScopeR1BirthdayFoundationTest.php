<?php

namespace Tests\Feature;

use App\Models\BirthdayLetter;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Scope R1 — Birthday Foundation & Access Control Test Suite
 *
 * Verifies User <-> Member 1:1 relationship, birthday letter data model,
 * one-letter-per-year invariant, self-wish prevention, anonymous display preference,
 * and birthday date editing rules.
 */
class ScopeR1BirthdayFoundationTest extends TestCase
{
    protected User $adminUser;
    protected User $kevinUser;
    protected User $ariaUser;
    protected User $megaUser;
    protected Member $kevinMember;
    protected Member $ariaMember;
    protected Member $megaMember;

    protected function setUp(): void
    {
        parent::setUp();

        $today = Carbon::now('Asia/Jakarta');

        // Setup test members
        $this->kevinMember = Member::create([
            'full_name' => 'Kevin Sanjaya ' . uniqid(),
            'date_of_birth' => '1998-08-02',
            'is_active' => true,
        ]);

        $this->ariaMember = Member::create([
            'full_name' => 'Aria Hansa ' . uniqid(),
            'date_of_birth' => '1996-' . $today->format('m-d'), // birthday is today
            'is_active' => true,
        ]);

        $this->megaMember = Member::create([
            'full_name' => 'Mega Suryani ' . uniqid(),
            'date_of_birth' => '1997-12-15',
            'is_active' => true,
        ]);

        // Setup test users with 1:1 member linkages
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_r1@bycgrowth.org'],
            [
                'name' => 'Admin R1',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->kevinUser = User::create([
            'name' => 'Kevin Account',
            'email' => 'kevin_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->kevinMember->id,
        ]);

        $this->ariaUser = User::create([
            'name' => 'Aria Account',
            'email' => 'aria_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->ariaMember->id,
        ]);

        $this->megaUser = User::create([
            'name' => 'Mega Account',
            'email' => 'mega_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->megaMember->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // reset any mock time

        $this->kevinUser->delete();
        $this->ariaUser->delete();
        $this->megaUser->delete();

        $this->kevinMember->delete();
        $this->ariaMember->delete();
        $this->megaMember->delete();

        parent::tearDown();
    }

    /**
     * 1. One user maps to one member (1:1 relational relationship).
     */
    public function test_01_one_user_maps_to_one_member(): void
    {
        $this->assertNotNull($this->kevinUser->member);
        $this->assertEquals($this->kevinMember->id, $this->kevinUser->member->id);

        $this->assertNotNull($this->kevinMember->user);
        $this->assertEquals($this->kevinUser->id, $this->kevinMember->user->id);
    }

    /**
     * 2. Valid user/member relationship works through role management.
     */
    public function test_02_valid_user_member_relationship_works_via_admin_roles(): void
    {
        $newMember = Member::create([
            'full_name' => 'New Fellowship Member ' . uniqid(),
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser);

        $email = 'new_member_' . uniqid() . '@bycgrowth.org';
        $response = $this->post('/admin/roles', [
            'name' => 'New Member Account',
            'email' => $email,
            'password' => 'secret123',
            'role' => 'user',
            'member_id' => $newMember->id,
        ]);

        $response->assertRedirect('/admin/roles');

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals($newMember->id, $user->member_id);

        $user->delete();
        $newMember->delete();
    }

    /**
     * 3. Duplicate user/member relationship is rejected (1 Member can only have 1 User).
     */
    public function test_03_duplicate_user_member_relationship_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        // Attempt to create second user linked to Kevin Member
        $response = $this->post('/admin/roles', [
            'name' => 'Impostor Kevin Account',
            'email' => 'impostor_' . uniqid() . '@bycgrowth.org',
            'password' => 'secret123',
            'role' => 'user',
            'member_id' => $this->kevinMember->id,
        ]);

        $response->assertSessionHasErrors('member_id');

        // Direct DB attempt throws QueryException due to unique constraint
        $this->expectException(QueryException::class);
        User::create([
            'name' => 'Direct DB Duplicate',
            'email' => 'duplicate_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'member_id' => $this->kevinMember->id,
        ]);
    }

    /**
     * 4. Authenticated sender identity comes from logged-in account.
     */
    public function test_04_authenticated_sender_identity_comes_from_logged_in_account(): void
    {
        $this->actingAs($this->kevinUser);

        $response = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'sender_name' => 'Fake Spoofed Name',
            'message' => 'Happy Birthday Aria from Kevin!',
        ]);

        $response->assertStatus(200);
        $letterId = $response->json('letter_id');

        $letter = BirthdayLetter::findOrFail($letterId);
        $this->assertEquals($this->kevinUser->id, $letter->user_id);
        // Display name comes from the sender member, ignoring spoofed client sender_name
        $this->assertEquals($this->kevinMember->full_name, $letter->display_name);

        $letter->delete();
    }

    /**
     * 5. Named letter stores and displays preference correctly.
     */
    public function test_05_named_letter_stores_and_displays_preference_correctly(): void
    {
        $this->actingAs($this->kevinUser);

        $response = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'is_anonymous' => 0,
            'message' => 'Happy Birthday with love and name visible!',
        ]);

        $response->assertStatus(200);
        $letter = BirthdayLetter::findOrFail($response->json('letter_id'));

        $this->assertFalse($letter->is_anonymous);
        $this->assertEquals($this->kevinMember->full_name, $letter->display_name);

        $letter->delete();
    }

    /**
     * 6. Anonymous letter still stores actual sender account for admin auditing.
     */
    public function test_06_anonymous_letter_still_stores_actual_sender_account(): void
    {
        $this->actingAs($this->kevinUser);

        $response = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'is_anonymous' => 1,
            'message' => 'Heartfelt blessings from an anonymous friend!',
        ]);

        $response->assertStatus(200);
        $letter = BirthdayLetter::findOrFail($response->json('letter_id'));

        // Display preference is Anonymous
        $this->assertTrue($letter->is_anonymous);
        $this->assertEquals('Anonymous', $letter->display_name);

        // Underlying database persists real sender user ID
        $this->assertEquals($this->kevinUser->id, $letter->user_id);
        $this->assertEquals($this->kevinMember->id, $letter->user->member->id);

        $letter->delete();
    }

    /**
     * 7. Sender cannot send a birthday letter to themselves.
     */
    public function test_07_sender_cannot_send_letter_to_themselves(): void
    {
        $this->actingAs($this->ariaUser);

        // Aria attempts to send letter to Aria Member
        $response = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'Happy Birthday to myself!',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'You cannot send a birthday letter to yourself.',
        ]);
    }

    /**
     * 8. Sender cannot create a second letter for the same recipient and year.
     */
    public function test_08_sender_cannot_create_second_letter_for_same_recipient_year(): void
    {
        $this->actingAs($this->kevinUser);

        // First letter in 2026
        $res1 = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'First birthday letter for 2026!',
        ]);
        $res1->assertStatus(200);

        // Second letter attempt to Aria in 2026
        $res2 = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'Attempting a duplicate letter!',
        ]);

        $res2->assertStatus(422);
        $res2->assertJson([
            'success' => false,
            'message' => 'You have already sent a birthday letter to this member for this year.',
        ]);

        BirthdayLetter::where('user_id', $this->kevinUser->id)->delete();
    }

    /**
     * 9. Same sender can send to a different recipient in the same year.
     */
    public function test_09_same_sender_can_send_to_different_recipient(): void
    {
        $this->actingAs($this->kevinUser);

        // Letter to Aria
        $res1 = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'Letter to Aria!',
        ]);
        $res1->assertStatus(200);

        // Letter to Mega
        $res2 = $this->postJson('/birthday/letter', [
            'member_id' => $this->megaMember->id,
            'message' => 'Letter to Mega!',
        ]);
        $res2->assertStatus(200);

        BirthdayLetter::where('user_id', $this->kevinUser->id)->delete();
    }

    /**
     * 10. Same sender can send to the same recipient in a different year.
     */
    public function test_10_same_sender_can_send_to_same_recipient_in_different_year(): void
    {
        $this->actingAs($this->kevinUser);

        // Create 2026 letter
        Carbon::setTestNow('2026-09-30 10:00:00');
        $res2026 = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'Kevin to Aria for 2026',
        ]);
        $res2026->assertStatus(200);

        // Move to 2027
        Carbon::setTestNow('2027-09-30 10:00:00');
        $res2027 = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'Kevin to Aria for 2027',
        ]);
        $res2027->assertStatus(200);

        $letters = BirthdayLetter::where('user_id', $this->kevinUser->id)
            ->where('member_id', $this->ariaMember->id)
            ->orderBy('birthday_year')
            ->get();

        $this->assertCount(2, $letters);
        $this->assertEquals(2026, $letters[0]->birthday_year);
        $this->assertEquals(2027, $letters[1]->birthday_year);

        BirthdayLetter::where('user_id', $this->kevinUser->id)->delete();
    }

    /**
     * 11. Sender can edit own letter during recipient birthday date.
     */
    public function test_11_sender_can_edit_own_letter_during_recipient_birthday(): void
    {
        $today = Carbon::now('Asia/Jakarta');
        // ariaMember birthday is today
        $this->actingAs($this->kevinUser);

        $createRes = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'Initial letter text',
        ]);
        $letterId = $createRes->json('letter_id');

        // Edit letter
        $editRes = $this->putJson('/birthday/letter/' . $letterId, [
            'message' => 'Updated letter text with extra love!',
        ]);

        $editRes->assertStatus(200);
        $editRes->assertJson([
            'success' => true,
        ]);

        $letter = BirthdayLetter::findOrFail($letterId);
        $this->assertEquals('Updated letter text with extra love!', $letter->message);

        $letter->delete();
    }

    /**
     * 12. Sender cannot edit own letter after recipient birthday date.
     */
    public function test_12_sender_cannot_edit_own_letter_after_recipient_birthday(): void
    {
        // megaMember birthday is December 15, which is not today
        $this->actingAs($this->kevinUser);

        $letter = BirthdayLetter::create([
            'member_id' => $this->megaMember->id,
            'user_id' => $this->kevinUser->id,
            'birthday_year' => 2026,
            'is_anonymous' => false,
            'sender_name' => $this->kevinMember->full_name,
            'message' => 'Original letter on birthday',
        ]);

        // Next day / non-birthday date edit attempt
        $editRes = $this->putJson('/birthday/letter/' . $letter->id, [
            'message' => 'Attempted late edit after birthday passed',
        ]);

        $editRes->assertStatus(422);
        $editRes->assertJson([
            'success' => false,
            'message' => 'Birthday letters can only be edited during the recipient\'s birthday.',
        ]);

        $letter->delete();
    }

    /**
     * 13. Admin can edit letter at any time, even after birthday.
     */
    public function test_13_admin_can_edit_letter_after_birthday(): void
    {
        $letter = BirthdayLetter::create([
            'member_id' => $this->megaMember->id,
            'user_id' => $this->kevinUser->id,
            'birthday_year' => 2026,
            'is_anonymous' => false,
            'sender_name' => $this->kevinMember->full_name,
            'message' => 'Original letter text',
        ]);

        // Acting as admin
        $this->actingAs($this->adminUser);

        $editRes = $this->putJson('/birthday/letter/' . $letter->id, [
            'message' => 'Admin edited message for moderation',
        ]);

        $editRes->assertStatus(200);
        $this->assertEquals('Admin edited message for moderation', $letter->fresh()->message);

        $letter->delete();
    }

    /**
     * 14. Normal user cannot edit another user's letter.
     */
    public function test_14_normal_user_cannot_edit_another_users_letter(): void
    {
        $letter = BirthdayLetter::create([
            'member_id' => $this->ariaMember->id,
            'user_id' => $this->kevinUser->id,
            'birthday_year' => 2026,
            'is_anonymous' => false,
            'sender_name' => $this->kevinMember->full_name,
            'message' => 'Kevin original message',
        ]);

        // Mega tries to edit Kevin's letter
        $this->actingAs($this->megaUser);

        $editRes = $this->putJson('/birthday/letter/' . $letter->id, [
            'message' => 'Hacked message by Mega',
        ]);

        $editRes->assertStatus(403);
        $editRes->assertJson([
            'success' => false,
            'message' => 'Forbidden. You can only edit your own birthday letters.',
        ]);

        $letter->delete();
    }

    /**
     * 15. Unauthenticated user cannot create a birthday letter.
     */
    public function test_15_unauthenticated_user_cannot_create_birthday_letter(): void
    {
        Auth::logout();

        $response = $this->postJson('/birthday/letter', [
            'member_id' => $this->ariaMember->id,
            'message' => 'Unauthenticated letter submission',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 16. Historical birthday years remain stored and distinguishable.
     */
    public function test_16_historical_birthday_years_remain_stored(): void
    {
        $letter2024 = BirthdayLetter::create([
            'member_id' => $this->ariaMember->id,
            'user_id' => $this->kevinUser->id,
            'birthday_year' => 2024,
            'message' => 'Happy 2024 Birthday!',
        ]);

        $letter2025 = BirthdayLetter::create([
            'member_id' => $this->ariaMember->id,
            'user_id' => $this->kevinUser->id,
            'birthday_year' => 2025,
            'message' => 'Happy 2025 Birthday!',
        ]);

        $this->assertDatabaseHas('birthday_letters', ['id' => $letter2024->id, 'birthday_year' => 2024]);
        $this->assertDatabaseHas('birthday_letters', ['id' => $letter2025->id, 'birthday_year' => 2025]);

        $letter2024->delete();
        $letter2025->delete();
    }

    /**
     * 17. Public endpoints do not expose all birthday letters or confidential sender details.
     */
    public function test_17_public_endpoints_do_not_expose_birthday_letters(): void
    {
        $letter = BirthdayLetter::create([
            'member_id' => $this->ariaMember->id,
            'user_id' => $this->kevinUser->id,
            'birthday_year' => 2026,
            'is_anonymous' => true,
            'sender_name' => 'Anonymous',
            'message' => 'Confidential secret letter',
        ]);

        // Unauthenticated access to letter show returns 401
        Auth::logout();
        $unauthRes = $this->getJson('/birthday/letter/' . $letter->id);
        $unauthRes->assertStatus(401);

        // Third party user (Mega) returns 403
        $this->actingAs($this->megaUser);
        $thirdPartyRes = $this->getJson('/birthday/letter/' . $letter->id);
        $thirdPartyRes->assertStatus(403);

        // Recipient (Aria) can view, but sees Anonymous and null sender details
        $this->actingAs($this->ariaUser);
        $recipientRes = $this->getJson('/birthday/letter/' . $letter->id);
        $recipientRes->assertStatus(200);
        $recipientRes->assertJsonPath('sender_name', 'Anonymous');
        $this->assertNull($recipientRes->json('sender'));

        // Admin can view real sender details
        $this->actingAs($this->adminUser);
        $adminRes = $this->getJson('/birthday/letter/' . $letter->id);
        $adminRes->assertStatus(200);
        $this->assertNotNull($adminRes->json('sender'));
        $this->assertEquals($this->kevinUser->id, $adminRes->json('sender.id'));

        $letter->delete();
    }

    /**
     * 18. Existing birthday popup data endpoint (/birthday/today) still works.
     */
    public function test_18_existing_birthday_popup_data_endpoint_still_works(): void
    {
        $response = $this->getJson('/birthday/today');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'date',
            'count',
            'members' => [
                '*' => ['id', 'full_name', 'photo_url'],
            ],
        ]);
    }

    /**
     * 19. Existing authentication remains functional.
     */
    public function test_19_existing_authentication_remains_functional(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin_r1@bycgrowth.org',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertTrue(Auth::check());
    }

    /**
     * 20. Admin access remains functional across admin routes.
     */
    public function test_20_admin_access_remains_functional(): void
    {
        $this->actingAs($this->adminUser);

        $res1 = $this->get('/admin/dashboard');
        $res1->assertStatus(200);

        $res2 = $this->get('/admin/roles');
        $res2->assertStatus(200);
        $res2->assertSee('Role & Account Management');
    }
}
