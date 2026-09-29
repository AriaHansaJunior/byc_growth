<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\MediaFile;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Scope11CashTransactionTableTest extends TestCase
{
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_s11@bycgrowth.org'],
            [
                'name' => 'Admin S11',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'user_s11@bycgrowth.org'],
            [
                'name' => 'User S11',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    /**
     * 1. Admin can see transaction table.
     */
    public function test_01_admin_can_see_transaction_table(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('id="cash-transactions-table"', false);
    }

    /**
     * 2. Normal user cannot access transaction table.
     */
    public function test_02_normal_user_cannot_access_transaction_table(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/cash-management');
        $response->assertStatus(403);
    }

    /**
     * 3. Public visitor cannot access transaction table.
     */
    public function test_03_public_visitor_cannot_access_transaction_table(): void
    {
        $response = $this->get('/cash-management');
        $response->assertRedirect('/admin/login');
    }

    /**
     * 4. Required columns/data are rendered in table header.
     */
    public function test_04_required_columns_are_rendered(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('>No<', false);
        $response->assertSee('>Name<', false);
        $response->assertSee('>Account Type<', false);
        $response->assertSee('>Amount<', false);
        $response->assertSee('>Proof<', false);
        $response->assertSee('>Input Time<', false);
    }

    /**
     * 5. Member name is displayed for transaction with member relation.
     */
    public function test_05_member_name_is_displayed(): void
    {
        $member = Member::create(['full_name' => 'Table Member Five', 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => 'Old Contributor Name',
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Test 5',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        // Prefers associated Member's current full name
        $response->assertSee('Table Member Five');
    }

    /**
     * 6. contributor_name fallback works for legacy transaction without member relation.
     */
    public function test_06_contributor_name_fallback_works_for_legacy_transaction(): void
    {
        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => null,
            'contributor_name' => 'Legacy Contributor Without Member',
            'amount' => 40000,
            'account_type' => 'Cash',
            'type' => 'inflow',
            'description' => 'Legacy Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Legacy Contributor Without Member');
    }

    /**
     * 7. Account type is displayed.
     */
    public function test_07_account_type_is_displayed(): void
    {
        $member = Member::create(['full_name' => 'Account Type Member', 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 30000,
            'account_type' => 'Mandiri Platinum',
            'type' => 'inflow',
            'description' => 'Test 7',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('Mandiri Platinum');
    }

    /**
     * 8. Amount is displayed.
     */
    public function test_08_amount_is_displayed(): void
    {
        $member = Member::create(['full_name' => 'Amount Member', 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 77500,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Test 8',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('77.500');
    }

    /**
     * 9. Input time is displayed.
     */
    public function test_09_input_time_is_displayed(): void
    {
        $member = Member::create(['full_name' => 'Input Time Member ' . uniqid(), 'is_active' => true]);

        $tx = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Test 9',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?name=' . urlencode($member->full_name));
        $response->assertStatus(200);
        $response->assertSee($tx->fresh()->created_at->format('M d, Y H:i:s'));
    }

    /**
     * 10. Proof control is rendered for transaction with proof.
     */
    public function test_10_proof_control_is_rendered(): void
    {
        $member = Member::create(['full_name' => 'Proof Control Member ' . uniqid(), 'is_active' => true]);
        $proof = UploadedFile::fake()->image('proof_ctrl.png');

        $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?name=' . urlencode($member->full_name));
        $response->assertStatus(200);
        $response->assertSee('btn-view-proof');
        $response->assertSee('data-url=', false);
        $response->assertSee('View');
    }

    /**
     * 11. Authorized admin can preview/access proof modal.
     */
    public function test_11_authorized_admin_can_preview_proof(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('id="modal-proof-preview"', false);
        $response->assertSee('id="modal-proof-img"', false);
        $response->assertSee('id="btn-close-proof"', false);
    }

    /**
     * 12. Unauthorized user cannot access proof / cash management.
     */
    public function test_12_unauthorized_user_cannot_access_proof(): void
    {
        $this->actingAs($this->regularUser)->get('/cash-management')->assertStatus(403);

        \Illuminate\Support\Facades\Auth::logout();
        $this->flushSession();

        $this->get('/cash-management')->assertRedirect('/admin/login');
    }

    /**
     * 13. Missing proof does not create a broken UI state.
     */
    public function test_13_missing_proof_does_not_create_broken_ui(): void
    {
        $member = Member::create(['full_name' => 'No Proof UI Member ' . uniqid(), 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'No proof tx',
            'proof_file_id' => null,
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?name=' . urlencode($member->full_name));
        $response->assertStatus(200);
        $response->assertSee('No proof');
    }

    /**
     * 14. Default pagination works.
     */
    public function test_14_default_pagination_works(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $response->assertSee('name="per_page"', false);
    }

    /**
     * 15. Per-page 5 works.
     */
    public function test_15_per_page_5_works(): void
    {
        $member = Member::create(['full_name' => 'Pagination Five Member ' . uniqid(), 'is_active' => true]);

        for ($i = 1; $i <= 7; $i++) {
            CashTransaction::create([
                'user_id' => $this->adminUser->id,
                'member_id' => $member->id,
                'contributor_name' => "Pagination Tx {$i}",
                'amount' => 10000 * $i,
                'account_type' => 'BCA',
                'type' => 'inflow',
                'description' => "Tx {$i}",
                'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'created_at' => Carbon::now('Asia/Jakarta')->subMinutes(10 - $i),
                'updated_at' => Carbon::now('Asia/Jakarta')->subMinutes(10 - $i),
            ]);
        }

        $response = $this->actingAs($this->adminUser)->get('/cash-management?per_page=5');
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertCount(5, $transactions->items());
        $this->assertEquals(5, $transactions->perPage());
        $this->assertGreaterThanOrEqual(7, $transactions->total());
    }

    /**
     * 16. Per-page 10 works.
     */
    public function test_16_per_page_10_works(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management?per_page=10');
        $response->assertStatus(200);
        $this->assertEquals(10, $response->viewData('currentPerPage'));
    }

    /**
     * 17. Per-page 25 works.
     */
    public function test_17_per_page_25_works(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management?per_page=25');
        $response->assertStatus(200);
        $this->assertEquals(25, $response->viewData('currentPerPage'));
    }

    /**
     * 18. Per-page 50 works.
     */
    public function test_18_per_page_50_works(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management?per_page=50');
        $response->assertStatus(200);
        $this->assertEquals(50, $response->viewData('currentPerPage'));
    }

    /**
     * 19. Row numbering remains correct across pages.
     */
    public function test_19_row_numbering_remains_correct_across_pages(): void
    {
        $member = Member::create(['full_name' => 'Row Number Member ' . uniqid(), 'is_active' => true]);

        for ($i = 1; $i <= 12; $i++) {
            CashTransaction::create([
                'user_id' => $this->adminUser->id,
                'member_id' => $member->id,
                'contributor_name' => "Row Number Tx {$i}",
                'amount' => 10000,
                'account_type' => 'BCA',
                'type' => 'inflow',
                'description' => "Row Tx {$i}",
                'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'created_at' => Carbon::now('Asia/Jakarta')->subMinutes(20 - $i),
                'updated_at' => Carbon::now('Asia/Jakarta')->subMinutes(20 - $i),
            ]);
        }

        // Page 2 with per_page = 5 should start with row #6
        $response = $this->actingAs($this->adminUser)->get('/cash-management?per_page=5&page=2');
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertEquals(6, $transactions->firstItem());
        $this->assertMatchesRegularExpression('/<td[^>]*>\s*6\s*<\/td>/', $response->getContent());
    }

    /**
     * 20. Name filter matches member full name.
     */
    public function test_20_name_filter_matches_member_full_name(): void
    {
        $memberA = Member::create(['full_name' => 'UniqueNameAlpha_' . uniqid(), 'is_active' => true]);
        $memberB = Member::create(['full_name' => 'UniqueNameBeta_' . uniqid(), 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $memberA->id,
            'contributor_name' => $memberA->full_name,
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Tx Alpha',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $memberB->id,
            'contributor_name' => $memberB->full_name,
            'amount' => 30000,
            'account_type' => 'Mandiri',
            'type' => 'inflow',
            'description' => 'Tx Beta',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?name=' . $memberA->full_name);
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertTrue($transactions->pluck('contributor_name')->contains($memberA->full_name));
        $this->assertFalse($transactions->pluck('contributor_name')->contains($memberB->full_name));
    }

    /**
     * 21. Name filter matches contributor_name fallback.
     */
    public function test_21_name_filter_matches_contributor_name_fallback(): void
    {
        $fallbackName = 'Standalone Contributor ' . uniqid();
        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => null,
            'contributor_name' => $fallbackName,
            'amount' => 50000,
            'account_type' => 'Cash',
            'type' => 'inflow',
            'description' => 'Tx Standalone',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?name=' . $fallbackName);
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertTrue($transactions->pluck('contributor_name')->contains($fallbackName));
    }

    /**
     * 22. Name filter combines correctly with pagination.
     */
    public function test_22_name_filter_combines_correctly_with_pagination(): void
    {
        $prefix = 'FilterPage_' . uniqid();
        $member = Member::create(['full_name' => $prefix . ' Member', 'is_active' => true]);

        for ($i = 1; $i <= 6; $i++) {
            CashTransaction::create([
                'user_id' => $this->adminUser->id,
                'member_id' => $member->id,
                'contributor_name' => $prefix . " Tx {$i}",
                'amount' => 10000 * $i,
                'account_type' => 'BCA',
                'type' => 'inflow',
                'description' => "Filtered Tx {$i}",
                'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'created_at' => Carbon::now('Asia/Jakarta')->subMinutes(10 - $i),
                'updated_at' => Carbon::now('Asia/Jakarta')->subMinutes(10 - $i),
            ]);
        }

        $response = $this->actingAs($this->adminUser)->get("/cash-management?name={$prefix}&per_page=5&page=2");
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertEquals(6, $transactions->firstItem());
        $this->assertCount(1, $transactions->items());
    }

    /**
     * 23. Account Type filter works.
     */
    public function test_23_account_type_filter_works(): void
    {
        $accTarget = 'TargetAcc_' . uniqid();
        $accOther = 'OtherAcc_' . uniqid();
        $member = Member::create(['full_name' => 'Bank Filter Member ' . uniqid(), 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 30000,
            'account_type' => $accTarget,
            'type' => 'inflow',
            'description' => 'Target Acc Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 30000,
            'account_type' => $accOther,
            'type' => 'inflow',
            'description' => 'Other Acc Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?account_type=' . $accTarget);
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertTrue($transactions->pluck('account_type')->contains($accTarget));
        $this->assertFalse($transactions->pluck('account_type')->contains($accOther));
    }

    /**
     * 24. Amount filtering works.
     */
    public function test_24_amount_filtering_works(): void
    {
        $member = Member::create(['full_name' => 'Amount Filter Member ' . uniqid(), 'is_active' => true]);
        $targetAmt = 123456.0;
        $otherAmt = 654321.0;

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => $targetAmt,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Target Amount Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => $otherAmt,
            'account_type' => 'Mandiri',
            'type' => 'inflow',
            'description' => 'Other Amount Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?amount=' . (int) $targetAmt);
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertTrue($transactions->pluck('amount')->contains($targetAmt));
        $this->assertFalse($transactions->pluck('amount')->contains($otherAmt));
    }

    /**
     * 25. Invalid amount filter is handled safely.
     */
    public function test_25_invalid_amount_filter_is_handled_safely(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management?amount=-9999');
        $response->assertStatus(200);

        $response2 = $this->actingAs($this->adminUser)->get('/cash-management?amount=malicious_text');
        $response2->assertStatus(200);
    }

    /**
     * 26. Input Date filter works.
     */
    public function test_26_input_date_filter_works(): void
    {
        $member = Member::create(['full_name' => 'Date Filter Member ' . uniqid(), 'is_active' => true]);

        $targetDate = Carbon::create(2026, 6, 15, 10, 0, 0, 'Asia/Jakarta');
        $otherDate = Carbon::create(2026, 6, 20, 10, 0, 0, 'Asia/Jakarta');

        $txTarget = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => 'Target Date Tx ' . uniqid(),
            'amount' => 30000,
            'account_type' => 'BCA',
            'type' => 'inflow',
            'description' => 'Target Date',
            'transaction_date' => $targetDate->toDateString(),
            'created_at' => $targetDate,
            'updated_at' => $targetDate,
        ]);

        $txOther = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => 'Other Date Tx ' . uniqid(),
            'amount' => 30000,
            'account_type' => 'Mandiri',
            'type' => 'inflow',
            'description' => 'Other Date',
            'transaction_date' => $otherDate->toDateString(),
            'created_at' => $otherDate,
            'updated_at' => $otherDate,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?date=2026-06-15');
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertTrue($transactions->pluck('id')->contains($txTarget->id));
        $this->assertFalse($transactions->pluck('id')->contains($txOther->id));
    }

    /**
     * 27. Invalid date filter is handled safely.
     */
    public function test_27_invalid_date_filter_is_handled_safely(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management?date=not-a-date');
        $response->assertStatus(200);

        $response2 = $this->actingAs($this->adminUser)->get('/cash-management?date=9999-99-99');
        $response2->assertStatus(200);
    }

    /**
     * 28. Multiple filters work together.
     */
    public function test_28_multiple_filters_work_together(): void
    {
        $comboPrefix = 'Combo_' . uniqid();
        $member = Member::create(['full_name' => $comboPrefix . ' Member', 'is_active' => true]);

        $accBCA = 'BCA_' . uniqid();
        $accMandiri = 'Mandiri_' . uniqid();

        $tx1 = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $comboPrefix . ' Member',
            'amount' => 50000,
            'account_type' => $accBCA,
            'type' => 'inflow',
            'description' => 'Combo 1',
            'transaction_date' => '2026-05-10',
            'created_at' => Carbon::parse('2026-05-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-05-10 10:00:00'),
        ]);

        $tx2 = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $comboPrefix . ' Member',
            'amount' => 30000,
            'account_type' => $accMandiri,
            'type' => 'inflow',
            'description' => 'Combo 2',
            'transaction_date' => '2026-05-10',
            'created_at' => Carbon::parse('2026-05-10 11:00:00'),
            'updated_at' => Carbon::parse('2026-05-10 11:00:00'),
        ]);

        // Combined: name + account_type + amount + date
        $response = $this->actingAs($this->adminUser)->get("/cash-management?name={$comboPrefix}&account_type={$accBCA}&amount=50000&date=2026-05-10");
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertTrue($transactions->pluck('id')->contains($tx1->id));
        $this->assertFalse($transactions->pluck('id')->contains($tx2->id));
    }

    /**
     * 29. Changing filters resets pagination to page 1.
     */
    public function test_29_changing_filters_resets_pagination_to_page_1(): void
    {
        // When filter form is submitted via GET, it does not include page parameter, defaulting to page 1
        $response = $this->actingAs($this->adminUser)->get('/cash-management?name=NewSearch');
        $response->assertStatus(200);
        $transactions = $response->viewData('transactions');
        $this->assertEquals(1, $transactions->currentPage());
    }

    /**
     * 30. Reset filters clears all filters and returns to page 1.
     */
    public function test_30_reset_filters_clears_all_filters(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management');
        $response->assertStatus(200);
        $this->assertEquals('', $response->viewData('filters')['name']);
        $this->assertEquals('', $response->viewData('filters')['account_type']);
        $this->assertEquals('', $response->viewData('filters')['amount']);
        $this->assertEquals('', $response->viewData('filters')['date']);
    }

    /**
     * 31. Total Cash Contribution is calculated across all matching records, not only current page.
     */
    public function test_31_total_cash_calculated_across_all_matching_records(): void
    {
        $member = Member::create(['full_name' => 'Total Multi Page Member ' . uniqid(), 'is_active' => true]);

        // Create 15 transactions of 10,000 each = 150,000 total
        for ($i = 1; $i <= 15; $i++) {
            CashTransaction::create([
                'user_id' => $this->adminUser->id,
                'member_id' => $member->id,
                'contributor_name' => "Total Tx {$i}",
                'amount' => 10000,
                'account_type' => 'BCA',
                'type' => 'inflow',
                'description' => "Tx {$i}",
                'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
                'created_at' => Carbon::now('Asia/Jakarta')->subMinutes(20 - $i),
                'updated_at' => Carbon::now('Asia/Jakarta')->subMinutes(20 - $i),
            ]);
        }

        // View page 1 with per_page = 5 (only 5 rows displayed on screen)
        $response = $this->actingAs($this->adminUser)->get('/cash-management?per_page=5&page=1');
        $response->assertStatus(200);

        // Total cash must be >= 150,000, NOT just 50,000 for the visible page
        $totalCash = $response->viewData('totalCash');
        $this->assertGreaterThanOrEqual(150000, $totalCash);
    }

    /**
     * 32. Total changes correctly when filters change.
     */
    public function test_32_total_changes_correctly_when_filters_change(): void
    {
        $uniqueAccount = 'SuperRareAccount_' . uniqid();
        $member = Member::create(['full_name' => 'Filtered Total Member ' . uniqid(), 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => 'Filtered Total Member',
            'amount' => 200000,
            'account_type' => $uniqueAccount,
            'type' => 'inflow',
            'description' => 'Rare Tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/cash-management?account_type=' . $uniqueAccount);
        $response->assertStatus(200);
        $this->assertEquals(200000, (float) $response->viewData('totalCash'));
        $response->assertSee('200.000');
    }

    /**
     * 33. Empty result has safe total behavior.
     */
    public function test_33_empty_result_has_safe_total_behavior(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/cash-management?name=NonExistentContributorXYZ123456');
        $response->assertStatus(200);
        $this->assertEquals(0, $response->viewData('totalCash'));
        $response->assertSee('No cash transactions found.');
        $response->assertSee('Rp 0');
    }

    /**
     * 34. S9 transaction creation still works.
     */
    public function test_34_s9_transaction_creation_still_works(): void
    {
        $member = Member::create(['full_name' => 'Regression S9 Member', 'is_active' => true]);
        $proof = UploadedFile::fake()->image('reg_s9.jpg');

        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
            'proof' => $proof,
        ]);

        $response->assertRedirect(route('cash-management'));
        $response->assertSessionHas('success');
    }

    /**
     * 35. S10 searchable member shortcut still works.
     */
    public function test_35_s10_searchable_member_shortcut_still_works(): void
    {
        $member = Member::create(['full_name' => 'Regression S10 Member', 'is_active' => true]);

        CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'member_id' => $member->id,
            'contributor_name' => $member->full_name,
            'amount' => 45000,
            'account_type' => 'Mandiri Shortcut',
            'type' => 'inflow',
            'description' => 'Shortcut tx',
            'transaction_date' => Carbon::now('Asia/Jakarta')->toDateString(),
            'created_at' => Carbon::now('Asia/Jakarta'),
            'updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($this->adminUser)->getJson("/admin/cash-management/shortcut/{$member->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'has_shortcut' => true,
            'account_type' => 'Mandiri Shortcut',
            'amount' => 45000.0,
        ]);
    }

    /**
     * 36. S10 proof freshness still works.
     */
    public function test_36_s10_proof_freshness_still_works(): void
    {
        $member = Member::create(['full_name' => 'Freshness Member', 'is_active' => true]);

        // Attempt submit without proof must fail
        $response = $this->actingAs($this->adminUser)->post('/admin/cash-management', [
            'member_id' => $member->id,
            'account_type' => 'BCA',
            'amount' => 30000,
        ]);

        $response->assertSessionHasErrors('proof');
    }
}
