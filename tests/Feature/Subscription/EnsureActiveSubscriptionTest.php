<?php

namespace Tests\Feature\Subscription;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EnsureActiveSubscriptionTest extends TestCase
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

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    private function mockGraceSettings(int $gracePeriodDays = 0): void
    {
        Cache::put('subscription.settings', [
            'grace_period_days' => $gracePeriodDays,
            'gst_rate_percent' => 18.0,
        ], now()->addDay());
    }

    private function createExpiredCompany(User $admin): Company
    {
        $company = Company::create([
            'name' => 'Expired Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $admin->id,
            'is_blocked' => false,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => Carbon::today()->subYear(),
            'ends_on' => Carbon::today()->subDay(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $admin->update(['company_id' => $company->id]);

        return $company;
    }

    public function test_locked_subscription_redirects_to_dashboard(): void
    {
        $this->mockGraceSettings(0);

        $admin = User::factory()->create(['user_role_id' => 3]);
        $this->createExpiredCompany($admin);

        $this->actingAs($admin)
            ->get('/trucks')
            ->assertRedirect(route('dashboard'));
    }

    public function test_grace_period_allows_viewing_modules(): void
    {
        $this->mockGraceSettings(7);

        $admin = User::factory()->create(['user_role_id' => 3]);
        $this->createExpiredCompany($admin);

        $this->actingAs($admin)
            ->get('/trucks')
            ->assertOk();
    }

    public function test_grace_period_redirects_write_attempts_to_dashboard(): void
    {
        $this->mockGraceSettings(7);

        $admin = User::factory()->create(['user_role_id' => 3]);
        $this->createExpiredCompany($admin);

        $this->actingAs($admin)
            ->post('/trucks', [])
            ->assertRedirect(route('dashboard'));
    }

    public function test_grace_period_redirects_export_routes_to_dashboard(): void
    {
        $this->mockGraceSettings(7);

        $admin = User::factory()->create(['user_role_id' => 3]);
        $this->createExpiredCompany($admin);

        $this->actingAs($admin)
            ->get('/reports/tds-export')
            ->assertRedirect(route('dashboard'));
    }

    public function test_pending_upcoming_subscription_is_applied_on_access_check(): void
    {
        $this->mockGraceSettings(0);

        $admin = User::factory()->create(['user_role_id' => 3]);
        $company = $this->createExpiredCompany($admin);

        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'renewal',
            'status' => 'completed',
            'subtotal' => 1000,
            'promo_discount' => 0,
            'gst_amount' => 180,
            'total_amount' => 1180,
        ]);

        $upcomingData = [
            'company_id' => $company->id,
            'payment_id' => $payment->id,
            'status' => 'pending',
            'included_branches' => 2,
            'users_per_branch' => 3,
            'included_lr_credits' => 3600,
            'included_trucks' => 0,
            'addon_branches' => 0,
            'addon_users' => 0,
            'addon_trucks' => 0,
            'addon_lr_credits' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('upcoming_subscriptions', 'starts_on')) {
            $upcomingData['starts_on'] = Carbon::today()->toDateString();
            $upcomingData['ends_on'] = Carbon::today()->addYear()->toDateString();
        }

        DB::table('upcoming_subscriptions')->insert($upcomingData);

        $this->actingAs($admin)
            ->get('/trucks')
            ->assertOk();

        $company->refresh();
        $this->assertTrue($company->subscription->isActive());
    }

    public function test_expired_subscription_inertia_request_redirects_to_dashboard_when_locked(): void
    {
        $this->mockGraceSettings(0);

        $admin = User::factory()->create(['user_role_id' => 3]);
        $this->createExpiredCompany($admin);

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get('/trucks')
            ->assertRedirect(route('dashboard'));
    }
}
