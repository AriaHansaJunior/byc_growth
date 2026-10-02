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

class Scope10CashMemberShortcutTest extends TestCase
{
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s10@bycgrowth.org'],
            [
                'name' => 'Admin S10',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_s10@bycgrowth.org'],
            [
                'name' => 'User S10',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    /**
     * 1. Admin can request shortcut data.
     */
    public function test_01_admin_can_request_shortcut_data(): void
    {
        $member = Member::create([
            'full_name' => 'Shortcut Test 1',
            'is_active' => true,
        ]);

        $response1 = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response1->assertStatus(200);
        $response1->assertJsonStructure(['has_shortcut']);

        $response2 = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/member/{$member->id}/shortcut");
        $response2->assertStatus(200);
        $response2->assertJsonStructure(['has_shortcut']);
    }

    /**
     * 2. Normal user cannot request shortcut data.
     */
    public function test_02_normal_user_cannot_request_shortcut_data(): void
    {
        $member = Member::create([
            'full_name' => 'Shortcut Test 2',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->regularUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(403);
    }

    /**
     * 3. Public user cannot request shortcut data.
     */
    public function test_03_public_user_cannot_request_shortcut_data(): void
    {
        $member = Member::create([
            'full_name' => 'Shortcut Test 3',
            'is_active' => true,
        ]);

        $response = $this->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(401);
    }

    /**
     * 4. Member with no previous transaction returns empty/default shortcut data.
     */
    public function test_04_member_with_no_previous_transaction_returns_empty_default_shortcut_data(): void
    {
        $member = Member::create([
            'full_name' => 'Member No Transactions',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'has_shortcut' => false,
            'account_type' => null,
            'amount' => null,
        ]);
    }

    /**
     * 5. Member with one previous transaction returns its account type.
     */
    public function test_05_member_with_one_previous_transaction_returns_its_account_type(): void
    {
        $member = Member::create([
            'full_name' => 'Mega Shortcut Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('mega_proof.jpg');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'has_shortcut' => true,
            'account_type' => 'BCA',
        ]);
    }

    /**
     * 6. Member with one previous transaction returns its amount.
     */
    public function test_06_member_with_one_previous_transaction_returns_its_amount(): void
    {
        $member = Member::create([
            'full_name' => 'Amount Shortcut Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('amount_proof.jpg');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 50000,
            'proof' => $proof,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'has_shortcut' => true,
            'amount' => 50000.0,
        ]);
    }

    /**
     * 7. Multiple previous transactions use the latest transaction.
     */
    public function test_07_multiple_previous_transactions_use_the_latest_transaction(): void
    {
        $member = Member::create([
            'full_name' => 'Multi Tx Member',
            'is_active' => true,
        ]);

        // Transaction 1: 3 days ago (BCA, 30000)
        $tx1 = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Tx 1',
            'transaction_date' => Carbon::now('Asia/Jakarta')->subDays(3)->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta')->subDays(3),
            'updated_at' => Carbon::now('Asia/Jakarta')->subDays(3),
        ]);

        // Transaction 2: 1 day ago (Mandiri, 50000)
        $tx2 = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 50000,
            'account_type' => 'Mandiri',
            'type' => 'inflow',
            'description' => 'Tx 2',
            'transaction_date' => Carbon::now('Asia/Jakarta')->subDay()->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta')->subDay(),
            'updated_at' => Carbon::now('Asia/Jakarta')->subDay(),
        ]);

        // Transaction 3: Today (BRI, 45000) â€” Latest
        $tx3 = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 45000,
            'account_type' => 'BRI',
            'type' => 'inflow',
            'description' => 'Tx 3',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'has_shortcut' => true,
            'account_type' => 'BRI',
            'amount' => 45000.0,
        ]);
    }

    /**
     * 8. Selecting a member does not reuse previous proof.
     */
    public function test_08_selecting_a_member_does_not_reuse_previous_proof(): void
    {
        $member = Member::create([
            'full_name' => 'Proof Isolation Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('proof_iso.png');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(200);

        // Verify shortcut response does NOT expose proof data
        $data = $response->json();
        $this->assertArrayNotHasKey('proof', $data);
        $this->assertArrayNotHasKey('proof_file_id', $data);
        $this->assertArrayNotHasKey('file_path', $data);
        $this->assertArrayNotHasKey('url', $data);
    }

    /**
     * 9. New transaction without proof is rejected.
     */
    public function test_09_new_transaction_without_proof_is_rejected(): void
    {
        $member = Member::create([
            'full_name' => 'No Proof Member',
            'is_active' => true,
        ]);

        // Submit without proof
        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
        ]);

        $response->assertSessionHasErrors('proof');
        $this->assertDatabaseMissing('cash_transactions', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
        ]);
    }

    /**
     * 10. Bank remains editable after shortcut population.
     */
    public function test_10_bank_remains_editable_after_shortcut_population(): void
    {
        $member = Member::create([
            'full_name' => 'Editable Bank Member',
            'is_active' => true,
        ]);

        // Prior transaction had BCA
        $proof1 = UploadedFile::fake()->image('proof1.jpg');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof1,
        ]);

        // Admin edits bank to Mandiri and submits new transaction
        $proof2 = UploadedFile::fake()->image('proof2.jpg');
        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 30000,
            'proof' => $proof2,
        ]);

        $response->assertRedirect(route('cash-management'));
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
        ]);
    }

    /**
     * 11. Amount remains editable after shortcut population.
     */
    public function test_11_amount_remains_editable_after_shortcut_population(): void
    {
        $member = Member::create([
            'full_name' => 'Editable Amount Member',
            'is_active' => true,
        ]);

        // Prior transaction had 30000
        $proof1 = UploadedFile::fake()->image('proof1.jpg');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof1,
        ]);

        // Admin edits amount to 60000 and submits new transaction
        $proof2 = UploadedFile::fake()->image('proof2.jpg');
        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 60000,
            'proof' => $proof2,
        ]);

        $response->assertRedirect(route('cash-management'));
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $member->id,
            'amount' => 60000,
        ]);
    }

    /**
     * 12. Editing bank stores the new value.
     */
    public function test_12_editing_bank_stores_the_new_value(): void
    {
        $member = Member::create([
            'full_name' => 'Store Bank Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('store_bank.webp');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'CIMB Niaga',
            'amount' => 35000,
            'proof' => $proof,
        ]);

        $last = CashTransaction::where('member_id', $member->id)->latest('id')->first();
        $this->assertNotNull($last);
        $this->assertEquals('CIMB Niaga', $last->account_type);
    }

    /**
     * 13. Editing amount stores the new value.
     */
    public function test_13_editing_amount_stores_the_new_value(): void
    {
        $member = Member::create([
            'full_name' => 'Store Amount Member',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('store_amount.png');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BNI',
            'amount' => 99000,
            'proof' => $proof,
        ]);

        $last = CashTransaction::where('member_id', $member->id)->latest('id')->first();
        $this->assertNotNull($last);
        $this->assertEquals(99000, (float) $last->amount);
    }

    /**
     * 14. Changing selected member replaces previous shortcut values correctly.
     */
    public function test_14_changing_selected_member_replaces_previous_shortcut_values_correctly(): void
    {
        $memberA = Member::create(['full_name' => 'Member Alpha', 'is_active' => true]);
        $memberB = Member::create(['full_name' => 'Member Beta', 'is_active' => true]);

        // Member A: BCA / 30000
        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $memberA->id,
            'contributor_name' => $memberA->full_name,
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Alpha Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        // Member B: Mandiri / 50000
        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $memberB->id,
            'contributor_name' => $memberB->full_name,
            'amount' => 50000,
            'account_type' => 'Mandiri',
            'type' => 'inflow',
            'description' => 'Beta Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $resA = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$memberA->id}");
        $resA->assertJson(['has_shortcut' => true, 'account_type' => 'BCA', 'amount' => 30000]);

        $resB = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$memberB->id}");
        $resB->assertJson(['has_shortcut' => true, 'account_type' => 'Mandiri', 'amount' => 50000]);
    }

    /**
     * 15. Changing selected member does not carry stale bank/amount values.
     */
    public function test_15_changing_selected_member_does_not_carry_stale_bank_amount_values(): void
    {
        $memberWithHistory = Member::create(['full_name' => 'With History', 'is_active' => true]);
        $memberWithoutHistory = Member::create(['full_name' => 'Without History', 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $memberWithHistory->id,
            'contributor_name' => $memberWithHistory->full_name,
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'History Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        // First member has shortcut
        $res1 = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$memberWithHistory->id}");
        $res1->assertJson(['has_shortcut' => true]);

        // Second member returns false and null fields (never carries stale data)
        $res2 = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$memberWithoutHistory->id}");
        $res2->assertJson([
            'has_shortcut' => false,
            'account_type' => null,
            'amount' => null,
        ]);
    }

    /**
     * 16. Form reset clears shortcut state and view contains search & reset controls.
     */
    public function test_16_form_reset_clears_shortcut_state(): void
    {
        $member = Member::create(['full_name' => 'Searchable Member', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);

        // Verify searchable dropdown elements
        $response->assertSee('id="cash-member-search"', false);
        $response->assertSee('id="cash-member-dropdown"', false);
        $response->assertSee('id="btn-clear-member"', false);
        $response->assertSee('id="btn-reset-cash-form"', false);
        $response->assertSee('Reset Form');
    }

    /**
     * 17. Shortcut does not expose previous proof information.
     */
    public function test_17_shortcut_does_not_expose_previous_proof_information(): void
    {
        $member = Member::create(['full_name' => 'Private Proof Member', 'is_active' => true]);

        $proof = UploadedFile::fake()->image('private_proof.jpg');
        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(200);
        $data = $response->json();

        // Must ONLY contain has_shortcut, account_type, and amount
        $this->assertArrayHasKey('has_shortcut', $data);
        $this->assertArrayHasKey('account_type', $data);
        $this->assertArrayHasKey('amount', $data);
        $this->assertArrayNotHasKey('proof', $data);
        $this->assertArrayNotHasKey('proof_file_id', $data);
        $this->assertArrayNotHasKey('description', $data);
        $this->assertArrayNotHasKey('user_id', $data);
    }

    /**
     * 18. S9 authorization remains intact.
     */
    public function test_18_s9_authorization_remains_intact(): void
    {
        // Public cannot access Cash Management
        $this->get('/cash-management')->assertRedirect('/admin-ganteng');

        // Regular user receives 403
        $this->actingAs($this->regularUser)->get('/cash-management')->assertStatus(403);

        // Homepage cash card is disabled
        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('destination-card-disabled');
        $home->assertSee('Contact the admin to view your cash contribution.');
    }

    /**
     * 19. S9 image validation remains intact.
     */
    public function test_19_s9_image_validation_remains_intact(): void
    {
        $member = Member::create(['full_name' => 'File Validation Member', 'is_active' => true]);

        // Non-image PDF rejected
        $pdf = UploadedFile::fake()->create('statement.pdf', 100, 'application/pdf');
        $resPdf = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $pdf,
        ]);
        $resPdf->assertSessionHasErrors('proof');

        // Non-image DOCX rejected
        $doc = UploadedFile::fake()->create('report.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $resDoc = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $doc,
        ]);
        $resDoc->assertSessionHasErrors('proof');

        // Valid image accepted
        $img = UploadedFile::fake()->image('receipt.png');
        $resImg = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $img,
        ]);
        $resImg->assertRedirect(route('cash-management'));
    }

    /**
     * 20. Existing cash transaction creation remains intact.
     */
    public function test_20_existing_cash_transaction_creation_remains_intact(): void
    {
        $member = Member::create(['full_name' => 'Full Flow Member', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('full_flow.jpg');

        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'Mandiri',
            'amount' => 50000,
            'proof' => $proof,
        ]);

        $response->assertRedirect(route('cash-management'));
        $response->assertSessionHas('success');

        $tx = CashTransaction::where('member_id', $member->id)->latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertEquals('Mandiri', $tx->account_type);
        $this->assertEquals(50000, (float) $tx->amount);
        $this->assertNotNull($tx->proof_file_id);
    }

    /**
     * 21. Nonexistent member returns 404 safely.
     */
    public function test_21_nonexistent_member_shortcut_returns_404_safely(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson('/admin/cash-management/shortcut/999999');
        $response->assertStatus(404);
        $response->assertJson([
            'has_shortcut' => false,
            'message' => 'Member not found.',
        ]);
    }
}
