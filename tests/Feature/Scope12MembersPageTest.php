<?php

namespace Tests\Feature;

use App\Models\BirthdayLetter;
use App\Models\CashTransaction;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope12MembersPageTest extends TestCase
{
    protected User $adminUser;
    protected User $regularUser;
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s12@bycgrowth.org'],
            [
                'name' => 'Admin S12',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_s12@bycgrowth.org'],
            [
                'name' => 'User S12',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    // ==========================================
    // PUBLIC MEMBERS (1 - 8)
    // ==========================================

    /**
     * 1. Public Members page is accessible without login.
     */
    public function test_01_public_members_page_is_accessible_without_login(): void
    {
        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Members Directory');
        $response->assertDontSee('Back to Home');
    }

    /**
     * 2. Members are loaded from MySQL.
     */
    public function test_02_members_are_loaded_from_mysql(): void
    {
        $uniqueName = 'Verified MySQL Member ' . uniqid();
        $member = Member::create([
            'full_name' => $uniqueName,
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee($uniqueName);

        $member->delete();
    }

    /**
     * 3. Member Full Name is displayed.
     */
    public function test_03_member_full_name_is_displayed(): void
    {
        $member = Member::create([
            'full_name' => 'Mega Suryani Test',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Mega Suryani Test');
        $response->assertSee('<h2 class="member-name"', false);

        $member->delete();
    }

    /**
     * 4. Member Photo is displayed when available.
     */
    public function test_04_member_photo_is_displayed_when_available(): void
    {
        $photo = UploadedFile::fake()->image('mega_photo.jpg');
        $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Mega With Photo',
            'photo' => $photo,
        ]);

        $member = Member::where('full_name', 'Mega With Photo')->firstOrFail();
        $this->assertNotNull($member->photo_file_id);
        $this->assertNotNull($member->photo_url);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee($member->photo_url);
        $response->assertSee('member-photo-img');

        // Cleanup
        $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);
    }

    /**
     * 5. Safe fallback works when photo is unavailable.
     */
    public function test_05_safe_fallback_works_when_photo_is_unavailable(): void
    {
        $member = Member::create([
            'full_name' => 'Indra Fallback Test',
            'photo_file_id' => null,
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Indra Fallback Test');
        $response->assertSee('member-photo-fallback');
        // Initial monogram 'I' is shown
        $response->assertSee('I');

        $member->delete();
    }

    /**
     * 6. Empty Members state works.
     */
    public function test_06_empty_members_state_works(): void
    {
        // Deactivate all active members temporarily
        $activeMembers = Member::where('is_active', true)->get();
        Member::where('is_active', true)->update(['is_active' => false]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('No members found.');

        // Restore active members
        foreach ($activeMembers as $m) {
            $m->update(['is_active' => true]);
        }
    }

    /**
     * 7. Position is NOT displayed.
     */
    public function test_07_position_is_not_displayed(): void
    {
        $member = Member::create([
            'full_name' => 'Position Exclusion Check',
            'position' => 'Secret Senior Coordinator Role',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Position Exclusion Check');
        $response->assertDontSee('Secret Senior Coordinator Role');

        $member->delete();
    }

    /**
     * 8. DOB is NOT displayed publicly.
     */
    public function test_08_dob_is_not_displayed_publicly(): void
    {
        $member = Member::create([
            'full_name' => 'DOB Privacy Check',
            'date_of_birth' => '1996-08-25',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('DOB Privacy Check');
        $response->assertDontSee('1996-08-25');
        $response->assertDontSee('August 25, 1996');
        $response->assertDontSee('25-08-1996');

        $member->delete();
    }

    // ==========================================
    // ADMIN (9 - 11)
    // ==========================================

    /**
     * 9. Admin can access Member Management.
     */
    public function test_09_admin_can_access_member_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Add New Member');
        $response->assertSee('id="btn-open-add-member"', false);
        $response->assertSee('id="modal-add-member"', false);
        $response->assertSee('id="modal-edit-member"', false);
    }

    /**
     * 10. Normal user cannot access Member Management.
     */
    public function test_10_normal_user_cannot_access_member_management(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/members');
        $response->assertStatus(200);
        $response->assertDontSee('id="btn-open-add-member"', false);
        $response->assertDontSee('id="modal-add-member"', false);

        // Attempt CRUD as normal user
        $storeResponse = $this->actingAs($this->regularUser)->post('/admin/members', [
            'full_name' => 'Unauthorized Member',
        ]);
        $storeResponse->assertStatus(403);
    }

    /**
     * 11. Public visitor cannot access Member CRUD.
     */
    public function test_11_public_visitor_cannot_access_member_crud(): void
    {
        $response = $this->post('/admin/members', [
            'full_name' => 'Public Unauth Member',
        ]);
        $response->assertRedirect('/admin-ganteng');

        $updateResponse = $this->post('/admin/members/1', [
            'full_name' => 'Public Unauth Member Edit',
        ]);
        $updateResponse->assertRedirect('/admin-ganteng');

        $deleteResponse = $this->delete('/admin/members/1');
        $deleteResponse->assertRedirect('/admin-ganteng');
    }

    // ==========================================
    // CREATE (12 - 17)
    // ==========================================

    /**
     * 12. Admin can create a member.
     */
    public function test_12_admin_can_create_a_member(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Create Member Test 12',
            'date_of_birth' => '2001-05-10',
        ]);

        $response->assertRedirect('/members');
        $response->assertSessionHas('success', 'Member added successfully.');
    }

    /**
     * 13. Created member is stored in MySQL.
     */
    public function test_13_created_member_is_stored_in_mysql(): void
    {
        Member::firstOrCreate([
            'full_name' => 'Create Member Test 12',
            'date_of_birth' => '2001-05-10',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('members', [
            'full_name' => 'Create Member Test 12',
            'date_of_birth' => '2001-05-10',
        ]);
    }

    /**
     * 14. Created member appears on the public Members Page.
     */
    public function test_14_created_member_appears_on_the_public_members_page(): void
    {
        Member::firstOrCreate([
            'full_name' => 'Create Member Test 12',
            'date_of_birth' => '2001-05-10',
            'is_active' => true,
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Create Member Test 12');
    }

    /**
     * 15. Full Name is stored correctly.
     */
    public function test_15_full_name_is_stored_correctly(): void
    {
        $member = Member::firstOrCreate([
            'full_name' => 'Create Member Test 12',
            'date_of_birth' => '2001-05-10',
            'is_active' => true,
        ]);
        $this->assertNotNull($member);
        $this->assertSame('Create Member Test 12', $member->full_name);
    }

    /**
     * 16. Photo upload works.
     */
    public function test_16_photo_upload_works(): void
    {
        $photo = UploadedFile::fake()->image('test_upload.jpg', 200, 200);

        $response = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Photo Upload Verified Member',
            'photo' => $photo,
        ]);

        $response->assertRedirect('/members');

        $member = Member::where('full_name', 'Photo Upload Verified Member')->firstOrFail();
        $this->assertNotNull($member->photo_file_id);

        $media = MediaFile::find($member->photo_file_id);
        $this->assertNotNull($media);
        $this->assertTrue(File::exists(public_path($media->file_path)));
        $this->createdFiles[] = public_path($media->file_path);

        $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);
    }

    /**
     * 17. DOB remains stored correctly for Birthday functionality.
     */
    public function test_17_dob_remains_stored_correctly_for_birthday_functionality(): void
    {
        $dobDate = '1998-12-14';
        $member = Member::create([
            'full_name' => 'Birthday Persistence Check',
            'date_of_birth' => $dobDate,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'date_of_birth' => $dobDate,
        ]);

        $targetDate = Carbon::parse('2026-12-14', 'Asia/Jakarta');
        $this->assertTrue($member->isBirthdayToday($targetDate));

        $nonBirthdayDate = Carbon::parse('2026-06-01', 'Asia/Jakarta');
        $this->assertFalse($member->isBirthdayToday($nonBirthdayDate));

        $member->delete();
    }

    // ==========================================
    // EDIT (18 - 21)
    // ==========================================

    /**
     * 18. Admin can edit member name.
     */
    public function test_18_admin_can_edit_member_name(): void
    {
        $member = Member::create([
            'full_name' => 'Original Name For Edit',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->post('/admin/members/' . $member->id, [
            'full_name' => 'Modified Name After Edit',
        ]);

        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', [
            'id' => $member->id,
            'full_name' => 'Modified Name After Edit',
        ]);

        $member->delete();
    }

    /**
     * 19. Existing photo remains when no replacement is uploaded.
     */
    public function test_19_existing_photo_remains_when_no_replacement_is_uploaded(): void
    {
        $photo = UploadedFile::fake()->image('keep_photo.png');
        $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Photo Retain Member',
            'photo' => $photo,
        ]);

        $member = Member::where('full_name', 'Photo Retain Member')->firstOrFail();
        $originalPhotoId = $member->photo_file_id;
        $this->assertNotNull($originalPhotoId);

        // Edit name without uploading a new photo or checking remove_photo
        $response = $this->actingAs($this->adminUser)->post('/admin/members/' . $member->id, [
            'full_name' => 'Photo Retain Member Renamed',
        ]);

        $response->assertRedirect('/members');
        $member->refresh();
        $this->assertSame($originalPhotoId, $member->photo_file_id);
        $this->assertSame('Photo Retain Member Renamed', $member->full_name);

        $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);
    }

    /**
     * 20. New photo can replace existing photo.
     */
    public function test_20_new_photo_can_replace_existing_photo(): void
    {
        $photo1 = UploadedFile::fake()->image('photo_initial.jpg');
        $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Photo Replace Member',
            'photo' => $photo1,
        ]);

        $member = Member::where('full_name', 'Photo Replace Member')->firstOrFail();
        $initialPhotoId = $member->photo_file_id;

        $photo2 = UploadedFile::fake()->image('photo_replacement.png');
        $response = $this->actingAs($this->adminUser)->post('/admin/members/' . $member->id, [
            'full_name' => 'Photo Replace Member',
            'photo' => $photo2,
        ]);

        $response->assertRedirect('/members');
        $member->refresh();

        $this->assertNotSame($initialPhotoId, $member->photo_file_id);
        $this->assertNull(MediaFile::find($initialPhotoId)); // Old media record deleted

        $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);
    }

    /**
     * 21. Edited name appears on the public Members Page.
     */
    public function test_21_edited_name_appears_on_the_public_members_page(): void
    {
        $member = Member::create([
            'full_name' => 'Pre-Edit Display Test',
            'is_active' => true,
        ]);

        $this->actingAs($this->adminUser)->post('/admin/members/' . $member->id, [
            'full_name' => 'Post-Edit Display Test',
        ]);

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee('Post-Edit Display Test');
        $response->assertDontSee('Pre-Edit Display Test');

        $member->delete();
    }

    // ==========================================
    // DELETE (22 - 24)
    // ==========================================

    /**
     * 22. Admin can delete a member according to existing relationship rules.
     */
    public function test_22_admin_can_delete_a_member_according_to_existing_relationship_rules(): void
    {
        $member = Member::create([
            'full_name' => 'Member For Safe Delete',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);
        $response->assertRedirect('/members');
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    /**
     * 23. Existing cash transaction history is not corrupted.
     */
    public function test_23_existing_cash_transaction_history_is_not_corrupted(): void
    {
        $member = Member::create([
            'full_name' => 'Financial Contributor Delete Test',
            'is_active' => true,
        ]);

        $tx = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 45000.00,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        // Delete the member
        $response = $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);
        $response->assertRedirect('/members');

        // Cash transaction must remain intact in MySQL
        $this->assertDatabaseHas('cash_transactions', [
            'id' => $tx->id,
            'contributor_name' => 'Financial Contributor Delete Test',
            'amount' => 45000.00,
            'account_type' => 'BCA',
        ]);

        $tx->refresh();
        $this->assertNull($tx->member_id); // Safely nullified, not corrupted

        $tx->delete();
    }

    /**
     * 24. Existing Birthday functionality remains safe.
     */
    public function test_24_existing_birthday_functionality_remains_safe(): void
    {
        $member = Member::create([
            'full_name' => 'Birthday Member With Letters',
            'date_of_birth' => '1999-04-12',
            'is_active' => true,
        ]);

        BirthdayLetter::create([
            'member_id' => $member->id,
            'sender_name' => 'Friend Sender',
            'message' => 'Happy Birthday!',
            'year' => 2026,
        ]);

        $response = $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);
        $response->assertRedirect('/members');

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertDatabaseMissing('birthday_letters', ['member_id' => $member->id]);
    }

    // ==========================================
    // REGRESSION (25 - 29)
    // ==========================================

    /**
     * 25. S9 Cash Management remains functional.
     */
    public function test_25_s9_cash_management_remains_functional(): void
    {
        $member = Member::create([
            'full_name' => 'S9 Regression Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('proof_s12.jpg');
        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response->assertRedirect('/cash-management');
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
            'contributor_name' => 'S9 Regression Member',
            'amount' => 30000.00,
        ]);

        CashTransaction::where('member_id', $member->id)->delete();
        $member->delete();
    }

    /**
     * 26. S10 member shortcut remains functional.
     */
    public function test_26_s10_member_shortcut_remains_functional(): void
    {
        $member = Member::create([
            'full_name' => 'S10 Shortcut Regression Member',
            'is_active' => true,
        ]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 50000.00,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/admin/cash-management/shortcut/' . $member->id);
        $response->assertStatus(200);
        $response->assertJson([
            'has_shortcut' => true,
            'account_type' => 'BCA',
            'amount' => 50000,
        ]);

        CashTransaction::where('member_id', $member->id)->delete();
        $member->delete();
    }

    /**
     * 27. S11 transaction table still resolves member names correctly.
     */
    public function test_27_s11_transaction_table_still_resolves_member_names_correctly(): void
    {
        $member = Member::create([
            'full_name' => 'S11 Table Name Resolution Member',
            'is_active' => true,
        ]);

        $tx = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 60000.00,
            'account_type' => 'BRI',
            'type' => 'inflow',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('S11 Table Name Resolution Member');

        $tx->delete();
        $member->delete();
    }

    /**
     * 28. Birthday popup remains functional.
     */
    public function test_28_birthday_popup_remains_functional(): void
    {
        $todaySurabaya = Carbon::now('Asia/Jakarta');
        $member = Member::create([
            'full_name' => 'Today Birthday Celebrant S12',
            'date_of_birth' => '2000-' . $todaySurabaya->format('m-d'),
            'is_active' => true,
        ]);

        $response = $this->get('/birthday/today');
        $response->assertStatus(200);
        $response->assertJson([
            'date' => $todaySurabaya->toDateString(),
            'count' => 1,
        ]);
        $response->assertJsonFragment([
            'full_name' => 'Today Birthday Celebrant S12',
        ]);

        $member->delete();
    }

    /**
     * 29. Existing community tests continue passing.
     */
    public function test_29_existing_community_tests_continue_passing(): void
    {
        $aboutResponse = $this->get('/about');
        $aboutResponse->assertStatus(200);
        $aboutResponse->assertSee('About BYC Growth');

        $activityResponse = $this->get('/activity');
        $activityResponse->assertStatus(200);
        $activityResponse->assertSee('Activities');
    }
}
