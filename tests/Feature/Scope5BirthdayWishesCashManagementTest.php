<?php

namespace Tests\Feature;

use App\Models\BirthdayLetter;
use App\Models\CashTransaction;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope5BirthdayWishesCashManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;
    protected User $recipientUser;
    protected Member $celebrantMember;
    protected Member $regularMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s5@bycgrowth.org'],
            [
                'name' => 'Admin S5',
                'username' => 'admin_s5',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'regular_s5@bycgrowth.org'],
            [
                'name' => 'Regular S5',
                'username' => 'regular_s5',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );

        // Active members
        $today = Carbon::now('Asia/Jakarta');
        $this->celebrantMember = Member::create([
            'full_name' => 'Celebrant S5 Member',
            'date_of_birth' => $today->copy()->subYears(25)->toDateString(),
            'is_active' => true,
        ]);

        $this->regularMember = Member::create([
            'full_name' => 'Regular S5 Contributor',
            'date_of_birth' => '1998-05-15',
            'is_active' => true,
        ]);

        // Link regular user to member
        $this->regularUser->update(['member_id' => $this->regularMember->id]);

        // Recipient user linked to celebrant member
        $this->recipientUser = User::create([
            'name' => 'Recipient S5 User',
            'username' => 'recipient_s5',
            'email' => 'recipient_s5@bycgrowth.org',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'member_id' => $this->celebrantMember->id,
        ]);
    }

    // =========================================================================
    // 1. ACCESS CONTROL TESTS
    // =========================================================================

    public function test_admin_can_access_birthday_wishes_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/birthday-wishes');
        $response->assertStatus(200);
        $response->assertSee('Birthday Wishes Administration');
        $response->assertSee('Archive Years:');
    }

    public function test_guest_cannot_access_birthday_wishes_management(): void
    {
        $response = $this->get('/admin/birthday-wishes');
        $response->assertRedirect('/admin/login');
    }

    public function test_normal_user_cannot_access_birthday_wishes_management(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin/birthday-wishes');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_cash_management(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Cash Management Ledger');
        $response->assertSee('Treasury Balance');
    }

    public function test_guest_cannot_access_cash_management(): void
    {
        $response = $this->get('/admin/cash-management');
        $response->assertRedirect('/admin/login');
    }

    public function test_normal_user_cannot_access_cash_management(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin/cash-management');
        $response->assertStatus(403);
    }

    // =========================================================================
    // 2. BIRTHDAY WISHES MANAGEMENT & ARCHIVE TESTS
    // =========================================================================

    public function test_admin_can_view_current_and_archived_wishes(): void
    {
        $this->actingAs($this->adminUser);
        $currentYear = (int) Carbon::now('Asia/Jakarta')->year;

        // Current year wish
        $currentWish = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->regularUser->id,
            'birthday_year' => $currentYear,
            'is_anonymous' => false,
            'sender_name' => $this->regularMember->full_name,
            'message' => 'Happy birthday in current year!',
        ]);

        // Archived past wish (2024)
        $pastWish = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->regularUser->id,
            'birthday_year' => 2024,
            'is_anonymous' => false,
            'sender_name' => $this->regularMember->full_name,
            'message' => 'Happy birthday in 2024!',
        ]);

        // 1. View default (current year)
        $resCurrent = $this->get('/admin/birthday-wishes');
        $resCurrent->assertStatus(200);
        $resCurrent->assertSee('Happy birthday in current year!');

        // 2. View specific archived year
        $resPast = $this->get('/admin/birthday-wishes?year=2024');
        $resPast->assertStatus(200);
        $resPast->assertSee('Happy birthday in 2024!');

        // 3. View all years
        $resAll = $this->get('/admin/birthday-wishes?year=all');
        $resAll->assertStatus(200);
        $resAll->assertSee('Happy birthday in current year!');
        $resAll->assertSee('Happy birthday in 2024!');
    }

    public function test_birthday_wishes_future_year_is_rejected(): void
    {
        $this->actingAs($this->adminUser);
        $futureYear = (int) Carbon::now('Asia/Jakarta')->year + 1;

        $response = $this->get('/admin/birthday-wishes?year=' . $futureYear);
        $response->assertStatus(403);
    }

    public function test_anonymous_sender_identity_is_visible_only_to_admin(): void
    {
        $currentYear = (int) Carbon::now('Asia/Jakarta')->year;

        $wish = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->regularUser->id,
            'birthday_year' => $currentYear,
            'is_anonymous' => true,
            'sender_name' => 'Anonymous',
            'message' => 'Secret heartfelt blessing from an anonymous friend.',
        ]);

        // 1. Admin area: Admin sees true sender identity and an anonymous indicator
        $adminRes = $this->actingAs($this->adminUser)->get('/admin/birthday-wishes');
        $adminRes->assertStatus(200);
        $adminRes->assertSee($this->regularMember->full_name);
        $adminRes->assertSee('Anonymous to Recipient');

        // Admin JSON show endpoint also reveals true sender
        $adminJson = $this->actingAs($this->adminUser)->getJson('/admin/birthday-wishes/' . $wish->id);
        $adminJson->assertStatus(200);
        $adminJson->assertJson([
            'real_sender_name' => $this->regularMember->full_name,
            'is_anonymous' => true,
        ]);

        // 2. User / Recipient area: Recipient celebrating today sees ONLY "Anonymous"
        $recipientRes = $this->actingAs($this->recipientUser)->get('/birthday/letter/' . $wish->id);
        $recipientRes->assertStatus(200);
        $recipientRes->assertJson([
            'sender_name' => 'Anonymous',
            'sender' => null,
        ]);
        $recipientRes->assertJsonMissing(['member_name' => $this->regularMember->full_name]);
    }

    public function test_admin_can_edit_birthday_wish(): void
    {
        $this->actingAs($this->adminUser);
        $currentYear = (int) Carbon::now('Asia/Jakarta')->year;

        $wish = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->regularUser->id,
            'birthday_year' => $currentYear,
            'is_anonymous' => false,
            'sender_name' => $this->regularMember->full_name,
            'message' => 'Original text that needs moderation.',
        ]);

        $response = $this->putJson('/admin/birthday-wishes/' . $wish->id, [
            'message' => 'Moderated and approved blessing message.',
            'is_anonymous' => true,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('birthday_letters', [
            'id' => $wish->id,
            'message' => 'Moderated and approved blessing message.',
            'is_anonymous' => true,
        ]);
    }

    public function test_admin_can_delete_birthday_wish(): void
    {
        $this->actingAs($this->adminUser);
        $currentYear = (int) Carbon::now('Asia/Jakarta')->year;

        $wish = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->regularUser->id,
            'birthday_year' => $currentYear,
            'is_anonymous' => false,
            'sender_name' => $this->regularMember->full_name,
            'message' => 'Spam message to be deleted.',
        ]);

        $response = $this->deleteJson('/admin/birthday-wishes/' . $wish->id);
        $response->assertStatus(200);
        $this->assertDatabaseMissing('birthday_letters', ['id' => $wish->id]);
    }

    public function test_nonexistent_birthday_wish_returns_404(): void
    {
        $this->actingAs($this->adminUser);

        $resGet = $this->getJson('/admin/birthday-wishes/999999');
        $resGet->assertStatus(404);

        $resPut = $this->putJson('/admin/birthday-wishes/999999', [
            'message' => 'Test nonexistent wish edit',
        ]);
        $resPut->assertStatus(404);

        $resDelete = $this->deleteJson('/admin/birthday-wishes/999999');
        $resDelete->assertStatus(404);
    }

    public function test_normal_user_cannot_mutate_wishes_via_admin(): void
    {
        $this->actingAs($this->regularUser);
        $currentYear = (int) Carbon::now('Asia/Jakarta')->year;

        $wish = BirthdayLetter::create([
            'member_id' => $this->celebrantMember->id,
            'user_id' => $this->adminUser->id,
            'birthday_year' => $currentYear,
            'is_anonymous' => false,
            'sender_name' => 'Admin Sender',
            'message' => 'Admin letter to protect.',
        ]);

        $resPut = $this->putJson('/admin/birthday-wishes/' . $wish->id, [
            'message' => 'Hacked message',
        ]);
        $resPut->assertStatus(403);

        $resDelete = $this->deleteJson('/admin/birthday-wishes/' . $wish->id);
        $resDelete->assertStatus(403);
    }

    // =========================================================================
    // 3. CASH MANAGEMENT TESTS
    // =========================================================================

    public function test_admin_can_record_valid_cash_transaction(): void
    {
        $this->actingAs($this->adminUser);

        $proof = UploadedFile::fake()->image('proof_alpha.jpg', 600, 600);

        $response = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $proof,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cash_transactions', [
            'member_id' => $this->regularMember->id,
            'contributor_name' => $this->regularMember->full_name,
            'account_type' => 'BCA',
            'amount' => 50000,
        ]);
    }

    public function test_cash_invalid_amounts_are_rejected(): void
    {
        $this->actingAs($this->adminUser);
        $proof = UploadedFile::fake()->image('proof.jpg');

        // Negative amount
        $resNeg = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => -20000,
            'proof' => $proof,
        ]);
        $resNeg->assertStatus(422);

        // Zero amount
        $resZero = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => 0,
            'proof' => $proof,
        ]);
        $resZero->assertStatus(422);
    }

    public function test_cash_missing_or_invalid_proof_is_rejected(): void
    {
        $this->actingAs($this->adminUser);

        // Missing proof
        $resMissing = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => 50000,
        ]);
        $resMissing->assertStatus(422);

        // Non-image proof (PDF)
        $fakePdf = UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf');
        $resPdf = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $fakePdf,
        ]);
        $resPdf->assertStatus(422);

        // Executable proof (PHP script)
        $fakeScript = UploadedFile::fake()->create('payload.php', 10, 'application/x-php');
        $resPhp = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $fakeScript,
        ]);
        $resPhp->assertStatus(422);
    }

    public function test_cash_system_input_time_is_server_generated(): void
    {
        $this->actingAs($this->adminUser);

        $proof = UploadedFile::fake()->image('proof.jpg');
        $before = Carbon::now('Asia/Jakarta')->subSecond();

        $this->post('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'Mandiri',
            'amount' => 75000,
            'proof' => $proof,
            'transaction_date' => '2001-01-01',
            'created_at' => '2001-01-01 00:00:00',
        ]);

        $after = Carbon::now('Asia/Jakarta')->addSecond();

        $tx = CashTransaction::where('member_id', $this->regularMember->id)->latest('id')->firstOrFail();
        $this->assertTrue($tx->created_at->between($before, $after));
        $this->assertEquals(Carbon::now('Asia/Jakarta')->toDateString(), $tx->transaction_date->toDateString());
    }

    public function test_cash_filtering_pagination_and_total_calculation(): void
    {
        $this->actingAs($this->adminUser);

        // Create transactions with distinct amounts and accounts
        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $this->regularMember->id,
            'contributor_name' => 'Alpha Contributor',
            'amount' => 100000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
        ]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $this->regularMember->id,
            'contributor_name' => 'Beta Contributor',
            'amount' => 200000,
            'account_type' => 'Mandiri',
            'type' => 'inflow',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
        ]);

        // 1. Filter by Account Type
        $resBca = $this->get('/admin/cash-management?account_type=BCA');
        $resBca->assertStatus(200);
        $resBca->assertSee('100.000');
        $resBca->assertDontSee('200.000');

        // 2. Filter by Name
        $resName = $this->get('/admin/cash-management?name=Beta');
        $resName->assertStatus(200);
        $resName->assertSee('200.000');
        $resName->assertDontSee('100.000');

        // 3. Filter by Amount
        $resAmt = $this->get('/admin/cash-management?amount=100000');
        $resAmt->assertStatus(200);
        $resAmt->assertSee('Alpha Contributor');
    }

    public function test_cash_member_shortcut_endpoint(): void
    {
        $this->actingAs($this->adminUser);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $this->regularMember->id,
            'contributor_name' => $this->regularMember->full_name,
            'amount' => 85000,
            'account_type' => 'Bank Jatim',
            'type' => 'inflow',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
        ]);

        $res = $this->getJson('/admin/cash-management/shortcut/' . $this->regularMember->id);
        $res->assertStatus(200);
        $res->assertJson([
            'has_shortcut' => true,
            'account_type' => 'Bank Jatim',
            'amount' => 85000,
        ]);
    }

    public function test_admin_can_edit_cash_transaction_and_replace_proof(): void
    {
        $this->actingAs($this->adminUser);

        // Upload initial proof
        $oldProofFile = UploadedFile::fake()->image('old_proof.jpg');
        $postRes = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $oldProofFile,
        ]);
        $postRes->assertStatus(201);
        $tx = CashTransaction::latest('id')->firstOrFail();
        $oldMediaId = $tx->proof_file_id;

        // Edit transaction: change account and replace proof image
        $newProofFile = UploadedFile::fake()->image('new_proof.png');
        $putRes = $this->putJson('/admin/cash-management/' . $tx->id, [
            'member_id' => $this->regularMember->id,
            'account_type' => 'Mandiri Digital',
            'amount' => 70000,
            'proof' => $newProofFile,
        ]);

        $putRes->assertStatus(200);
        $tx->refresh();
        $this->assertEquals('Mandiri Digital', $tx->account_type);
        $this->assertEquals(70000, (float) $tx->amount);
        $this->assertNotEquals($oldMediaId, $tx->proof_file_id);
        // Old media record must be deleted
        $this->assertDatabaseMissing('media_files', ['id' => $oldMediaId]);
    }

    public function test_admin_can_delete_cash_transaction_with_media_cleanup(): void
    {
        $this->actingAs($this->adminUser);

        $proofFile = UploadedFile::fake()->image('tx_proof.jpg');
        $postRes = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'Cash',
            'amount' => 40000,
            'proof' => $proofFile,
        ]);
        $postRes->assertStatus(201);
        $tx = CashTransaction::latest('id')->firstOrFail();
        $mediaId = $tx->proof_file_id;

        $delRes = $this->deleteJson('/admin/cash-management/' . $tx->id);
        $delRes->assertStatus(200);

        $this->assertDatabaseMissing('cash_transactions', ['id' => $tx->id]);
        $this->assertDatabaseMissing('media_files', ['id' => $mediaId]);
    }

    public function test_nonexistent_cash_transaction_returns_404(): void
    {
        $this->actingAs($this->adminUser);

        $resGet = $this->getJson('/admin/cash-management/transaction/999999');
        $resGet->assertStatus(404);

        $resPut = $this->putJson('/admin/cash-management/999999', [
            'account_type' => 'BCA',
            'amount' => 50000,
        ]);
        $resPut->assertStatus(404);

        $resDelete = $this->deleteJson('/admin/cash-management/999999');
        $resDelete->assertStatus(404);
    }

    public function test_normal_user_cannot_mutate_cash_via_admin(): void
    {
        $this->actingAs($this->regularUser);

        $proofFile = UploadedFile::fake()->image('unauth_proof.jpg');

        $resPost = $this->postJson('/admin/cash-management', [
            'member_id' => $this->regularMember->id,
            'account_type' => 'BCA',
            'amount' => 50000,
            'proof' => $proofFile,
        ]);
        $resPost->assertStatus(403);
    }

    public function test_deleting_member_preserves_cash_transaction_history(): void
    {
        $this->actingAs($this->adminUser);

        $tempMember = Member::create([
            'full_name' => 'Temporary Contributor',
            'is_active' => true,
        ]);

        $proof = UploadedFile::fake()->image('proof.jpg');
        $this->postJson('/admin/cash-management', [
            'member_id' => $tempMember->id,
            'account_type' => 'Cash',
            'amount' => 35000,
            'proof' => $proof,
        ]);

        $tx = CashTransaction::where('contributor_name', 'Temporary Contributor')->firstOrFail();
        $this->assertEquals($tempMember->id, $tx->member_id);

        // Delete the member
        $tempMember->delete();

        // Transaction record still exists in MySQL with contributor_name intact, and member_id nulled
        $tx->refresh();
        $this->assertNull($tx->member_id);
        $this->assertEquals('Temporary Contributor', $tx->contributor_name);
        $this->assertEquals(35000, (float) $tx->amount);
    }

    // =========================================================================
    // 4. REGRESSION VERIFICATION (S0, S1, S2, S3, S4, R1-R3, S9-S11)
    // =========================================================================

    public function test_s0_authentication_and_admin_ganteng_intact(): void
    {
        $this->get('/admin-ganteng')->assertStatus(200);
        $this->get('/admin/login')->assertStatus(200);

        $loginRes = $this->post('/admin-ganteng', [
            'login' => $this->adminUser->username,
            'password' => 'password123',
        ]);
        $loginRes->assertRedirect('/admin/dashboard');

        // Logout redirects to /admin-ganteng
        $logoutRes = $this->actingAs($this->adminUser)->post('/admin/logout');
        $logoutRes->assertRedirect('/admin-ganteng');
    }

    public function test_s1_admin_shell_username_and_navigation(): void
    {
        $res = $this->actingAs($this->adminUser)->get('/admin/dashboard');
        $res->assertStatus(200);
        $res->assertSee($this->adminUser->username);
        $res->assertSee('Admin Portal');
        $res->assertSee('Birthday Wishes');
        $res->assertSee('Cash Management');
    }

    public function test_s2_homepage_management_accessible(): void
    {
        $this->actingAs($this->adminUser)->get('/admin/homepage')->assertStatus(200);
    }

    public function test_s3_members_and_activities_management_accessible(): void
    {
        $this->actingAs($this->adminUser)->get('/admin/members')->assertStatus(200);
        $this->actingAs($this->adminUser)->get('/admin/activities')->assertStatus(200);
    }

    public function test_s4_games_management_accessible(): void
    {
        $this->actingAs($this->adminUser)->get('/admin/games')->assertStatus(200);
    }

    public function test_user_facing_cash_management_portal_intact(): void
    {
        $res = $this->actingAs($this->adminUser)->get('/cash-management');
        $res->assertStatus(200);
        $res->assertSee('id="cash-transactions-table"', false);
    }

    public function test_user_facing_birthday_wishes_portal_intact(): void
    {
        $res = $this->actingAs($this->adminUser)->get('/birthday-wishes');
        $res->assertStatus(200);
    }
}
