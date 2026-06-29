<?php

namespace Tests\Feature\Subscription;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_expired_subscription_redirects_with_error_instead_of_403_page(): void
    {
        $admin = User::factory()->create([
            'user_role_id' => 3,
        ]);

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

        $this->actingAs($admin)
            ->get('/trucks')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Your subscription has expired. Please contact your super admin to renew.');
    }

    public function test_expired_subscription_inertia_request_redirects_with_error(): void
    {
        $admin = User::factory()->create([
            'user_role_id' => 3,
        ]);

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

        $this->actingAs($admin)
            ->withHeaders([
                'X-Inertia' => 'true',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->get('/trucks')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }
}
