<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Pump;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PumpQuickAddTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
    }

    private function makeAdmin(): User
    {
        $owner = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Pump Co',
            'user_id' => $owner->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $owner->update(['company_id' => $company->id]);

        return $owner->fresh();
    }

    public function test_ajax_store_returns_json_for_quick_add(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('master.pump.store'), [
                'pump_name' => 'Highway Pump',
                'contact_name' => 'Ravi',
                'contact_number' => '9876543210',
                'remark' => 'Near toll',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pump.pump_name', 'Highway Pump')
            ->assertJsonPath('pump.contact_name', 'Ravi');

        $this->assertDatabaseHas('pumps', [
            'pump_name' => 'Highway Pump',
            'contact_number' => '9876543210',
            'user_id' => $admin->id,
        ]);
    }

    public function test_ajax_store_returns_validation_errors_as_json(): void
    {
        $admin = $this->makeAdmin();

        Pump::create([
            'user_id' => $admin->id,
            'pump_name' => 'Existing Pump',
            'contact_name' => 'Old',
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('master.pump.store'), [
                'pump_name' => 'Another Pump',
                'contact_name' => 'New',
                'contact_number' => '9876543210',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contact_number']);
    }
}
