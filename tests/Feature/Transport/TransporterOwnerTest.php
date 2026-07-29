<?php

namespace Tests\Feature\Transport;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
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

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
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

    public function test_transporter_store_without_email_keeps_null_and_skips_portal_user(): void
    {
        UserRole::create(['id' => 6, 'name' => 'transporter']);
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'No Email Logistics',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'advance_amount' => 0,
            'pan_active' => true,
            'address' => '123 Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '9876543210',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ]);

        $response->assertRedirect(route('transporters.index'));
        $this->assertDatabaseHas('transporter_masters', [
            'name' => 'No Email Logistics',
            'pan_number' => 'ABCDE1234F',
            'email' => null,
        ]);

        $masterId = TransporterMaster::query()->where('pan_number', 'ABCDE1234F')->value('id');
        $this->assertNull(User::query()->where('transporter_master_id', $masterId)->first());
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
            'declaration_given' => false,
            'tds_percentage' => 20,
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

        $master = TransporterMaster::factory()->create([
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
            'email' => 'newone@example.com',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'mobile_number' => '8888888888',
            'pan_active' => true,
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'declaration_given' => false,
            'tds_percentage' => 20,
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
            'tds_percentage' => 1,
        ]);

        $response->assertRedirect(route('truck_owners.index'));
        $this->assertDatabaseHas('transporter_masters', [
            'name' => 'Owner One',
            'pan_number' => 'ABCPD1234Q',
            'email' => null,
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
            'lr_number' => 'LR-TO-'.uniqid(),
            'gr_number' => 'GR-TO-'.uniqid(),
            'invoice_number' => 'INV-TO-'.uniqid(),
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
            'tds_percentage' => 0,
        ]);

        $response->assertRedirect(route('truck_owners.index'));

        $fb->refresh();
        $this->assertEquals(5.0, (float) $fb->tds_amount);
    }

    public function test_truck_owner_store_requires_remark_when_tds_overridden(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($admin)->post('/truck_owners', [
            'company_id' => $admin->company_id,
            'name' => 'Owner Two',
            'pan_number' => 'ABCPD1234Q',
            'pan_active' => true,
            'declaration_given' => false,
            'total_trucks' => 1,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7666666666',
            'tds_percentage' => 5,
        ]);

        $response->assertSessionHasErrors(['tds_adjustment_remark']);
    }

    public function test_transporter_store_accepts_declaration_and_zero_tds(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'Declared Agency',
            'email' => 'declared@example.com',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'advance_amount' => 0,
            'pan_active' => true,
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7555555555',
            'declaration_given' => true,
            'tds_percentage' => 0,
        ]);

        $response->assertRedirect(route('transporters.index'));
        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'is_owner' => 0,
            'declaration_given' => 1,
            'tds_percentage' => 0,
        ]);
    }

    public function test_transporter_dummy_pan_defaults_tds_zero_but_allows_override_and_remark_persists(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($admin->company_id);
        $remark = 'Dummy transporter remark';
        $updatedRemark = 'Dummy transporter remark updated';

        $createResponse = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'Dummy Agency',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => $dummyPan,
            'advance_amount' => 0,
            'pan_active' => true,
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7555555000',
            'declaration_given' => true,
            // User-modified TDS should be persisted (default UI baseline is 0%).
            'tds_percentage' => 5,
            'tds_adjustment_remark' => $remark,
        ]);

        $createResponse->assertRedirect(route('transporters.index'));

        $masterId = TransporterMaster::query()->where('pan_number', $dummyPan)->value('id');
        $transporter = Transporter::query()
            ->where('transporter_master_id', $masterId)
            ->where('company_id', $admin->company_id)
            ->where('is_owner', 0)
            ->firstOrFail();

        $this->assertEquals(5.0, (float) $transporter->tds_percentage);
        $this->assertSame($remark, (string) $transporter->tds_adjustment_remark);

        $updateResponse = $this->actingAs($admin)->put("/transporters/{$transporter->id}", [
            'company_id' => $admin->company_id,
            'mobile_number' => '7555555000',
            'advance_amount' => 0,
            'declaration_given' => true,
            'total_trucks' => $transporter->total_trucks,
            'tds_percentage' => 2.5,
            'tds_adjustment_remark' => $updatedRemark,
        ]);

        $updateResponse->assertRedirect(route('transporters.index'));

        $transporter->refresh();
        $this->assertEquals(2.5, (float) $transporter->tds_percentage);
        $this->assertSame($updatedRemark, (string) $transporter->tds_adjustment_remark);
    }

    public function test_truck_owner_dummy_pan_defaults_tds_zero_but_allows_override_and_remark_persists(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($admin->company_id);
        $remark = 'Dummy truck owner remark';
        $updatedRemark = 'Dummy truck owner remark updated';

        $createResponse = $this->actingAs($admin)->post('/truck_owners', [
            'company_id' => $admin->company_id,
            'name' => 'Dummy Owner',
            'pan_number' => $dummyPan,
            'pan_active' => true,
            'declaration_given' => true,
            'total_trucks' => 2,
            'advance_amount' => 0,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7555555010',
            'tds_percentage' => 5,
            'tds_adjustment_remark' => $remark,
        ]);

        $createResponse->assertRedirect(route('truck_owners.index'));

        $masterId = TransporterMaster::query()->where('pan_number', $dummyPan)->value('id');
        $truckOwner = Transporter::query()
            ->where('transporter_master_id', $masterId)
            ->where('company_id', $admin->company_id)
            ->where('is_owner', 1)
            ->firstOrFail();

        $this->assertEquals(5.0, (float) $truckOwner->tds_percentage);
        $this->assertSame($remark, (string) $truckOwner->tds_adjustment_remark);

        $updateResponse = $this->actingAs($admin)->put("/truck_owners/{$truckOwner->id}", [
            'company_id' => $admin->company_id,
            'mobile_number' => '7555555010',
            'advance_amount' => 0,
            'declaration_given' => true,
            'total_trucks' => $truckOwner->total_trucks,
            'tds_percentage' => 2.5,
            'tds_adjustment_remark' => $updatedRemark,
        ]);

        $updateResponse->assertRedirect(route('truck_owners.index'));

        $truckOwner->refresh();
        $this->assertEquals(2.5, (float) $truckOwner->tds_percentage);
        $this->assertSame($updatedRemark, (string) $truckOwner->tds_adjustment_remark);
    }

    public function test_transporter_dummy_pan_allows_multiple_records_within_company(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($admin->company_id);

        $remark1 = 'Dummy transporter remark 1';
        $remark2 = 'Dummy transporter remark 2';

        $create1 = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'Dummy Agency 1',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => $dummyPan,
            'advance_amount' => 0,
            'pan_active' => true,
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7555555001',
            'declaration_given' => true,
            'tds_percentage' => 5,
            'tds_adjustment_remark' => $remark1,
        ]);

        $create1->assertRedirect(route('transporters.index'));

        $firstMasterId = TransporterMaster::query()->where('pan_number', $dummyPan)->value('id');

        $create2 = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'Dummy Agency 2',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => $dummyPan,
            'advance_amount' => 0,
            'pan_active' => true,
            'address' => 'Addr',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7555555002',
            'declaration_given' => true,
            'tds_percentage' => 5,
            'tds_adjustment_remark' => $remark2,
        ]);

        $create2->assertRedirect(route('transporters.index'));

        $this->assertSame(2, TransporterMaster::query()->where('pan_number', $dummyPan)->count());

        $trans1 = Transporter::query()->where('mobile_number', '7555555001')->firstOrFail();
        $trans2 = Transporter::query()->where('mobile_number', '7555555002')->firstOrFail();
        $this->assertNotSame($trans1->transporter_master_id, $trans2->transporter_master_id);

        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'is_owner' => 0,
            'mobile_number' => '7555555001',
            'tds_adjustment_remark' => $remark1,
        ]);

        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'is_owner' => 0,
            'mobile_number' => '7555555002',
            'tds_adjustment_remark' => $remark2,
        ]);
    }

    public function test_truck_owner_dummy_pan_allows_multiple_records_within_company(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($admin->company_id);

        $remark1 = 'Dummy truck owner remark 1';
        $remark2 = 'Dummy truck owner remark 2';

        $create1 = $this->actingAs($admin)->post('/truck_owners', [
            'company_id' => $admin->company_id,
            'name' => 'Dummy Owner 1',
            'pan_number' => $dummyPan,
            'pan_active' => true,
            'declaration_given' => true,
            'total_trucks' => 2,
            'advance_amount' => 0,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7555555011',
            'tds_percentage' => 5,
            'tds_adjustment_remark' => $remark1,
        ]);

        $create1->assertRedirect(route('truck_owners.index'));

        $firstMasterId = TransporterMaster::query()->where('pan_number', $dummyPan)->value('id');

        $create2 = $this->actingAs($admin)->post('/truck_owners', [
            'company_id' => $admin->company_id,
            'name' => 'Dummy Owner 2',
            'pan_number' => $dummyPan,
            'pan_active' => true,
            'declaration_given' => true,
            'total_trucks' => 3,
            'advance_amount' => 0,
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '7555555012',
            'tds_percentage' => 5,
            'tds_adjustment_remark' => $remark2,
        ]);

        $create2->assertRedirect(route('truck_owners.index'));

        $this->assertSame(2, TransporterMaster::query()->where('pan_number', $dummyPan)->count());

        $trans1 = Transporter::query()->where('mobile_number', '7555555011')->firstOrFail();
        $trans2 = Transporter::query()->where('mobile_number', '7555555012')->firstOrFail();
        $this->assertNotSame($trans1->transporter_master_id, $trans2->transporter_master_id);

        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'is_owner' => 1,
            'mobile_number' => '7555555011',
            'tds_adjustment_remark' => $remark1,
        ]);

        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'is_owner' => 1,
            'mobile_number' => '7555555012',
            'tds_adjustment_remark' => $remark2,
        ]);
    }

    public function test_transporter_edit_shows_tds_refresh_when_mismatch(): void
    {
        $admin = $this->makeAdmin();

        $master = TransporterMaster::factory()->create([
            'pan_number' => 'ABCPD1234Q',
            'pan_active' => false,
        ]);

        $transporter = Transporter::factory()->forCompany($admin->company_id)->create([
            'transporter_master_id' => $master->id,
            'declaration_given' => false,
            'tds_percentage' => 1,
            'mobile_number' => '9876543210',
        ]);

        $response = $this->actingAs($admin)->get("/transporters/{$transporter->id}/edit");

        $response->assertOk();
        $response->assertViewHas('hasTdsMismatch', true);
        $response->assertSee('Refresh TDS %', false);
    }

    public function test_transporter_refresh_tds_updates_stored_percentage(): void
    {
        $admin = $this->makeAdmin();

        $master = TransporterMaster::factory()->create([
            'pan_number' => 'ABCPD1234Q',
            'pan_active' => false,
        ]);

        $transporter = Transporter::factory()->forCompany($admin->company_id)->create([
            'transporter_master_id' => $master->id,
            'declaration_given' => false,
            'tds_percentage' => 1,
            'tds_adjustment_remark' => 'Manual override',
            'mobile_number' => '9876543210',
        ]);

        $response = $this->actingAs($admin)->post("/transporters/{$transporter->id}/refresh-tds");

        $response->assertRedirect(route('transporters.edit', $transporter));
        $response->assertSessionHas('success');

        $transporter->refresh();
        $this->assertEquals(20.0, (float) $transporter->tds_percentage);
        $this->assertNull($transporter->tds_adjustment_remark);
    }

    public function test_truck_owner_refresh_tds_updates_stored_percentage(): void
    {
        $admin = $this->makeAdmin();

        $master = TransporterMaster::factory()->create([
            'pan_number' => 'ABCPD1234Q',
            'pan_active' => false,
        ]);

        $truckOwner = Transporter::factory()->forCompany($admin->company_id)->truckOwner()->create([
            'transporter_master_id' => $master->id,
            'declaration_given' => false,
            'tds_percentage' => 1,
            'mobile_number' => '7777777777',
            'total_trucks' => 1,
        ]);

        $response = $this->actingAs($admin)->post("/truck_owners/{$truckOwner->id}/refresh-tds");

        $response->assertRedirect(route('truck_owners.edit', $truckOwner));
        $response->assertSessionHas('success');

        $truckOwner->refresh();
        $this->assertEquals(20.0, (float) $truckOwner->tds_percentage);
    }

    public function test_transporter_refresh_tds_rejects_when_no_mismatch(): void
    {
        $admin = $this->makeAdmin();

        $master = TransporterMaster::factory()->create([
            'pan_number' => 'ABCPD1234Q',
            'pan_active' => false,
        ]);

        $transporter = Transporter::factory()->forCompany($admin->company_id)->create([
            'transporter_master_id' => $master->id,
            'declaration_given' => false,
            'tds_percentage' => 20,
            'mobile_number' => '9876543210',
        ]);

        $response = $this->actingAs($admin)->post("/transporters/{$transporter->id}/refresh-tds");

        $response->assertRedirect(route('transporters.edit', $transporter));
        $response->assertSessionHas('error');
        $this->assertEquals(20.0, (float) $transporter->fresh()->tds_percentage);
    }

    public function test_truck_owner_refresh_tds_syncs_eligible_freight_booking(): void
    {
        $admin = $this->makeAdmin();

        $master = TransporterMaster::factory()->create([
            'pan_number' => 'ABCPD1234Q',
            'pan_active' => false,
        ]);

        $truckOwner = Transporter::factory()->forCompany($admin->company_id)->truckOwner()->create([
            'transporter_master_id' => $master->id,
            'declaration_given' => false,
            'tds_percentage' => 1,
            'mobile_number' => '7777777777',
            'total_trucks' => 1,
        ]);

        $truck = Truck::create([
            'transporter_id' => $truckOwner->id,
            'truck_number' => 'GJ01XX0099',
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
            'lr_number' => 'LR-REF-'.uniqid(),
            'gr_number' => 'GR-REF-'.uniqid(),
            'invoice_number' => 'INV-REF-'.uniqid(),
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

        $freightBooking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $truckOwner->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 200,
            'balance_amount' => 790,
            'tds_amount' => 10,
            'company_id' => $admin->company_id,
            'transporter_tds_percentage' => 1,
            'voucher_number' => 'VB-REF-001',
        ]);

        $response = $this->actingAs($admin)->post("/truck_owners/{$truckOwner->id}/refresh-tds");

        $response->assertRedirect(route('truck_owners.edit', $truckOwner));
        $response->assertSessionHas('success');

        $freightBooking->refresh();
        $this->assertEquals(20.0, (float) $freightBooking->transporter_tds_percentage);
        $this->assertEquals(200.0, (float) $freightBooking->tds_amount);
        $this->assertEquals(600.0, (float) $freightBooking->balance_amount);
    }
}
