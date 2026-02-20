<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyEnterExitTest extends TestCase
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
    }

    protected function makeSuperAdmin(): User
    {
        return User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Admin Company',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
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

    public function test_super_admin_can_enter_owned_company(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $response = $this->actingAs($super)->get(route('company.enter', $company));

        $response->assertRedirect(route('dashboard'));
        $this->assertEquals($company->id, session('super_admin_company_id'));
    }

    public function test_super_admin_cannot_enter_company_they_do_not_own(): void
    {
        $super = $this->makeSuperAdmin();
        $otherSuper = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $company = $this->createCompanyFor($otherSuper);

        $response = $this->actingAs($super)->get(route('company.enter', $company));

        $response->assertForbidden();
        $this->assertNull(session('super_admin_company_id'));
    }

    public function test_admin_cannot_enter_company(): void
    {
        $admin = $this->makeAdmin();
        $company = $admin->company;

        $response = $this->actingAs($admin)->get(route('company.enter', $company));

        $response->assertForbidden();
    }

    public function test_super_admin_can_exit_company(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $this->actingAs($super)->get(route('company.enter', $company));
        $this->assertEquals($company->id, session('super_admin_company_id'));

        $response = $this->actingAs($super)->get(route('company.exit'));

        $response->assertRedirect(route('company.index'));
        $this->assertNull(session('super_admin_company_id'));
    }

    public function test_super_admin_with_company_context_can_access_lorry_receipts(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $this->actingAs($super)->get(route('company.enter', $company));

        $response = $this->actingAs($super)->get('/lorry_receipts');

        $response->assertOk();
    }

    public function test_user_get_company_id_attribute_returns_session_when_super_admin_in_context(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $this->actingAs($super);
        session(['super_admin_company_id' => $company->id]);

        $this->assertEquals($company->id, $super->fresh()->company_id);
    }

    public function test_user_can_act_as_admin_returns_true_for_super_admin_with_session(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $this->actingAs($super);
        session(['super_admin_company_id' => $company->id]);

        $this->assertTrue($super->fresh()->canActAsAdmin());
    }

    public function test_user_can_act_as_admin_returns_false_for_super_admin_without_session(): void
    {
        $super = $this->makeSuperAdmin();

        $this->actingAs($super);

        $this->assertFalse($super->fresh()->canActAsAdmin());
    }

    public function test_super_admin_without_context_sees_dashboard_global(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $response = $this->actingAs($super)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard_global');
        $response->assertSee($company->name);
    }

    public function test_super_admin_with_context_can_access_company_resources(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $this->createCompanyFor($super);

        $this->actingAs($super)->get(route('company.enter', $company));

        $this->actingAs($super)->get('/transporters')->assertOk();
        $this->actingAs($super)->get('/consignees')->assertOk();
        $this->actingAs($super)->get('/freight_bookings')->assertOk();
        $this->actingAs($super)->get('/invoices')->assertOk();
        $this->actingAs($super)->get('/voucher_payments')->assertOk();
    }

    public function test_super_admin_without_context_forbidden_from_company_resources(): void
    {
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)->get('/transporters')->assertForbidden();
        $this->actingAs($super)->get('/consignees')->assertForbidden();
        $this->actingAs($super)->get('/invoices')->assertForbidden();
        $this->actingAs($super)->get('/voucher_payments')->assertForbidden();
    }
}
