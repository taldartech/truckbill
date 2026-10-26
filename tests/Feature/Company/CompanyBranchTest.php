<?php

namespace Tests\Feature\Company;

use App\Http\Controllers\BranchController;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Country;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
            'name' => 'Company '.$owner->id,
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
        $this->assertStringContainsString($owned->name, (string) $response->json('data.0.name'));
        $this->assertStringContainsString('company-name-link', (string) $response->json('data.0.name'));
        $this->assertArrayNotHasKey('gst_scheme', $response->json('data.0'));
        $this->assertArrayNotHasKey('gst_type', $response->json('data.0'));
    }

    public function test_company_index_hides_gst_scheme_and_gst_type_columns(): void
    {
        $super = $this->makeSuperAdmin();
        $this->createCompanyFor($super);

        $response = $this->actingAs($super)->get(route('company.index'));

        $response->assertOk();
        $response->assertDontSee('>GST Scheme<', false);
        $response->assertDontSee('>GST Type<', false);
        $response->assertSee('companyDetailsModal', false);
    }

    public function test_company_show_returns_details_json_for_owner(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super, [
            'name' => 'Detail Co',
            'short_name' => 'DCO',
            'gst_number' => '27AABCU9603R1ZM',
            'pan_number' => 'AABCU9603R',
            'gst_scheme' => 'forward charge',
            'gst_type' => 'regular',
            'address' => '12 MG Road',
            'contact_person' => 'Amit',
            'contact_number' => '9876543210',
            'bank' => 'HDFC Bank',
            'account_number' => '123456789012',
            'ifsc' => 'HDFC0001234',
        ]);

        $response = $this->actingAs($super)->get(route('company.show', $company));

        $response->assertOk()->assertJsonFragment([
            'id' => $company->id,
            'name' => 'Detail Co',
            'short_name' => 'DCO',
            'gst_number' => '27AABCU9603R1ZM',
            'gst_scheme' => 'Forward charge',
            'gst_type' => 'Regular',
            'bank' => 'HDFC Bank',
            'can_edit' => true,
        ]);
        $this->assertArrayNotHasKey('dummy_pan_number', $response->json());
    }

    public function test_company_show_is_forbidden_for_non_owner(): void
    {
        $super = $this->makeSuperAdmin();
        $other = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($other, ['name' => 'Other Co']);

        $this->actingAs($super)
            ->get(route('company.show', $company))
            ->assertForbidden();
    }

    public function test_master_admin_can_view_any_company_details(): void
    {
        $master = $this->makeMasterAdmin();
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super, ['name' => 'Visible Co']);

        $response = $this->actingAs($master)->get(route('company.show', $company));

        $response->assertOk()->assertJsonFragment([
            'id' => $company->id,
            'name' => 'Visible Co',
            'can_edit' => false,
        ]);
        $this->assertArrayHasKey('dummy_pan_number', $response->json());
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

    public function test_company_create_page_uses_redesigned_form_shell(): void
    {
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->get(route('company.create'));

        $response->assertOk()
            ->assertViewIs('company.create')
            ->assertSee('app-create-page', false)
            ->assertSee('app-form-section', false)
            ->assertSee('Register Company')
            ->assertSee('GST &amp; Tax', false)
            ->assertSee('Bank Details')
            ->assertDontSee('Company Type')
            ->assertSee('id="companyForm"', false)
            ->assertSee('js/company-form.js', false);
    }

    public function test_company_create_defaults_country_to_india(): void
    {
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        Country::create(['name' => 'United States', 'code' => 'US']);
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->get(route('company.create'));

        $response->assertOk()
            ->assertViewHas('selectedCountryId', $india->id)
            ->assertSee('value="'.$india->id.'" selected', false);
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
        $this->markTestSkipped('Legacy subscription fields dropped from companies table in migration 2026_06_19_000006');
    }

    public function test_super_admin_cannot_update_company_subscription_fields(): void
    {
        $this->markTestSkipped('Legacy subscription fields dropped from companies table in migration 2026_06_19_000006');
    }

    public function test_non_master_admin_cannot_update_company_subscription_fields(): void
    {
        $this->markTestSkipped('Legacy subscription fields dropped from companies table in migration 2026_06_19_000006');
    }

    public function test_company_block_and_unblock_toggle_flag(): void
    {
        $master = $this->makeMasterAdmin();
        $company = $this->createCompanyFor($master);

        $this->actingAs($master)
            ->get(route('company.block', $company->id))
            ->assertMethodNotAllowed();

        $blockResponse = $this->actingAs($master)->post(route('company.block', $company->id));
        $blockResponse->assertRedirect();
        $this->assertSame(true, (bool) $company->fresh()->is_blocked);

        $this->actingAs($master)
            ->get(route('company.unblock', $company->id))
            ->assertMethodNotAllowed();

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

    public function test_branch_create_page_uses_redesigned_form_shell(): void
    {
        $super = $this->makeSuperAdmin();
        $this->createCompanyFor($super, ['name' => 'Acme Logistics']);

        $response = $this->actingAs($super)->get(route('branches.create'));

        $response->assertOk()
            ->assertViewIs('branches.create')
            ->assertSee('app-create-page', false)
            ->assertSee('app-form-section', false)
            ->assertSee('Register Branch')
            ->assertSee('Branch Details')
            ->assertSee('Acme Logistics')
            ->assertSee('id="branchForm"', false)
            ->assertSee('data-confirm-mode="create"', false)
            ->assertSee('js/branch-form.js', false)
            ->assertDontSee('Register New Branch');
    }

    public function test_branch_edit_page_uses_redesigned_form_shell(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super, ['name' => 'Acme Logistics']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Main Branch',
            'head_office' => true,
        ]);

        $response = $this->actingAs($super)->get(route('branches.edit', $branch));

        $response->assertOk()
            ->assertViewIs('branches.edit')
            ->assertSee('app-create-page', false)
            ->assertSee('app-form-section', false)
            ->assertSee('Edit Branch')
            ->assertSee('Branch Details')
            ->assertSee('Main Branch')
            ->assertSee('id="branchForm"', false)
            ->assertSee('data-confirm-mode="update"', false)
            ->assertSee('js/branch-form.js', false);
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

    public function test_branch_index_datatables_ajax_search_and_sorting(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super, ['name' => 'Zeta Freight']);
        $branch1 = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Alpha Branch',
            'branch_contact_number' => '1111111111',
            'branch_address' => '100 Alpha Road',
            'head_office' => true,
        ]);
        $branch2 = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Beta Branch',
            'branch_contact_number' => '2222222222',
            'branch_address' => '200 Beta Street',
            'head_office' => false,
        ]);

        // 1. Basic AJAX call
        $response = $this->actingAs($super)
            ->getJson('/branches', ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertOk()
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
        $this->assertCount(2, $response->json('data'));
        $this->assertStringContainsString('btn-action-edit', $response->json('data.0.action'));
        $this->assertStringContainsString('btn-action-delete', $response->json('data.0.action'));

        // 2. Search query matching Alpha
        $searchResponse = $this->actingAs($super)->getJson('/branches?' . http_build_query([
            'search' => ['value' => 'Alpha'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchResponse->assertOk();
        $this->assertCount(1, $searchResponse->json('data'));

        // Search matching contact number
        $searchPhone = $this->actingAs($super)->getJson('/branches?' . http_build_query([
            'search' => ['value' => '222222'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchPhone->assertOk();
        $this->assertCount(1, $searchPhone->json('data'));

        // Search matching address
        $searchAddr = $this->actingAs($super)->getJson('/branches?' . http_build_query([
            'search' => ['value' => 'Beta Street'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchAddr->assertOk();
        $this->assertCount(1, $searchAddr->json('data'));

        // Search matching company name
        $searchComp = $this->actingAs($super)->getJson('/branches?' . http_build_query([
            'search' => ['value' => 'Zeta Freight'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchComp->assertOk();
        $this->assertCount(2, $searchComp->json('data'));

        // 3. Sorting on columns 1, 2, 3, 4, and default
        foreach ([1, 2, 3, 4, 99] as $col) {
            $sortRequest = Request::create('/branches', 'GET');
            $sortRequest->headers->set('X-Requested-With', 'XMLHttpRequest');
            $sortRequest->merge([
                'order' => [['column' => $col, 'dir' => 'asc']],
            ]);
            $this->actingAs($super);
            $sortResponse = app(BranchController::class)->index($sortRequest);
            $this->assertSame(200, $sortResponse->getStatusCode());
        }
    }

    public function test_branch_edit_forbidden_for_other_company(): void
    {
        $super1 = $this->makeSuperAdmin();
        $super2 = $this->makeSuperAdmin();
        $company2 = $this->createCompanyFor($super2);
        $branch = Branch::create([
            'company_id' => $company2->id,
            'branch_name' => 'Foreign Branch',
            'head_office' => false,
        ]);

        $response = $this->actingAs($super1)->get(route('branches.edit', $branch));
        $response->assertForbidden();
    }

    public function test_branch_constraints_validation_store_and_update(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $branch1 = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Main Office',
            'head_office' => true,
        ]);

        // Duplicate branch name on create
        $respDuplicateName = $this->actingAs($super)->post('/branches', [
            'company_id' => $company->id,
            'branch_name' => 'Main Office',
            'head_office' => false,
        ]);
        $respDuplicateName->assertSessionHasErrors(['branch_name']);

        // Duplicate head office on create
        $respDuplicateHO = $this->actingAs($super)->post('/branches', [
            'company_id' => $company->id,
            'branch_name' => 'Second Office',
            'head_office' => true,
        ]);
        $respDuplicateHO->assertSessionHasErrors(['head_office']);

        // Create second branch without head office
        $branch2 = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Second Office',
            'head_office' => false,
        ]);

        // Update branch2 with branch1 name
        $respUpdateDuplicate = $this->actingAs($super)->put("/branches/{$branch2->id}", [
            'company_id' => $company->id,
            'branch_name' => 'Main Office',
            'head_office' => false,
        ]);
        $respUpdateDuplicate->assertSessionHasErrors(['branch_name']);

        // Update branch2 to head office when branch1 is already head office
        $respUpdateHO = $this->actingAs($super)->put("/branches/{$branch2->id}", [
            'company_id' => $company->id,
            'branch_name' => 'Second Office',
            'head_office' => true,
        ]);
        $respUpdateHO->assertSessionHasErrors(['head_office']);
    }

    public function test_branch_store_rejects_invalid_contact_and_foreign_company(): void
    {
        $super = $this->makeSuperAdmin();
        $owned = $this->createCompanyFor($super);
        $otherSuper = $this->makeSuperAdmin();
        $foreign = $this->createCompanyFor($otherSuper);

        $this->actingAs($super)->from(route('branches.create'))->post('/branches', [
            'company_id' => $owned->id,
            'branch_name' => 'Bad Contact',
            'branch_contact_number' => '123',
            'head_office' => false,
        ])->assertRedirect(route('branches.create'))
            ->assertSessionHasErrors(['branch_contact_number']);

        $this->actingAs($super)->post('/branches', [
            'company_id' => $foreign->id,
            'branch_name' => 'Foreign Branch',
            'head_office' => false,
        ])->assertForbidden();
    }

    public function test_branch_update_rejects_duplicate_contact_number(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'First',
            'branch_contact_number' => '9876543210',
            'head_office' => true,
        ]);
        $second = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Second',
            'branch_contact_number' => '9123456789',
            'head_office' => false,
        ]);

        $this->actingAs($super)->put("/branches/{$second->id}", [
            'company_id' => $company->id,
            'branch_name' => 'Second',
            'branch_contact_number' => '9876543210',
            'head_office' => false,
        ])->assertSessionHasErrors(['branch_contact_number']);
    }

    public function test_branch_destroy_requires_remark_and_soft_deletes(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);
        $branch = Branch::create([
            'company_id' => $company->id,
            'branch_name' => 'Closing Branch',
            'head_office' => false,
        ]);

        // 1. Missing delete remark
        $failResp = $this->actingAs($super)->delete("/branches/{$branch->id}", []);
        $failResp->assertSessionHasErrors(['delete_remark']);

        // 2. Valid delete remark
        $successResp = $this->actingAs($super)->delete("/branches/{$branch->id}", [
            'delete_remark' => 'Branch operations closed',
        ]);
        $successResp->assertRedirect(route('branches.index'));
        $successResp->assertSessionHas('success');

        $this->assertSoftDeleted('branches', [
            'id' => $branch->id,
            'deleted_by' => $super->id,
            'deleted_remark' => 'Branch operations closed',
        ]);
    }
}

