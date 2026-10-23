<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Driver;
use App\Models\GoodType;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokerMastersManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        if (! UserRole::where('id', 3)->exists()) {
            UserRole::create(['id' => 1, 'name' => 'master_admin']);
            UserRole::create(['id' => 2, 'name' => 'super_admin']);
            UserRole::create(['id' => 3, 'name' => 'admin']);
            UserRole::create(['id' => 4, 'name' => 'moderator']);
        }
    }

    private function createAdminWithCompany(): array
    {
        $admin = User::factory()->create([
            'user_role_id' => 3,
        ]);

        $company = Company::create([
            'name' => 'Prime Transport Network',
            'short_name' => 'PTN',
            'user_id' => $admin->id,
            'is_blocked' => false,
        ]);

        $admin->company_id = $company->id;
        $admin->save();

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => 50,
        ]);

        return [$admin, $company];
    }

    // -------------------------------------------------------------
    // GOODS TYPE TESTS
    // -------------------------------------------------------------

    public function test_goods_type_index_loads_view(): void
    {
        [$admin] = $this->createAdminWithCompany();

        $response = $this->actingAs($admin)->get('/master/goods-types');
        $response->assertOk()->assertViewIs('master.goods-types');
    }

    public function test_goods_type_datatable_ajax_returns_json(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();
        GoodType::create(['name' => 'Steel Rods', 'user_id' => $company->user_id]);
        GoodType::create(['name' => 'Cement Bags', 'user_id' => $company->user_id]);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/goods-types?draw=1&start=0&length=10');

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_goods_type_store_and_duplicate_validation(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        // 1. Success store
        $response = $this->actingAs($admin)->post('/master/goods-types', [
            'name' => 'Electronics Goods',
        ]);
        $response->assertRedirect(route('master.goods-types.index'));
        $this->assertDatabaseHas('good_types', [
            'name' => 'Electronics Goods',
            'user_id' => $company->user_id,
        ]);

        // 2. Reject duplicate name within same company
        $duplicateResponse = $this->actingAs($admin)->post('/master/goods-types', [
            'name' => 'Electronics Goods',
        ]);
        $duplicateResponse->assertSessionHasErrors(['name']);
    }

    public function test_goods_type_ajax_quick_add_modal(): void
    {
        [$admin] = $this->createAdminWithCompany();

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/master/goods-types', [
                'name' => 'Automobile Parts',
                'from_modal' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('goodsType.name', 'Automobile Parts');
    }

    public function test_goods_type_update_and_destroy_with_remark(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();
        $gt = GoodType::create(['name' => 'Timber Logs', 'user_id' => $company->user_id]);

        // Edit view
        $editResponse = $this->actingAs($admin)->get("/master/goods-types/{$gt->id}/edit");
        $editResponse->assertOk()->assertViewIs('master.goods-types-form');

        // Update
        $updateResponse = $this->actingAs($admin)->put("/master/goods-types/{$gt->id}", [
            'name' => 'Treated Timber Logs',
        ]);
        $updateResponse->assertRedirect(route('master.goods-types.index'));
        $this->assertSame('Treated Timber Logs', $gt->fresh()->name);

        // Destroy requires delete_remark
        $failDestroy = $this->actingAs($admin)->delete("/master/goods-types/{$gt->id}", []);
        $failDestroy->assertSessionHasErrors(['delete_remark']);

        // Destroy success
        $destroyResponse = $this->actingAs($admin)->delete("/master/goods-types/{$gt->id}", [
            'delete_remark' => 'No longer transported',
        ]);
        $destroyResponse->assertRedirect(route('master.goods-types.index'));
        $this->assertSoftDeleted('good_types', ['id' => $gt->id]);
    }

    public function test_goods_type_bulk_upload_template_download(): void
    {
        [$admin] = $this->createAdminWithCompany();

        $response = $this->actingAs($admin)->get('/master/goods-types/bulk-upload/template');
        $response->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=goods_type_template.csv');
    }

    // -------------------------------------------------------------
    // PUMP TESTS
    // -------------------------------------------------------------

    public function test_pump_index_and_datatable_ajax(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();
        Pump::create([
            'pump_name' => 'Indian Oil Highway Depot',
            'contact_name' => 'Suraj Singh',
            'contact_number' => '9820011223',
            'user_id' => $company->user_id,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=1&start=0&length=10');

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_pump_store_and_contact_number_unique_validation(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        $response = $this->actingAs($admin)->post('/master/pumps', [
            'pump_name' => 'Bharat Petroleum NH8',
            'contact_name' => 'Mahesh Verma',
            'contact_number' => '9876543210',
            'remark' => 'Bulk diesel discounts available',
        ]);

        $response->assertRedirect(route('master.pump.index'));
        $this->assertDatabaseHas('pumps', [
            'pump_name' => 'Bharat Petroleum NH8',
            'contact_number' => '9876543210',
            'user_id' => $company->user_id,
        ]);

        // Duplicate contact number within same company
        $dupResponse = $this->actingAs($admin)->post('/master/pumps', [
            'pump_name' => 'Different Pump Name',
            'contact_name' => 'Another Contact',
            'contact_number' => '9876543210',
        ]);
        $dupResponse->assertSessionHasErrors(['contact_number']);
    }

    public function test_pump_ajax_store_returns_json(): void
    {
        [$admin] = $this->createAdminWithCompany();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/master/pumps', [
                'pump_name' => 'HPCL Highway Plaza',
                'contact_name' => 'Rajesh Joshi',
                'contact_number' => '9811122233',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('pump.pump_name', 'HPCL Highway Plaza');
    }

    public function test_pump_edit_update_and_destroy(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();
        $pump = Pump::create([
            'pump_name' => 'Shell City Fuel',
            'contact_name' => 'Karan Rao',
            'contact_number' => '9988776655',
            'user_id' => $company->user_id,
        ]);

        $editResponse = $this->actingAs($admin)->get("/master/pumps/{$pump->id}/edit");
        $editResponse->assertOk()->assertViewIs('master.pump-form');

        $updateResponse = $this->actingAs($admin)->put("/master/pumps/{$pump->id}", [
            'pump_name' => 'Shell Premium Fuel Station',
            'contact_name' => 'Karan Rao',
            'contact_number' => '9988776655',
        ]);
        $updateResponse->assertRedirect(route('master.pump.index'));
        $this->assertSame('Shell Premium Fuel Station', $pump->fresh()->pump_name);

        $destroyResponse = $this->actingAs($admin)->delete("/master/pumps/{$pump->id}", [
            'delete_remark' => 'Closed operations',
        ]);
        $destroyResponse->assertRedirect(route('master.pump.index'));
        $this->assertSoftDeleted('pumps', ['id' => $pump->id]);
    }

    public function test_pump_bulk_upload_template_download(): void
    {
        [$admin] = $this->createAdminWithCompany();

        $response = $this->actingAs($admin)->get('/master/pumps/bulk-upload/template');
        $response->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=pump_template.csv');
    }

    // -------------------------------------------------------------
    // TRUCK DRIVER & TRANSPORTER ASSIGNMENTS
    // -------------------------------------------------------------

    public function test_assign_driver_to_truck(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'MH04DE5678',
            'truck_type' => 'Container',
        ]);

        $driver = Driver::create([
            'company_id' => $company->id,
            'user_id' => $company->user_id,
            'name' => 'Balwinder Singh',
            'mobile_number' => '9833445566',
        ]);

        $response = $this->actingAs($admin)->put("/trucks/{$truck->id}/assign-driver", [
            'driver_id' => $driver->id,
        ]);

        $response->assertRedirect();
        $this->assertSame($driver->id, $truck->fresh()->driver_id);
    }

    public function test_assign_and_remove_transporter_from_truck(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        $master = TransporterMaster::factory()->create(['name' => 'Express Logistics']);
        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '9900112233',
            'is_owner' => 1,
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'DL01AA9999',
            'truck_type' => 'Trailer',
            'transporter_id' => null,
        ]);

        // Assign Transporter via assignOwner
        $assignResponse = $this->actingAs($admin)->post('/assign-owner', [
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
        ]);
        $assignResponse->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame($transporter->id, $truck->fresh()->transporter_id);

        // Remove Transporter
        $removeResponse = $this->actingAs($admin)->patch("/trucks/{$truck->id}/remove-transporter");
        $removeResponse->assertOk()
            ->assertJsonStructure(['success']);
        $this->assertNull($truck->fresh()->transporter_id);
    }

    public function test_transporter_refresh_tds_endpoint(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        $master = TransporterMaster::factory()->create([
            'pan_number' => 'AAAP0000A', // 4th char P -> 1%
            'pan_active' => true,
        ]);
        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
            'declaration_given' => false,
            'tds_percentage' => 20.0, // initially 20%
        ]);

        $response = $this->actingAs($admin)
            ->post("/transporters/{$transporter->id}/refresh-tds");

        $response->assertRedirect();
        $this->assertEquals(1.0, (float) $transporter->fresh()->tds_percentage);
    }
}
