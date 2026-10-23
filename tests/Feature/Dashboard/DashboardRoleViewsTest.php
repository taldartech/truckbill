<?php

namespace Tests\Feature\Dashboard;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\TransporterMaster;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardRoleViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($date, $format) {
                if (! $date) {
                    return null;
                }
                $timestamp = strtotime($date);

                return date('M Y', $timestamp);
            });
        }
    }

    private function seedRoles(): void
    {
        $roles = [
            'master_admin',
            'super_admin',
            'admin',
            'moderator',
            'accountant',
            'transporter',
            'customer',
        ];

        foreach ($roles as $role) {
            UserRole::firstOrCreate(['name' => $role]);
        }
    }

    private function setupCompanyAndUser(string $roleName): array
    {
        $role = UserRole::where('name', $roleName)->first();
        $user = User::factory()->create(['user_role_id' => $role->id]);

        $company = Company::create([
            'name' => 'Omni Express Logistics',
            'short_name' => 'OEL',
            'user_id' => $user->id,
            'is_blocked' => false,
        ]);

        $user->company_id = $company->id;
        $user->save();

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 5,
            'users_per_branch' => 5,
            'lr_credits_remaining' => 1000,
        ]);

        return [$user, $company];
    }

    public function test_admin_dashboard_renders_with_periods(): void
    {
        [$admin, $company] = $this->setupCompanyAndUser('admin');

        // Seed an invoice to populate stats
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Global Retailers',
            'address' => 'Delhi',
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DASH-01',
            'invoice_type' => 'regular',
            'generated_date' => now()->toDateString(),
            'total_amount' => 12000,
            'net_amount' => 12000,
        ]);

        // Default 'month'
        $responseMonth = $this->actingAs($admin)->get('/dashboard');
        $responseMonth->assertStatus(200);
        $responseMonth->assertViewIs('dashboard');
        $responseMonth->assertViewHas(['monthlyStats', 'dashboardPeriod', 'chartPayload']);

        // '7d'
        $response7d = $this->actingAs($admin)->get('/dashboard?period=7d');
        $response7d->assertStatus(200);
        $response7d->assertViewHas('dashboardPeriod', '7d');

        // '30d'
        $response30d = $this->actingAs($admin)->get('/dashboard?period=30d');
        $response30d->assertStatus(200);
        $response30d->assertViewHas('dashboardPeriod', '30d');
    }

    public function test_moderator_dashboard_renders_successfully(): void
    {
        [$moderator] = $this->setupCompanyAndUser('moderator');

        $response = $this->actingAs($moderator)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('operationalStats');
    }

    public function test_accountant_dashboard_renders_successfully(): void
    {
        [$accountant] = $this->setupCompanyAndUser('accountant');

        $response = $this->actingAs($accountant)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('financialStats');
    }

    public function test_customer_dashboard_renders_successfully(): void
    {
        [$customerUser, $company] = $this->setupCompanyAndUser('customer');

        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Client Org',
            'address' => 'Pune',
        ]);
        $customerUser->consignees()->attach($consignee->id);

        $response = $this->actingAs($customerUser)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    public function test_transporter_dashboard_renders_successfully(): void
    {
        [$transporterUser, $company] = $this->setupCompanyAndUser('transporter');

        // First test without master
        $responseWithout = $this->actingAs($transporterUser)->get('/dashboard');
        $responseWithout->assertStatus(200);
        $responseWithout->assertViewIs('dashboard');

        // Now test with assigned master
        $master = TransporterMaster::factory()->create(['name' => 'Speedline Transport']);
        $transporterUser->transporter_master_id = $master->id;
        $transporterUser->save();

        $responseWith = $this->actingAs($transporterUser)->get('/dashboard');
        $responseWith->assertStatus(200);
        $responseWith->assertViewIs('dashboard');
        $responseWith->assertViewHas('transporterStats');
    }

    public function test_master_admin_dashboard_renders_successfully(): void
    {
        [$masterAdmin] = $this->setupCompanyAndUser('master_admin');

        $response = $this->actingAs($masterAdmin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('systemStats');
    }

    public function test_super_admin_dashboard_renders_successfully(): void
    {
        [$superAdmin, $company] = $this->setupCompanyAndUser('super_admin');

        $response = $this->actingAs($superAdmin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('adminStats');

        // Super admin with company context in session
        $responseContext = $this->actingAs($superAdmin)->withSession([
            'super_admin_company_id' => $company->id,
        ])->get('/dashboard');
        $responseContext->assertStatus(200);
        $responseContext->assertViewHas('monthlyStats');
    }
}
