<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUiCleanupTest extends TestCase
{
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_test@bycgrowth.org'],
            [
                'name' => 'Admin Test',
                'username' => 'admin_test',
                'password' => Hash::make('secret123'),
                'role' => 'admin',
            ]
        );
    }

    protected function tearDown(): void
    {
        // Clean up temporary user to strictly maintain clean database
        if (isset($this->adminUser) && $this->adminUser->email === 'admin_test@bycgrowth.org') {
            $this->adminUser->delete();
        }

        parent::tearDown();
    }

    /**
     * 1. Admin Dashboard cards: status/action badges removed, only icon, title, description kept.
     */
    public function test_01_admin_dashboard_cards_have_status_badges_removed(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/dashboard');

        $response->assertStatus(200);

        // Titles and descriptions must exist
        $response->assertSee('Dashboard');
        $response->assertSee('Centralized administration command center');
        $response->assertSee('Homepage');
        $response->assertSee('Manage slideshow images');
        $response->assertSee('Members');
        $response->assertSee('Add/edit member profiles');
        $response->assertSee('Activities');
        $response->assertSee('Record community events');
        $response->assertSee('Games');
        $response->assertSee('Universal Game System');
        $response->assertSee('Birthday Wishes');
        $response->assertSee('Full archive browse');
        $response->assertSee('Cash Management');
        $response->assertSee('Transaction ledger');
        $response->assertSee('Roles / Accounts');
        $response->assertSee('Manage user and admin accounts');

        // Status badges must NOT exist
        $response->assertDontSee('Current &bull; Control Center', false);
        $response->assertDontSee('Active &bull; Manage Homepage', false);
        $response->assertDontSee('Active &bull; View Roster', false);
        $response->assertDontSee('Active &bull; Manage Activities', false);
        $response->assertDontSee('Active &bull; Open Games', false);
        $response->assertDontSee('Active &bull; Open Archive', false);
        $response->assertDontSee('Active &bull; Open Ledger', false);
        $response->assertDontSee('Active &bull; Manage Accounts', false);
        $response->assertDontSee('module-status-badge');
    }

    /**
     * 2. Cash Management ledger acts as ledger and transaction creation form is inside modal.
     */
    public function test_02_cash_management_transaction_creation_is_in_modal(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/cash-management');

        $response->assertStatus(200);

        // Record Transaction button exists
        $response->assertSee('Record Transaction');
        $response->assertSee('id="btn-open-record-tx"', false);

        // Modal container exists and contains form
        $response->assertSee('id="modal-record-tx"', false);
        $response->assertSee('id="form-record-tx"', false);

        // The form has all expected fields inside the modal
        $response->assertSee('record-member-id');
        $response->assertSee('record-account-type');
        $response->assertSee('record-amount');
        $response->assertSee('record-proof');
        $response->assertSee('Auto-recorded on server submission');
        $response->assertSee('Submit Transaction');
        $response->assertSee('Reset Form');

        // Main page ledger does NOT have direct inline #record-tx-section card
        $response->assertDontSee('id="record-tx-section"', false);
    }

    /**
     * 3. Roles / Accounts create account modal fits viewport properly.
     */
    public function test_03_roles_create_account_modal_structure(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/roles');

        $response->assertStatus(200);

        // Modal exists with viewport containment
        $response->assertSee('id="modal-add-user"', false);
        $response->assertSee('Create New User Account');
        $response->assertSee('max-height: calc(100vh - 40px)', false);

        // All fields and action buttons exist
        $response->assertSee('add-user-username');
        $response->assertSee('add-user-name');
        $response->assertSee('add-user-email');
        $response->assertSee('add-user-password');
        $response->assertSee('add-user-role');
        $response->assertSee('add-user-member-id');
        $response->assertSee('Create Account');
        $response->assertSee('Cancel');
    }

    /**
     * 4. Destructive actions have confirmation attributes, while simple filter resets do not.
     */
    public function test_04_destructive_actions_and_filter_reset_behavior(): void
    {
        $responseRoles = $this->actingAs($this->adminUser)->get('/admin/roles');
        $responseRoles->assertStatus(200);

        // Simple filter reset has NO confirmation popup
        $responseRoles->assertSee('Reset');
        $responseRoles->assertSee(route('admin.roles'));

        $responseCash = $this->actingAs($this->adminUser)->get('/admin/cash-management');
        $responseCash->assertStatus(200);

        // Simple filter reset on cash ledger
        $responseCash->assertSee('Reset');
        $responseCash->assertSee(route('admin.cash-management'));
    }

    /**
     * 5. Cash Management form disables native validation bubbles and employs unified BYC Growth error presentation.
     */
    public function test_05_cash_management_form_has_novalidate_and_unified_error_ui(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/cash-management');
        $response->assertStatus(200);

        // Native bubbles disabled via novalidate
        $response->assertSee('<form method="POST" action="' . route('admin.cash.store') . '" enctype="multipart/form-data" id="form-record-tx" novalidate', false);
        $response->assertSee('<form id="form-edit-tx" method="POST" action="" enctype="multipart/form-data" novalidate', false);

        // Unified error presentation script exists
        $response->assertSee('setFieldError', false);
        $response->assertSee('clearFieldError', false);
        $response->assertSee('form-field-error', false);
        $response->assertSee('input-invalid', false);

        // Field error messages configured
        $response->assertSee('Please select a member.');
        $response->assertSee('Please enter the account type.');
        $response->assertSee('Please enter the amount.');
        $response->assertSee('Please enter a valid amount.');
        $response->assertSee('Please upload a proof image.');
        $response->assertSee('This file type is not supported.');
    }

    /**
     * 6. Roles & Accounts modals disable native validation bubbles and use unified BYC Growth error presentation.
     */
    public function test_06_roles_account_forms_have_novalidate_and_unified_error_ui(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/roles');
        $response->assertStatus(200);

        // Native bubbles disabled via novalidate
        $response->assertSee('<form method="POST" action="' . route('admin.roles.store') . '" id="form-add-user" novalidate', false);
        $response->assertSee('<form method="POST" id="form-edit-user" action="" novalidate', false);

        // Unified error presentation script exists
        $response->assertSee('setFieldError', false);
        $response->assertSee('clearFieldError', false);
        $response->assertSee('form-field-error', false);
        $response->assertSee('input-invalid', false);

        // Field error messages configured
        $response->assertSee('Please enter a username.');
        $response->assertSee('Please enter an email address.');
        $response->assertSee('Please enter a password.');
        $response->assertSee('Please select an access role.');
    }

    /**
     * 7. Backend validation rules are strictly preserved for cash transactions.
     */
    public function test_07_cash_transaction_store_preserves_backend_validation_rules(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->from('/admin/cash-management')
            ->post('/admin/cash-management', [
                'member_id' => '',
                'account_type' => '',
                'amount' => '',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin/cash-management');
        $response->assertSessionHasErrors(['member_id', 'account_type', 'amount', 'proof']);
    }

    /**
     * 8. Backend validation rules are strictly preserved for user roles.
     */
    public function test_08_roles_store_preserves_backend_validation_rules(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->from('/admin/roles')
            ->post('/admin/roles', [
                'username' => '',
                'email' => '',
                'password' => '',
                'role' => '',
            ]);

        $response->assertStatus(302);
        $response->assertRedirect('/admin/roles');
        $response->assertSessionHasErrors(['username', 'email', 'password', 'role']);
    }
}

