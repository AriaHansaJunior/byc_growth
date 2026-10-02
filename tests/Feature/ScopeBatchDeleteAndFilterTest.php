<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\BirthdayLetter;
use App\Models\CashTransaction;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScopeBatchDeleteAndFilterTest extends TestCase
{
    protected User $adminUser;
    protected User $regularUser;

    protected array $createdActivityIds = [];
    protected array $createdMemberIds = [];
    protected array $createdWishIds = [];
    protected array $createdTxIds = [];
    protected array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin_super_batch@bycgrowth.test'],
            [
                'username' => 'admin_super_batch',
                'name' => 'Super Admin Batch',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $this->regularUser = User::firstOrCreate(
            ['email' => 'joe_batch@bycgrowth.test'],
            [
                'username' => 'joe_batch',
                'name' => 'Regular Joe Batch',
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdWishIds)) {
            BirthdayLetter::whereIn('id', $this->createdWishIds)->delete();
        }

        if (!empty($this->createdTxIds)) {
            CashTransaction::whereIn('id', $this->createdTxIds)->delete();
        }

        if (!empty($this->createdMemberIds)) {
            Member::whereIn('id', $this->createdMemberIds)->delete();
        }

        if (!empty($this->createdActivityIds)) {
            Activity::whereIn('id', $this->createdActivityIds)->delete();
        }

        if (!empty($this->createdUserIds)) {
            User::whereIn('id', $this->createdUserIds)->delete();
        }

        parent::tearDown();
    }

    public function test_unauthenticated_or_regular_users_cannot_access_batch_delete_routes(): void
    {
        $routes = [
            route('admin.activities.batch-delete'),
            route('admin.members.batch-delete'),
            route('admin.birthday-wishes.batch-delete'),
            route('admin.cash.batch-delete'),
            route('admin.roles.batch-delete'),
        ];

        foreach ($routes as $route) {
            // Guest must not be able to execute batch delete (must either redirect or return 403)
            $response = $this->post($route, ['ids' => [1, 2]]);
            $this->assertTrue(in_array($response->status(), [302, 403], true));

            // Non-admin forbidden
            $response = $this->actingAs($this->regularUser)->post($route, ['ids' => [1, 2]]);
            $response->assertStatus(403);
        }
    }

    public function test_activities_batch_delete_and_filters(): void
    {
        $act1 = Activity::create([
            'name' => 'Alpha Batch Camp ' . uniqid(),
            'description' => 'First event description',
            'event_date' => '2026-06-01',
        ]);
        $this->createdActivityIds[] = $act1->id;

        $uniqueBeta = 'Beta Batch Fellowship ' . uniqid();
        $act2 = Activity::create([
            'name' => $uniqueBeta,
            'description' => 'Second event description',
            'event_date' => '2026-07-01',
        ]);
        $this->createdActivityIds[] = $act2->id;

        $uniqueGamma = 'Gamma Batch Retreat ' . uniqid();
        $act3 = Activity::create([
            'name' => $uniqueGamma,
            'description' => 'Third event description',
            'event_date' => '2026-08-01',
        ]);
        $this->createdActivityIds[] = $act3->id;

        // Filter and sort testing
        $response = $this->actingAs($this->adminUser)->get(route('admin.activities', [
            'search' => $uniqueBeta,
            'sort' => 'name_asc',
        ]));
        $response->assertStatus(200);
        $response->assertSee($uniqueBeta);
        $response->assertDontSee($uniqueGamma);

        // Batch delete act1 and act2
        $deleteResponse = $this->actingAs($this->adminUser)->post(route('admin.activities.batch-delete'), [
            'ids' => [$act1->id, $act2->id],
        ]);
        $deleteResponse->assertRedirect(route('admin.activities'));
        $this->assertDatabaseMissing('activities', ['id' => $act1->id]);
        $this->assertDatabaseMissing('activities', ['id' => $act2->id]);
        $this->assertDatabaseHas('activities', ['id' => $act3->id]);
    }

    public function test_members_batch_delete_and_filters(): void
    {
        $uniqueAlice = 'Alice Batch ' . uniqid();
        $member1 = Member::create([
            'full_name' => $uniqueAlice,
            'date_of_birth' => '2000-01-15',
            'is_active' => true,
        ]);
        $this->createdMemberIds[] = $member1->id;

        $uniqueBob = 'Bob Batch ' . uniqid();
        $member2 = Member::create([
            'full_name' => $uniqueBob,
            'date_of_birth' => '2001-05-20',
            'is_active' => true,
        ]);
        $this->createdMemberIds[] = $member2->id;

        $uniqueCharlie = 'Charlie Batch ' . uniqid();
        $member3 = Member::create([
            'full_name' => $uniqueCharlie,
            'date_of_birth' => '1999-12-10',
            'is_active' => true,
        ]);
        $this->createdMemberIds[] = $member3->id;

        // Filter testing
        $response = $this->actingAs($this->adminUser)->get(route('admin.members', [
            'search' => $uniqueAlice,
            'sort' => 'name_asc',
        ]));
        $response->assertStatus(200);
        $response->assertSee($uniqueAlice);
        $response->assertDontSee($uniqueBob);

        // Batch delete member1 and member2
        $deleteResponse = $this->actingAs($this->adminUser)->post(route('admin.members.batch-delete'), [
            'ids' => [$member1->id, $member2->id],
        ]);
        $deleteResponse->assertRedirect(route('admin.members'));
        $this->assertDatabaseMissing('members', ['id' => $member1->id]);
        $this->assertDatabaseMissing('members', ['id' => $member2->id]);
        $this->assertDatabaseHas('members', ['id' => $member3->id]);
    }

    public function test_birthday_wishes_batch_delete_and_filters(): void
    {
        $recipient = Member::create([
            'full_name' => 'David Celebrant ' . uniqid(),
            'date_of_birth' => '1998-10-02',
            'is_active' => true,
        ]);
        $this->createdMemberIds[] = $recipient->id;

        $wishMessage1 = 'Unique wish one ' . uniqid();
        $wish1 = BirthdayLetter::create([
            'member_id' => $recipient->id,
            'user_id' => $this->regularUser->id,
            'sender_name' => 'Joe Sender',
            'message' => $wishMessage1,
            'birthday_year' => 2026,
            'is_anonymous' => false,
        ]);
        $this->createdWishIds[] = $wish1->id;

        $wishMessage2 = 'Unique wish two ' . uniqid();
        $wish2 = BirthdayLetter::create([
            'member_id' => $recipient->id,
            'user_id' => $this->adminUser->id,
            'sender_name' => 'Admin Sender',
            'message' => $wishMessage2,
            'birthday_year' => 2026,
            'is_anonymous' => true,
        ]);
        $this->createdWishIds[] = $wish2->id;

        // Filter and sort testing
        $response = $this->actingAs($this->adminUser)->get(route('admin.birthday-wishes', [
            'search' => $wishMessage1,
            'sort' => 'newest',
        ]));
        $response->assertStatus(200);
        $response->assertSee($wishMessage1);
        $response->assertDontSee($wishMessage2);

        // Batch delete
        $deleteResponse = $this->actingAs($this->adminUser)->post(route('admin.birthday-wishes.batch-delete'), [
            'ids' => [$wish1->id, $wish2->id],
        ]);
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('birthday_letters', ['id' => $wish1->id]);
        $this->assertDatabaseMissing('birthday_letters', ['id' => $wish2->id]);
    }

    public function test_cash_management_batch_delete_and_filters(): void
    {
        $uniqueEmma = 'Emma Batch ' . uniqid();
        $tx1 = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'contributor_name' => $uniqueEmma,
            'amount' => 50000,
            'account_type' => 'Cash',
            'transaction_date' => now(),
        ]);
        $this->createdTxIds[] = $tx1->id;

        $uniqueFrank = 'Frank Batch ' . uniqid();
        $tx2 = CashTransaction::create([
            'user_id' => $this->adminUser->id,
            'contributor_name' => $uniqueFrank,
            'amount' => 150000,
            'account_type' => 'Transfer',
            'transaction_date' => now(),
        ]);
        $this->createdTxIds[] = $tx2->id;

        // Filter testing
        $response = $this->actingAs($this->adminUser)->get(route('admin.cash-management', [
            'name' => $uniqueFrank,
            'sort' => 'amount_desc',
        ]));
        $response->assertStatus(200);
        $response->assertSee($uniqueFrank);
        $response->assertDontSee($uniqueEmma);

        // Batch delete tx1
        $deleteResponse = $this->actingAs($this->adminUser)->post(route('admin.cash.batch-delete'), [
            'ids' => [$tx1->id],
        ]);
        $deleteResponse->assertRedirect(route('admin.cash-management'));
        $this->assertDatabaseMissing('cash_transactions', ['id' => $tx1->id]);
        $this->assertDatabaseHas('cash_transactions', ['id' => $tx2->id]);
    }

    public function test_roles_batch_delete_enforces_self_deletion_invariant(): void
    {
        $uniqueUser1 = 'user_one_' . uniqid();
        $user1 = User::create([
            'username' => $uniqueUser1,
            'name' => 'User One',
            'email' => $uniqueUser1 . '@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);
        $this->createdUserIds[] = $user1->id;

        $uniqueUser2 = 'user_two_' . uniqid();
        $user2 = User::create([
            'username' => $uniqueUser2,
            'name' => 'User Two',
            'email' => $uniqueUser2 . '@test.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);
        $this->createdUserIds[] = $user2->id;

        // Filter testing
        $response = $this->actingAs($this->adminUser)->get(route('admin.roles', [
            'search' => $uniqueUser1,
            'role' => 'user',
            'sort' => 'username_asc',
        ]));
        $response->assertStatus(200);
        $response->assertSee('@' . $uniqueUser1);
        $response->assertDontSee('@' . $uniqueUser2);

        // Attempting to batch delete ONLY self must be rejected
        $selfDeleteResponse = $this->actingAs($this->adminUser)->post(route('admin.roles.batch-delete'), [
            'ids' => [$this->adminUser->id],
        ]);
        $selfDeleteResponse->assertSessionHas('error', 'Cannot delete your own active administrator account.');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);

        // Batch deleting multiple users where self is included safely ignores self and deletes the others
        $batchResponse = $this->actingAs($this->adminUser)->post(route('admin.roles.batch-delete'), [
            'ids' => [$this->adminUser->id, $user1->id, $user2->id],
        ]);
        $batchResponse->assertRedirect(route('admin.roles'));
        $batchResponse->assertSessionHas('success', 'Successfully deleted 2 accounts.');

        // Self remains active, user1 and user2 are deleted
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
        $this->assertDatabaseMissing('users', ['id' => $user1->id]);
        $this->assertDatabaseMissing('users', ['id' => $user2->id]);
    }
}
