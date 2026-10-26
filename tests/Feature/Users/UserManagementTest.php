<?php

namespace Tests\Feature\Users;

use App\Http\Controllers\UserController;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
        UserRole::firstOrCreate(['name' => 'customer']);
    }

    protected function makeMasterAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_role_id' => 1,
            'company_id' => null,
        ], $overrides));
    }

    protected function makeSuperAdmin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'user_role_id' => 2,
            'company_id' => null,
        ], $overrides));
    }

    protected function createCompanyFor(User $owner, array $attributes = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Company ' . $owner->id,
            'user_id' => $owner->id,
            'is_blocked' => false,
        ], $attributes));
    }

    protected function createBranch(Company $company, array $attributes = []): Branch
    {
        return Branch::create(array_merge([
            'company_id' => $company->id,
            'branch_name' => 'HQ',
        ], $attributes));
    }

    public function test_master_admin_can_view_users_index(): void
    {
        $admin = $this->makeMasterAdmin();

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk()->assertViewIs('users.index');
    }

    public function test_master_admin_datatable_returns_super_admin_users(): void
    {
        $admin = $this->makeMasterAdmin();
        $super = $this->makeSuperAdmin();

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?draw=1&start=0&length=10');

        $response->assertOk()
            ->assertJsonFragment(['recordsTotal' => 1])
            ->assertJsonFragment(['recordsFiltered' => 1]);

        // Master admin should only see super_admin users, not themselves
        $this->assertCount(1, $response->json('data'));
        $emails = collect($response->json('data'))->pluck('email');
        $this->assertTrue($emails->contains($super->email));
        $this->assertFalse($emails->contains($admin->email));
    }

    public function test_super_admin_datatable_is_scoped_to_owned_companies(): void
    {
        $super = $this->makeSuperAdmin();
        $ownedCompany = $this->createCompanyFor($super);
        $otherCompany = Company::create(['name' => 'Other Co', 'user_id' => null]);

        $ownedUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $ownedCompany->id,
            'email' => 'owned@example.com',
        ]);
        $otherUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $otherCompany->id,
            'email' => 'other@example.com',
        ]);

        $response = $this
            ->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?draw=1&start=0&length=10');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('owned@example.com', $response->json('data')[0]['email']);
    }

    public function test_user_create_page_uses_redesigned_form_shell(): void
    {
        $actor = $this->makeSuperAdmin();
        $this->createCompanyFor($actor, ['name' => 'Acme Logistics']);

        $response = $this->actingAs($actor)->get(route('users.create'));

        $response->assertOk()
            ->assertViewIs('users.create')
            ->assertSee('app-create-page', false)
            ->assertSee('app-form-section', false)
            ->assertSee('Create User')
            ->assertSee('Login Credentials')
            ->assertSee('Acme Logistics')
            ->assertSee('id="userForm"', false)
            ->assertSee('data-confirm-mode="create"', false)
            ->assertSee('js/user-form.js', false)
            ->assertDontSee('Add New User');
    }

    public function test_master_admin_create_page_hides_company_and_branch(): void
    {
        $actor = $this->makeMasterAdmin();

        $response = $this->actingAs($actor)->get(route('users.create'));

        $response->assertOk()
            ->assertViewIs('users.create')
            ->assertSee('app-create-page', false)
            ->assertSee('Super Admin')
            ->assertSee('name="role"', false)
            ->assertSee('value="super_admin"', false)
            ->assertDontSee('name="company_id"', false)
            ->assertDontSee('name="branch_id"', false);
    }

    public function test_store_creates_admin_with_company_and_branch(): void
    {
        // Super admin can create company users
        $actor = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($actor);
        $branch = $this->createBranch($company);

        $response = $this->actingAs($actor)->post(route('users.store'), [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(3, $user->user_role_id);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_master_admin_can_create_super_admin(): void
    {
        $actor = $this->makeMasterAdmin();

        $response = $this->actingAs($actor)->post(route('users.store'), [
            'name' => 'New Super Admin',
            'email' => 'newsuper@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'super_admin',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'newsuper@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(2, $user->user_role_id); // super_admin role_id
        $this->assertNull($user->company_id);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_store_requires_company_and_branch_for_non_super_admin(): void
    {
        $actor = $this->makeMasterAdmin();

        $response = $this->actingAs($actor)->from(route('users.create'))->post(route('users.store'), [
            'name' => 'No Company',
            'email' => 'nocompany@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors(['company_id', 'branch_id']);
        $this->assertDatabaseMissing('users', ['email' => 'nocompany@example.com']);
    }

    public function test_user_edit_page_uses_redesigned_form_shell(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super, ['name' => 'Acme Logistics']);
        $branch = $this->createBranch($company, ['branch_name' => 'Main Branch']);
        $target = User::factory()->create([
            'name' => 'Existing Admin',
            'user_role_id' => 3,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        $response = $this->actingAs($super)->get(route('users.edit', $target));

        $response->assertOk()
            ->assertViewIs('users.edit')
            ->assertSee('app-create-page', false)
            ->assertSee('app-form-section', false)
            ->assertSee('Edit User')
            ->assertSee('User Details')
            ->assertSee('Login Credentials')
            ->assertSee('Existing Admin')
            ->assertSee('Acme Logistics')
            ->assertSee('Leave blank to keep the current password')
            ->assertSee('id="userForm"', false)
            ->assertSee('data-confirm-mode="update"', false)
            ->assertSee('js/user-form.js', false);
    }

    public function test_edit_forbidden_when_user_not_in_owned_company(): void
    {
        $super = $this->makeSuperAdmin();
        $otherOwner = $this->makeMasterAdmin(['email' => 'other@owner.com']);
        $otherCompany = $this->createCompanyFor($otherOwner);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->actingAs($super)->get(route('users.edit', $target));

        $response->assertForbidden();
    }

    public function test_destroy_soft_deletes_user_in_owned_company(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $response = $this->actingAs($super)->delete('/users/' . $target->id, [
            'delete_remark' => 'User left company',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['id' => $target->id, 'deleted_remark' => 'User left company']);
    }

    public function test_destroy_forbidden_for_user_outside_owned_company(): void
    {
        $super = $this->makeSuperAdmin();
        $otherOwner = $this->makeMasterAdmin(['email' => 'another@owner.com']);
        $otherCompany = $this->createCompanyFor($otherOwner);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->actingAs($super)->delete('/users/' . $target->id);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_update_changes_role_and_password(): void
    {
        // Super admin can update company users
        $actor = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($actor);
        $branch = $this->createBranch($company);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($actor)->put(route('users.update', $target), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'newsecret',
            'password_confirmation' => 'newsecret',
            'role' => 'moderator',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Updated Name', $target->name);
        $this->assertSame('updated@example.com', $target->email);
        $this->assertSame(4, $target->user_role_id);
        $this->assertTrue(Hash::check('newsecret', $target->password));
    }

    public function test_master_admin_can_update_super_admin(): void
    {
        $actor = $this->makeMasterAdmin();
        $target = $this->makeSuperAdmin(['email' => 'super@example.com']);

        $response = $this->actingAs($actor)->put(route('users.update', $target), [
            'name' => 'Updated Super Admin',
            'email' => 'updatedsuper@example.com',
            'password' => 'newsecret',
            'password_confirmation' => 'newsecret',
            'role' => 'super_admin',
        ]);

        $response->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Updated Super Admin', $target->name);
        $this->assertSame('updatedsuper@example.com', $target->email);
        $this->assertSame(2, $target->user_role_id); // super_admin role_id
        $this->assertTrue(Hash::check('newsecret', $target->password));
    }

    public function test_master_admin_block_cascades_to_linked_companies_and_users(): void
    {
        $master = $this->makeMasterAdmin();
        $targetSuperAdmin = $this->makeSuperAdmin(['email' => 'target-super@example.com']);
        $company = $this->createCompanyFor($targetSuperAdmin, ['name' => 'Linked Company']);
        $branch = $this->createBranch($company);
        $linkedUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'email' => 'linked-user@example.com',
        ]);

        $blockResponse = $this->actingAs($master)->post(route('users.block', $targetSuperAdmin), [
            'reason' => 'Master block reason',
        ]);

        $blockResponse->assertRedirect(route('users.index'));
        $targetSuperAdmin->refresh();
        $linkedUser->refresh();
        $company->refresh();

        $this->assertTrue((bool) $targetSuperAdmin->is_blocked);
        $this->assertTrue((bool) $linkedUser->is_blocked);
        $this->assertTrue((bool) $company->is_blocked);
        $this->assertSame('Master block reason', $targetSuperAdmin->block_reason);
        $this->assertSame($master->id, $targetSuperAdmin->blocked_by);
        $this->assertNotNull($targetSuperAdmin->session_token);
        $this->assertSame('Master block reason', $linkedUser->block_reason);
        $this->assertSame($master->id, $linkedUser->blocked_by);
        $this->assertNotNull($linkedUser->session_token);

        $unblockResponse = $this->actingAs($master)->post(route('users.unblock', $targetSuperAdmin), [
            'reason' => 'Master unblock reason',
        ]);

        $unblockResponse->assertRedirect(route('users.index'));
        $targetSuperAdmin->refresh();
        $linkedUser->refresh();
        $company->refresh();

        $this->assertFalse((bool) $targetSuperAdmin->is_blocked);
        $this->assertFalse((bool) $linkedUser->is_blocked);
        $this->assertFalse((bool) $company->is_blocked);
        $this->assertNull($targetSuperAdmin->block_reason);
        $this->assertNull($targetSuperAdmin->blocked_by);
        $this->assertNull($targetSuperAdmin->session_token);
        $this->assertNull($linkedUser->block_reason);
        $this->assertNull($linkedUser->blocked_by);
        $this->assertNull($linkedUser->session_token);
    }

    public function test_master_admin_datatable_global_search_filters_super_admins(): void
    {
        $admin = $this->makeMasterAdmin();
        $this->makeSuperAdmin(['name' => 'Searchable Super', 'email' => 'searchable@example.com']);
        $this->makeSuperAdmin(['name' => 'Other Person', 'email' => 'other-super@example.com']);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => 'Searchable'],
            ]));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('searchable@example.com', $response->json('data.0.email'));
        $this->assertSame(1, $response->json('recordsFiltered'));
    }

    public function test_master_admin_datatable_column_search_and_sorting(): void
    {
        $admin = $this->makeMasterAdmin();
        $this->makeSuperAdmin(['name' => 'Zulu Admin', 'email' => 'zulu@example.com']);
        $this->makeSuperAdmin(['name' => 'Alpha Admin', 'email' => 'alpha@example.com']);

        $columnSearch = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => [
                    ['search' => ['value' => '']],
                    ['search' => ['value' => 'Alpha']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                ],
            ]));

        $columnSearch->assertOk();
        $this->assertCount(1, $columnSearch->json('data'));
        $this->assertSame('alpha@example.com', $columnSearch->json('data.0.email'));

        $emailColumnSearch = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => [
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => 'zulu@']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                ],
            ]));

        $emailColumnSearch->assertOk();
        $this->assertCount(1, $emailColumnSearch->json('data'));
        $this->assertSame('zulu@example.com', $emailColumnSearch->json('data.0.email'));

        foreach ([0 => 'id', 1 => 'name', 2 => 'email', 3 => 'role'] as $column => $label) {
            $sorted = $this
                ->actingAs($admin)
                ->withHeader('X-Requested-With', 'XMLHttpRequest')
                ->get('/users?'.http_build_query([
                    'draw' => 1,
                    'start' => 0,
                    'length' => 10,
                    'order' => [['column' => $column, 'dir' => 'asc']],
                ]));

            $sorted->assertOk();
            $this->assertCount(2, $sorted->json('data'), "Expected two rows when sorting by {$label}");
        }

        $defaultSort = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'order' => [['column' => 99, 'dir' => 'asc']],
            ]));
        $defaultSort->assertOk();
        $this->assertCount(2, $defaultSort->json('data'));
    }

    public function test_master_admin_datatable_includes_block_and_unblock_actions(): void
    {
        $admin = $this->makeMasterAdmin();
        $this->makeSuperAdmin(['email' => 'blocked@example.com', 'is_blocked' => true]);
        $this->makeSuperAdmin(['email' => 'active@example.com', 'is_blocked' => false]);

        $response = $this
            ->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?draw=1&start=0&length=10');

        $response->assertOk();
        $actionsByEmail = collect($response->json('data'))
            ->mapWithKeys(fn (array $row) => [$row['email'] => $row['actions']]);

        $this->assertStringContainsString('/users/', $actionsByEmail['blocked@example.com']);
        $this->assertStringContainsString('unblock', $actionsByEmail['blocked@example.com']);
        $this->assertStringContainsString('block', $actionsByEmail['active@example.com']);
    }

    public function test_block_and_unblock_require_reason(): void
    {
        $master = $this->makeMasterAdmin();
        $target = $this->makeSuperAdmin();

        $this->actingAs($master)
            ->post(route('users.block', $target), [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($master)
            ->post(route('users.unblock', $target), [])
            ->assertSessionHasErrors('reason');
    }

    public function test_super_admin_datatable_supports_role_and_company_column_search(): void
    {
        $super = $this->makeSuperAdmin();
        $companyA = $this->createCompanyFor($super, ['name' => 'Alpha Logistics']);
        $companyB = $this->createCompanyFor($super, ['name' => 'Beta Logistics']);
        $branchA = $this->createBranch($companyA);
        $branchB = $this->createBranch($companyB);

        User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'name' => 'Alpha Admin',
            'email' => 'alpha-admin@example.com',
        ]);
        User::factory()->create([
            'user_role_id' => 4,
            'company_id' => $companyB->id,
            'branch_id' => $branchB->id,
            'name' => 'Beta Moderator',
            'email' => 'beta-mod@example.com',
        ]);

        $roleSearch = $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => [
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => 'moderator']],
                    ['search' => ['value' => '']],
                ],
            ]));
        $roleSearch->assertOk();
        $this->assertCount(1, $roleSearch->json('data'));
        $this->assertSame('beta-mod@example.com', $roleSearch->json('data.0.email'));

        $companySearch = $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => [
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => 'Alpha Log']],
                ],
            ]));
        $companySearch->assertOk();
        $this->assertCount(1, $companySearch->json('data'));
        $this->assertSame('alpha-admin@example.com', $companySearch->json('data.0.email'));

        $companySort = $this->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'order' => [['column' => 4, 'dir' => 'asc']],
            ]));
        $companySort->assertOk();
        $this->assertCount(2, $companySort->json('data'));
    }

    public function test_master_admin_can_edit_super_admin_user_form(): void
    {
        $master = $this->makeMasterAdmin();
        $target = $this->makeSuperAdmin(['name' => 'Editable Super', 'email' => 'editable-super@example.com']);

        $this->actingAs($master)
            ->get(route('users.edit', $target))
            ->assertOk()
            ->assertViewIs('users.edit')
            ->assertSee('Editable Super')
            ->assertSee('editable-super@example.com');
    }

    public function test_update_without_password_keeps_existing_hash(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $branch = $this->createBranch($company);
        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'password' => bcrypt('original-secret'),
        ]);
        $originalHash = $target->password;

        $this->actingAs($super)->put(route('users.update', $target), [
            'name' => 'Renamed Admin',
            'email' => $target->email,
            'role' => 'admin',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ])->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Renamed Admin', $target->name);
        $this->assertSame($originalHash, $target->password);
    }

    public function test_update_moves_user_to_another_branch_when_subscription_allows(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $branchA = $this->createBranch($company, ['branch_name' => 'Branch A']);
        $branchB = $this->createBranch($company, ['branch_name' => 'Branch B']);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => Carbon::today()->subMonth(),
            'ends_on' => Carbon::today()->addYear(),
            'included_branches' => 5,
            'users_per_branch' => 5,
            'lr_credits_remaining' => 50,
        ]);

        $target = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
            'branch_id' => $branchA->id,
        ]);

        $this->actingAs($super)->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'admin',
            'company_id' => $company->id,
            'branch_id' => $branchB->id,
        ])->assertRedirect(route('users.index'));

        $this->assertSame($branchB->id, $target->fresh()->branch_id);
    }

    public function test_master_admin_cannot_block_non_super_admin_user(): void
    {
        $master = $this->makeMasterAdmin();
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $this->actingAs($master)
            ->post(route('users.block', $admin), ['reason' => 'Should not apply'])
            ->assertForbidden();

        $this->actingAs($master)
            ->post(route('users.unblock', $admin), ['reason' => 'Should not apply'])
            ->assertForbidden();
    }

    public function test_can_manage_user_allows_customer_linked_via_consignee(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Portal Customer',
        ]);
        $customerRoleId = UserRole::where('name', 'customer')->value('id');
        $customerUser = User::create([
            'name' => 'Portal Customer User',
            'email' => 'portal-customer@example.com',
            'password' => bcrypt('password'),
            'user_role_id' => $customerRoleId,
            'company_id' => $company->id,
        ]);
        $customerUser->update(['company_id' => null]);
        $customerUser->consignees()->attach($consignee->id);

        $method = new ReflectionMethod(UserController::class, 'canManageUser');
        $method->setAccessible(true);
        $controller = app(UserController::class);

        $this->assertTrue($method->invoke($controller, $super, $customerUser->fresh()));

        $unlinkedCustomer = User::create([
            'name' => 'Unlinked Customer',
            'email' => 'unlinked-customer@example.com',
            'password' => bcrypt('password'),
            'user_role_id' => $customerRoleId,
            'company_id' => $company->id,
        ]);
        $unlinkedCustomer->update(['company_id' => null]);

        $this->assertFalse($method->invoke($controller, $super, $unlinkedCustomer->fresh()));
    }

    public function test_datatable_rejects_unscoped_role_via_internal_guard(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $controller = app(UserController::class);
        $method = new ReflectionMethod(UserController::class, 'getUsersData');
        $method->setAccessible(true);

        $this->actingAs($admin);

        try {
            $method->invoke($controller, Request::create('/users', 'GET', ['draw' => 1]));
            $this->fail('Expected unauthorized datatable access to abort.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_get_total_records_returns_zero_for_unscoped_role(): void
    {
        $company = Company::create(['name' => 'Scoped Co', 'is_blocked' => false]);
        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $method = new ReflectionMethod(UserController::class, 'getTotalRecords');
        $method->setAccessible(true);
        $controller = app(UserController::class);

        $this->assertSame(0, $method->invoke($controller, $admin));
    }

    public function test_update_returns_403_when_user_cannot_be_managed(): void
    {
        $super = $this->makeSuperAdmin();
        $otherSuper = $this->makeSuperAdmin(['email' => 'other-super@example.com']);
        $foreignCompany = $this->createCompanyFor($otherSuper);
        $foreignAdmin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $foreignCompany->id,
        ]);

        $this->actingAs($super)
            ->put(route('users.update', $foreignAdmin), [
                'name' => 'Blocked Update',
                'email' => $foreignAdmin->email,
                'role' => 'admin',
                'company_id' => $foreignCompany->id,
                'branch_id' => $this->createBranch($foreignCompany)->id,
            ])
            ->assertForbidden();
    }

    public function test_can_manage_user_returns_false_for_non_privileged_roles(): void
    {
        $company = Company::create(['name' => 'Role Guard Co', 'is_blocked' => false]);
        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
        $target = User::factory()->create([
            'user_role_id' => 4,
            'company_id' => $company->id,
        ]);

        $method = new ReflectionMethod(UserController::class, 'canManageUser');
        $method->setAccessible(true);
        $controller = app(UserController::class);

        $this->assertFalse($method->invoke($controller, $admin, $target));
    }

    public function test_datatable_column_zero_search_is_ignored(): void
    {
        $admin = $this->makeMasterAdmin();
        $this->makeSuperAdmin(['email' => 'visible@example.com']);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/users?'.http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'columns' => [
                    ['search' => ['value' => 'no-match-term']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                    ['search' => ['value' => '']],
                ],
            ]));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('visible@example.com', $response->json('data.0.email'));
    }
}

