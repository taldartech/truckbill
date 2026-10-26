<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DropdownApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    private function actingAdmin(): User
    {
        $company = Company::create([
            'name' => 'Dropdown Co',
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    public function test_states_endpoint_returns_states_for_country_ordered_by_name(): void
    {
        $admin = $this->actingAdmin();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        State::create(['name' => 'Assam', 'country_id' => $country->id]);
        State::create(['name' => 'Other State', 'country_id' => Country::create(['name' => 'Nepal', 'code' => 'NP'])->id]);

        $response = $this->actingAs($admin)->getJson('/api/states/'.$country->id);

        $response->assertOk();
        $names = collect($response->json())->pluck('name')->all();
        $this->assertSame(['Assam', 'Gujarat'], $names);
    }

    public function test_cities_endpoint_returns_cities_for_state(): void
    {
        $admin = $this->actingAdmin();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $otherState = State::create(['name' => 'Maharashtra', 'country_id' => $country->id]);

        City::create(['name' => 'Surat', 'state_id' => $state->id, 'country_id' => $country->id]);
        City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        City::create(['name' => 'Mumbai', 'state_id' => $otherState->id, 'country_id' => $country->id]);

        $response = $this->actingAs($admin)->getJson('/api/cities/'.$state->id);

        $response->assertOk();
        $names = collect($response->json())->pluck('name')->all();
        $this->assertSame(['Ahmedabad', 'Surat'], $names);
    }

    public function test_dropdown_endpoints_require_authentication(): void
    {
        $this->getJson('/api/states/1')->assertRedirect(route('login'));
        $this->getJson('/api/cities/1')->assertRedirect(route('login'));
    }
}
