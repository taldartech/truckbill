<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyGstSchemeRestrictionTest extends TestCase
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
    }

    protected function makeUser(string $role): User
    {
        $company = Company::create([
            'name' => 'Company '.$role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'gst_scheme' => 'forward charge',
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $roleId = UserRole::where('name', $role)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    public function test_edit_page_shows_gst_scheme_disabled_when_invoices_exist_in_fy(): void
    {
        Carbon::setTestNow('2025-06-15 12:00:00');

        $admin = $this->makeUser('super_admin');
        $company = $admin->company;
        $company->update(['user_id' => $admin->id]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer',
            'address' => 'Addr',
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'generated_date' => '2025-05-01',
            'invoice_type' => 'regular',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);

        $response = $this->actingAs($admin)->get("/company/{$company->id}/edit");

        $response->assertOk()
            ->assertSee('Cannot change GST scheme')
            ->assertSee('Invoices have been created for the current financial year');

        Carbon::setTestNow();
    }

    public function test_edit_page_shows_gst_scheme_enabled_when_no_invoices_in_fy(): void
    {
        $admin = $this->makeUser('super_admin');
        $company = $admin->company;
        $company->update(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->get("/company/{$company->id}/edit");

        $response->assertOk()
            ->assertDontSee('Cannot change GST scheme');
    }

    public function test_update_blocks_gst_scheme_change_when_invoices_exist_in_fy(): void
    {
        Carbon::setTestNow('2025-06-15 12:00:00');

        $admin = $this->makeUser('super_admin');
        $company = $admin->company;
        $company->update(['user_id' => $admin->id, 'gst_scheme' => 'forward charge']);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer',
            'address' => 'Addr',
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'generated_date' => '2025-05-01',
            'invoice_type' => 'regular',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);

        $payload = [
            'name' => $company->name,
            'gst_scheme' => 'reverse charge',
            'gst_type' => 'regular',
            'gst_slab' => config('payments.gst_slabs')[0],
            'address' => $company->address ?? 'Addr',
            'pan_number' => $company->pan_number ?? 'ABCDE1234F',
        ];

        $response = $this->actingAs($admin)->put("/company/{$company->id}", $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('gst_scheme');

        $company->refresh();
        $this->assertSame('forward charge', $company->gst_scheme);

        Carbon::setTestNow();
    }

    public function test_update_allows_gst_scheme_change_when_no_invoices_in_fy(): void
    {
        $admin = $this->makeUser('super_admin');
        $company = $admin->company;
        $company->update(['user_id' => $admin->id, 'gst_scheme' => 'forward charge']);

        $payload = [
            'name' => $company->name,
            'gst_scheme' => 'reverse charge',
            'gst_type' => 'regular',
            'gst_slab' => config('payments.gst_slabs')[0],
            'address' => $company->address ?? 'Addr',
            'pan_number' => $company->pan_number ?? 'ABCDE1234F',
        ];

        $response = $this->actingAs($admin)->put("/company/{$company->id}", $payload);

        $response->assertRedirect(route('company.index'));
        $company->refresh();
        $this->assertSame('reverse charge', $company->gst_scheme);
    }
}
