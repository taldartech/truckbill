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

    public function test_super_admin_can_view_company_bank_details_index(): void
    {
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->get(route('company_bank_details.index'));

        $response->assertOk();
        $response->assertViewIs('company_bank_details.index');
        $response->assertSee('Company Bank Details');
    }

    public function test_super_admin_can_view_edit_form(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $super->company;

        $response = $this->actingAs($super)->get(route('company_bank_details.edit', $company->id));

        $response->assertOk();
        $response->assertViewIs('company_bank_details.edit');
        $response->assertSee($company->name);
        $response->assertSee('bank');
        $response->assertSee('account_number');
        $response->assertSee('ifsc');
    }

    public function test_super_admin_can_update_company_bank_details(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $super->company;

        $payload = [
            'bank' => 'State Bank of India',
            'account_number' => '12345678901234',
            'ifsc' => 'SBIN0001234',
        ];

        $response = $this->actingAs($super)->put(route('company_bank_details.update', $company->id), $payload);

        $response->assertRedirect(route('company_bank_details.index'));
        $response->assertSessionHas('success', 'Company bank details updated successfully.');

        $company->refresh();
        $this->assertSame('State Bank of India', $company->bank);
        $this->assertSame('12345678901234', $company->account_number);
        $this->assertSame('SBIN0001234', $company->ifsc);
    }

    public function test_update_validates_ifsc_format(): void
    {
        $super = $this->makeSuperAdmin();
        $company = $super->company;

        $response = $this->actingAs($super)->put(route('company_bank_details.update', $company->id), [
            'bank' => 'Test Bank',
            'account_number' => '12345',
            'ifsc' => 'INVALID',
        ]);

        $response->assertSessionHasErrors('ifsc');
        $company->refresh();
        $this->assertNull($company->bank);
    }

    public function test_super_admin_cannot_edit_bank_details_for_company_they_do_not_own(): void
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

        $this->actingAs($super)->get(route('company_bank_details.edit', $foreignCompany->id))->assertForbidden();
        $this->actingAs($super)->put(route('company_bank_details.update', $foreignCompany->id), [
            'bank' => 'Test Bank',
            'account_number' => '12345',
            'ifsc' => 'SBIN0001234',
        ])->assertForbidden();
    }

    public function test_admin_forbidden_from_company_bank_details(): void
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

        $this->actingAs($admin)->get(route('company_bank_details.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('company_bank_details.edit', $company->id))->assertForbidden();
    }
}
