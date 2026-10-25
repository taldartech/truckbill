<?php

namespace Tests\Feature\Accounting;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Ledger;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        $role = UserRole::create(['id' => 3, 'name' => 'admin']);

        $this->company = Company::create([
            'name' => 'Ledger Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);

        CompanySubscription::create([
            'company_id' => $this->company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $this->user = User::factory()->create([
            'user_role_id' => $role->id,
            'company_id' => $this->company->id,
        ]);

        $this->company->update(['user_id' => $this->user->id]);
    }

    public function test_super_admin_can_view_ledgers_index_and_provisions_coa(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.ledgers.index'));

        $response->assertOk()
            ->assertViewIs('accounting.ledgers.index')
            ->assertViewHas('company')
            ->assertViewHas('groups')
            ->assertViewHas('allGroups');
    }

    public function test_super_admin_with_session_company_views_selected_company_ledgers(): void
    {
        $superAdminRole = UserRole::where('name', 'super_admin')->first();
        $superUser = User::factory()->create([
            'user_role_id' => $superAdminRole->id,
            'company_id' => null,
        ]);

        $company2 = Company::create([
            'name' => 'Second Ledger Co',
            'user_id' => $superUser->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
        ]);

        CompanySubscription::create([
            'company_id' => $company2->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $response = $this->actingAs($superUser)
            ->withSession(['super_admin_company_id' => $company2->id])
            ->get(route('accounting.ledgers.index'));

        $response->assertOk()
            ->assertViewHas('company', fn ($comp) => $comp->id === $company2->id);
    }

    public function test_store_creates_ledger_and_redirects_for_web_request(): void
    {
        // First provision COA
        $this->actingAs($this->user)->get(route('accounting.ledgers.index'));
        $group = AccountGroup::where('company_id', $this->company->id)->first();

        $response = $this->actingAs($this->user)->post(route('accounting.ledgers.store'), [
            'account_group_id' => $group->id,
            'name' => 'Office Expenses',
            'code' => 'EXP-01',
            'opening_balance' => '500.00',
            'opening_balance_type' => 'debit',
            'opening_balance_reference' => 'OB-REF-1',
            'gstin' => '24ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
        ]);

        $response->assertRedirect(route('accounting.ledgers.index'))
            ->assertSessionHas('success', "Ledger 'Office Expenses' created successfully.");

        $this->assertDatabaseHas('ledgers', [
            'company_id' => $this->company->id,
            'account_group_id' => $group->id,
            'name' => 'Office Expenses',
            'code' => 'EXP-01',
            'opening_balance' => 500.00,
            'opening_balance_type' => 'debit',
            'is_builtin' => false,
            'is_active' => true,
        ]);
    }

    public function test_store_creates_ledger_and_returns_json_for_ajax_request(): void
    {
        $this->actingAs($this->user)->get(route('accounting.ledgers.index'));
        $group = AccountGroup::where('company_id', $this->company->id)->first();

        $response = $this->actingAs($this->user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('accounting.ledgers.store'), [
                'account_group_id' => $group->id,
                'name' => 'Repair and Maintenance',
                'opening_balance_type' => 'credit',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('ledger.name', 'Repair and Maintenance')
            ->assertJsonPath('ledger.group_name', $group->name);
    }

    public function test_store_validation_errors(): void
    {
        $response = $this->actingAs($this->user)->post(route('accounting.ledgers.store'), [
            'name' => '',
            'opening_balance_type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors(['account_group_id', 'name', 'opening_balance_type']);
    }
}
