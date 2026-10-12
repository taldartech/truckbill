<?php

namespace Tests\Feature\Transport;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Driver;
use App\Models\State;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TruckTest extends TestCase
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
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Main Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
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

    protected function seedGeo(): array
    {
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        return [$country, $state, $city];
    }

    protected function makeTransporter(User $admin, City $city): Transporter
    {
        $master = \App\Models\TransporterMaster::factory()
            ->withLocation($city->id, $city->state_id, $city->country_id)
            ->create(['name' => 'Fast Logistics', 'pan_number' => 'ABCDE1234F']);

        return Transporter::factory()
            ->forCompany($admin->company_id)
            ->create([
                'transporter_master_id' => $master->id,
                'mobile_number' => '9999999999',
                'declaration_given' => false,
                'tds_percentage' => 20,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);
    }

    public function test_truck_index_view_loads(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/trucks');

        $response->assertOk()->assertViewIs('trucks.index');
    }

    public function test_store_creates_truck_with_unique_number_per_company(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $response = $this->actingAs($admin)->post('/trucks', [
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
            'registration_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('trucks.index'));
        $this->assertDatabaseHas('trucks', [
            'truck_number' => 'GJ01AB1234',
            'transporter_id' => $transporter->id,
        ]);
    }

    public function test_ajax_store_returns_json_payload_for_quick_add(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('/trucks', [
                'transporter_id' => $transporter->id,
                'truck_number' => 'GJ01AX1234',
                'truck_type' => 'Flatbed',
                'registration_date' => now()->toDateString(),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('truck.truck_number', 'GJ01AX1234')
            ->assertJsonPath('truck.transporter_id', $transporter->id);

        $this->assertDatabaseHas('trucks', [
            'truck_number' => 'GJ01AX1234',
            'transporter_id' => $transporter->id,
        ]);
    }

    public function test_ajax_store_rejects_duplicate_truck_number_with_json_errors(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01DP1234',
            'truck_type' => 'Flatbed',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('/trucks', [
                'transporter_id' => $transporter->id,
                'truck_number' => 'GJ01DP1234',
                'truck_type' => 'Flatbed',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.truck_number.0', 'The truck number must be unique within the company.');
    }

    public function test_store_rejects_duplicate_truck_number_in_company(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        $response = $this->actingAs($admin)->from('/trucks')->post('/trucks', [
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        $response->assertSessionHasErrors(['truck_number']);
    }

    public function test_store_allows_creating_truck_without_owner(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/trucks', [
            'transporter_id' => null,
            'truck_number' => 'GJ02CD5678',
            'truck_type' => 'Container',
            'registration_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('trucks.index'));
        $this->assertDatabaseHas('trucks', [
            'truck_number' => 'GJ02CD5678',
            'transporter_id' => null,
            'company_id' => $admin->company_id,
        ]);
    }

    public function test_update_changes_truck_and_enforces_uniqueness(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        $response = $this->actingAs($admin)->put("/trucks/{$truck->id}", [
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01XY9999',
            'truck_type' => 'Tanker',
        ]);

        $response->assertRedirect(route('trucks.index'));
        $this->assertDatabaseHas('trucks', [
            'id' => $truck->id,
            'truck_number' => 'GJ01XY9999',
            'truck_type' => 'Tanker',
        ]);
    }

    public function test_check_truck_number_api_flags_existing_number(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        $response = $this->actingAs($admin)->get('/check-truck-number?truck_number=GJ01AB1234');

        $response->assertOk();
        $this->assertTrue($response->json('exists'));
    }
}

