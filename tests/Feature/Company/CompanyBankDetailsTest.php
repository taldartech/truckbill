<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyBankDetailsTest extends TestCase
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

    public function test_super_admin_can_update_bank_details_on_company_edit(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $super->company;

        $payload = [
            'name' => $company->name,
            'pan_number' => $company->pan_number ?? 'AABAD1452A',
            'gst_scheme' => $company->gst_scheme ?? 'reverse charge',
            'address' => $company->address ?? 'Test address',
            'contact_person' => $company->contact_person ?? 'Contact',
            'contact_number' => $company->contact_number ?? '9876543210',
            'company_type' => $company->company_type ?? 'Broker',
            'country_id' => $company->country_id,
            'state_id' => $company->state_id,
            'city_id' => $company->city_id,
            'pin_code' => $company->pin_code ?? '400001',
            'gst_slab' => $company->gst_slab,
            'bank' => 'State Bank of India',
            'account_number' => '12345678901234',
            'ifsc' => 'SBIN0001234',
        ];

        $response = $this->actingAs($super)->put(route('company.update', $company->id), $payload);

        $response->assertRedirect(route('company.index'));

        $company->refresh();
        $this->assertSame('State Bank of India', $company->bank);
        $this->assertSame('12345678901234', $company->account_number);
        $this->assertSame('SBIN0001234', $company->ifsc);
    }

    public function test_company_update_validates_ifsc_format(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $super->company;

        $response = $this->actingAs($super)->put(route('company.update', $company->id), [
            'name' => $company->name,
            'pan_number' => $company->pan_number ?? 'AABAD1452A',
            'gst_scheme' => $company->gst_scheme ?? 'reverse charge',
            'address' => $company->address ?? 'Test address',
            'contact_person' => $company->contact_person ?? 'Contact',
            'contact_number' => $company->contact_number ?? '9876543210',
            'company_type' => $company->company_type ?? 'Broker',
            'pin_code' => $company->pin_code ?? '400001',
            'gst_slab' => $company->gst_slab,
            'bank' => 'Test Bank',
            'account_number' => '12345',
            'ifsc' => 'INVALID',
        ]);

        $response->assertSessionHasErrors('ifsc');
    }

    public function test_admin_forbidden_from_company_settings(): void
    {
        $company = Company::create([
            'name' => 'Admin Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => null,
            'is_blocked' => false,
        ]);

        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)->get(route('company_settings.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('company_settings.edit', $company->id))->assertForbidden();
    }
}
