<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterPanelSummaryDashboardTest extends TestCase
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
    }

    protected function makeMasterAdmin(): User
    {
        return User::factory()->create([
            'user_role_id' => 1,
            'company_id' => null,
        ]);
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
            'name' => 'Admin Co',
            'user_id' => null,
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
            'name' => 'Company '.$owner->id.'-'.uniqid(),
            'user_id' => $owner->id,
            'is_blocked' => false,
        ], $overrides));
    }

    public function test_master_admin_can_access_summary_dashboard(): void
    {
        $master = $this->makeMasterAdmin();

        $response = $this->actingAs($master)->get(route('master.summary-dashboard'));

        $response->assertOk()->assertViewIs('master.summary_dashboard');
    }

    public function test_super_admin_cannot_access_summary_dashboard_without_company_context(): void
    {
        $super = $this->makeSuperAdmin();

        $response = $this->actingAs($super)->get(route('master.summary-dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_cannot_access_summary_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('master.summary-dashboard'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_summary_dashboard(): void
    {
        $response = $this->get(route('master.summary-dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_default_date_range_is_current_month(): void
    {
        $master = $this->makeMasterAdmin();

        $response = $this->actingAs($master)->get(route('master.summary-dashboard'));

        $response->assertOk();
        $response->assertViewHas('filters', function (array $filters) {
            return $filters['from_date'] === Carbon::now()->startOfMonth()->toDateString()
                && $filters['to_date'] === Carbon::now()->endOfMonth()->toDateString();
        });
    }

    public function test_super_admin_filter_scopes_to_owned_companies(): void
    {
        $master = $this->makeMasterAdmin();
        $superA = $this->makeSuperAdmin();
        $superB = $this->makeSuperAdmin();

        $companyA = $this->createCompanyFor($superA, ['name' => 'Alpha Logistics']);
        $this->createCompanyFor($superB, ['name' => 'Beta Transport']);

        $this->seedOperationalData($companyA);

        $response = $this->actingAs($master)->get(route('master.summary-dashboard', [
            'super_admin_user_id' => $superA->id,
            'from_date' => Carbon::now()->startOfMonth()->toDateString(),
            'to_date' => Carbon::now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewHas('companyBreakdown', function ($rows) {
            return collect($rows)->count() === 1
                && collect($rows)->first()['name'] === 'Alpha Logistics';
        });
        $response->assertViewHas('kpis', fn (array $kpis) => $kpis['total_lrs'] === 1);
    }

    public function test_company_filter_intersects_with_super_admin_selection(): void
    {
        $master = $this->makeMasterAdmin();
        $super = $this->makeSuperAdmin();

        $companyOne = $this->createCompanyFor($super, ['name' => 'One Co']);
        $companyTwo = $this->createCompanyFor($super, ['name' => 'Two Co']);

        $this->seedOperationalData($companyOne);
        $this->seedOperationalData($companyTwo);

        $response = $this->actingAs($master)->get(route('master.summary-dashboard', [
            'super_admin_user_id' => $super->id,
            'company_id' => $companyOne->id,
            'from_date' => Carbon::now()->startOfMonth()->toDateString(),
            'to_date' => Carbon::now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewHas('companyBreakdown', function ($rows) {
            return collect($rows)->count() === 1
                && collect($rows)->first()['name'] === 'One Co';
        });
    }

    private function seedOperationalData(Company $company): LorryReceipt
    {
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer',
            'address' => 'Address',
        ]);
        $consignor = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignor',
            'address' => 'Address',
        ]);
        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignee',
            'address' => 'Address',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-'.uniqid(),
            'gr_date' => Carbon::now()->toDateString(),
            'delivery_status' => 'Pending',
            'weight' => 10,
            'amount' => 1000,
            'invoice_generated' => 0,
            'source_city' => 'City A',
            'source_state' => 'State A',
            'destination_city' => 'City B',
            'destination_state' => 'State B',
            'lane' => 'Lane 1',
            'good_type' => 'General',
            'fr_rate' => 100,
        ]);

        FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 200,
            'advance_amount' => 0,
            'balance_amount' => 200,
            'tds_amount' => 0,
            'is_delivered' => false,
            'is_cancelled' => false,
            'voucher_number' => 'VB-'.uniqid(),
        ]);

        return $lr;
    }
}
