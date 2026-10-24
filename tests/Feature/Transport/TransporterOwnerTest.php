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
use App\Exports\TransporterMasterExport;
use App\Exports\TransporterReportExport;
use App\Imports\TransporterImport;
use App\Models\AdvancePayment;
use App\Models\PaymentMode;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\TransporterOnboardingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\VoucherPayment;
use Tests\TestCase;

class TransporterOwnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        \Illuminate\Support\Collection::macro('hasPages', fn () => false);
        \Illuminate\Support\Collection::macro('links', fn () => '');
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

        // Cross company 403
        $otherAdmin = $this->makeAdmin();
        $this->actingAs($otherAdmin)->post("/transporters/{$transporter->id}/refresh-tds")->assertStatus(403);
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

    public function test_transporter_index_ajax_datatables_and_searching(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $master1 = TransporterMaster::create([
            'name' => 'Alpha Logistics',
            'pan_number' => 'ALPHAP1234A',
            'pan_active' => true,
        ]);
        $t1 = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master1->id,
            'is_owner' => 0,
            'city_id' => $city->id,
            'state_id' => $state->id,
            'country_id' => $country->id,
            'mobile_number' => '9991112221',
            'declaration_given' => false,
            'tds_percentage' => 1,
        ]);

        $master2 = TransporterMaster::create([
            'name' => 'Beta Express',
            'pan_number' => 'BETAP5678B',
            'pan_active' => true,
        ]);
        $t2 = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master2->id,
            'is_owner' => 0,
            'city_id' => $city->id,
            'state_id' => $state->id,
            'country_id' => $country->id,
            'mobile_number' => '9991112222',
            'declaration_given' => false,
            'tds_percentage' => 2,
        ]);

        // Basic AJAX
        $res = $this->actingAs($admin)->getJson('/transporters', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $res->assertOk()->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
        $this->assertEquals(2, $res->json('recordsTotal'));

        // Sorting by master name (column 0) without search (triggers lines 246-310)
        $resOrder0 = $this->actingAs($admin)->call('GET', '/transporters', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => '0', 'dir' => 'desc']],
        ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $resOrder0->assertOk();
        $this->assertStringContainsString('Beta Express', $resOrder0->json('data.0.name'));

        // Sorting by master name (column 0) asc and PAN (column 3) desc without search (triggers lines 246-310)
        $resOrder0Asc = $this->actingAs($admin)->call('GET', '/transporters', [
            'draw' => 2,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => '0', 'dir' => 'asc']],
        ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $resOrder0Asc->assertOk();

        $resOrder3Desc = $this->actingAs($admin)->call('GET', '/transporters', [
            'draw' => 3,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => '3', 'dir' => 'desc']],
        ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $resOrder3Desc->assertOk();

        // Non-joined query sorting without any search (lines 188-244): columns 1, 2, 4, 5, 6, 99
        foreach ([1, 2, 4, 5, 6, 99] as $localCol) {
            $this->actingAs($admin)->call('GET', '/transporters', [
                'draw' => 70 + $localCol,
                'start' => 0,
                'length' => 10,
                'order' => [['column' => (string) $localCol, 'dir' => 'asc']],
            ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])->assertOk();

            $this->actingAs($admin)->call('GET', '/transporters', [
                'draw' => 80 + $localCol,
                'start' => 0,
                'length' => 10,
                'order' => [['column' => (string) $localCol, 'dir' => 'desc']],
            ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        }

        // Trigger lines 277-279 (if (! $transporter) return null;)
        $triggerMissing = false;
        Transporter::addGlobalScope('test_missing_scope', function ($builder) use (&$triggerMissing, $t2) {
            if ($triggerMissing && ! empty($builder->getEagerLoads())) {
                $builder->where('transporters.id', '!=', $t2->id);
            }
        });
        $triggerMissing = true;
        $resMissing = $this->actingAs($admin)->call('GET', '/transporters', [
            'draw' => 4,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => '0', 'dir' => 'asc']],
        ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $resMissing->assertOk();
        $triggerMissing = false;

        // Sorting with search and different columns (0, 1, 2, 3, 4, 5, 6, default)
        foreach ([0, 1, 2, 3, 4, 5, 6, 99] as $col) {
            $this->actingAs($admin)->call('GET', '/transporters', [
                'draw' => 10 + $col,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => 'Alpha'],
                'order' => [['column' => (string) $col, 'dir' => 'asc']],
            ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])->assertOk();

            $this->actingAs($admin)->call('GET', '/transporters', [
                'draw' => 20 + $col,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => 'Alpha'],
                'order' => [['column' => (string) $col, 'dir' => 'desc']],
            ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        }

        // Column specific searches (0, 1, 2, 3, 4, 5, 6)
        $colSearchCases = [
            0 => 'Alpha',
            1 => 'ALP',
            2 => 'BEN1',
            3 => 'ALPHA',
            4 => 'Person',
            5 => '999111',
            6 => '100',
        ];
        foreach ($colSearchCases as $colIdx => $val) {
            $cols = [];
            $cols[$colIdx] = ['data' => 'col', 'search' => ['value' => $val]];
            $this->actingAs($admin)->call('GET', '/transporters', [
                'draw' => 40 + $colIdx,
                'start' => 0,
                'length' => 10,
                'columns' => $cols,
            ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        }

        // Global search by mobile_number, short_name, beneficiary_id, company name
        foreach (['ALP', 'BEN1', '999111', 'Company'] as $term) {
            $this->actingAs($admin)->call('GET', '/transporters', [
                'draw' => 50,
                'search' => ['value' => $term],
            ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        }

        // User without company_id (covers !$companyId in query)
        $superUser = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $this->actingAs($superUser)
            ->withSession(['super_admin_company_id' => $admin->company_id])
            ->call('GET', '/transporters', ['draw' => 60], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        // Trigger catch block in getTransportersData (lines 439-450)
        $shouldThrowDt = true;
        \DB::listen(function ($query) use (&$shouldThrowDt) {
            if ($shouldThrowDt && stripos($query->sql, 'transporters') !== false && stripos($query->sql, 'is_owner') !== false) {
                throw new \Exception('Simulated DataTables failure');
            }
        });
        $resDtErr = $this->actingAs($admin)->call('GET', '/transporters', [
            'draw' => 999,
        ], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
        $shouldThrowDt = false;
        $resDtErr->assertStatus(500);
        $this->assertArrayHasKey('error', $resDtErr->json());
    }

    public function test_transporter_bulk_upload_flow_and_validation(): void
    {
        $admin = $this->makeAdmin();

        // 1. Template download & execute stream callback
        $resTemplate = $this->actingAs($admin)->get('/transporters/bulk-upload-template');
        $resTemplate->assertOk();
        $this->assertTrue(str_contains($resTemplate->headers->get('content-disposition') ?? '', 'transporter_template.csv'));
        ob_start();
        $resTemplate->sendContent();
        $csvContent = ob_get_clean();
        $this->assertStringContainsString('PAN Number', $csvContent);

        // 2. Empty validation returns json
        $resEmpty = $this->actingAs($admin)->postJson('/transporters/bulk-upload', []);
        $resEmpty->assertJson(['success' => false]);

        // 3. Real CSV upload with partial success and zero success
        [$country, $state, $city] = $this->seedGeo();
        // Create CSV with 1 valid row and 1 invalid row (partial success)
        $csvHeader = "PAN Number,Transport Name,Email,GST Number,PAN Active,Contact Person Name,Mobile Number,Country,State,City,Address,Declaration Given,TDS Percentage,TDS Adjustment Remark,Short Name,Advance Amount\n";
        $csvRowValid = "BKUPP1234A,Bulk Trans 1,bulk1@test.com,,Yes,,9876543210,{$country->name},{$state->name},{$city->name},Address 1,No,1,,,0\n";
        $csvRowInvalid = ",,,,,,,,,,,,,,, \n";
        $csvContent = $csvHeader . $csvRowValid . $csvRowInvalid;

        $tempFile = UploadedFile::fake()->createWithContent('bulk_partial.csv', $csvContent);
        $resPartial = $this->actingAs($admin)->post('/transporters/bulk-upload', [
            'file' => $tempFile,
        ]);
        $resPartial->assertOk()->assertJson(['success' => true, 'partial_success' => true]);

        // Create CSV with only invalid rows (zero success)
        $csvZeroContent = $csvHeader . $csvRowInvalid;
        $tempZeroFile = UploadedFile::fake()->createWithContent('bulk_zero.csv', $csvZeroContent);
        $resZero = $this->actingAs($admin)->post('/transporters/bulk-upload', [
            'file' => $tempZeroFile,
        ]);
        $resZero->assertOk()->assertJson(['success' => false, 'partial_success' => false]);

        // 4. CSV upload with 100% clean rows (zero errors -> lines 92-99)
        $csvCleanRow = "CLNPA1234A,Clean Transporter,clean@test.com,,Yes,,9876543299,{$country->name},{$state->name},{$city->name},Address Clean,No,1,,,0\n";
        $csvCleanContent = $csvHeader . $csvCleanRow;
        $cleanFile = UploadedFile::fake()->createWithContent('bulk_clean.csv', $csvCleanContent);
        $resClean = $this->actingAs($admin)->post('/transporters/bulk-upload', [
            'file' => $cleanFile,
        ]);
        $resClean->assertOk()->assertJson(['success' => true, 'errors' => []]);
    }

    public function test_transporter_bulk_upload_handles_exception_cleanly(): void
    {
        $admin = $this->makeAdmin();
        Excel::shouldReceive('import')->once()->andThrow(new \Exception('Bulk upload fail'));
        $file = UploadedFile::fake()->create('transporters.xlsx', 100);
        $resException = $this->actingAs($admin)->post('/transporters/bulk-upload', [
            'file' => $file,
        ]);
        $resException->assertStatus(500)->assertJson(['success' => false]);
    }

    public function test_transporter_show_and_cross_company_protection(): void
    {
        $admin = $this->makeAdmin();
        $master = TransporterMaster::create(['name' => 'Show Logistics', 'pan_number' => 'SHOWP1234A', 'pan_active' => true]);
        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);

        AdvancePayment::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 500.00,
            'payment_date' => now()->toDateString(),
        ]);

        // Authorized show returns JSON
        $res = $this->actingAs($admin)->get("/transporters/{$transporter->id}");
        $res->assertOk()->assertJson([
            'id' => $transporter->id,
            'name' => 'Show Logistics',
        ]);
        $this->assertCount(1, $res->json('advance_payments'));

        // Cross company 403
        $otherAdmin = $this->makeAdmin();
        $this->actingAs($otherAdmin)->get("/transporters/{$transporter->id}")->assertStatus(403);
    }

    public function test_transporter_create_view_loads(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $res = $this->actingAs($admin)->get('/transporters/create');
        $res->assertOk()->assertViewIs('transporters.create');
        $this->assertNotEmpty($res->viewData('countries'));

        // Test with old input for country_id and state_id
        $resOld = $this->actingAs($admin)->withSession([
            '_old_input' => [
                'country_id' => $country->id,
                'state_id' => $state->id,
            ],
        ])->get('/transporters/create');
        $resOld->assertOk();
        $this->assertNotEmpty($resOld->viewData('states'));
        $this->assertNotEmpty($resOld->viewData('cities'));
    }

    public function test_transporter_store_with_email_creates_user_and_sends_notification(): void
    {
        UserRole::firstOrCreate(['id' => 6], ['name' => 'transporter']);
        $mock = \Mockery::mock(\App\Services\Notifications\TransporterOnboardingNotificationService::class);
        $mock->shouldReceive('sendAccountCreated')
            ->once()
            ->with('portal@transporter.com', 'Portal User Transporter');
        $this->app->instance(\App\Services\Notifications\TransporterOnboardingNotificationService::class, $mock);

        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'Portal User Transporter',
            'email' => 'portal@transporter.com',
            'gst_number' => '24ABCDE1234F1Z9',
            'pan_number' => 'ABCDE1234G',
            'advance_amount' => 0,
            'pan_active' => true,
            'address' => '789 Logistics Way',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'mobile_number' => '9876543211',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ]);

        $response->assertRedirect(route('transporters.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'portal@transporter.com')->first();
        $this->assertNotNull($user);
        $roleId = UserRole::where('name', 'transporter')->value('id');
        $this->assertEquals($roleId, $user->user_role_id);
        $this->assertNotNull($user->transporter_master_id);

        // Store with existing master and dummy PAN without remark -> validation error
        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($admin->company_id);
        $dummyMaster = TransporterMaster::create([
            'name' => 'Dummy Master Ex',
            'pan_number' => $dummyPan,
            'pan_active' => true,
        ]);
        $resDummyNoRemark = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'transporter_master_id' => $dummyMaster->id,
            'mobile_number' => '9876543212',
            'declaration_given' => true,
            'tds_percentage' => 0,
            'tds_adjustment_remark' => '',
        ]);
        $resDummyNoRemark->assertSessionHasErrors('tds_adjustment_remark');

        // Store with existing master and dummy PAN with remark -> success
        $resDummyWithRemark = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'transporter_master_id' => $dummyMaster->id,
            'mobile_number' => '9876543213',
            'declaration_given' => false,
            'tds_percentage' => 0,
            'tds_adjustment_remark' => 'Dummy remark given',
        ]);
        $resDummyWithRemark->assertRedirect(route('transporters.index'));

        // Store with existing master and declaration given = true
        $realMaster = TransporterMaster::create([
            'name' => 'Real Master Ex',
            'pan_number' => 'REALM1234A',
            'pan_active' => true,
        ]);
        $resDecl = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'transporter_master_id' => $realMaster->id,
            'mobile_number' => '9876543214',
            'declaration_given' => true,
            'tds_percentage' => 0,
        ]);
        $resDecl->assertRedirect(route('transporters.index'));

        // Store with existing master where submitted TDS matches calculated (line 598: : null)
        $matchRateMaster = TransporterMaster::create([
            'name' => 'Match Rate Master',
            'pan_number' => 'MTRCP1234A', // 4th char C -> 2.00%
            'pan_active' => true,
        ]);
        $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'transporter_master_id' => $matchRateMaster->id,
            'mobile_number' => '9876543291',
            'declaration_given' => false,
            'tds_percentage' => 2.00,
        ])->assertRedirect(route('transporters.index'));

        // Store with existing master and declaration given = false with TDS mismatch remark
        $mismatchMaster = TransporterMaster::create([
            'name' => 'Mismatch Master Ex',
            'pan_number' => 'MISMP1234A',
            'pan_active' => true,
        ]);
        $resMismatch = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'transporter_master_id' => $mismatchMaster->id,
            'mobile_number' => '9876543215',
            'declaration_given' => false,
            'tds_percentage' => 5.00,
            'tds_adjustment_remark' => 'Custom rate agreed',
        ]);
        $resMismatch->assertRedirect(route('transporters.index'));

        // Store new master with dummy PAN and empty remark -> throws ValidationException (lines 637-640)
        $resNewDummyFail = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'New Dummy Agency Fail',
            'pan_number' => $dummyPan,
            'pan_active' => true,
            'declaration_given' => true,
            'tds_percentage' => 0,
            'tds_adjustment_remark' => '',
            'mobile_number' => '9876543220',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Addr',
        ]);
        $resNewDummyFail->assertSessionHasErrors('tds_adjustment_remark');

        // Store new master without email and with TDS mismatch remark (lines 642-644, 690)
        $resNoEmail = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'No Email Agency',
            'pan_number' => 'NOEML1234A',
            'pan_active' => true,
            'declaration_given' => false,
            'tds_percentage' => 5.00,
            'tds_adjustment_remark' => 'Custom rate for no email',
            'mobile_number' => '9876543221',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Addr',
        ]);
        $resNoEmail->assertRedirect(route('transporters.index'));
        $resNoEmail->assertSessionHas('success', 'Transporter created successfully. No email was provided, so notifications were not sent.');

        // Store new master with dummy PAN and valid remark (lines 634-635, 658)
        $resNewDummyPass = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'New Dummy Pass',
            'pan_number' => $dummyPan,
            'pan_active' => true,
            'declaration_given' => false,
            'tds_percentage' => 0,
            'tds_adjustment_remark' => 'Valid dummy reason given',
            'mobile_number' => '9876543222',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Addr',
        ]);
        $resNewDummyPass->assertRedirect(route('transporters.index'));

        // Store new master with declaration given = true (line 658)
        $resNewDecl = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'New Decl Agency',
            'pan_number' => 'DECLP1234A',
            'pan_active' => true,
            'declaration_given' => true,
            'tds_percentage' => 0,
            'mobile_number' => '9876543223',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Addr',
        ]);
        $resNewDecl->assertRedirect(route('transporters.index'));

        $resNewMatch = $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'New Match Agency',
            'pan_number' => 'MATCP1234A',
            'pan_active' => true,
            'declaration_given' => false,
            'tds_percentage' => 2.00,
            'mobile_number' => '9876543224',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Addr',
        ]);
        $resNewMatch->assertRedirect(route('transporters.index'));

        // Directly invoke controller store with empty dummy remark to execute controller lines 637-639
        $controller = app(\App\Http\Controllers\TransporterController::class);
        $mockReq = \Mockery::mock(\App\Http\Requests\StoreTransporterRequest::class);
        $mockReq->shouldReceive('validated')->andReturn([
            'company_id' => $admin->company_id,
            'name' => 'Direct Dummy Agency',
            'pan_number' => $dummyPan,
            'pan_active' => true,
            'declaration_given' => false,
            'tds_percentage' => 0,
            'tds_adjustment_remark' => '',
            'mobile_number' => '9876543230',
        ]);
        $mockReq->shouldReceive('filled')->with('transporter_master_id')->andReturn(false);
        $mockReq->shouldReceive('boolean')->with('is_msme')->andReturn(false);
        $mockReq->shouldReceive('input')->with('msme_reg_no')->andReturn(null);

        try {
            $controller->store($mockReq);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('tds_adjustment_remark', $e->errors());
        }
    }

    public function test_transporter_edit_and_update_functionality_and_permissions(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $master = TransporterMaster::create([
            'name' => 'Original Transporter',
            'pan_number' => 'ORIGP1234A',
            'pan_active' => true,
        ]);

        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ]);

        // Cross company edit 403
        $otherAdmin = $this->makeAdmin();
        $this->actingAs($otherAdmin)->get("/transporters/{$transporter->id}/edit")->assertStatus(403);
        $this->actingAs($otherAdmin)->put("/transporters/{$transporter->id}", [
            'company_id' => $otherAdmin->company_id,
            'mobile_number' => '9876543210',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ])->assertStatus(403);

        // Edit view authorized
        $res = $this->actingAs($admin)->get("/transporters/{$transporter->id}/edit");
        $res->assertOk()->assertViewIs('transporters.edit');

        // Update with declaration given and new TDS
        $updateRes = $this->actingAs($admin)->put("/transporters/{$transporter->id}", [
            'company_id' => $admin->company_id,
            'name' => 'Updated Transporter Name',
            'pan_number' => 'ORIGP1234A',
            'pan_active' => true,
            'declaration_given' => true,
            'tds_percentage' => 0,
            'mobile_number' => '9876543210',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Updated Address',
            'is_msme' => true,
            'msme_reg_no' => 'UDYAM-GJ-01-0012345',
        ]);
        $updateRes->assertRedirect(route('transporters.index'));
        $updateRes->assertSessionHas('success');

        $transporter->refresh();
        $this->assertTrue((bool) $transporter->declaration_given);
        $this->assertEquals(0.0, (float) $transporter->tds_percentage);
        $this->assertTrue((bool) $transporter->is_msme);
        $this->assertEquals('UDYAM-GJ-01-0012345', $transporter->msme_reg_no);
        $this->assertEquals('UDYAM-GJ-01-0012345', $master->fresh()->msme_reg_no);

        // Update as super_admin allows updating short_name and beneficiary_id
        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => $admin->company_id,
        ]);
        $superRes = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $admin->company_id])
            ->put("/transporters/{$transporter->id}", [
                'company_id' => $admin->company_id,
                'name' => 'Super Admin Transporter',
                'short_name' => 'SATR',
                'beneficiary_id' => 'BENEF123',
                'pan_number' => 'ORIGP1234A',
                'pan_active' => true,
                'declaration_given' => true,
                'tds_percentage' => 0,
                'mobile_number' => '9876543210',
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'address' => 'Updated Address',
            ]);
        $superRes->assertRedirect(route('transporters.index'));
        $transporter->refresh();
        $this->assertEquals('SATR', $transporter->short_name);
        $this->assertEquals('BENEF123', $transporter->beneficiary_id);

        // Edit view with old input
        $resOldEdit = $this->actingAs($admin)->withSession([
            '_old_input' => [
                'country_id' => $country->id,
                'state_id' => $state->id,
            ],
        ])->get("/transporters/{$transporter->id}/edit");
        $resOldEdit->assertOk();

        // Update dummy PAN without remark -> validation error (lines 776-778)
        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($admin->company_id);
        $dummyMaster = TransporterMaster::create([
            'name' => 'Dummy Up Master',
            'pan_number' => $dummyPan,
            'pan_active' => true,
        ]);
        $dummyTrans = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $dummyMaster->id,
            'is_owner' => 0,
            'mobile_number' => '9876543299',
            'declaration_given' => true,
            'tds_percentage' => 0,
        ]);
        $resDummyUpFail = $this->actingAs($admin)->put("/transporters/{$dummyTrans->id}", [
            'company_id' => $admin->company_id,
            'mobile_number' => '9876543299',
            'declaration_given' => true,
            'tds_percentage' => 0,
            'tds_adjustment_remark' => '',
        ]);
        $resDummyUpFail->assertSessionHasErrors('tds_adjustment_remark');

        // Update dummy PAN with valid remark -> success
        $resDummyUpPass = $this->actingAs($admin)->put("/transporters/{$dummyTrans->id}", [
            'company_id' => $admin->company_id,
            'mobile_number' => '9876543299',
            'declaration_given' => true,
            'tds_percentage' => 0,
            'tds_adjustment_remark' => 'Valid dummy update reason',
        ]);
        $resDummyUpPass->assertRedirect(route('transporters.index'));

        // Update with matching calculated rate (line 783: : null)
        $this->actingAs($admin)->put("/transporters/{$transporter->id}", [
            'company_id' => $admin->company_id,
            'mobile_number' => '9876543210',
            'declaration_given' => false,
            'tds_percentage' => 20.00,
        ])->assertRedirect(route('transporters.index'));

        // Update with TDS change > 0.01 triggers freight booking sync
        $resTdsSync = $this->actingAs($admin)->put("/transporters/{$transporter->id}", [
            'company_id' => $admin->company_id,
            'mobile_number' => '9876543210',
            'declaration_given' => false,
            'tds_percentage' => 5.00,
            'tds_adjustment_remark' => 'Rate adjusted',
        ]);
        $resTdsSync->assertRedirect(route('transporters.index'));
    }

    public function test_transporter_destroy_soft_deletes_with_remark_and_forbids_cross_company(): void
    {
        $admin = $this->makeAdmin();
        $master = TransporterMaster::create(['name' => 'To Delete', 'pan_number' => 'DELP1234A', 'pan_active' => true]);
        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);

        // Authorized destroy
        $delRes = $this->actingAs($admin)->delete("/transporters/{$transporter->id}", [
            'delete_remark' => 'Transporter closed business',
        ]);
        $delRes->assertRedirect(route('transporters.index'));
        $delRes->assertSessionHas('success');

        $this->assertSoftDeleted('transporters', ['id' => $transporter->id]);
        $transporter->refresh();
        $this->assertEquals('Transporter closed business', $transporter->deleted_remark);
        $this->assertEquals($admin->id, $transporter->deleted_by);
    }

    public function test_transporter_ajax_apis(): void
    {
        $admin = $this->makeAdmin();

        // 1. calculateTdsPercentage (Individual, Corporate, Dummy PAN)
        $resCalcInd = $this->actingAs($admin)->postJson('/api/calculate-tds-percentage', [
            'pan_number' => 'AAAPA1234A',
            'pan_active' => true,
            'declaration_given' => false,
            'is_owner' => 0,
        ]);
        $resCalcInd->assertOk()->assertJson(['tds_percentage' => '1.00']);

        $resCalcCorp = $this->actingAs($admin)->postJson('/api/calculate-tds-percentage', [
            'pan_number' => 'AAACC1234A',
            'pan_active' => true,
            'declaration_given' => false,
            'is_owner' => 0,
        ]);
        $resCalcCorp->assertOk()->assertJson(['tds_percentage' => '2.00']);

        $resCalcDummy = $this->actingAs($admin)->postJson('/api/calculate-tds-percentage', [
            'pan_number' => '1DUMMY123A',
        ]);
        $resCalcDummy->assertOk()->assertJson(['tds_percentage' => '0.00']);

        // 2. getMasterByPan (Invalid PAN, Dummy PAN, Not found, Found with already_registered true/false)
        $this->actingAs($admin)->getJson('/api/transporter-master-by-pan?pan_number=INV')->assertStatus(422)->assertJson(['error' => 'Invalid PAN number']);

        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($admin->company_id);
        $this->actingAs($admin)->getJson("/api/transporter-master-by-pan?pan_number={$dummyPan}&company_id={$admin->company_id}")->assertOk()->assertJson(['found' => false, 'is_dummy_pan' => true]);

        $this->actingAs($admin)->getJson('/api/transporter-master-by-pan?pan_number=NOTFD1234A')->assertOk()->assertJson(['found' => false, 'is_dummy_pan' => false]);

        $master = TransporterMaster::create([
            'name' => 'API Master',
            'pan_number' => 'APIMP1234A',
            'pan_active' => true,
        ]);

        $agency = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9999999991',
        ]);
        $owner = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 1,
            'mobile_number' => '9999999992',
        ]);

        $resPan = $this->actingAs($admin)->getJson("/api/transporter-master-by-pan?pan_number=APIMP1234A&company_id={$admin->company_id}&is_owner=0");
        $resPan->assertOk()->assertJson([
            'found' => true,
            'already_registered' => true,
            'master' => [
                'name' => 'API Master',
                'pan_number' => 'APIMP1234A',
            ],
        ]);

        $otherAdmin = $this->makeAdmin();
        $resPanNotReg = $this->actingAs($admin)->getJson("/api/transporter-master-by-pan?pan_number=APIMP1234A&company_id={$otherAdmin->company_id}&is_owner=0");
        $resPanNotReg->assertOk()->assertJson([
            'found' => true,
            'already_registered' => false,
        ]);

        // 3. getTruckOwnersApi & getAgenciesApi (With company and with company_id = null)
        $resAgencies = $this->actingAs($admin)->getJson('/api/agencies');
        $resAgencies->assertOk();
        $agencyIds = collect($resAgencies->json())->pluck('id')->toArray();
        $this->assertContains($agency->id, $agencyIds);

        $resOwners = $this->actingAs($admin)->getJson('/api/truck-owners');
        $resOwners->assertOk();
        $ownerIds = collect($resOwners->json())->pluck('id')->toArray();
        $this->assertContains($owner->id, $ownerIds);

        $superUser = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $this->actingAs($superUser)->withSession(['super_admin_company_id' => $admin->company_id])->getJson('/api/agencies')->assertOk();
        $this->actingAs($superUser)->withSession(['super_admin_company_id' => $admin->company_id])->getJson('/api/truck-owners')->assertOk();

        // 4. Exception handling in APIs & DataTables (covers lines 439-450, 957-961, 984-988)
        $failTruckOwners = false;
        $failAgencies = false;
        $failDt = false;
        \DB::listen(function ($query) use (&$failTruckOwners, &$failAgencies, &$failDt) {
            if ($failTruckOwners && str_contains($query->sql, 'transporter_masters')) {
                $failTruckOwners = false;
                throw new \Exception('Simulated truck owners DB failure');
            }
            if ($failAgencies && str_contains($query->sql, 'transporter_masters')) {
                $failAgencies = false;
                throw new \Exception('Simulated agencies DB failure');
            }
            if ($failDt && str_contains($query->sql, 'is_owner')) {
                $failDt = false;
                throw new \Exception('Simulated datatables DB failure');
            }
        });

        $failTruckOwners = true;
        $this->actingAs($admin)->getJson('/api/truck-owners')
            ->assertStatus(500)
            ->assertJson(['error' => 'Failed to fetch truck owners']);

        $failAgencies = true;
        $this->actingAs($admin)->getJson('/api/agencies')
            ->assertStatus(500)
            ->assertJson(['error' => 'Failed to fetch agencies']);

        $failDt = true;
        $this->actingAs($admin)->call('GET', '/transporters', ['draw' => 999], [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(500)
            ->assertJsonStructure(['error']);
    }

    public function test_transporter_rbac_roles(): void
    {
        $admin = $this->makeAdmin();
        $master = TransporterMaster::create(['name' => 'RBAC Co', 'pan_number' => 'RBACP1234A', 'pan_active' => true]);
        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);

        $moderator = User::factory()->create([
            'user_role_id' => 4, // moderator
            'company_id' => $admin->company_id,
        ]);

        // Moderator can view index & create
        $this->actingAs($moderator)->get('/transporters')->assertOk();
        $this->actingAs($moderator)->get('/transporters/create')->assertOk();

        // Moderator forbidden on edit, update, destroy, refresh-tds
        $this->actingAs($moderator)->get("/transporters/{$transporter->id}/edit")->assertStatus(403);
        $this->actingAs($moderator)->put("/transporters/{$transporter->id}", ['delete_remark' => 'x'])->assertStatus(403);
        $this->actingAs($moderator)->delete("/transporters/{$transporter->id}", ['delete_remark' => 'x'])->assertStatus(403);
        $this->actingAs($moderator)->post("/transporters/{$transporter->id}/refresh-tds")->assertStatus(403);

        // Master admin forbidden on company transporter index
        $masterAdmin = User::factory()->create([
            'user_role_id' => 1, // master_admin
            'company_id' => null,
        ]);
        $this->actingAs($masterAdmin)->get('/transporters')->assertStatus(403);
    }

    public function test_transporter_model_attributes_and_helpers(): void
    {
        $admin = $this->makeAdmin();
        [$country, $state, $city] = $this->seedGeo();

        $master = TransporterMaster::create([
            'name' => 'Model Test Master',
            'pan_number' => 'ABCDE1234F',
            'email' => 'master@test.com',
            'gst_number' => '24ABCDE1234F1Z5',
            'address' => 'Model Test Address',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'pan_active' => true,
            'is_msme' => true,
            'msme_reg_no' => 'UDYAM-GJ-01-0012345',
        ]);

        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);

        $this->assertEquals('Model Test Master', $transporter->name);
        $this->assertEquals('ABCDE1234F', $transporter->pan_number);
        $this->assertEquals('24ABCDE1234F1Z5', $transporter->gst_number);
        $this->assertTrue($transporter->pan_active);
        $this->assertTrue($transporter->is_msme);
        $this->assertEquals('UDYAM-GJ-01-0012345', $transporter->msme_reg_no);
        $this->assertEquals('Model Test Address', $transporter->address);
        $this->assertEquals($city->id, $transporter->city_id);
        $this->assertEquals($state->id, $transporter->state_id);
        $this->assertEquals($country->id, $transporter->country_id);
        $this->assertEquals($city->id, $transporter->city?->id);
        $this->assertEquals($state->id, $transporter->state?->id);
        $this->assertEquals($country->id, $transporter->country?->id);
        $this->assertTrue($master->hasNotificationEmail());

        // displayNameWithShortName branches
        $this->assertEquals('Model Test Master', $transporter->displayNameWithShortName());
        $transporter->short_name = 'MTM';
        $this->assertEquals('Model Test Master (MTM)', $transporter->displayNameWithShortName());

        $emptyTransporter = new Transporter();
        $this->assertEquals('Not Set', $emptyTransporter->displayNameWithShortName());
        $emptyTransporter->short_name = 'XYZ';
        $this->assertEquals('Not Set (XYZ)', $emptyTransporter->displayNameWithShortName());

        // Null fallbacks when transporter master is null
        $this->assertNull($emptyTransporter->country_id);
        $this->assertNull($emptyTransporter->city_id);
        $this->assertNull($emptyTransporter->state_id);
        $this->assertNull($emptyTransporter->city);
        $this->assertNull($emptyTransporter->state);
        $this->assertNull($emptyTransporter->country);

        $this->assertInstanceOf(Company::class, $transporter->company);
        $this->assertInstanceOf(TransporterMaster::class, $transporter->transporterMaster);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $transporter->trucks);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $transporter->lorryReceipts);
    }

    public function test_transporter_import_full_flow(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);
        [$country, $state, $city] = $this->seedGeo();

        $import = new TransporterImport($admin->company_id);

        $collection = collect([
            collect([
                'name' => 'Imported Co 1',
                'pan_number' => 'ABCDE1234F',
                'pan_active' => 'Yes',
                'mobile_number' => '9876500001',
                'address' => 'Line 1',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'country' => 'India',
                'declaration_given' => 'Yes',
                'tds_percentage' => '0',
            ]),
            // Duplicate PAN in same file -> skipped
            collect([
                'name' => 'Imported Co Duplicate',
                'pan_number' => 'ABCDE1234F',
                'pan_active' => 'Yes',
                'mobile_number' => '9876500002',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'country' => 'India',
                'declaration_given' => 'Yes',
                'tds_percentage' => '0',
            ]),
            // Missing required name/pan -> error
            collect([
                'name' => '',
                'pan_number' => '',
            ]),
        ]);

        $import->collection($collection);

        $this->assertDatabaseHas('transporters', [
            'company_id' => $admin->company_id,
            'declaration_given' => 1,
        ]);
        $this->assertNotEmpty($import->errors);
        $this->assertEquals(500, $import->chunkSize());
    }

    public function test_transporter_bank_account_controller_flow(): void
    {
        $admin = $this->makeAdmin();
        $superAdmin = User::factory()->create([
            'user_role_id' => 2, // super_admin required
            'company_id' => $admin->company_id,
        ]);

        $master = TransporterMaster::create([
            'name' => 'Bank Master',
            'pan_number' => 'BANKP1234A',
            'pan_active' => true,
        ]);
        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);

        // Index without company
        $resNoCo = $this->actingAs($superAdmin)->get('/transporter_bank_accounts/index');
        $resNoCo->assertOk()->assertViewIs('transporter_bank_accounts.index');

        // Index with company and search
        $resWithCo = $this->actingAs($superAdmin)->get("/transporter_bank_accounts/index?company_id={$admin->company_id}&search=Bank");
        $resWithCo->assertOk()->assertViewIs('transporter_bank_accounts.index');
        $this->assertCount(1, $resWithCo->viewData('transporters'));

        // Update beneficiary ID and short name
        $updateRes = $this->actingAs($superAdmin)->put("/transporter_bank_accounts/{$transporter->id}/beneficiary-id", [
            'beneficiary_id' => 'BEN_BANK_001',
            'short_name' => 'BNK',
        ]);
        $updateRes->assertRedirect()->assertSessionHas('success');
        $transporter->refresh();
        $this->assertEquals('BEN_BANK_001', $transporter->beneficiary_id);
        $this->assertEquals('BNK', $transporter->short_name);

        // Validation error on duplicate beneficiary ID
        $transporter2 = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543211',
        ]);
        $dupRes = $this->actingAs($superAdmin)->put("/transporter_bank_accounts/{$transporter2->id}/beneficiary-id", [
            'beneficiary_id' => 'BEN_BANK_001',
            'short_name' => 'BNK2',
        ]);
        $dupRes->assertSessionHasErrors(['beneficiary_id']);
    }

    public function test_merge_transporter_masters_command(): void
    {
        $master1 = TransporterMaster::create([
            'name' => 'Merge Master',
            'pan_number' => 'MERGE1234A',
            'pan_active' => true,
        ]);
        $master2 = TransporterMaster::create([
            'name' => 'Merge Master',
            'pan_number' => 'MERGE1234A',
            'pan_active' => true,
        ]);
        $admin = $this->makeAdmin();
        Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master2->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);

        // Invalid keep id
        $this->artisan('transporter-masters:merge', [
            'keep-id' => 99999,
        ])->assertExitCode(1);

        // Dry run
        $this->artisan('transporter-masters:merge', [
            'keep-id' => $master1->id,
            '--merge-ids' => (string) $master2->id,
        ])->assertExitCode(0);

        // Apply merge
        $this->artisan('transporter-masters:merge', [
            'keep-id' => $master1->id,
            '--merge-ids' => (string) $master2->id,
            '--apply' => true,
        ])
        ->expectsConfirmation('Apply this merge?', 'yes')
        ->assertExitCode(0);

        $this->assertDatabaseMissing('transporter_masters', ['id' => $master2->id]);
        $this->assertDatabaseHas('transporter_masters', ['id' => $master1->id]);
    }

    public function test_transporter_master_export(): void
    {
        $admin = $this->makeAdmin();
        $master = TransporterMaster::create([
            'name' => 'Export Master A',
            'pan_number' => 'EXPMA1234A',
            'pan_active' => true,
        ]);
        Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);
        Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 1,
            'mobile_number' => '9876543211',
            'declaration_given' => true,
            'total_trucks' => 5,
        ]);

        $this->actingAs($admin);

        // 1. type = 'transporter'
        $export = new TransporterMasterExport('transporter');
        $rows = $export->collection();
        $this->assertCount(1, $rows);
        $this->assertContains('Name', $export->headings());
        $this->assertArrayHasKey('O', $export->columnFormats());

        // 2. type = 'owner' (covers lines 30-31)
        $exportOwner = new TransporterMasterExport('owner');
        $ownerRows = $exportOwner->collection();
        $this->assertCount(1, $ownerRows);
        $this->assertEquals('Truck Owner', $ownerRows[0]['Type']);
        $this->assertEquals('Yes', $ownerRows[0]['Declaration Given']);
        $this->assertEquals(5, $ownerRows[0]['Total Trucks']);

        // 3. type = 'truck_owner'
        $exportTruckOwner = new TransporterMasterExport('truck_owner');
        $this->assertCount(1, $exportTruckOwner->collection());

        // 4. type = 'both'
        $exportBoth = new TransporterMasterExport('both');
        $this->assertCount(2, $exportBoth->collection());
    }

    public function test_transporter_dashboard_controller_when_user_has_no_transporter_master_id(): void
    {
        $admin = $this->makeAdmin();
        $role = UserRole::firstOrCreate(['name' => 'transporter']);

        $userNoMaster = User::create([
            'name' => 'Transporter No Master',
            'email' => 'no_master_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_role_id' => $role->id,
            'transporter_master_id' => null,
            'company_id' => $admin->company_id,
            'is_blocked' => false,
        ]);

        $master = TransporterMaster::create([
            'name' => 'Dummy Master',
            'pan_number' => 'DUMMY1234A',
            'pan_active' => true,
        ]);

        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876543210',
        ]);

        $customer = Consignee::create(['company_id' => $admin->company_id, 'name' => 'C1', 'address' => 'A1']);
        $consignor = Consignee::create(['company_id' => $admin->company_id, 'name' => 'C2', 'address' => 'A2']);
        $consignee = Consignee::create(['company_id' => $admin->company_id, 'name' => 'C3', 'address' => 'A3']);
        $truck = Truck::create(['transporter_id' => $transporter->id, 'truck_number' => 'MH01AA1111', 'truck_type' => 'Flatbed']);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'lr_number' => 'LR-NOMAST-1',
            'gr_number' => 'GR-NOMAST-1',
            'invoice_number' => 'INV-NOMAST-1',
            'gr_date' => now()->toDateString(),
            'source_city' => 'CityA',
            'source_state' => 'StateA',
            'destination_city' => 'CityB',
            'destination_state' => 'StateB',
            'lane' => 'A-B',
            'good_type' => 'General',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'delivery_status' => 'Pending',
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 200,
            'balance_amount' => 800,
            'tds_amount' => 10,
            'company_id' => $admin->company_id,
            'transporter_tds_percentage' => 1,
            'voucher_number' => 'VB-NOMAST-1',
        ]);

        $this->actingAs($userNoMaster);

        // 1. freightBookings with empty transporterIds
        $response = $this->get(route('transporter.freight_bookings.index'));
        $response->assertOk();
        $response->assertViewIs('transporter.freight_bookings');
        $this->assertTrue($response->viewData('bookings')->isEmpty());

        // 2. showFreightBooking with empty transporterIds -> 403
        $responseShow = $this->get(route('transporter.freight_bookings.show', $booking->id));
        $responseShow->assertForbidden();

        // 3. payments with empty transporterIds
        $responsePay = $this->get(route('transporter.payments.index'));
        $responsePay->assertOk();
        $responsePay->assertViewIs('transporter.payments');
        $this->assertTrue($responsePay->viewData('voucherPayments')->isEmpty());
        $this->assertTrue($responsePay->viewData('advancePayments')->isEmpty());
    }

    public function test_transporter_dashboard_controller_with_transporter_master_and_data(): void
    {
        $admin = $this->makeAdmin();
        $role = UserRole::firstOrCreate(['name' => 'transporter']);

        $masterA = TransporterMaster::create([
            'name' => 'Master Transporter A',
            'pan_number' => 'MASTA1234A',
            'pan_active' => true,
        ]);

        $masterB = TransporterMaster::create([
            'name' => 'Master Transporter B',
            'pan_number' => 'MASTB1234B',
            'pan_active' => true,
        ]);

        $transporterA = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $masterA->id,
            'is_owner' => 0,
            'mobile_number' => '9999911111',
        ]);

        $transporterB = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $masterB->id,
            'is_owner' => 0,
            'mobile_number' => '8888822222',
        ]);

        $userMasterA = User::create([
            'name' => 'User Master A',
            'email' => 'user_master_a_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'user_role_id' => $role->id,
            'transporter_master_id' => $masterA->id,
            'company_id' => $admin->company_id,
            'is_blocked' => false,
        ]);

        $customer = Consignee::create(['company_id' => $admin->company_id, 'name' => 'Cust A', 'address' => 'Addr A']);
        $consignor = Consignee::create(['company_id' => $admin->company_id, 'name' => 'Consignor A', 'address' => 'Addr B']);
        $consignee = Consignee::create(['company_id' => $admin->company_id, 'name' => 'Consignee A', 'address' => 'Addr C']);
        $truckA = Truck::create(['transporter_id' => $transporterA->id, 'truck_number' => 'MH02BB2222', 'truck_type' => 'Trailer']);
        $truckB = Truck::create(['transporter_id' => $transporterB->id, 'truck_number' => 'MH03CC3333', 'truck_type' => 'Flatbed']);

        $lrA = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truckA->id,
            'transporter_id' => $transporterA->id,
            'reference_by' => $transporterA->id,
            'lr_number' => 'LR-MASTA-1',
            'gr_number' => 'GR-MASTA-1',
            'invoice_number' => 'INV-MASTA-1',
            'gr_date' => now()->toDateString(),
            'source_city' => 'Pune',
            'source_state' => 'Maharashtra',
            'destination_city' => 'Nagpur',
            'destination_state' => 'Maharashtra',
            'lane' => 'PUN-NAG',
            'good_type' => 'Machinery',
            'weight' => 20,
            'fr_rate' => 500,
            'amount' => 10000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'delivery_status' => 'Pending',
        ]);

        $bookingA = FreightBooking::create([
            'lr_id' => $lrA->id,
            'transporter_id' => $transporterA->id,
            'tr_fr_rate' => 500,
            'tr_amount' => 10000,
            'advance_amount' => 2000,
            'balance_amount' => 8000,
            'tds_amount' => 50,
            'company_id' => $admin->company_id,
            'transporter_tds_percentage' => 1,
            'voucher_number' => 'VB-MASTA-1',
        ]);

        $lrB = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truckB->id,
            'transporter_id' => $transporterB->id,
            'reference_by' => $transporterB->id,
            'lr_number' => 'LR-MASTB-1',
            'gr_number' => 'GR-MASTB-1',
            'invoice_number' => 'INV-MASTB-1',
            'gr_date' => now()->toDateString(),
            'source_city' => 'Surat',
            'source_state' => 'Gujarat',
            'destination_city' => 'Jaipur',
            'destination_state' => 'Rajasthan',
            'lane' => 'SUR-JAI',
            'good_type' => 'Textiles',
            'weight' => 15,
            'fr_rate' => 800,
            'amount' => 12000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'delivery_status' => 'Pending',
        ]);

        $bookingB = FreightBooking::create([
            'lr_id' => $lrB->id,
            'transporter_id' => $transporterB->id,
            'tr_fr_rate' => 800,
            'tr_amount' => 12000,
            'advance_amount' => 3000,
            'balance_amount' => 9000,
            'tds_amount' => 60,
            'company_id' => $admin->company_id,
            'transporter_tds_percentage' => 1,
            'voucher_number' => 'VB-MASTB-1',
        ]);

        $voucherPaymentA = VoucherPayment::create([
            'freight_booking_id' => $bookingA->id,
            'company_id' => $admin->company_id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 2000,
            'payment_status' => 'paid',
            'date' => now()->toDateString(),
        ]);

        $advancePaymentA = AdvancePayment::create([
            'transporter_id' => $transporterA->id,
            'company_id' => $admin->company_id,
            'advance_amount' => 1000,
            'payment_date' => now()->toDateString(),
        ]);

        $this->actingAs($userMasterA);

        // 1. freightBookings with default date range
        $resDefault = $this->get(route('transporter.freight_bookings.index'));
        $resDefault->assertOk();
        $resDefault->assertViewIs('transporter.freight_bookings');
        $this->assertEquals(1, $resDefault->viewData('bookings')->total());
        $this->assertEquals($bookingA->id, $resDefault->viewData('bookings')->first()->id);

        // 2. freightBookings with explicit date range filter
        $startDate = now()->subDays(10)->toDateString();
        $endDate = now()->addDays(10)->toDateString();
        $resFiltered = $this->get(route('transporter.freight_bookings.index', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));
        $resFiltered->assertOk();
        $resFiltered->assertViewIs('transporter.freight_bookings');
        $this->assertEquals(1, $resFiltered->viewData('bookings')->total());

        // 3. showFreightBooking for unauthorized booking (belonging to Master B) -> 403
        $resForbidden = $this->get(route('transporter.freight_bookings.show', $bookingB->id));
        $resForbidden->assertForbidden();

        // 4. showFreightBooking for authorized booking
        $resShow = $this->get(route('transporter.freight_bookings.show', $bookingA->id));
        $resShow->assertOk();
        $resShow->assertViewIs('transporter.freight_booking_show');
        $this->assertEquals($bookingA->id, $resShow->viewData('freightBooking')->id);

        // 5. payments with data
        $resPayments = $this->get(route('transporter.payments.index'));
        $resPayments->assertOk();
        $resPayments->assertViewIs('transporter.payments');
        $this->assertEquals(1, $resPayments->viewData('voucherPayments')->total());
        $this->assertEquals(1, $resPayments->viewData('advancePayments')->total());
    }

    public function test_store_transporter_request_validation_edge_cases(): void
    {
        $admin = $this->makeAdmin();
        $country1 = Country::create(['name' => 'India', 'code' => 'IN']);
        $country2 = Country::create(['name' => 'USA', 'code' => 'US']);
        $state1 = State::create(['name' => 'Gujarat', 'code' => 'GJ', 'country_id' => $country1->id]);
        $state2 = State::create(['name' => 'Texas', 'code' => 'TX', 'country_id' => $country2->id]);
        $city1 = City::create(['name' => 'Ahmedabad', 'state_id' => $state1->id, 'country_id' => $country1->id]);
        $city2 = City::create(['name' => 'Houston', 'state_id' => $state2->id, 'country_id' => $country2->id]);

        $master = TransporterMaster::create([
            'name' => 'Existing Master Agency',
            'pan_number' => 'ABCDE1111K',
            'pan_active' => true,
        ]);
        Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9998887771',
        ]);

        // Case 1: Line 78 - duplicate transporter registration for same company via transporter_master_id
        $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'mobile_number' => '9998887772',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ])->assertSessionHasErrors(['transporter_master_id' => 'This PAN is already registered as a transporter/agency for this company.']);

        // Case 2: Line 98 - Invalid PAN format (10 chars, but wrong format)
        $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'Invalid Pan Transporter',
            'pan_number' => '1234567890',
            'pan_active' => 1,
            'mobile_number' => '9998887773',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ])->assertSessionHasErrors(['pan_number' => 'The PAN number format is invalid.']);

        // Case 3: Line 119 - State does not belong to selected country
        $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'Country Mismatch Transporter',
            'pan_number' => 'ABCDE2222M',
            'pan_active' => 1,
            'country_id' => $country1->id,
            'state_id' => $state2->id,
            'mobile_number' => '9998887774',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ])->assertSessionHasErrors(['state_id' => 'The selected state does not belong to the selected country.']);

        // Case 4: Line 131 - City does not belong to selected state
        $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'name' => 'City Mismatch Transporter',
            'pan_number' => 'ABCDE3333N',
            'pan_active' => 1,
            'country_id' => $country1->id,
            'state_id' => $state1->id,
            'city_id' => $city2->id,
            'mobile_number' => '9998887775',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ])->assertSessionHasErrors(['city_id' => 'The selected city does not belong to the selected state.']);

        // Case 5: Line 146 - Non-existent transporter_master_id triggers after-validator early return
        $this->actingAs($admin)->post('/transporters', [
            'company_id' => $admin->company_id,
            'transporter_master_id' => 999999,
            'mobile_number' => '9998887776',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ])->assertSessionHasErrors('transporter_master_id');
    }

    public function test_update_transporter_request_validation_edge_cases(): void
    {
        $admin = $this->makeAdmin();

        $master = TransporterMaster::create([
            'name' => 'Valid Master Agency',
            'pan_number' => 'VALID1234A',
            'pan_active' => true,
        ]);

        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9998887779',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ]);

        // Explicitly bind 'transporter' parameter to return an instance with null transporterMaster
        $transporterWithoutMaster = clone $transporter;
        $transporterWithoutMaster->setRelation('transporterMaster', null);

        $this->app['router']->bind('transporter', function () use ($transporterWithoutMaster) {
            return $transporterWithoutMaster;
        });

        // Triggers UpdateTransporterRequest withValidator after-hook where $master is null (Line 63: return;)
        $this->actingAs($admin)->put("/transporters/{$transporter->id}", [
            'company_id' => $admin->company_id,
            'mobile_number' => '123',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ])->assertSessionHasErrors('mobile_number');

        // Also test directly via FormRequest to ensure complete isolation and execution
        $request = new \App\Http\Requests\UpdateTransporterRequest();
        $request->setUserResolver(fn () => $admin);
        $request->setRouteResolver(fn () => new class($transporterWithoutMaster) {
            public function __construct(private $transporter) {}
            public function parameter($name, $default = null) { return $this->transporter; }
        });
        $validator = \Illuminate\Support\Facades\Validator::make([
            'company_id' => $admin->company_id,
            'mobile_number' => '9998887779',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ], $request->rules());
        $request->withValidator($validator);
        $validator->validate();
        $this->assertFalse($validator->fails());
    }

    public function test_transporter_import_all_edge_cases(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        Country::creating(function ($country) {
            if (empty($country->code)) {
                $country->code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', (string) $country->name) . 'XX', 0, 2));
            }
        });

        $import = new TransporterImport();
        $ref = new \ReflectionClass(TransporterImport::class);

        // 1. parseBoolean direct testing (covers lines 401-414)
        $parseBool = $ref->getMethod('parseBoolean');
        $parseBool->setAccessible(true);
        $this->assertTrue($parseBool->invoke($import, true));
        $this->assertNull($parseBool->invoke($import, false)); // In PHP, (string) false is empty string so line 397 returns null
        $this->assertNull($parseBool->invoke($import, null));
        $this->assertNull($parseBool->invoke($import, ''));
        $this->assertTrue($parseBool->invoke($import, '1'));
        $this->assertTrue($parseBool->invoke($import, 'yes'));
        $this->assertTrue($parseBool->invoke($import, 'true'));
        $this->assertTrue($parseBool->invoke($import, 'y'));
        $this->assertTrue($parseBool->invoke($import, 'active'));
        $this->assertFalse($parseBool->invoke($import, '0'));
        $this->assertFalse($parseBool->invoke($import, 'no'));
        $this->assertFalse($parseBool->invoke($import, 'false'));
        $this->assertFalse($parseBool->invoke($import, 'n'));
        $this->assertFalse($parseBool->invoke($import, 'inactive'));
        $this->assertNull($parseBool->invoke($import, 'invalid_boolean_string'));

        // 2. validateRow direct testing (covers lines 298, 301)
        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber((int) $admin->company_id);
        $valMethod = $ref->getMethod('validateRow');
        $valMethod->setAccessible(true);

        // Dummy PAN validation bypass (line 298)
        $dummyErrors = $valMethod->invoke($import, [
            'pan_number' => $dummyPan,
            'transport_name' => 'Agency Dummy',
            'mobile_number' => '9876543210',
            'pan_active' => true,
            'country' => 'India',
            'state' => 'Gujarat',
            'city' => 'Ahmedabad',
            'declaration_given' => true,
            'tds_percentage' => 0,
        ], $dummyPan);
        $this->assertEmpty($dummyErrors);

        // Invalid regex PAN format (line 301)
        $regexErrors = $valMethod->invoke($import, [
            'pan_number' => '1234567890',
            'transport_name' => 'Agency Reg',
            'mobile_number' => '9876543210',
            'pan_active' => true,
            'country' => 'India',
            'state' => 'Gujarat',
            'city' => 'Ahmedabad',
            'declaration_given' => true,
            'tds_percentage' => 0,
        ], null);
        $this->assertContains('The PAN number format is invalid (e.g. ABCDE1234F).', $regexErrors);

        // 3. User without company_id (covers lines 38-42)
        $userWithoutCompany = User::factory()->create(['company_id' => null]);
        $this->actingAs($userWithoutCompany);
        $importNoComp = new TransporterImport();
        $importNoComp->collection(collect([
            collect(['name' => 'No Comp', 'pan_number' => 'ABCDE1111A', 'mobile_number' => '9999999999']),
        ]));
        $this->assertNotEmpty($importNoComp->errors);
        $this->assertEquals('Company could not be identified for the current user.', $importNoComp->errors[0]['errors'][0]);

        // 4. Back as admin: Pre-seed existing records for duplicates and master reuse
        $this->actingAs($admin);
        $existingMaster = TransporterMaster::create([
            'name' => 'Existing Company Master',
            'pan_number' => 'ABCDE7777K',
            'pan_active' => true,
        ]);
        Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $existingMaster->id,
            'is_owner' => 0,
            'mobile_number' => '9876599999',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ]);

        $reuseMaster = TransporterMaster::create([
            'name' => 'Other Master To Reuse',
            'pan_number' => 'ABCDE8888M',
            'pan_active' => true,
        ]);

        // Country/State/City mismatch pre-seed to hit ?? $stateQuery->first() and ?? $cityQuery->first()
        $otherCountry = Country::create(['name' => 'Otherland', 'code' => 'OL']);
        $diffState = State::create(['name' => 'DiffState', 'country_id' => $otherCountry->id]);
        $diffCity = City::create(['name' => 'DiffCity', 'state_id' => $diffState->id, 'country_id' => $otherCountry->id]);
        $targetCountry = Country::create(['name' => 'Targetland', 'code' => 'TL']);

        $importRows = collect([
            // Row 1: Reusing existing master (lines 185-187), new Country, State, City creation (lines 126, 143, 166)
            collect([
                'name' => 'Imported With Reused Master',
                'pan_number' => 'ABCDE8888M',
                'pan_active' => '1',
                'mobile_number' => '9876511111',
                'country' => 'AutocreatedCountry',
                'state' => 'AutocreatedState',
                'city' => 'AutocreatedCity',
                'declaration_given' => 'Yes',
                'tds_percentage' => '2',
                'email' => 'import1@example.com',
                'gst_number' => '24ABCDE8888M1Z5',
                'address' => 'Sample Address',
                'short_name' => 'Short 1',
                'advance_amount' => '1500',
                'tds_adjustment_remark' => 'Remark 1',
            ]),
            // Row 2: Duplicate mobile in file (lines 77-81)
            collect([
                'name' => 'Dup Mobile in File',
                'pan_number' => 'ABCDE9999N',
                'pan_active' => 'Yes',
                'mobile_number' => '9876511111', // same as row 1
                'country' => 'AutocreatedCountry',
                'state' => 'AutocreatedState',
                'city' => 'AutocreatedCity',
                'declaration_given' => 'Yes',
                'tds_percentage' => '0',
            ]),
            // Row 3: PAN already exists for this company in DB (lines 96-101)
            collect([
                'name' => 'PAN Already in Company',
                'pan_number' => 'ABCDE7777K', // already in company
                'pan_active' => 'Yes',
                'mobile_number' => '9876522222',
                'country' => 'AutocreatedCountry',
                'state' => 'AutocreatedState',
                'city' => 'AutocreatedCity',
                'declaration_given' => 'Yes',
                'tds_percentage' => '0',
            ]),
            // Row 4: Mobile already exists for this company in DB (lines 110-114)
            collect([
                'name' => 'Mobile Already in Company',
                'pan_number' => 'ABCDE3333X',
                'pan_active' => 'Yes',
                'mobile_number' => '9876599999', // already in company
                'country' => 'AutocreatedCountry',
                'state' => 'AutocreatedState',
                'city' => 'AutocreatedCity',
                'declaration_given' => 'Yes',
                'tds_percentage' => '0',
            ]),
            // Row 5: Dummy PAN row (covers lines 45, 68, 87, 185, 220)
            collect([
                'name' => 'Dummy PAN Agency 1',
                'pan_number' => $dummyPan,
                'pan_active' => 'Yes',
                'mobile_number' => '9876533333',
                'country' => 'AutocreatedCountry',
                'state' => 'AutocreatedState',
                'city' => 'AutocreatedCity',
                'declaration_given' => 'No',
                'tds_percentage' => '0',
            ]),
            // Row 6: Another Dummy PAN row (multiple dummy PAN allowed)
            collect([
                'name' => 'Dummy PAN Agency 2',
                'pan_number' => $dummyPan,
                'pan_active' => '0',
                'mobile_number' => '9876544444',
                'country' => 'Targetland',
                'state' => 'DiffState', // state in Otherland, but requested with Targetland -> triggers ?? $stateQuery->first()
                'city' => 'DiffCity',   // city in DiffState, requested with state -> triggers ?? $cityQuery->first()
                'declaration_given' => 'No',
                'tds_percentage' => '0',
            ]),
        ]);

        $importMain = new TransporterImport();
        $importMain->collection($importRows);

        $this->assertGreaterThanOrEqual(3, $importMain->successCount);
        $this->assertNotEmpty($importMain->errors);

        // 5. Trigger catch block (\Throwable $e) at lines 226-231
        $dispatcher = Transporter::getEventDispatcher();
        try {
            Transporter::saving(function () {
                throw new \Exception('Forced transporter create error');
            });

            $importError = new TransporterImport();
            $importError->collection(collect([
                collect([
                    'name' => 'Error Co',
                    'pan_number' => 'ABCDE5555Y',
                    'pan_active' => 'Yes',
                    'mobile_number' => '9876566666',
                    'country' => 'India',
                    'state' => 'Gujarat',
                    'city' => 'Ahmedabad',
                    'declaration_given' => 'Yes',
                    'tds_percentage' => '0',
                ]),
            ]));

            $this->assertStringContainsString('Unable to create transporter: Forced transporter create error', $importError->errors[0]['errors'][0]);
        } finally {
            Transporter::setEventDispatcher($dispatcher);
        }

        // 6. Test fallback location branches: lines 138, 150, 161, 174, 177
        $stateOnlyCountry = Country::create(['name' => 'StateOnlyCountry', 'code' => 'SO']);
        $stateOnly = State::create(['name' => 'StateOnly', 'country_id' => $stateOnlyCountry->id]);

        $cityOnlyCountry = Country::create(['name' => 'CityOnlyCountry', 'code' => 'CO']);
        $cityOnlyState = State::create(['name' => 'CityOnlyState', 'country_id' => $cityOnlyCountry->id]);
        $cityOnly = City::create(['name' => 'CityOnly', 'state_id' => $cityOnlyState->id, 'country_id' => $cityOnlyCountry->id]);

        $realValidator = \Illuminate\Support\Facades\Validator::getFacadeRoot();
        try {
            $validatorFactory = \Mockery::mock(\Illuminate\Validation\Factory::class);
            $mockValidator = \Mockery::mock(\Illuminate\Validation\Validator::class);
            $mockValidator->shouldReceive('fails')->andReturn(false);
            $validatorFactory->shouldReceive('make')->andReturn($mockValidator);
            \Illuminate\Support\Facades\Validator::swap($validatorFactory);

            $importFallback = new TransporterImport();
            $importFallback->collection(collect([
                // Row with no country, only state -> triggers lines 138, 150
                collect([
                    'name' => 'Fallback State',
                    'pan_number' => 'ABCDE6666Z',
                    'pan_active' => true,
                    'mobile_number' => '9876577777',
                    'country' => '',
                    'state' => 'StateOnly',
                    'city' => '',
                    'declaration_given' => true,
                    'tds_percentage' => 0,
                ]),
                // Row with no country and no state, only city -> triggers lines 161, 174, 177
                collect([
                    'name' => 'Fallback City',
                    'pan_number' => 'ABCDE7777W',
                    'pan_active' => true,
                    'mobile_number' => '9876588888',
                    'country' => '',
                    'state' => '',
                    'city' => 'CityOnly',
                    'declaration_given' => true,
                    'tds_percentage' => 0,
                ]),
            ]));
        } finally {
            \Illuminate\Support\Facades\Validator::swap($realValidator);
        }
    }

    public function test_transporter_tds_refresh_service_all_edge_cases(): void
    {
        $admin = $this->makeAdmin();
        $service = app(\App\Services\TransporterTdsRefresh::class);

        // 1. Dummy PAN transporter: calculatedPercentage returns 0.00 (line 21) & hasMismatch returns false (line 49)
        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber((int) $admin->company_id);
        $dummyMaster = TransporterMaster::create([
            'name' => 'Dummy TDS Master',
            'pan_number' => $dummyPan,
            'pan_active' => true,
        ]);
        $dummyTransporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $dummyMaster->id,
            'is_owner' => 0,
            'mobile_number' => '9988112233',
            'declaration_given' => false,
            'tds_percentage' => 20,
        ]);

        $this->assertFalse($service->hasMismatch($dummyTransporter));
        $this->assertEquals(0.00, $service->calculatedPercentage($dummyTransporter));

        // 2. isDummyPan edge cases via reflection (lines 34, 38)
        $ref = new \ReflectionClass(\App\Services\TransporterTdsRefresh::class);
        $isDummyPanMethod = $ref->getMethod('isDummyPan');
        $isDummyPanMethod->setAccessible(true);

        // Line 34: panNumber is null or empty string
        $this->assertFalse($isDummyPanMethod->invoke($service, $dummyTransporter, null));
        $this->assertFalse($isDummyPanMethod->invoke($service, $dummyTransporter, ''));

        // Line 38: transporter has no company_id
        $noCompTransporter = new Transporter(['company_id' => null]);
        $this->assertFalse($isDummyPanMethod->invoke($service, $noCompTransporter, 'ABCDE1234F'));
        $this->assertFalse($isDummyPanMethod->invoke($service, $noCompTransporter, $dummyPan));
    }

    public function test_transporter_tds_calculator_all_branches(): void
    {
        $calculator = app(\App\Services\TransporterTdsCalculator::class);

        // 1. Declaration given -> 0% (line 14)
        $this->assertEquals(0.00, $calculator->calculate(true, 'ABCP1234F', true));
        $this->assertEquals(0.00, $calculator->calculate(true, '', false));

        // 2. Pan inactive -> default 20% (line 18)
        $this->assertEquals(20.00, $calculator->calculate(false, 'ABCP1234F', false));

        // 3. Pan length < 4 -> default 20% (line 22)
        $this->assertEquals(20.00, $calculator->calculate(false, 'ABC', true));
        $this->assertEquals(20.00, $calculator->calculate(false, '', true));

        // 4. Fourth char P or H -> owner 1% (line 28)
        $this->assertEquals(1.00, $calculator->calculate(false, 'ABCP1234F', true));
        $this->assertEquals(1.00, $calculator->calculate(false, 'abch1234f', true));

        // 5. Fourth char C, F, A, B, J, L, T -> agency 2% (line 32)
        foreach (['C', 'F', 'A', 'B', 'J', 'L', 'T', 'c', 'f'] as $char) {
            $this->assertEquals(2.00, $calculator->calculate(false, "ABC{$char}1234F", true));
        }

        // 6. Fourth char else -> default 20% (line 35)
        $this->assertEquals(20.00, $calculator->calculate(false, 'ABCX1234F', true));
    }

    public function test_transporter_advance_ledger_service_flows(): void
    {
        $admin = $this->makeAdmin();

        $master = TransporterMaster::create([
            'name' => 'Ledger Master',
            'pan_number' => 'ABCDE9876Q',
            'pan_active' => true,
        ]);
        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'is_owner' => 0,
            'mobile_number' => '9876501234',
            'declaration_given' => false,
            'tds_percentage' => 20,
            'advance_amount' => 500,
        ]);

        $mockNotification = \Mockery::mock(\App\Services\Notifications\AdvancePaymentNotificationService::class);
        $mockNotification->shouldReceive('notifyAdvancePaid')
            ->once()
            ->with(\Mockery::type(\App\Models\AdvancePayment::class), $admin);

        $service = new \App\Services\TransporterAdvanceLedgerService($mockNotification);

        // 1. Successful positive advance with notification (lines 35-49)
        $payment = $service->recordAdvancePayment(
            (int) $admin->company_id,
            $transporter->id,
            250.00,
            '2026-10-23',
            'Advance for fuel',
            $admin,
            true
        );

        $this->assertEquals(250.00, $payment->advance_amount);
        $this->assertEquals('Advance for fuel', $payment->remark);
        $this->assertEquals(750.00, (float) $transporter->fresh()->advance_amount);

        // 2. Deduction with negative amount and notifyCredit = false (line 46 evaluates to false)
        $deductionPayment = $service->recordAdvancePayment(
            (int) $admin->company_id,
            $transporter->id,
            -200.00,
            '2026-10-23',
            'Advance recovery',
            $admin,
            false
        );
        $this->assertEquals(-200.00, $deductionPayment->advance_amount);
        $this->assertEquals(550.00, (float) $transporter->fresh()->advance_amount);

        // 3. Deduction exceeds balance -> ValidationException (lines 29-33)
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->expectExceptionMessage('Deduction exceeds available book balance of 550.00.');
        $service->recordAdvancePayment(
            (int) $admin->company_id,
            $transporter->id,
            -600.00,
            '2026-10-23'
        );
    }

    public function test_transporter_report_export_full_coverage(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        PaymentMode::create(['name' => 'Cash']);
        PaymentMode::create(['name' => 'Bank Transfer']);

        $transporter = Transporter::factory()->forCompany($admin->company_id)->create();
        $refTransporter = Transporter::factory()->forCompany($admin->company_id)->create();
        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01XY9999',
            'truck_type' => 'Flatbed',
        ]);
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Report Customer',
        ]);

        // LR 1: All relations present, with unloading_weight
        $lr1 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $refTransporter->id,
            'lr_number' => 'LR-FULL-01',
            'gr_number' => 'GR-FULL-01',
            'source_city' => 'Ahmedabad',
            'source_state' => 'GJ',
            'destination_city' => 'Surat',
            'destination_state' => 'GJ',
            'lane' => 'West',
            'good_type' => 'Steel',
            'memo_id' => 5001,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'fr_rate' => 100,
            'amount' => 100,
            'weight' => 20.5,
            'unloading_weight' => 20.2,
            'no_of_units' => 50,
            'gr_date' => now()->toDateString(),
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr1->id,
            'company_id' => $admin->company_id,
            'transporter_id' => $transporter->id,
            'voucher_number' => 8888,
            'tr_fr_rate' => 500.00,
            'tr_amount' => 5000.00,
            'balance_amount' => 4500.00,
            'tds_amount' => 50.00,
            'damage_amount' => 10.00,
            'shortage_amount' => 20.00,
            'misc_expense' => 30.00,
            'negative_adjusted_amount' => 15.00,
        ]);

        $lr1->freight_booking_id = $booking->id;
        $lr1->save();

        // Voucher with valid payment mode ('Cash')
        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-TR-EX-1',
            'payment_type' => 'cash',
            'payment_mode' => 'Cash',
            'amount' => 500.00,
            'payment_status' => 'paid',
            'company_id' => $admin->company_id,
        ]);

        // Voucher with payment mode NOT in paymentNames (triggers !isset branch)
        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-TR-EX-2',
            'payment_type' => 'other',
            'payment_mode' => 'CryptoUnknown',
            'amount' => 100.00,
            'payment_status' => 'paid',
            'company_id' => $admin->company_id,
        ]);

        // LR 2: Without active freightBooking ($booking2 soft-deleted so $fr is null -> lines 83-86), null relations ('N/A' fallbacks)
        $lr2 = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => 999999, // Non-existent consignor -> 'N/A'
            'consignee_id' => 999999, // Non-existent consignee -> 'N/A'
            'truck_id' => null, // null truck -> 'N/A'
            'transporter_id' => $transporter->id,
            'reference_by' => $refTransporter->id,
            'lr_number' => 'LR-NULL-02',
            'gr_number' => 'GR-NULL-02',
            'source_city' => 'Ahmedabad',
            'source_state' => 'GJ',
            'destination_city' => 'Vadodara',
            'destination_state' => 'GJ',
            'lane' => 'West',
            'good_type' => 'Steel',
            'memo_id' => 5002,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'fr_rate' => 100,
            'amount' => 100,
            'weight' => 15.0,
            'unloading_weight' => null, // falls back to weight
            'no_of_units' => null,
            'gr_date' => now()->toDateString(),
        ]);

        $booking2 = FreightBooking::create([
            'lr_id' => $lr2->id,
            'company_id' => $admin->company_id,
            'transporter_id' => $transporter->id,
            'voucher_number' => 8889,
            'tr_fr_rate' => 300.00,
            'tr_amount' => 3000.00,
            'balance_amount' => 3000.00,
        ]);
        $lr2->freight_booking_id = $booking2->id;
        $lr2->save();
        $booking2->delete(); // Soft delete so relation returns null

        $start = now()->subDays(5)->toDateString();
        $end = now()->addDays(5)->toDateString();

        // 1. Export with specific transporterId, truckId, and referenceBy filters
        $exportWithFilters = new TransporterReportExport($start, $end, $transporter->id, $truck->id, $refTransporter->id);
        $filteredData = $exportWithFilters->collection();
        $this->assertCount(1, $filteredData);
        $row1 = $filteredData->first();
        $this->assertEquals('LR-FULL-01', $row1[1]);
        $this->assertEquals('GJ01XY9999', $row1[3]);
        $this->assertEquals('Report Customer', $row1[8]);
        $this->assertEquals('Report Customer', $row1[9]);

        // 2. Export without filters -> retrieves both LRs
        $exportNoFilters = new TransporterReportExport($start, $end, null, null, null);
        $allData = $exportNoFilters->collection();
        $this->assertCount(2, $allData);

        // Check LR 2 (soft-deleted booking -> lines 83-86, null truck/consignor/consignee)
        $row2 = $allData->firstWhere(fn ($r) => $r[1] === 'LR-NULL-02');
        $this->assertNotNull($row2);
        $this->assertEquals('N/A', $row2[3]); // truck
        $this->assertEquals(15.0, $row2[6]); // unloading_weight fallback to weight
        $this->assertEquals('N/A', $row2[8]); // consignor
        $this->assertEquals('N/A', $row2[9]); // consignee

        // Check headings & columnFormats
        $headings = $exportNoFilters->headings();
        $this->assertContains('Cash', $headings);
        $this->assertContains('Bank Transfer', $headings);
        $this->assertContains('Damage Amount', $headings);

        $formats = $exportNoFilters->columnFormats();
        $this->assertArrayHasKey('A', $formats);
        $this->assertArrayHasKey('F', $formats);
        $this->assertArrayHasKey('G', $formats);

        // 3. Auth user without company (company_id = null) to cover !company branch
        $userWithoutCompany = User::factory()->create([
            'user_role_id' => 1,
            'company_id' => null,
        ]);
        $this->actingAs($userWithoutCompany);

        $exportNoCompany = new TransporterReportExport($start, $end, null, null, null);
        $noCompanyData = $exportNoCompany->collection();
        $this->assertNotEmpty($noCompanyData);
    }
}

