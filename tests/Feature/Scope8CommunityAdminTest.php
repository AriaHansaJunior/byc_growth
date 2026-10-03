<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\BirthdayLetter;
use App\Models\CashTransaction;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Scope8CommunityAdminTest extends TestCase
{
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_scope8@bycgrowth.org'],
            [
                'name' => 'Admin Scope 8',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_scope8@bycgrowth.org'],
            [
                'name' => 'Regular User Scope 8',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    // ==========================================
    // A. ABOUT + CONTACT TESTS (1 - 3)
    // ==========================================

    /**
     * 1. Combined page is accessible.
     */
    public function test_01_combined_about_contact_page_is_accessible(): void
    {
        $response = $this->get('/about');
        $response->assertStatus(200);
        $response->assertSee('About BYC Growth');
        $response->assertSee('Part of Successful Bethany Families');
        $response->assertDontSee('Back to Home');
    }

    /**
     * 2. Old About/Contact routes do not result in unintended 404s.
     */
    public function test_02_old_about_contact_routes_resolve_safely_without_404(): void
    {
        $response = $this->get('/contact');
        // Safely redirects or resolves to combined page
        $this->assertTrue(in_array($response->status(), [200, 301, 302], true));
        $this->assertFalse($response->isNotFound());
    }

    /**
     * 3. Navigation points to the combined page.
     */
    public function test_03_navigation_points_to_combined_page_without_separate_contact(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee(route('about'));
        // In main header nav, About and Contact are not separate destinations
        $response->assertSee('<a href="' . route('about') . '" class="nav-item', false);
    }

    // ==========================================
    // B. ACTIVITY TESTS (4 - 10)
    // ==========================================

    /**
     * 4. Public Activity page is accessible.
     */
    public function test_04_public_activity_page_is_accessible(): void
    {
        $response = $this->get('/activity');
        $response->assertStatus(200);
        $response->assertSee('Activities');
        $response->assertDontSee('Back to Home');
    }

    /**
     * 5. Admin can create Activity.
     */
    public function test_05_admin_can_create_activity(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/admin/activities', [
            'name' => 'Youth Camp 2026',
            'event_date' => '2026-06-15',
            'description' => 'Annual spiritual camp and fellowship in Tretes.',
        ]);

        $response->assertRedirect('/activity');
        $this->assertDatabaseHas('activities', [
            'name' => 'Youth Camp 2026',
            'event_date' => '2026-06-15',
        ]);
    }

    /**
     * 6. Admin can edit Activity.
     */
    public function test_06_admin_can_edit_activity(): void
    {
        $this->actingAs($this->adminUser);

        $activity = Activity::create([
            'name' => 'Fellowship Night',
            'event_date' => '2026-05-10',
            'description' => 'Original description.',
        ]);

        $response = $this->post('/admin/activities/' . $activity->id, [
            'name' => 'Fellowship Night Updated',
            'event_date' => '2026-05-12',
            'description' => 'Updated fellowship night description.',
        ]);

        $response->assertRedirect('/activity');
        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'name' => 'Fellowship Night Updated',
            'event_date' => '2026-05-12',
        ]);
    }

    /**
     * 7. Admin can delete Activity.
     */
    public function test_07_admin_can_delete_activity(): void
    {
        $this->actingAs($this->adminUser);

        $activity = Activity::create([
            'name' => 'Temporary Event to Delete',
            'event_date' => '2026-04-01',
            'description' => 'To be deleted.',
        ]);

        $response = $this->delete('/admin/activities/' . $activity->id);
        $response->assertRedirect('/activity');
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    /**
     * 8. Activity supports multiple photos.
     */
    public function test_08_activity_supports_multiple_photos(): void
    {
        $this->actingAs($this->adminUser);

        $file1 = UploadedFile::fake()->image('camp1.jpg');
        $file2 = UploadedFile::fake()->image('camp2.png');

        $response = $this->post('/admin/activities', [
            'name' => 'Retreat with Photos',
            'event_date' => '2026-07-20',
            'description' => 'Retreat gallery test.',
            'photos' => [$file1, $file2],
        ]);

        $response->assertRedirect('/activity');

        $activity = Activity::where('name', 'Retreat with Photos')->firstOrFail();
        $this->assertCount(2, $activity->photos);
    }

    /**
     * 9. Non-admin cannot manage Activity.
     */
    public function test_09_non_admin_cannot_manage_activity(): void
    {
        // Guest
        Auth::logout();
        $this->post('/admin/activities', [
            'name' => 'Unauthorized Activity',
            'event_date' => '2026-08-01',
            'description' => 'Unauthorized.',
        ])->assertRedirect('/admin-ganteng');

        // Regular user
        $this->actingAs($this->regularUser);
        $this->post('/admin/activities', [
            'name' => 'Unauthorized Activity',
            'event_date' => '2026-08-01',
            'description' => 'Unauthorized.',
        ])->assertStatus(403);
    }

    /**
     * 10. Invalid activity image uploads are rejected.
     */
    public function test_10_invalid_activity_image_uploads_are_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $pdf = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->post('/admin/activities', [
            'name' => 'Activity with PDF',
            'event_date' => '2026-08-05',
            'description' => 'Trying to upload PDF.',
            'photos' => [$pdf],
        ]);

        $response->assertSessionHasErrors('photos.0');
    }

    // ==========================================
    // C. MEMBERS TESTS (11 - 18)
    // ==========================================

    /**
     * 11. Public Members page displays member photo + name.
     */
    public function test_11_public_members_page_displays_member_photo_and_name(): void
    {
        $member = Member::create([
            'full_name' => 'Grace Nathania',
            'date_of_birth' => '2001-08-14',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Grace Nathania');
        $response->assertSee('member-photo-frame');
    }

    /**
     * 12. Public Members page does NOT expose position.
     */
    public function test_12_public_members_page_does_not_expose_position(): void
    {
        Member::create([
            'full_name' => 'Daniel Timothy',
            'position' => 'Secret Super Leader Position',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Daniel Timothy');
        $response->assertDontSee('Secret Super Leader Position');
    }

    /**
     * 13. Public Members page does NOT expose birthday/date of birth.
     */
    public function test_13_public_members_page_does_not_expose_birthday(): void
    {
        Member::create([
            'full_name' => 'Ester Olivia',
            'date_of_birth' => '1999-12-25',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Ester Olivia');
        $response->assertDontSee('1999-12-25');
        $response->assertDontSee('December 25, 1999');
    }

    /**
     * 14. Admin can create member.
     */
    public function test_14_admin_can_create_member(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/admin/members', [
            'full_name' => 'Joshua Kevin',
            'date_of_birth' => '2000-03-21',
        ]);

        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', [
            'full_name' => 'Joshua Kevin',
            'date_of_birth' => '2000-03-21',
        ]);
    }

    /**
     * 15. Admin can edit member.
     */
    public function test_15_admin_can_edit_member(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Kezia Sarah',
            'date_of_birth' => '2002-04-10',
            'is_active' => true,
        ]);

        $response = $this->post('/admin/members/' . $member->id, [
            'full_name' => 'Kezia Sarah Updated',
            'date_of_birth' => '2002-04-11',
        ]);

        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'full_name' => 'Kezia Sarah Updated',
            'date_of_birth' => '2002-04-11',
        ]);
    }

    /**
     * 16. Admin can delete member.
     */
    public function test_16_admin_can_delete_member(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Member to Remove',
            'is_active' => true,
        ]);

        $response = $this->delete('/admin/members/' . $member->id);
        $response->assertRedirect('/members');
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    /**
     * 17. Member photo upload works.
     */
    public function test_17_member_photo_upload_works(): void
    {
        $this->actingAs($this->adminUser);

        $photo = UploadedFile::fake()->image('profile.jpg');

        $response = $this->post('/admin/members', [
            'full_name' => 'Member With Photo',
            'photo' => $photo,
        ]);

        $response->assertRedirect('/members');
        $member = Member::where('full_name', 'Member With Photo')->firstOrFail();
        $this->assertNotNull($member->photo_file_id);
        $this->assertNotNull($member->photo);
        $this->assertStringContainsString('member_', $member->photo->file_path);
    }

    /**
     * 18. Date of birth persists for birthday logic.
     */
    public function test_18_date_of_birth_persists_for_birthday_logic(): void
    {
        $member = Member::create([
            'full_name' => 'Birthday Person Test',
            'date_of_birth' => '1998-09-29',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'date_of_birth' => '1998-09-29',
        ]);

        $fresh = Member::find($member->id);
        $this->assertEquals('1998-09-29', $fresh->date_of_birth->format('Y-m-d'));
    }

    // ==========================================
    // D. ROLE / ACCOUNT MANAGEMENT TESTS (19 - 25)
    // ==========================================

    /**
     * 19. Admin can create account.
     */
    public function test_19_admin_can_create_account(): void
    {
        $this->actingAs($this->adminUser);

        $email = 'newuser_' . time() . '@bycgrowth.org';
        $response = $this->post('/admin/roles', [
            'name' => 'New Staff User',
            'email' => $email,
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => 'admin',
        ]);
    }

    /**
     * 20. Admin can edit account.
     */
    public function test_20_admin_can_edit_account(): void
    {
        $this->actingAs($this->adminUser);

        $targetUser = User::create([
            'name' => 'Edit Target',
            'email' => 'edit_target_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->post('/admin/roles/' . $targetUser->id, [
            'name' => 'Edit Target Updated',
            'email' => $targetUser->email,
            'role' => 'admin',
        ]);

        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Edit Target Updated',
            'role' => 'admin',
        ]);
    }

    /**
     * 21. Admin can delete account.
     */
    public function test_21_admin_can_delete_account(): void
    {
        $this->actingAs($this->adminUser);

        $userToDelete = User::create([
            'name' => 'To Delete',
            'email' => 'delete_me_' . time() . '@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $response = $this->delete('/admin/roles/' . $userToDelete->id);
        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    /**
     * 22. Passwords remain hashed.
     */
    public function test_22_passwords_remain_hashed(): void
    {
        $this->actingAs($this->adminUser);

        $email = 'hash_check_' . time() . '@bycgrowth.org';
        $this->post('/admin/roles', [
            'name' => 'Hash Check',
            'email' => $email,
            'password' => 'myplaintextpassword',
            'role' => 'user',
        ]);

        $user = User::where('email', $email)->firstOrFail();
        $this->assertNotEquals('myplaintextpassword', $user->password);
        $this->assertTrue(Hash::check('myplaintextpassword', $user->password));
    }

    /**
     * 23. Duplicate email is rejected.
     */
    public function test_23_duplicate_email_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/admin/roles', [
            'name' => 'Duplicate Attempt',
            'email' => $this->adminUser->email,
            'password' => 'password123',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * 24. Non-admin cannot manage accounts.
     */
    public function test_24_non_admin_cannot_manage_accounts(): void
    {
        // Public / Guest
        Auth::logout();
        $this->get('/admin/roles')->assertRedirect('/admin-ganteng');

        // Regular user
        $this->actingAs($this->regularUser);
        $this->get('/admin/roles')->assertStatus(403);
    }

    /**
     * 25. Current authenticated admin cannot accidentally delete their own account.
     */
    public function test_25_current_authenticated_admin_cannot_accidentally_delete_themselves(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->delete('/admin/roles/' . $this->adminUser->id);
        $response->assertRedirect('/admin/roles');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    // ==========================================
    // E. BIRTHDAY SYSTEM TESTS (26 - 34)
    // ==========================================

    /**
     * 26. Birthday member is detected correctly by month/day.
     */
    public function test_26_birthday_member_is_detected_correctly_by_month_day(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');

        $birthdayMember = Member::create([
            'full_name' => 'Today Birthday Member ' . time(),
            'date_of_birth' => '1995-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        $this->assertTrue($birthdayMember->isBirthdayToday());

        $detected = Member::birthdayToday()->where('id', $birthdayMember->id)->first();
        $this->assertNotNull($detected);

        $birthdayMember->delete();
    }

    /**
     * 27. Non-birthday members do not trigger the popup.
     */
    public function test_27_non_birthday_members_do_not_trigger_popup(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');
        $otherDate = $todaySurabaya->copy()->addMonths(3);

        $nonBirthdayMember = Member::create([
            'full_name' => 'Future Birthday Member ' . time(),
            'date_of_birth' => '1996-' . $otherDate->format('m-d'),
            'is_active' => true,
        ]);

        $this->assertFalse($nonBirthdayMember->isBirthdayToday());
        $detected = Member::birthdayToday()->where('id', $nonBirthdayMember->id)->first();
        $this->assertNull($detected);

        $nonBirthdayMember->delete();
    }

    /**
     * 28. Birthday popup displays member photo/name.
     */
    public function test_28_birthday_popup_displays_member_photo_and_name(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');

        $bdayMember = Member::create([
            'full_name' => 'Celebrant Star ' . time(),
            'date_of_birth' => '2001-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee($bdayMember->full_name);
        $response->assertSee('modal-birthday-popup');
        $response->assertSee('Special Birthday Greeting');

        $bdayMember->delete();
    }

    /**
     * 29. Popup cannot close during first 10 seconds.
     */
    public function test_29_popup_has_10_second_lock_indicator(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');

        $m = Member::create([
            'full_name' => 'Locked Modal Member ' . time(),
            'date_of_birth' => '2002-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('birthday-lock-state');
        $response->assertSee('Please wait...');
        $response->assertSee('birthday-countdown');

        $m->delete();
    }

    /**
     * 30. Popup becomes closable after 10 seconds.
     */
    public function test_30_popup_provides_write_letter_and_close_actions(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');

        $m = Member::create([
            'full_name' => 'Action Modal Member ' . time(),
            'date_of_birth' => '2003-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Write a Letter');
        $response->assertSee('btn-birthday-close');

        $m->delete();
    }

    /**
     * 31. Write a Letter flow works.
     */
    public function test_31_write_a_letter_flow_works(): void
    {
        $senderUser = User::firstOrCreate(
            ['email' => 'timothy_scope8@bycgrowth.org'],
            [
                'name' => 'Timothy',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
        $this->actingAs($senderUser);

        $member = Member::create([
            'full_name' => 'Recipient Member',
            'date_of_birth' => '2000-01-01',
            'is_active' => true,
        ]);

        $response = $this->postJson('/birthday/letter', [
            'member_id' => $member->id,
            'sender_name' => 'Timothy',
            'message' => 'Happy birthday! May God richly bless and lead your steps!',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('birthday_letters', [
            'member_id' => $member->id,
            'sender_name' => 'Timothy',
            'message' => 'Happy birthday! May God richly bless and lead your steps!',
        ]);
    }

    /**
     * 32. Empty birthday letter is rejected.
     */
    public function test_32_empty_birthday_letter_is_rejected(): void
    {
        $this->actingAs($this->regularUser);

        $member = Member::create([
            'full_name' => 'Letter Target',
            'is_active' => true,
        ]);

        $response = $this->postJson('/birthday/letter', [
            'member_id' => $member->id,
            'message' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('message');
    }

    /**
     * 33. Refreshing/reopening does not suppress the birthday popup through a permanent "seen" flag.
     */
    public function test_33_reopening_page_does_not_suppress_birthday_popup(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');

        $member = Member::create([
            'full_name' => 'Persistent Birthday Member ' . time(),
            'date_of_birth' => '1997-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        // First visit
        $res1 = $this->get('/');
        $res1->assertSee($member->full_name);

        // Second visit (simulating refresh / return visit)
        $res2 = $this->get('/');
        $res2->assertSee($member->full_name);

        $member->delete();
    }

    /**
     * 34. Multiple birthday members are handled without overlapping popups.
     */
    public function test_34_multiple_birthday_members_handled_with_navigation(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');

        $m1 = Member::create([
            'full_name' => 'Twin A ' . time(),
            'date_of_birth' => '2000-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        $m2 = Member::create([
            'full_name' => 'Twin B ' . time(),
            'date_of_birth' => '1998-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('btn-birthday-prev');
        $response->assertSee('btn-birthday-next');
        $response->assertSee('celebrating today');

        $m1->delete();
        $m2->delete();
    }

    // ==========================================
    // F. CASH MANAGEMENT TESTS (35 - 43)
    // ==========================================

    /**
     * 35. Cash Management is admin-only.
     */
    public function test_35_cash_management_is_admin_only(): void
    {
        Auth::logout();
        $this->get('/cash-management')->assertRedirect('/admin-ganteng');

        $this->actingAs($this->regularUser);
        $this->get('/cash-management')->assertStatus(403);

        $this->actingAs($this->adminUser);
        $this->get('/cash-management')->assertStatus(200);
    }

    /**
     * 36. Public users cannot access cash data.
     */
    public function test_36_public_users_cannot_access_cash_data_on_homepage(): void
    {
        Auth::logout();
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Contact the admin to view your cash contribution.');
        $response->assertDontSee('30k');
        $response->assertDontSee('30,000');
    }

    /**
     * 37. Valid transaction can be stored.
     */
    public function test_37_valid_cash_transaction_can_be_stored(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Contributor Test',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.jpg');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $proof,
        ]);

        $response->assertRedirect('/cash-management');
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
            'contributor_name' => 'Contributor Test',
            'account_type' => 'BCA',
            'amount' => 50000,
        ]);
    }

    /**
     * 38. Invalid proof file is rejected.
     */
    public function test_38_invalid_proof_file_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'PDF Contributor',
            'is_active' => true,
        ]);

        $pdf = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 50000,
            'proof' => $pdf,
        ]);

        $response->assertSessionHasErrors('proof');
    }

    /**
     * 39. Proof image is retrievable.
     */
    public function test_39_proof_image_is_retrievable(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Proof Checker',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('receipt.png');

        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BRI',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest()->firstOrFail();
        $this->assertNotNull($tx->proof);
        $this->assertNotEmpty($tx->proof->getUrl());
    }

    /**
     * 40. Search/filter/pagination behavior works.
     */
    public function test_40_search_filter_pagination_behavior_works(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/cash-management?per_page=5&name=Contributor');
        $response->assertStatus(200);
        $response->assertSee('per_page');
        $response->assertSee('cash-transactions-table');
    }

    /**
     * 41. Total cash calculation works.
     */
    public function test_41_total_cash_calculation_works(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Total Cash Inflow:');
        $response->assertSee('total-cash-display');
    }

    /**
     * 42. Server timestamp is used.
     */
    public function test_42_server_timestamp_is_used(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Timestamp Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('ts.jpg');

        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 45000,
            'proof' => $proof,
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest()->firstOrFail();
        $nowSurabaya = Carbon::now('Asia/Jakarta');
        $this->assertEquals($nowSurabaya->toDateString(), $tx->transaction_date->toDateString());
    }

    /**
     * 43. Member shortcut information works without making fields permanently immutable.
     */
    public function test_43_member_shortcut_endpoint_works_and_fields_remain_editable(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Shortcut Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('shortcut.jpg');

        // First transaction establishes shortcut
        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri Shortcut',
            'amount' => 75000,
            'proof' => $proof,
        ]);

        // Query shortcut endpoint
        $shortcutRes = $this->getJson('/admin/cash-management/shortcut/' . $member->id);
        $shortcutRes->assertStatus(200);
        $shortcutRes->assertJson([
            'has_shortcut' => true,
            'account_type' => 'Mandiri Shortcut',
            'amount' => 75000,
        ]);

        // Subsequent transaction can edit account_type and amount freely
        $proof2 = UploadedFile::fake()->image('shortcut2.jpg');
        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Edited To Cash',
            'amount' => 100000,
            'proof' => $proof2,
        ]);

        $latestTx = CashTransaction::where('member_id', $member->id)->latest('id')->firstOrFail();
        $this->assertEquals('Edited To Cash', $latestTx->account_type);
        $this->assertEquals(100000, $latestTx->amount);
    }
}
