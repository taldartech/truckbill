<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
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
        $super = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Super Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $super->id,
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $super->update(['company_id' => $company->id]);

        return $super->fresh();
    }

    public function test_super_admin_can_view_company_settings_index(): void
    {
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->get(route('company_settings.index'));

        $response->assertOk();
        $response->assertViewIs('company_settings.index');
        $response->assertSee('Company settings');
        $response->assertSee('Super Co');
    }

    public function test_super_admin_can_view_company_settings_edit_form(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $super->company;

        $response = $this->actingAs($super)->get(route('company_settings.edit', $company));

        $response->assertOk();
        $response->assertViewIs('company_settings.edit');
        $response->assertSee('Print header', false);
        $response->assertSee('Document designs', false);
    }

    public function test_super_admin_can_update_company_settings(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $super->company;

        $payload = [
            'template_name' => 'Voucher Header Co',
            'template_address' => '123 Main Street',
            'template_header' => 'Transport Broker',
            'template_note' => 'Thank you',
            'template_jurisdictional_boundary' => 'Mumbai',
            'invoice_template' => 'modern',
            'lr_copy_template' => 'compact',
            'freight_challan_template' => 'modern',
        ];

        $response = $this->actingAs($super)->put(route('company_settings.update', $company), $payload);

        $response->assertRedirect(route('company_settings.index'));
        $response->assertSessionHas('success');

        $setting = CompanySetting::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($setting);
        $this->assertSame('Voucher Header Co', $setting->template_name);
        $this->assertSame('modern', $setting->invoice_template);
        $this->assertSame('compact', $setting->lr_copy_template);
        $this->assertSame('modern', $setting->freight_challan_template);
    }

    public function test_super_admin_cannot_edit_settings_for_foreign_company(): void
    {
        $super = $this->makeSuperAdmin();

        $otherOwner = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $foreignCompany = Company::create([
            'name' => 'Other Owner Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $otherOwner->id,
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $this->actingAs($super)->get(route('company_settings.edit', $foreignCompany))->assertForbidden();
        $this->actingAs($super)->put(route('company_settings.update', $foreignCompany), [
            'template_name' => 'Test',
            'template_address' => 'Addr',
            'invoice_template' => 'classic',
            'lr_copy_template' => 'classic',
            'freight_challan_template' => 'classic',
        ])->assertForbidden();
    }
}
