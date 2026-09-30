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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Scope S3 — Members & Activities Management
 *
 * Covers:
 * - Members Management (Access, CRUD, Photo Upload/Replace/Delete, Media Cleanup)
 * - Public Members UI (Photo + Name ONLY, no Position, DOB internal)
 * - Deletion Safety & Historical Data Preservation (Cash, User, Birthday)
 * - Activities Management (Access, CRUD, Multi-photo Gallery, Photo Removal, Media Cleanup)
 * - Public Activities (Live database synchronization, Admin Controls Hidden for Public/Guest)
 * - S0-S2 Regressions (/admin-ganteng, logout redirect, username display, icons)
 */
class Scope3MembersActivitiesManagementTest extends TestCase
{
    protected User $adminUser;
    protected User $normalUser;
    protected array $createdFiles = [];
    protected array $createdMemberIds = [];
    protected array $createdActivityIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s3@bycgrowth.org'],
            [
                'name' => 'Admin S3',
                'username' => 'admin_s3',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->normalUser = User::firstOrCreate(
            ['email' => 'user_s3@bycgrowth.org'],
            [
                'name' => 'User S3',
                'username' => 'user_s3',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        foreach ($this->createdMemberIds as $id) {
            $m = Member::find($id);
            if ($m) {
                if ($m->photo_file_id) {
                    $media = MediaFile::find($m->photo_file_id);
                    if ($media) {
                        $fullPath = public_path($media->file_path);
                        if (File::exists($fullPath)) {
                            File::delete($fullPath);
                        }
                        $media->delete();
                    }
                }
                $m->delete();
            }
        }

        foreach ($this->createdActivityIds as $id) {
            $a = Activity::find($id);
            if ($a) {
                foreach ($a->photos as $p) {
                    $fullPath = public_path($p->file_path);
                    if (File::exists($fullPath)) {
                        File::delete($fullPath);
                    }
                    $p->delete();
                }
                $a->delete();
            }
        }

        foreach ($this->createdFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        parent::tearDown();
    }

    // ==========================================
    // 1. MEMBERS MANAGEMENT (1 - 15)
    // ==========================================

    /**
     * 1. Admin can access Members management
     */
    public function test_01_admin_can_access_members_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/members');

        $response->assertStatus(200);
        $response->assertViewIs('admin.members');
        $response->assertSee('Members Management');
        $response->assertSee('Live Roster God Mode');
        $response->assertSee('id="btn-open-add-member"', false);
    }

    /**
     * 2. Normal user cannot access member management actions
     */
    public function test_02_normal_user_cannot_access_member_management_actions(): void
    {
        // GET admin members
        $this->actingAs($this->normalUser)
            ->get('/admin/members')
            ->assertStatus(403);

        // POST create member
        $this->actingAs($this->normalUser)
            ->post('/admin/members', ['full_name' => 'Intruder Member'])
            ->assertStatus(403);

        $member = Member::create(['full_name' => 'Test Member Guard ' . uniqid()]);
        $this->createdMemberIds[] = $member->id;

        // POST/PUT update member
        $this->actingAs($this->normalUser)
            ->post('/admin/members/' . $member->id, ['full_name' => 'Hacked Member'])
            ->assertStatus(403);

        // DELETE member
        $this->actingAs($this->normalUser)
            ->delete('/admin/members/' . $member->id)
            ->assertStatus(403);
    }

    /**
     * 3. Guest cannot access member management actions
     */
    public function test_03_guest_cannot_access_member_management_actions(): void
    {
        $this->get('/admin/members')->assertRedirect('/admin/login');
        $this->post('/admin/members', ['full_name' => 'Ghost Member'])->assertRedirect('/admin/login');
        $this->post('/admin/members/1', ['full_name' => 'Ghost Member'])->assertRedirect('/admin/login');
        $this->delete('/admin/members/1')->assertRedirect('/admin/login');
    }

    /**
     * 4. Admin can create member
     */
    public function test_04_admin_can_create_member(): void
    {
        $fullName = 'Member Created By S3 ' . uniqid();
        $response = $this->actingAs($this->adminUser)
            ->from('/admin/members')
            ->post('/admin/members', [
                'full_name' => $fullName,
                'date_of_birth' => '2001-08-15',
            ]);

        $response->assertRedirect('/admin/members');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('members', [
            'full_name' => $fullName,
            'date_of_birth' => '2001-08-15',
        ]);

        $member = Member::where('full_name', $fullName)->first();
        if ($member) {
            $this->createdMemberIds[] = $member->id;
        }
    }

    /**
     * 5. Admin can upload member photo
     */
    public function test_05_admin_can_upload_member_photo(): void
    {
        $photoName = 'Photo Member ' . uniqid();
        $photo = UploadedFile::fake()->image('profile_s3.jpg', 400, 400);

        $response = $this->actingAs($this->adminUser)
            ->from('/admin/members')
            ->post('/admin/members', [
                'full_name' => $photoName,
                'date_of_birth' => '1998-11-20',
                'photo' => $photo,
            ]);

        $response->assertRedirect('/admin/members');

        $member = Member::where('full_name', $photoName)->latest('id')->firstOrFail();
        $this->createdMemberIds[] = $member->id;

        $this->assertNotNull($member->photo_file_id);
        $this->assertNotNull($member->photo);
        $filePath = public_path($member->photo->file_path);
        $this->assertTrue(File::exists($filePath));

        $this->createdFiles[] = $filePath;
    }

    /**
     * 6. Admin can edit member and replace photo
     */
    public function test_06_admin_can_edit_member_and_replace_photo(): void
    {
        $initialName = 'Initial Member ' . uniqid();
        $updatedName = 'Updated Member ' . uniqid();
        $photo1 = UploadedFile::fake()->image('initial_photo.jpg', 300, 300);

        $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => $initialName,
            'date_of_birth' => '1995-05-05',
            'photo' => $photo1,
        ]);

        $member = Member::where('full_name', $initialName)->latest('id')->firstOrFail();
        $this->createdMemberIds[] = $member->id;

        $oldFilePath = public_path($member->photo->file_path);
        $this->createdFiles[] = $oldFilePath;

        // Replace with new photo
        $photo2 = UploadedFile::fake()->image('replacement_photo.webp', 300, 300);

        $response = $this->actingAs($this->adminUser)
            ->from('/admin/members')
            ->post('/admin/members/' . $member->id, [
                'full_name' => $updatedName,
                'date_of_birth' => '1995-06-06',
                'photo' => $photo2,
            ]);

        $response->assertRedirect('/admin/members');

        $member->refresh();
        $this->assertEquals($updatedName, $member->full_name);
        $this->assertEquals('1995-06-06', $member->date_of_birth->format('Y-m-d'));

        // Old photo must be cleaned up from disk
        $this->assertFalse(File::exists($oldFilePath));

        // New photo must exist
        $newFilePath = public_path($member->photo->file_path);
        $this->assertTrue(File::exists($newFilePath));
        $this->createdFiles[] = $newFilePath;
    }

    /**
     * 7. Admin can remove member photo
     */
    public function test_07_admin_can_remove_member_photo(): void
    {
        $name = 'Member For Photo Removal ' . uniqid();
        $photo = UploadedFile::fake()->image('to_remove.png', 200, 200);

        $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => $name,
            'photo' => $photo,
        ]);

        $member = Member::where('full_name', $name)->latest('id')->firstOrFail();
        $this->createdMemberIds[] = $member->id;

        $filePath = public_path($member->photo->file_path);
        $this->createdFiles[] = $filePath;
        $this->assertTrue(File::exists($filePath));

        $response = $this->actingAs($this->adminUser)
            ->from('/admin/members')
            ->post('/admin/members/' . $member->id, [
                'full_name' => $name,
                'remove_photo' => 1,
            ]);

        $response->assertRedirect('/admin/members');

        $member->refresh();
        $this->assertNull($member->photo_file_id);
        $this->assertFalse(File::exists($filePath));
    }

    /**
     * 8. Invalid member data is rejected
     */
    public function test_08_invalid_member_data_is_rejected(): void
    {
        // Missing full_name
        $response = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => '',
        ]);
        $response->assertSessionHasErrors('full_name');

        // Non-image file upload
        $textDoc = UploadedFile::fake()->create('malicious.php', 100);
        $response2 = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Hacker Test ' . uniqid(),
            'photo' => $textDoc,
        ]);
        $response2->assertSessionHasErrors('photo');

        // Oversized file (>5MB)
        $oversized = UploadedFile::fake()->image('huge.jpg')->size(6000);
        $response3 = $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => 'Oversized Test ' . uniqid(),
            'photo' => $oversized,
        ]);
        $response3->assertSessionHasErrors('photo');
    }

    /**
     * 9. Invalid or nonexistent member ID is handled safely
     */
    public function test_09_invalid_or_nonexistent_member_id_handled_safely(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/admin/members/99999999', ['full_name' => 'Ghost'])
            ->assertStatus(404);

        $this->actingAs($this->adminUser)
            ->delete('/admin/members/99999999')
            ->assertStatus(404);
    }

    /**
     * 10. Public Members shows Photo + Full Name and NEVER position
     */
    public function test_10_public_members_shows_photo_and_full_name_and_never_position(): void
    {
        $uniqueName = 'Visible Roster Member ' . uniqid();
        $member = Member::create([
            'full_name' => $uniqueName,
            'is_active' => true,
        ]);
        $this->createdMemberIds[] = $member->id;

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee($uniqueName);

        // Strictly verify absence of position/role/job fields
        $content = $response->getContent();
        $this->assertStringNotContainsString('class="member-position"', $content);
        $this->assertStringNotContainsString('class="member-role"', $content);
        $this->assertStringNotContainsString('class="member-title"', $content);
    }

    /**
     * 11. DOB remains internal and is not exposed on public Members page
     */
    public function test_11_dob_remains_internal_not_exposed_on_public_members(): void
    {
        $name = 'Internal DOB Member ' . uniqid();
        $member = Member::create([
            'full_name' => $name,
            'date_of_birth' => '1993-10-28',
            'is_active' => true,
        ]);
        $this->createdMemberIds[] = $member->id;

        $response = $this->get('/members');
        $response->assertStatus(200);
        $response->assertSee($name);
        // The date string must NOT be visible on public card
        $response->assertDontSee('1993-10-28');
        $response->assertDontSee('Oct 28, 1993');
        $response->assertDontSee('October 28, 1993');
    }

    /**
     * 12. Admin can delete member with complete media cleanup
     */
    public function test_12_admin_can_delete_member_with_media_cleanup(): void
    {
        $name = 'Member For Full Deletion ' . uniqid();
        $photo = UploadedFile::fake()->image('member_to_clean.jpg');

        $this->actingAs($this->adminUser)->post('/admin/members', [
            'full_name' => $name,
            'photo' => $photo,
        ]);

        $member = Member::where('full_name', $name)->latest('id')->firstOrFail();
        $mediaPath = public_path($member->photo->file_path);
        $this->createdFiles[] = $mediaPath;
        $this->assertTrue(File::exists($mediaPath));

        $response = $this->actingAs($this->adminUser)
            ->from('/admin/members')
            ->delete('/admin/members/' . $member->id);

        $response->assertRedirect('/admin/members');
        $this->assertDatabaseMissing('members', ['id' => $member->id]);
        $this->assertFalse(File::exists($mediaPath));
    }

    /**
     * 13. Member deletion safely preserves cash transaction history
     */
    public function test_13_member_deletion_safely_preserves_cash_transaction_history(): void
    {
        $member = Member::create([
            'full_name' => 'Cash Safety Contributor ' . uniqid(),
            'is_active' => true,
        ]);

        $tx = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 75000.00,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'transaction_date' => Carbon::now()->toDateString(),
        ]);

        $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);

        $this->assertDatabaseMissing('members', ['id' => $member->id]);

        $tx->refresh();
        $this->assertNull($tx->member_id);
        $this->assertEquals(75000.00, $tx->amount);

        $tx->delete();
    }

    /**
     * 14. Member deletion preserves user account integrity
     */
    public function test_14_member_deletion_preserves_user_account_integrity(): void
    {
        $member = Member::create([
            'full_name' => 'Linked User Member ' . uniqid(),
            'is_active' => true,
        ]);

        $linkedUser = User::create([
            'name' => 'Linked Account',
            'username' => 'linked_acc_' . uniqid(),
            'email' => 'linked_' . uniqid() . '@bycgrowth.org',
            'password' => Hash::make('secret'),
            'member_id' => $member->id,
            'role' => 'user',
        ]);

        $this->actingAs($this->adminUser)->delete('/admin/members/' . $member->id);

        $this->assertDatabaseMissing('members', ['id' => $member->id]);

        $linkedUser->refresh();
        $this->assertNull($linkedUser->member_id);
        $this->assertDatabaseHas('users', ['id' => $linkedUser->id]);

        $linkedUser->delete();
    }

    /**
     * 15. Existing Birthday relationship remains functional
     */
    public function test_15_existing_birthday_relationship_remains_functional(): void
    {
        $today = Carbon::now('Asia/Jakarta');
        $member = Member::create([
            'full_name' => 'Celebrant S3 Test ' . uniqid(),
            'date_of_birth' => $today->copy()->subYears(20)->toDateString(),
            'is_active' => true,
        ]);
        $this->createdMemberIds[] = $member->id;

        $this->assertTrue($member->isBirthdayToday());

        $letter = BirthdayLetter::create([
            'user_id' => $this->normalUser->id,
            'member_id' => $member->id,
            'sender_name' => 'Friend S3',
            'message' => 'Blessings on your birthday!',
            'birthday_year' => $today->year,
            'year' => $today->year,
        ]);

        $this->assertDatabaseHas('birthday_letters', [
            'id' => $letter->id,
            'member_id' => $member->id,
        ]);

        $member->delete();
        // Recipient letters cascade safely
        $this->assertDatabaseMissing('birthday_letters', ['id' => $letter->id]);
    }

    // ==========================================
    // 2. ACTIVITIES MANAGEMENT (16 - 25)
    // ==========================================

    /**
     * 16. Admin can access Activities management
     */
    public function test_16_admin_can_access_activities_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/activities');

        $response->assertStatus(200);
        $response->assertViewIs('admin.activities');
        $response->assertSee('Activities Management');
        $response->assertSee('Live Timeline God Mode');
        $response->assertSee('id="btn-open-add-activity"', false);
    }

    /**
     * 17. Normal user cannot mutate activities
     */
    public function test_17_normal_user_cannot_mutate_activities(): void
    {
        // GET admin activities
        $this->actingAs($this->normalUser)->get('/admin/activities')->assertStatus(403);

        // POST create
        $this->actingAs($this->normalUser)->post('/admin/activities', [
            'name' => 'Hacker Event',
            'event_date' => '2026-10-01',
            'description' => 'Disallowed',
        ])->assertStatus(403);

        $activity = Activity::create([
            'name' => 'Activity Guard Test ' . uniqid(),
            'event_date' => '2026-10-01',
            'description' => 'Test event',
        ]);
        $this->createdActivityIds[] = $activity->id;

        // POST update
        $this->actingAs($this->normalUser)->post('/admin/activities/' . $activity->id, [
            'name' => 'Hacked Event',
            'event_date' => '2026-10-02',
            'description' => 'Mutated',
        ])->assertStatus(403);

        // DELETE
        $this->actingAs($this->normalUser)->delete('/admin/activities/' . $activity->id)->assertStatus(403);
    }

    /**
     * 18. Guest cannot mutate activities
     */
    public function test_18_guest_cannot_mutate_activities(): void
    {
        $this->get('/admin/activities')->assertRedirect('/admin/login');
        $this->post('/admin/activities', ['name' => 'Unauthorized'])->assertRedirect('/admin/login');
        $this->post('/admin/activities/1', ['name' => 'Unauthorized'])->assertRedirect('/admin/login');
        $this->delete('/admin/activities/1')->assertRedirect('/admin/login');
    }

    /**
     * 19. Admin can create activity with multiple photos
     */
    public function test_19_admin_can_create_activity_with_multiple_photos(): void
    {
        $name = 'BYC Fellowship Gala ' . uniqid();
        $photo1 = UploadedFile::fake()->image('activity_photo1.jpg', 600, 400);
        $photo2 = UploadedFile::fake()->image('activity_photo2.png', 600, 400);

        $response = $this->actingAs($this->adminUser)
            ->from('/admin/activities')
            ->post('/admin/activities', [
                'name' => $name,
                'event_date' => '2026-07-25',
                'description' => 'An evening of praise, fellowship, and celebration.',
                'photos' => [$photo1, $photo2],
            ]);

        $response->assertRedirect('/admin/activities');
        $response->assertSessionHas('success');

        $activity = Activity::where('name', $name)->latest('id')->firstOrFail();
        $this->createdActivityIds[] = $activity->id;

        $this->assertCount(2, $activity->photos);

        foreach ($activity->photos as $photo) {
            $path = public_path($photo->file_path);
            $this->assertTrue(File::exists($path));
            $this->createdFiles[] = $path;
        }
    }

    /**
     * 20. Admin can edit activity and add more photos
     */
    public function test_20_admin_can_edit_activity_and_add_photos(): void
    {
        $name = 'Briefing Event ' . uniqid();
        $updatedName = 'Briefing Event (Updated) ' . uniqid();

        $activity = Activity::create([
            'name' => $name,
            'event_date' => '2026-06-01',
            'description' => 'Original briefing notes.',
        ]);
        $this->createdActivityIds[] = $activity->id;

        $newPhoto = UploadedFile::fake()->image('new_event_photo.webp');

        $response = $this->actingAs($this->adminUser)
            ->from('/admin/activities')
            ->post('/admin/activities/' . $activity->id, [
                'name' => $updatedName,
                'event_date' => '2026-06-02',
                'description' => 'Updated briefing description and agenda.',
                'photos' => [$newPhoto],
            ]);

        $response->assertRedirect('/admin/activities');

        $activity->refresh();
        $this->assertEquals($updatedName, $activity->name);
        $this->assertEquals('2026-06-02', $activity->event_date->format('Y-m-d'));
        $this->assertCount(1, $activity->photos);

        $this->createdFiles[] = public_path($activity->photos->first()->file_path);
    }

    /**
     * 21. Admin can remove individual activity gallery photos
     */
    public function test_21_admin_can_remove_individual_activity_gallery_photos(): void
    {
        $name = 'Activity For Photo Pruning ' . uniqid();
        $photo1 = UploadedFile::fake()->image('p1.jpg');
        $photo2 = UploadedFile::fake()->image('p2.jpg');

        $this->actingAs($this->adminUser)->post('/admin/activities', [
            'name' => $name,
            'event_date' => '2026-09-01',
            'description' => 'Testing partial photo removal.',
            'photos' => [$photo1, $photo2],
        ]);

        $activity = Activity::where('name', $name)->latest('id')->firstOrFail();
        $this->createdActivityIds[] = $activity->id;
        $this->assertCount(2, $activity->photos);

        $firstPhoto = $activity->photos->first();
        $secondPhoto = $activity->photos->last();
        $firstPath = public_path($firstPhoto->file_path);
        $secondPath = public_path($secondPhoto->file_path);

        $this->createdFiles[] = $firstPath;
        $this->createdFiles[] = $secondPath;

        // Remove only the first photo
        $response = $this->actingAs($this->adminUser)
            ->from('/admin/activities')
            ->post('/admin/activities/' . $activity->id, [
                'name' => $name,
                'event_date' => '2026-09-01',
                'description' => 'Testing partial photo removal.',
                'remove_photo_ids' => [$firstPhoto->id],
            ]);

        $response->assertRedirect('/admin/activities');

        $activity->refresh();
        $this->assertCount(1, $activity->photos);
        $this->assertFalse(File::exists($firstPath)); // Deleted
        $this->assertTrue(File::exists($secondPath)); // Kept
    }

    /**
     * 22. Admin can delete activity with complete media cleanup
     */
    public function test_22_admin_can_delete_activity_with_complete_media_cleanup(): void
    {
        $name = 'Activity To Delete ' . uniqid();
        $photo = UploadedFile::fake()->image('event_cleanup.jpg');

        $this->actingAs($this->adminUser)->post('/admin/activities', [
            'name' => $name,
            'event_date' => '2026-08-10',
            'description' => 'Will be completely removed.',
            'photos' => [$photo],
        ]);

        $activity = Activity::where('name', $name)->latest('id')->firstOrFail();
        $photoPath = public_path($activity->photos->first()->file_path);
        $this->createdFiles[] = $photoPath;
        $this->assertTrue(File::exists($photoPath));

        $response = $this->actingAs($this->adminUser)
            ->from('/admin/activities')
            ->delete('/admin/activities/' . $activity->id);

        $response->assertRedirect('/admin/activities');
        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
        $this->assertFalse(File::exists($photoPath));
    }

    /**
     * 23. Invalid activity data is rejected
     */
    public function test_23_invalid_activity_data_is_rejected(): void
    {
        // Missing name and description
        $response = $this->actingAs($this->adminUser)->post('/admin/activities', [
            'name' => '',
            'event_date' => 'not-a-date',
            'description' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'event_date', 'description']);

        // Uploading non-image file
        $badFile = UploadedFile::fake()->create('script.sh', 50);
        $response2 = $this->actingAs($this->adminUser)->post('/admin/activities', [
            'name' => 'Bad Upload ' . uniqid(),
            'event_date' => '2026-10-10',
            'description' => 'Test',
            'photos' => [$badFile],
        ]);

        $response2->assertSessionHasErrors('photos.0');
    }

    /**
     * 24. Invalid or nonexistent activity ID handled safely
     */
    public function test_24_invalid_or_nonexistent_activity_id_handled_safely(): void
    {
        $this->actingAs($this->adminUser)->post('/admin/activities/99999999', [
            'name' => 'Ghost Activity',
            'event_date' => '2026-10-10',
            'description' => 'Ghost',
        ])->assertStatus(404);

        $this->actingAs($this->adminUser)->delete('/admin/activities/99999999')->assertStatus(404);
    }

    /**
     * 25. Public Activities reflects database changes without exposing admin controls
     */
    public function test_25_public_activities_reflects_database_changes_without_admin_controls(): void
    {
        $eventName = 'Public Community Worship Night ' . uniqid();
        $activity = Activity::create([
            'name' => $eventName,
            'event_date' => '2026-11-15',
            'description' => 'A public night of prayer and acoustic worship.',
        ]);
        $this->createdActivityIds[] = $activity->id;

        // Public/guest access
        $response = $this->get('/activity');
        $response->assertStatus(200);
        $response->assertSee($eventName);
        $response->assertSee('November 15, 2026');
        $response->assertSee('A public night of prayer and acoustic worship.');

        // Guest must NOT see edit/delete controls or add button
        $response->assertDontSee('id="btn-open-add-activity"', false);
        $response->assertDontSee('btn-edit-activity');
    }

    // ==========================================
    // 3. S0 - S2 REGRESSION (26)
    // ==========================================

    /**
     * 26. S0 - S2 functionality remains intact
     */
    public function test_26_s0_to_s2_regression_admin_ganteng_logout_icons_and_username(): void
    {
        // /admin-ganteng login entrance accessible
        $this->get('/admin-ganteng')->assertStatus(200)->assertSee('Admin Portal');

        // Logout redirects to /admin-ganteng
        $logoutResponse = $this->actingAs($this->adminUser)->post('/admin/logout');
        $logoutResponse->assertRedirect('/admin-ganteng');
        $this->assertGuest();

        // Dashboard renders chart & shield icons and uses authenticated username
        $jojoAdmin = User::updateOrCreate(
            ['email' => 'jojo_ganteng@gmail.com'],
            [
                'name' => 'Jojo Admin',
                'username' => 'rilbiezzz',
                'password' => Hash::make('jojo123'),
                'role' => 'admin',
            ]
        );

        $dashResponse = $this->actingAs($jojoAdmin)->get('/admin/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('<strong>rilbiezzz</strong>', false);
        $dashResponse->assertSee('Welcome back, rilbiezzz');
        $dashResponse->assertDontSee('Jojo Admin (rilbiezzz)');

        // Chart & Shield SVG paths rendered
        $dashResponse->assertSee('M3 13.125C3 12.504 3.504 12', false);
        $dashResponse->assertSee('M9 12.75 11.25 15 15 9.75', false);
    }
}
