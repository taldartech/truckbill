<?php

namespace Tests\Feature\Master;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationQuickAddTest extends TestCase
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
        $company = Company::create([
            'name' => 'Loc Co',
            'user_id' => null,
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

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    public function test_ajax_store_creates_state_for_quick_add(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('master.states.store'), [
                'name' => 'Gujarat',
                'code' => 'GJ',
                'country_id' => $country->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state.name', 'Gujarat')
            ->assertJsonPath('state.country_id', $country->id);

        $this->assertDatabaseHas('states', [
            'name' => 'Gujarat',
            'country_id' => $country->id,
        ]);
    }

    public function test_ajax_store_creates_city_for_quick_add(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create([
            'name' => 'Gujarat',
            'code' => 'GJ',
            'country_id' => $country->id,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('master.cities.store'), [
                'name' => 'Ahmedabad',
                'state_id' => $state->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('city.name', 'Ahmedabad')
            ->assertJsonPath('city.state_id', $state->id);

        $this->assertDatabaseHas('cities', [
            'name' => 'Ahmedabad',
            'state_id' => $state->id,
            'country_id' => $country->id,
        ]);
    }
}
