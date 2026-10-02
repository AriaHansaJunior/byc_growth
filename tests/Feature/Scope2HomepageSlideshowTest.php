<?php

namespace Tests\Feature;

use App\Models\HomepageSlide;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Scope S2 â€” Homepage Content & Slideshow Management Test Suite
 *
 * Verifies:
 * - Access & Authorization (Admin vs Normal vs Guest)
 * - Create & Image Upload (Validation, persistence, error handling, orphan prevention)
 * - Read & User Homepage Integration (Database querying, fallback, sequence order)
 * - Delete & Media Cleanup (Admin only, DB removal, file cleanup, 404 safety)
 * - Reordering (Up/down controls, batch reorder, injection prevention, duplicate rejection)
 * - Separation (Admin controls absent from public user view)
 */
class Scope2HomepageSlideshowTest extends TestCase
{
    protected User $adminUser;
    protected User $normalUser;
    protected array $createdFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin User
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Utama',
                'username' => 'admin_utama',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        if ($this->adminUser->role !== 'admin') {
            $this->adminUser->update(['role' => 'admin']);
        }

        // 2. Normal User
        $this->normalUser = User::firstOrCreate(
            ['email' => 'member_s2@example.com'],
            [
                'name' => 'Fellowship Member S2',
                'username' => 'member_s2',
                'password' => Hash::make('secret123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        // Clean up any test files uploaded during test runs
        foreach ($this->createdFiles as $path) {
            if (File::exists($path) && is_file($path)) {
                File::delete($path);
            }
        }

        self::restoreDefaultSlides();

        parent::tearDown();
    }

    public static function restoreDefaultSlides(): void
    {
        HomepageSlide::query()->delete();

        $defaultSlides = [
            [
                'file' => 'assets/images/hero-slide-1.jpg',
                'title' => 'Growing in Faith & Fellowship',
                'caption' => 'A vibrant youth fellowship rooted in faith, love, and spiritual unity.',
                'sort_order' => 1,
            ],
            [
                'file' => 'assets/images/hero-slide-2.jpg',
                'title' => 'Sunday Service & Worship',
                'caption' => 'Connecting hearts through passionate worship, prayer, and God\'s Word.',
                'sort_order' => 2,
            ],
            [
                'file' => 'assets/images/hero-slide-3.jpg',
                'title' => 'Community Outreach & Service',
                'caption' => 'Sharing Christ\'s love through active service and genuine community care.',
                'sort_order' => 3,
            ],
        ];

        foreach ($defaultSlides as $item) {
            $media = MediaFile::where('file_path', $item['file'])->first();
            $mediaId = $media ? $media->id : null;

            HomepageSlide::create([
                'media_file_id' => $mediaId,
                'title' => $item['title'],
                'caption' => $item['caption'],
                'sort_order' => $item['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    protected function trackFile(string $path): void
    {
        $this->createdFiles[] = $path;
    }

    // ==========================================
    // ACCESS & AUTHORIZATION (1 - 4)
    // ==========================================

    /**
     * 1. Admin can access Admin Homepage
     */
    public function test_01_admin_can_access_admin_homepage(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/homepage');

        $response->assertStatus(200);
        $response->assertSee('Homepage Management');
        $response->assertSee('Slideshow Management');
        $response->assertSee('Add Photo');
        $response->assertSee('id="modal-add-slide"', false);
    }

    /**
     * 2. Normal user cannot access Admin Homepage management
     */
    public function test_02_normal_user_cannot_access_admin_homepage_management(): void
    {
        $response = $this->actingAs($this->normalUser)->get('/admin/homepage');

        $response->assertStatus(403);
    }

    /**
     * 3. Unauthenticated user cannot access Admin Homepage management
     */
    public function test_03_unauthenticated_user_cannot_access_admin_homepage(): void
    {
        $response = $this->get('/admin/homepage');

        $response->assertRedirect('/admin-ganteng');
    }

    /**
     * 4. Unauthenticated user cannot mutate slideshow data
     */
    public function test_04_unauthenticated_user_cannot_mutate_slideshow_data(): void
    {
        $slide = HomepageSlide::first() ?? HomepageSlide::create(['sort_order' => 1, 'is_active' => true]);

        // Store
        $this->post('/admin/homepage/slides', [
            'image' => UploadedFile::fake()->image('test.jpg'),
        ])->assertRedirect('/admin-ganteng');

        // Delete
        $this->delete("/admin/homepage/slides/{$slide->id}")
            ->assertRedirect('/admin-ganteng');

        // Reorder
        $this->post('/admin/homepage/slides/reorder', [
            'order' => [$slide->id],
        ])->assertRedirect('/admin-ganteng');

        // Move Up
        $this->post("/admin/homepage/slides/{$slide->id}/move-up")
            ->assertRedirect('/admin-ganteng');

        // Move Down
        $this->post("/admin/homepage/slides/{$slide->id}/move-down")
            ->assertRedirect('/admin-ganteng');
    }

    /**
     * 5. Normal authenticated user cannot mutate slideshow data
     */
    public function test_05_normal_user_cannot_mutate_slideshow_data(): void
    {
        $slide = HomepageSlide::first() ?? HomepageSlide::create(['sort_order' => 1, 'is_active' => true]);

        // Store
        $this->actingAs($this->normalUser)
            ->post('/admin/homepage/slides', [
                'image' => UploadedFile::fake()->image('test.jpg'),
            ])->assertStatus(403);

        // Delete
        $this->actingAs($this->normalUser)
            ->delete("/admin/homepage/slides/{$slide->id}")
            ->assertStatus(403);

        // Reorder
        $this->actingAs($this->normalUser)
            ->post('/admin/homepage/slides/reorder', [
                'order' => [$slide->id],
            ])->assertStatus(403);

        // Move Up
        $this->actingAs($this->normalUser)
            ->post("/admin/homepage/slides/{$slide->id}/move-up")
            ->assertStatus(403);

        // Move Down
        $this->actingAs($this->normalUser)
            ->post("/admin/homepage/slides/{$slide->id}/move-down")
            ->assertStatus(403);
    }

    // ==========================================
    // CREATE & IMAGE UPLOAD (6 - 11)
    // ==========================================

    /**
     * 6. Admin can upload a valid image (JPEG)
     */
    public function test_06_admin_can_upload_valid_jpeg_image(): void
    {
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('fellowship_youth.jpg', 1200, 800);

        $response = $this->post('/admin/homepage/slides', [
            'image' => $file,
            'title' => 'Youth Retreat 2026',
            'caption' => 'Growing together in faith and genuine love.',
        ]);

        $response->assertRedirect('/admin/homepage');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('homepage_slides', [
            'title' => 'Youth Retreat 2026',
            'caption' => 'Growing together in faith and genuine love.',
        ]);

        $slide = HomepageSlide::where('title', 'Youth Retreat 2026')->firstOrFail();
        $this->assertNotNull($slide->media_file_id);
        $this->assertNotNull($slide->media);
        $this->trackFile(public_path($slide->media->file_path));
    }

    /**
     * 7. Admin can upload valid PNG and WEBP images
     */
    public function test_07_admin_can_upload_valid_png_and_webp_images(): void
    {
        $this->actingAs($this->adminUser);

        // PNG
        $pngFile = UploadedFile::fake()->image('worship_service.png', 800, 600);
        $res1 = $this->post('/admin/homepage/slides', [
            'image' => $pngFile,
            'title' => 'Sunday Worship PNG',
        ]);
        $res1->assertRedirect('/admin/homepage');
        $slidePng = HomepageSlide::where('title', 'Sunday Worship PNG')->firstOrFail();
        $this->trackFile(public_path($slidePng->media->file_path));

        // WEBP
        $webpFile = UploadedFile::fake()->image('outreach.webp', 800, 600);
        $res2 = $this->post('/admin/homepage/slides', [
            'image' => $webpFile,
            'title' => 'Community Outreach WEBP',
        ]);
        $res2->assertRedirect('/admin/homepage');
        $slideWebp = HomepageSlide::where('title', 'Community Outreach WEBP')->firstOrFail();
        $this->trackFile(public_path($slideWebp->media->file_path));
    }

    /**
     * 8. Invalid file type is rejected
     */
    public function test_08_invalid_file_type_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $slidesBefore = HomepageSlide::count();
        $mediaBefore = MediaFile::count();

        // PDF attempt
        $pdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $res1 = $this->post('/admin/homepage/slides', [
            'image' => $pdf,
            'title' => 'Malicious PDF',
        ]);
        $res1->assertSessionHasErrors('image');

        // TXT attempt
        $txt = UploadedFile::fake()->create('script.txt', 100, 'text/plain');
        $res2 = $this->post('/admin/homepage/slides', [
            'image' => $txt,
            'title' => 'Malicious TXT',
        ]);
        $res2->assertSessionHasErrors('image');

        // Verify no orphaned records
        $this->assertEquals($slidesBefore, HomepageSlide::count());
        $this->assertEquals($mediaBefore, MediaFile::count());
    }

    /**
     * 9. Oversized file (> 5MB) is rejected
     */
    public function test_09_oversized_file_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $slidesBefore = HomepageSlide::count();

        // 6MB image
        $largeFile = UploadedFile::fake()->create('huge.jpg', 6144, 'image/jpeg');

        $response = $this->post('/admin/homepage/slides', [
            'image' => $largeFile,
            'title' => 'Oversized Slide',
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertEquals($slidesBefore, HomepageSlide::count());
    }

    /**
     * 10. Missing file is rejected
     */
    public function test_10_missing_file_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/admin/homepage/slides', [
            'title' => 'No Image Attached',
        ]);

        $response->assertSessionHasErrors('image');
    }

    /**
     * 11. Database record is created and linked after successful upload
     */
    public function test_11_database_record_is_created_after_successful_upload(): void
    {
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('test_slide_db.jpg', 1000, 600);

        $this->post('/admin/homepage/slides', [
            'image' => $file,
            'title' => 'Database Link Test',
        ]);

        $slide = HomepageSlide::where('title', 'Database Link Test')->firstOrFail();
        $this->assertDatabaseHas('media_files', [
            'id' => $slide->media_file_id,
            'fileable_type' => HomepageSlide::class,
            'fileable_id' => $slide->id,
        ]);

        $this->trackFile(public_path($slide->media->file_path));
    }

    // ==========================================
    // READ & USER HOMEPAGE INTEGRATION (12 - 15)
    // ==========================================

    /**
     * 12. User Homepage reads slideshow from database
     */
    public function test_12_user_homepage_reads_slideshow_from_database(): void
    {
        $this->actingAs($this->adminUser);

        $uniqueTitle = 'Unique Title ' . uniqid();
        $file = UploadedFile::fake()->image('unique_slide.jpg', 800, 600);

        $this->post('/admin/homepage/slides', [
            'image' => $file,
            'title' => $uniqueTitle,
        ]);

        $slide = HomepageSlide::where('title', $uniqueTitle)->firstOrFail();
        $this->trackFile(public_path($slide->media->file_path));

        // Public visitor fetches homepage
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee($slide->image_url, false);
    }

    /**
     * 13. Slideshow order in database is strictly respected on User Homepage
     */
    public function test_13_slideshow_order_is_respected_on_user_homepage(): void
    {
        // Clear existing slides for strict order testing
        HomepageSlide::query()->delete();

        $slide1 = HomepageSlide::create([
            'title' => 'Alpha Photo Sequence',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $slide2 = HomepageSlide::create([
            'title' => 'Beta Photo Sequence',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        $content = $response->getContent();
        $pos1 = strpos($content, 'Alpha Photo Sequence');
        $pos2 = strpos($content, 'Beta Photo Sequence');

        $this->assertNotFalse($pos1, 'First slide not found on homepage');
        $this->assertNotFalse($pos2, 'Second slide not found on homepage');
        $this->assertTrue($pos1 < $pos2, 'Slide 1 should appear before Slide 2 in homepage output');
    }

    /**
     * 14. Fallback is rendered gracefully when zero slides exist
     */
    public function test_14_fallback_rendered_when_zero_slides_exist(): void
    {
        HomepageSlide::query()->delete();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('hero-group-photo');
        $response->assertSee('group-photo-dummy.svg');
    }

    /**
     * 15. Normal user homepage does NOT render administrative controls
     */
    public function test_15_admin_controls_not_visible_on_normal_user_homepage(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $response->assertDontSee('btn-open-add-slide', false);
        $response->assertDontSee('btn-delete-slide', false);
        $response->assertDontSee('btn-reorder-up', false);
        $response->assertDontSee('btn-reorder-down', false);
        $response->assertDontSee('id="modal-add-slide"', false);
        $response->assertDontSee('Slideshow Management', false);
    }

    // ==========================================
    // DELETE & MEDIA CLEANUP (16 - 20)
    // ==========================================

    /**
     * 16. Admin can delete slideshow image
     */
    public function test_16_admin_can_delete_slideshow_image(): void
    {
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('to_delete.jpg', 800, 600);
        $this->post('/admin/homepage/slides', [
            'image' => $file,
            'title' => 'Slide To Delete',
        ]);

        $slide = HomepageSlide::where('title', 'Slide To Delete')->firstOrFail();
        $slideId = $slide->id;

        $delRes = $this->delete("/admin/homepage/slides/{$slideId}");
        $delRes->assertRedirect('/admin/homepage');
        $delRes->assertSessionHas('success');

        $this->assertDatabaseMissing('homepage_slides', ['id' => $slideId]);
    }

    /**
     * 17. Delete requires proper admin authorization
     */
    public function test_17_delete_requires_proper_admin_authorization(): void
    {
        $slide = HomepageSlide::first() ?? HomepageSlide::create(['sort_order' => 1, 'is_active' => true]);

        // Unauthenticated
        $this->delete("/admin/homepage/slides/{$slide->id}")
            ->assertRedirect('/admin-ganteng');

        // Regular user
        $this->actingAs($this->normalUser)
            ->delete("/admin/homepage/slides/{$slide->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('homepage_slides', ['id' => $slide->id]);
    }

    /**
     * 18. Associated uploaded media file is cleaned up when slide is deleted
     */
    public function test_18_associated_media_file_is_cleaned_up_on_delete(): void
    {
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('cleanup_test.jpg', 800, 600);
        $this->post('/admin/homepage/slides', [
            'image' => $file,
            'title' => 'Cleanup Test Slide',
        ]);

        $slide = HomepageSlide::where('title', 'Cleanup Test Slide')->firstOrFail();
        $mediaPath = public_path($slide->media->file_path);
        $mediaId = $slide->media_file_id;

        $this->assertFileExists($mediaPath);

        $this->delete("/admin/homepage/slides/{$slide->id}");

        $this->assertDatabaseMissing('homepage_slides', ['id' => $slide->id]);
        $this->assertDatabaseMissing('media_files', ['id' => $mediaId]);
        $this->assertFileDoesNotExist($mediaPath);
    }

    /**
     * 19. Deleting nonexistent slideshow image returns 404
     */
    public function test_19_nonexistent_image_is_handled_safely_on_delete(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->delete('/admin/homepage/slides/99999999');

        $response->assertStatus(404);
    }

    // ==========================================
    // REORDERING (20 - 25)
    // ==========================================

    /**
     * 20. Admin can reorder slideshow via batch endpoint
     */
    public function test_20_admin_can_reorder_slideshow_via_batch_endpoint(): void
    {
        $this->actingAs($this->adminUser);

        HomepageSlide::query()->delete();

        $s1 = HomepageSlide::create(['title' => 'Item 1', 'sort_order' => 1, 'is_active' => true]);
        $s2 = HomepageSlide::create(['title' => 'Item 2', 'sort_order' => 2, 'is_active' => true]);
        $s3 = HomepageSlide::create(['title' => 'Item 3', 'sort_order' => 3, 'is_active' => true]);

        // Invert order: s3, s1, s2
        $response = $this->post('/admin/homepage/slides/reorder', [
            'order' => [$s3->id, $s1->id, $s2->id],
        ]);

        $response->assertRedirect('/admin/homepage');

        $this->assertEquals(1, $s3->fresh()->sort_order);
        $this->assertEquals(2, $s1->fresh()->sort_order);
        $this->assertEquals(3, $s2->fresh()->sort_order);
    }

    /**
     * 21. Reorder reflects on User Homepage
     */
    public function test_21_reorder_reflects_on_user_homepage(): void
    {
        $this->actingAs($this->adminUser);

        HomepageSlide::query()->delete();

        $s1 = HomepageSlide::create(['title' => 'First In Line', 'sort_order' => 1, 'is_active' => true]);
        $s2 = HomepageSlide::create(['title' => 'Second In Line', 'sort_order' => 2, 'is_active' => true]);

        // Invert
        $this->post('/admin/homepage/slides/reorder', [
            'order' => [$s2->id, $s1->id],
        ]);

        $userHome = $this->get('/');
        $userHome->assertStatus(200);

        $content = $userHome->getContent();
        $posSecond = strpos($content, 'Second In Line');
        $posFirst = strpos($content, 'First In Line');

        $this->assertTrue($posSecond < $posFirst, 'Second In Line should now appear before First In Line');
    }

    /**
     * 22. Admin can move slide up and down with dedicated endpoints
     */
    public function test_22_admin_can_move_slide_up_and_down(): void
    {
        $this->actingAs($this->adminUser);

        HomepageSlide::query()->delete();

        $s1 = HomepageSlide::create(['title' => 'Move 1', 'sort_order' => 1, 'is_active' => true]);
        $s2 = HomepageSlide::create(['title' => 'Move 2', 'sort_order' => 2, 'is_active' => true]);

        // Move s2 up
        $this->post("/admin/homepage/slides/{$s2->id}/move-up");
        $this->assertEquals(1, $s2->fresh()->sort_order);
        $this->assertEquals(2, $s1->fresh()->sort_order);

        // Move s2 down
        $this->post("/admin/homepage/slides/{$s2->id}/move-down");
        $this->assertEquals(2, $s2->fresh()->sort_order);
        $this->assertEquals(1, $s1->fresh()->sort_order);
    }

    /**
     * 23. Invalid slide IDs in reorder are rejected
     */
    public function test_23_invalid_ids_in_reorder_are_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/admin/homepage/slides/reorder', [
            'order' => [9999999, 8888888],
        ]);

        $response->assertSessionHasErrors('order.0');
    }

    /**
     * 24. Foreign IDs cannot be injected into reorder
     */
    public function test_24_foreign_ids_cannot_be_injected_into_reorder(): void
    {
        $this->actingAs($this->adminUser);

        $s1 = HomepageSlide::first() ?? HomepageSlide::create(['sort_order' => 1, 'is_active' => true]);

        $response = $this->post('/admin/homepage/slides/reorder', [
            'order' => [$s1->id, 9999999],
        ]);

        $response->assertSessionHasErrors();
    }

    /**
     * 25. Duplicate IDs in reorder are rejected
     */
    public function test_25_duplicate_ids_in_reorder_are_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $s1 = HomepageSlide::first() ?? HomepageSlide::create(['sort_order' => 1, 'is_active' => true]);

        $response = $this->post('/admin/homepage/slides/reorder', [
            'order' => [$s1->id, $s1->id],
        ]);

        $response->assertSessionHasErrors('error');
    }

    /**
     * 26. AJAX reorder returns JSON response
     */
    public function test_26_ajax_reorder_returns_json_response(): void
    {
        $this->actingAs($this->adminUser);

        HomepageSlide::query()->delete();
        $s1 = HomepageSlide::create(['title' => 'Ajax 1', 'sort_order' => 1, 'is_active' => true]);
        $s2 = HomepageSlide::create(['title' => 'Ajax 2', 'sort_order' => 2, 'is_active' => true]);

        $response = $this->postJson('/admin/homepage/slides/reorder', [
            'order' => [$s2->id, $s1->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /**
     * 27. Admin can upload photo without title/caption; defaults title to filename and caption to null
     */
    public function test_27_upload_photo_without_title_and_caption(): void
    {
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('my-awesome-photo.jpg', 1200, 1200);

        $response = $this->post('/admin/homepage/slides', [
            'image' => $file,
        ]);

        $response->assertRedirect('/admin/homepage');
        $response->assertSessionHas('success', 'Slideshow photo added successfully.');

        $this->assertDatabaseHas('homepage_slides', [
            'title' => 'my-awesome-photo.jpg',
            'caption' => null,
        ]);

        $slide = HomepageSlide::where('title', 'my-awesome-photo.jpg')->firstOrFail();
        $this->assertNull($slide->caption);
        $this->assertNotNull($slide->media);
        $this->assertEquals('my-awesome-photo.jpg', $slide->media->original_name);
        $this->trackFile(public_path($slide->media->file_path));
    }

    /**
     * 28. Admin homepage renders floating toast container and crop studio modal without title/caption inputs
     */
    public function test_28_admin_homepage_modal_crop_studio_and_floating_toast(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->withSession(['success' => 'Slideshow photo added successfully.'])
            ->get('/admin/homepage');

        $response->assertStatus(200);

        // Assert floating toast container and item
        $response->assertSee('id="admin-toast-container"', false);
        $response->assertSee('class="admin-toast-item toast-success alert-box-success"', false);
        $response->assertSee('Slideshow photo added successfully.');

        // Assert 1:1 crop studio exists in modal
        $response->assertSee('id="crop-studio"', false);
        $response->assertSee('id="crop-viewport"', false);
        $response->assertDontSee('1:1 Square Crop Preview');

        // Assert legacy title and caption inputs are removed from modal
        $response->assertDontSee('name="title"', false);
        $response->assertDontSee('name="caption"', false);
    }
}
