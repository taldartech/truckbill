<?php

namespace Tests\Feature\Company;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyBranchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = sys_get_temp_dir().'/truckbill-views-'.getmypid();
        if (! is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }
        config(['view.compiled' => $compiledPath]);

        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    protected function makeMasterAdmin(): User
    {
        return User::factory()->create([
            'user_role_id' => 1,
        ]);
    }

    protected function makeSuperAdmin(): User
    {
        return User::factory()->create([
            'user_role_id' => 2,
        ]);
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'user_role_id' => 3,
        ]);
    }

    protected function createCompanyFor(User $owner, array $overrides = []): Company
    {
        return Company::create(array_merge([
            'name' => 'Company ' . $owner->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $owner->id,
            'is_blocked' => false,
        ], $overrides));
    }

    public function test_company_index_master_admin_returns_datatable_payload(): void
    {
        $master = $this->makeMasterAdmin();
        $this->createCompanyFor($master, ['name' => 'Index Co']);

        $response = $this
            ->actingAs($master)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/company?draw=1&start=0&length=10');

        $response->assertOk()->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);
    }

    public function test_company_datatable_scoped_for_super_admin(): void
    {
        $super = $this->makeSuperAdmin();
        $owned = $this->createCompanyFor($super);

        $response = $this
            ->actingAs($super)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/company?draw=1&start=0&length=10');

        $response->assertOk();
        $response->assertJsonFragment(['recordsTotal' => 1]);
        $response->assertJsonFragment(['recordsFiltered' => 1]);
        $this->assertSame($owned->name, $response->json('data.0.name'));
    }

    public function test_company_datatable_master_admin_sees_their_companies(): void
    {
        $master = $this->makeMasterAdmin();
        $this->createCompanyFor($master, ['name' => 'First Co']);
        $this->createCompanyFor($master, ['name' => 'Second Co']);

        $response = $this
            ->actingAs($master)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get('/company?draw=1&start=0&length=10');

        $response->assertOk();
        $response->assertJsonFragment(['recordsTotal' => 2]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_company_store_creates_company_with_owner(): void
    {
        $master = $this->makeMasterAdmin();

        $response = $this->actingAs($master)->post('/company', [
            'name' => 'New Co',
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);

        $response->assertRedirect(route('company.index'));
        $this->assertDatabaseHas('companies', [
            'name' => 'New Co',
            'user_id' => $master->id,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
    }

    public function test_company_update_changes_fields(): void
    {
        $master = $this->makeMasterAdmin();
        $company = $this->createCompanyFor($master, ['name' => 'Old Name']);

        $response = $this->actingAs($master)->put("/company/{$company->id}", [
            'name' => 'Updated Name',
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);

        $response->assertRedirect(route('company.index'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_master_admin_can_update_company_subscription_fields(): void
    {
        $master = $this->makeMasterAdmin();
        $company = $this->createCompanyFor($master, [
            'plan_end_date' => now()->subDay()->toDateString(),
            'lr_credits' => 10,
            'truck_credits' => 5,
            'name' => 'Subscription Co',
        ]);

        $response = $this->actingAs($master)->patch(route('company.subscription.update', $company), [
            'plan_end_date' => now()->addDays(30)->toDateString(),
            'lr_credits' => 120,
            'truck_credits' => 45,
        ]);

        $response->assertRedirect(route('company.index'));
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Subscription Co',
            'lr_credits' => 120,
            'truck_credits' => 45,
        ]);
    }

    public function test_super_admin_cannot_update_company_subscription_fields(): void
    {
        $master = $this->makeMasterAdmin();
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($master);

        $response = $this->actingAs($super)->patch(route('company.subscription.update', $company), [
            'plan_end_date' => now()->addDays(15)->toDateString(),
            'lr_credits' => 200,
            'truck_credits' => 20,
        ]);

        $response->assertForbidden();
    }

    public function test_non_master_admin_cannot_update_company_subscription_fields(): void
    {
        $master = $this->makeMasterAdmin();
        $admin = $this->makeAdmin();
        $company = $this->createCompanyFor($master);

        $response = $this->actingAs($admin)->patch(route('company.subscription.update', $company), [
            'plan_end_date' => now()->addDays(15)->toDateString(),
            'lr_credits' => 200,
            'truck_credits' => 20,
        ]);

        $response->assertForbidden();
    }

    public function test_company_block_and_unblock_toggle_flag(): void
    {
        $master = $this->makeMasterAdmin();
        $company = $this->createCompanyFor($master);

        $blockResponse = $this->actingAs($master)->post(route('company.block', $company->id));
        $blockResponse->assertRedirect();
        $this->assertSame(true, (bool) $company->fresh()->is_blocked);

        $unblockResponse = $this->actingAs($master)->post(route('company.unblock', $company->id));
        $unblockResponse->assertRedirect();
        $this->assertSame(false, (bool) $company->fresh()->is_blocked);
    }

    public function test_branch_index_returns_view_for_super_admin(): void
    {
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->get('/branches');

        $response->assertOk()->assertViewIs('branches.index');
    }

    public function test_branch_store_creates_branch_for_company(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $response = $this->actingAs($super)->post('/branches', [
            'company_id' => $company->id,
            'branch_name' => 'HQ',
            'branch_address' => '123 Street',
            'branch_contact_number' => '1234567890',
            'head_office' => true,
        ]);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'company_id' => $company->id,
            'branch_name' => 'HQ',
        ]);
    }

    public function test_branch_store_requires_company(): void
    {
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->from(route('branches.create'))->post('/branches', [
            'branch_name' => 'No Company',
            'branch_contact_number' => '1234567890',
            'head_office' => true,
        ]);

        $response->assertRedirect(route('branches.create'));
        $response->assertSessionHasErrors(['company_id']);
    }

    public function test_branch_update_edits_branch(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Old Branch',
            'head_office' => false,
        ]);

        $response = $this->actingAs($super)->put("/branches/{$branch->id}", [
            'company_id' => $company->id,
            'branch_name' => 'New Branch',
            'branch_contact_number' => '0987654321',
            'head_office' => true,
        ]);

        $response->assertRedirect(route('branches.index'));
        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'branch_name' => 'New Branch',
            'branch_contact_number' => '0987654321',
            'head_office' => true,
        ]);
    }
}

