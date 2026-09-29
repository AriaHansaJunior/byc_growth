<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope9CashManagementTest extends TestCase
{
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s9@bycgrowth.org'],
            [
                'name' => 'Admin S9',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_s9@bycgrowth.org'],
            [
                'name' => 'User S9',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    /**
     * 1. Admin can access Cash Management.
     */
    public function test_01_admin_can_access_cash_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Cash Management');
        $response->assertSee('Record Contribution');
    }

    /**
     * 2. Normal authenticated user cannot access Cash Management.
     */
    public function test_02_normal_authenticated_user_cannot_access_cash_management(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/cash-management');
        $response->assertStatus(403);
    }

    /**
     * 3. Unauthenticated public user cannot access Cash Management.
     */
    public function test_03_unauthenticated_public_user_cannot_access_cash_management(): void
    {
        Auth::logout();
        $response = $this->get('/cash-management');
        $response->assertRedirect('/admin/login');
    }

    /**
     * 4. Public homepage Cash Management card remains visible.
     */
    public function test_04_public_homepage_cash_management_card_remains_visible(): void
    {
        Auth::logout();
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Cash Management');
        $response->assertSee('Contact the admin to view your cash contribution.');
    }

    /**
     * 5. Public homepage Cash Management card cannot enter the protected area.
     */
    public function test_05_public_homepage_cash_management_card_cannot_enter_protected_area(): void
    {
        Auth::logout();
        $response = $this->get('/');
        $response->assertStatus(200);

        // Verify the card is visually disabled and is NOT a functional link to cash-management
        $response->assertSee('destination-card-disabled');
        $response->assertSee('aria-disabled="true"', false);
        $response->assertDontSee('<a href="' . route('cash-management') . '" class="destination-card dest-cash">', false);
    }

    /**
     * 6. Admin can submit a valid transaction.
     */
    public function test_06_admin_can_submit_valid_transaction(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'S9 Contributor Alpha',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('proof_alpha.jpg');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $proof,
        ]);

        $response->assertRedirect('/cash-management');
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
            'contributor_name' => 'S9 Contributor Alpha',
            'account_type' => 'BCA',
            'amount' => 50000,
        ]);
    }

    /**
     * 7. Full name/member association is persisted correctly.
     */
    public function test_07_full_name_and_member_association_persisted_correctly(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Jonathan Wijaya',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.jpg');

        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest('id')->firstOrFail();
        $this->assertEquals($member->id, $tx->member_id);
        $this->assertEquals('Jonathan Wijaya', $tx->contributor_name);
        $this->assertEquals('Jonathan Wijaya', $tx->member->full_name);
    }

    /**
     * 8. Account type is persisted correctly.
     */
    public function test_08_account_type_persisted_correctly(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Account Type Checker',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.png');

        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Bank Jatim Transfer',
            'amount' => 75000,
            'proof' => $proof,
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest('id')->firstOrFail();
        $this->assertEquals('Bank Jatim Transfer', $tx->account_type);
    }

    /**
     * 9. Valid transfer amount is persisted correctly.
     */
    public function test_09_valid_transfer_amount_persisted_correctly(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Amount Checker',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.jpg');

        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Cash',
            'amount' => 125000,
            'proof' => $proof,
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest('id')->firstOrFail();
        $this->assertEquals(125000, (float) $tx->amount);
    }

    /**
     * 10. Server timestamp is generated server-side.
     */
    public function test_10_server_timestamp_is_generated_server_side(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Timestamp Tester',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('proof.jpg');

        $before = Carbon::now('Asia/Jakarta')->subSecond();

        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BRI',
            'amount' => 60000,
            'proof' => $proof,
        ]);

        $after = Carbon::now('Asia/Jakarta')->addSecond();

        $tx = CashTransaction::where('member_id', $member->id)->latest('id')->firstOrFail();
        $this->assertTrue($tx->created_at->between($before, $after));
        $this->assertEquals(Carbon::now('Asia/Jakarta')->toDateString(), $tx->transaction_date->toDateString());
    }

    /**
     * 11. Client cannot override the server transaction timestamp.
     */
    public function test_11_client_cannot_override_server_transaction_timestamp(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'Spoof Timestamp Attempt',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('proof.jpg');

        // Client attempts to pass a spoofed date in the past
        $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $proof,
            'transaction_date' => '1999-01-01',
            'created_at' => '1999-01-01 00:00:00',
        ]);

        $tx = CashTransaction::where('member_id', $member->id)->latest('id')->firstOrFail();
        // Server overrides spoofed date with current server date
        $this->assertNotEquals('1999-01-01', $tx->transaction_date->toDateString());
        $this->assertEquals(Carbon::now('Asia/Jakarta')->toDateString(), $tx->transaction_date->toDateString());
    }

    /**
     * 12. Valid PNG proof is accepted.
     */
    public function test_12_valid_png_proof_is_accepted(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'PNG Contributor',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.png');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response->assertRedirect('/cash-management');
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
        ]);
    }

    /**
     * 13. Valid JPG proof is accepted.
     */
    public function test_13_valid_jpg_proof_is_accepted(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'JPG Contributor',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.jpg');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response->assertRedirect('/cash-management');
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
        ]);
    }

    /**
     * 14. Valid JPEG proof is accepted.
     */
    public function test_14_valid_jpeg_proof_is_accepted(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'JPEG Contributor',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.jpeg');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BNI',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response->assertRedirect('/cash-management');
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
        ]);
    }

    /**
     * 15. Valid supported image proof is accepted (WEBP).
     */
    public function test_15_valid_webp_proof_is_accepted(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'WEBP Contributor',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('transfer.webp');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response->assertRedirect('/cash-management');
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
        ]);
    }

    /**
     * 16. PDF proof is rejected.
     */
    public function test_16_pdf_proof_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'PDF Reject Tester',
            'is_active' => true,
        ]);

        $pdf = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $pdf,
        ]);

        $response->assertSessionHasErrors('proof');
    }

    /**
     * 17. PPT/PPTX proof is rejected.
     */
    public function test_17_ppt_proof_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'PPT Reject Tester',
            'is_active' => true,
        ]);

        $pptx = UploadedFile::fake()->create('presentation.pptx', 200, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $pptx,
        ]);

        $response->assertSessionHasErrors('proof');
    }

    /**
     * 18. DOC/DOCX proof is rejected.
     */
    public function test_18_doc_proof_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'DOC Reject Tester',
            'is_active' => true,
        ]);

        $docx = UploadedFile::fake()->create('document.docx', 150, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $docx,
        ]);

        $response->assertSessionHasErrors('proof');
    }

    /**
     * 19. Non-image files are rejected (e.g. ZIP, TXT).
     */
    public function test_19_non_image_files_are_rejected(): void
    {
        $this->actingAs($this->adminUser);

        $member = Member::create([
            'full_name' => 'ZIP Reject Tester',
            'is_active' => true,
        ]);

        $zip = UploadedFile::fake()->create('archive.zip', 300, 'application/zip');

        $response = $this->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $zip,
        ]);

        $response->assertSessionHasErrors('proof');
    }

    /**
     * 20. Invalid transaction does not leave broken database/media state.
     */
    public function test_20_invalid_transaction_does_not_leave_broken_database_or_media_state(): void
    {
        $this->actingAs($this->adminUser);

        $mediaCountBefore = MediaFile::count();
        $txCountBefore = CashTransaction::count();

        // Submit with invalid negative amount
        $proof = UploadedFile::fake()->image('test.jpg');
        $response = $this->post('/admin/cash-management', [
            'member_id' => 9999999, // non-existent member
            'account_type' => 'BCA',
            'amount' => -500, // invalid negative amount
            'proof' => $proof,
        ]);

        $response->assertSessionHasErrors(['member_id', 'amount']);

        // Verify no orphaned media or transactions were created
        $this->assertEquals($mediaCountBefore, MediaFile::count());
        $this->assertEquals($txCountBefore, CashTransaction::count());
    }

    /**
     * 21. Existing Scope 8 cash functionality does not regress.
     */
    public function test_21_existing_scope_8_cash_functionality_does_not_regress(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Total Cash Inflow:');
        $response->assertSee('total-cash-display');
        $response->assertSee('Source Bank / Account Type');
        $response->assertSee('Transfer Amount');
        $response->assertSee('Transfer Proof Image');
    }
}
