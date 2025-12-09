<?php

namespace Tests\Feature\Transport;

use App\Models\City;
use App\Models\Company;
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
        return Transporter::create([
            'company_id' => $admin->company_id,
            'name' => 'Fast Logistics',
            'pan_number' => 'ABCDE1234F',
            'mobile_number' => '9999999999',
            'city_id' => $city->id,
            'state_id' => $city->state_id,
            'country_id' => $city->country_id,
            'is_owner' => 0,
            'pan_active' => true,
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

