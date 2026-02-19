<?php

namespace Tests\Feature\Transport;

use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransporterOwnerTest extends TestCase
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
        UserRole::create(['id' => 4, 'name' => 'moderator']);
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
            'user_role_id' => 3, // admin
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

    public function test_transporter_index_view_loads(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/transporters');

        $response->assertOk()->assertViewIs('transporters.index');
    }

    public function test_transporter_store_creates_agency(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $payload = [
            'company_id' => $admin->company_id,
            'name' => 'Fast Logistics',
            'email' => 'fast@example.com',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'advance_amount' => 1000,
            'pan_active' => true,
            'address' => '123 Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '9876543210',
            'short_name' => 'FAST',
            'contact_person_name' => 'John Doe',
        ];

        $response = $this->actingAs($admin)->post('/transporters', $payload);

        $response->assertRedirect(route('transporters.index'));
        $this->assertDatabaseHas('transporter_masters', [
            'name' => 'Fast Logistics',
            'pan_number' => 'ABCDE1234F',
        ]);
        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'is_owner' => 0,
        ]);
    }

    public function test_transporter_store_rejects_duplicate_pan_within_company(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $master = \App\Models\TransporterMaster::factory()->create([
            'name' => 'Existing',
            'pan_number' => 'ABCDE1234F',
        ]);
        Transporter::factory()->forCompany($admin->company_id)->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '9999999999',
        ]);

        $response = $this->actingAs($admin)->from('/transporters/create')->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'New One',
            'pan_number' => 'ABCDE1234F',
            'mobile_number' => '8888888888',
            'pan_active' => true,
        ]);

        $response->assertRedirect('/transporters/create');
        $response->assertSessionHasErrors(['pan_number']);
    }

    public function test_truck_owner_index_view_loads(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/truck_owners');

        $response->assertOk()->assertViewIs('truck_owner.index');
    }

    public function test_truck_owner_store_creates_owner_and_sets_tds(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($admin)->post('/truck_owners', [
            'company_id' => $admin->company_id,
            'name' => 'Owner One',
            'pan_number' => 'ABCPD1234Q', // fourth char P -> 1% when declaration not given
            'pan_active' => true,
            'declaration_given' => false,
            'total_trucks' => 5,
            'advance_amount' => 2000,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7777777777',
        ]);

        $response->assertRedirect(route('truck_owners.index'));
        $this->assertDatabaseHas('transporter_masters', [
            'name' => 'Owner One',
            'pan_number' => 'ABCPD1234Q',
        ]);
        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'is_owner' => 1,
            'tds_percentage' => 1.00,
        ]);
    }

    public function test_truck_owner_update_does_not_change_tds_when_payment_complete(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $master = TransporterMaster::factory()->create([
            'name' => 'Owner Paid',
            'pan_number' => 'ABCPD1234Q',
            'pan_active' => true,
        ]);

        $truckOwner = Transporter::factory()->forCompany($admin->company_id)->truckOwner()->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '7777777777',
            'declaration_given' => false,
            'tds_percentage' => 1,
            'total_trucks' => 2,
            'advance_amount' => 0,
        ]);

        $truck = Truck::create([
            'transporter_id' => $truckOwner->id,
            'truck_number' => 'GJ01XX0001',
            'truck_type' => 'Flatbed',
        ]);

        $customer = Consignee::create(['company_id' => $admin->company_id, 'name' => 'C', 'address' => 'A']);
        $consignor = Consignee::create(['company_id' => $admin->company_id, 'name' => 'S', 'address' => 'A']);
        $consignee = Consignee::create(['company_id' => $admin->company_id, 'name' => 'E', 'address' => 'A']);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truck->id,
            'transporter_id' => $truckOwner->id,
            'reference_by' => $truckOwner->id,
            'lr_number' => 'LR-TO-' . uniqid(),
            'gr_number' => 'GR-TO-' . uniqid(),
            'invoice_number' => 'INV-TO-' . uniqid(),
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'L1',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'delivery_status' => 'Pending',
        ]);

        $fb = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $truckOwner->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 1000,
            'balance_amount' => 0,
            'tds_amount' => 5,
            'company_id' => $admin->company_id,
            'transporter_tds_percentage' => 1,
            'voucher_number' => 'VB-TO-001',
        ]);

        $response = $this->actingAs($admin)->put("/truck_owners/{$truckOwner->id}", [
            'company_id' => $admin->company_id,
            'contact_person_name' => 'Updated Name',
            'mobile_number' => '7777777777',
            'advance_amount' => 0,
            'declaration_given' => true,
            'total_trucks' => 2,
        ]);

        $response->assertRedirect(route('truck_owners.index'));

        $fb->refresh();
        $this->assertEquals(5.0, (float) $fb->tds_amount);
    }
}

