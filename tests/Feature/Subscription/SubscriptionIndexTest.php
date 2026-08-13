<?php

namespace Tests\Feature\Subscription;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    public function test_super_admin_can_view_themed_subscription_index(): void
    {
        $super = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Acme Logistics',
            'gst_slab' => config('payments.gst_slabs')[0],
            'user_id' => $super->id,
            'is_blocked' => false,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => Carbon::today()->subMonth(),
            'ends_on' => Carbon::today()->addMonth(),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => 50,
        ]);

        $response = $this->actingAs($super)->get(route('subscription.index'));

        $response->assertOk()
            ->assertViewIs('subscription.index')
            ->assertSee('app-index-page', false)
            ->assertSee('app-index-list-card', false)
            ->assertSee('Subscriptions')
            ->assertSee('Payment history')
            ->assertSee('Active plans')
            ->assertSee('Acme Logistics')
            ->assertSee('Manage');
    }

    public function test_admin_cannot_view_subscription_index(): void
    {
        $admin = User::factory()->create(['user_role_id' => 3]);

        $this->actingAs($admin)
            ->get(route('subscription.index'))
            ->assertForbidden();
    }
}
