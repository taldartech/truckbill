<?php

namespace Tests\Feature\Master;

use App\Http\Controllers\PumpController;
use App\Imports\GoodsTypeImport;
use App\Imports\PumpImport;
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
use Illuminate\Http\UploadedFile;
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
        $this->assertStringContainsString('Goods Name', $response->streamedContent());
    }

    public function test_goods_type_controller_all_remaining_branches(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        // 1. Create view
        $createResponse = $this->actingAs($admin)->get('/master/goods-types/create');
        $createResponse->assertOk()->assertViewIs('master.goods-types-form');

        // 2. Search and sorting in getGoodsTypesData
        $gt1 = GoodType::create(['name' => 'Aluminium Sheets', 'user_id' => $company->user_id]);
        $gt2 = GoodType::create(['name' => 'Copper Cables', 'user_id' => $company->user_id]);

        // 2a. Global search by name
        $resGlobal = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/goods-types?draw=2&start=0&length=10&search[value]=Aluminium');
        $resGlobal->assertOk()->assertJsonPath('recordsFiltered', 1);

        // 2b. Column specific search: column 1 (name)
        $resCol1 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/goods-types?draw=3&start=0&length=10&columns[1][search][value]=Copper');
        $resCol1->assertOk()->assertJsonPath('recordsFiltered', 1);

        // 2c. Sorting column 0 (id desc)
        $resSort0 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/goods-types?draw=4&order[0][column]=0&order[0][dir]=desc');
        $resSort0->assertOk();

        // 2d. Sorting column 1 (name asc)
        $resSort1 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/goods-types?draw=5&order[0][column]=1&order[0][dir]=asc');
        $resSort1->assertOk();

        // 2e. Sorting default column (e.g. column 99)
        $resSortDef = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/goods-types?draw=6&order[0][column]=99&order[0][dir]=asc');
        $resSortDef->assertOk();

        // 3. Bulk Upload
        // 3a. Validation failure (missing file)
        $resNoFile = $this->actingAs($admin)->postJson('/master/goods-types/bulk-upload', []);
        $resNoFile->assertStatus(422)->assertJsonValidationErrors(['file']);

        // 3b. Valid file upload with valid row + invalid row (missing name)
        $csvHeader = "Goods Name,Remark\n";
        $csvValidRow = "Plastic Granules,Valid\n";
        $csvInvalidRow = ",Missing Name\n";
        $file = UploadedFile::fake()->createWithContent('goods_types.csv', $csvHeader . $csvValidRow . $csvInvalidRow);
        $resUpload = $this->actingAs($admin)->postJson('/master/goods-types/bulk-upload', [
            'file' => $file,
        ]);
        $resUpload->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('failed', 1);

        $this->assertDatabaseHas('good_types', [
            'name' => 'Plastic Granules',
            'user_id' => $company->user_id,
        ]);

        // 3c. Exception handling in bulk upload catch block (corrupt file)
        $corruptFile = UploadedFile::fake()->createWithContent('corrupt.xlsx', 'invalid zip binary content');
        $resErr = $this->actingAs($admin)->postJson('/master/goods-types/bulk-upload', [
            'file' => $corruptFile,
        ]);
        $resErr->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bulk upload failed.');
    }

    public function test_goods_type_import_full_flow_and_all_edge_cases(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();
        $this->actingAs($admin);

        $import = new GoodsTypeImport();

        // 1. chunkSize
        $this->assertSame(500, $import->chunkSize());

        // 2. Direct collection() call
        GoodType::create(['name' => 'Existing Timber', 'user_id' => $company->user_id]);

        $rows = collect([
            // Valid row with 'name'
            collect(['name' => 'Textile Rolls']),
            // Valid row with alternative header 'goods_type_name'
            collect(['goods_type_name' => 'Iron Ingots']),
            // Invalid row: missing name
            collect(['name' => '']),
            // Invalid row: duplicate name
            collect(['name' => 'Existing Timber']),
        ]);

        $import->collection($rows);

        $this->assertSame(2, $import->imported);
        $this->assertCount(2, $import->errors);
        $this->assertSame(4, $import->errors[0]['row']);
        $this->assertSame(5, $import->errors[1]['row']);

        // 3. Reflection tests for normalizeRow()
        $refMethod = new \ReflectionMethod(GoodsTypeImport::class, 'normalizeRow');
        $refMethod->setAccessible(true);

        // Alternative key: goods_name
        $norm1 = $refMethod->invoke($import, ['goods_name' => 'Machinery']);
        $this->assertSame('Machinery', $norm1['name']);

        // Alternative key: goods_type
        $norm2 = $refMethod->invoke($import, ['goods_type' => 'Coal']);
        $this->assertSame('Coal', $norm2['name']);

        // Empty row
        $normEmpty = $refMethod->invoke($import, []);
        $this->assertNull($normEmpty['name'] ?? null);
    }

    public function test_good_type_model_attributes_traits_and_soft_deletes(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        // 1. Fillable attributes check
        $goodType = new GoodType();
        $this->assertEquals([
            'user_id',
            'name',
            'deleted_by',
            'deleted_remark',
        ], $goodType->getFillable());

        // 2. Creation and attribute persistence
        $goodType = GoodType::create([
            'user_id' => $company->user_id,
            'name' => 'Industrial Chemicals',
            'deleted_by' => null,
            'deleted_remark' => null,
        ]);

        $this->assertDatabaseHas('good_types', [
            'id' => $goodType->id,
            'name' => 'Industrial Chemicals',
            'user_id' => $company->user_id,
        ]);
        $this->assertFalse($goodType->trashed());

        // 3. Update attributes
        $goodType->update([
            'name' => 'Specialized Chemicals',
            'deleted_remark' => 'Updated remark test',
        ]);
        $this->assertSame('Specialized Chemicals', $goodType->fresh()->name);
        $this->assertSame('Updated remark test', $goodType->fresh()->deleted_remark);

        // 4. Soft delete, withTrashed & onlyTrashed scopes
        $goodType->deleted_by = $admin->id;
        $goodType->deleted_remark = 'Product line discontinued';
        $goodType->save();
        $goodType->delete();

        $this->assertTrue($goodType->trashed());
        $this->assertSoftDeleted('good_types', [
            'id' => $goodType->id,
            'deleted_by' => $admin->id,
            'deleted_remark' => 'Product line discontinued',
        ]);

        $this->assertCount(0, GoodType::where('id', $goodType->id)->get());
        $this->assertCount(1, GoodType::withTrashed()->where('id', $goodType->id)->get());
        $this->assertCount(1, GoodType::onlyTrashed()->where('id', $goodType->id)->get());

        // 5. Restore soft-deleted model
        $goodType->restore();
        $this->assertFalse($goodType->trashed());
        $this->assertNotSoftDeleted('good_types', ['id' => $goodType->id]);
        $this->assertCount(1, GoodType::where('id', $goodType->id)->get());

        // 6. Force delete model permanently
        $goodType->forceDelete();
        $this->assertDatabaseMissing('good_types', ['id' => $goodType->id]);
        $this->assertCount(0, GoodType::withTrashed()->where('id', $goodType->id)->get());
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
        $this->assertStringContainsString('Pump Name', $response->streamedContent());
    }

    public function test_pump_controller_all_remaining_branches(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        // 1. Non-AJAX index view
        $indexResponse = $this->actingAs($admin)->get('/master/pumps');
        $indexResponse->assertOk()->assertViewIs('master.pump');

        // 2. Create view
        $createResponse = $this->actingAs($admin)->get('/master/pumps/create');
        $createResponse->assertOk()->assertViewIs('master.pump-form');

        // 3. Search and sorting branches in getPumpsData
        $pump1 = Pump::create([
            'pump_name' => 'Bharat Fuels Alpha',
            'contact_name' => 'Rakesh Patel',
            'contact_number' => '9800000001',
            'user_id' => $company->user_id,
        ]);
        $pump2 = Pump::create([
            'pump_name' => 'Indian Oil Beta',
            'contact_name' => 'Suresh Shah',
            'contact_number' => '9800000002',
            'user_id' => $company->user_id,
        ]);

        // 3a. Global search by pump_name, contact_name, contact_number
        $resGlobal = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=2&start=0&length=10&search[value]=Bharat');
        $resGlobal->assertOk()->assertJsonPath('recordsFiltered', 1);

        // 3b. Column specific search: column 1 (pump_name)
        $resCol1 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=3&start=0&length=10&columns[1][search][value]=Indian');
        $resCol1->assertOk()->assertJsonPath('recordsFiltered', 1);

        // 3c. Column specific search: column 2 (contact_name)
        $resCol2 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=4&start=0&length=10&columns[2][search][value]=Suresh');
        $resCol2->assertOk()->assertJsonPath('recordsFiltered', 1);

        // 3d. Column specific search: column 3 (contact_number)
        $resCol3 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=5&start=0&length=10&columns[3][search][value]=9800000001');
        $resCol3->assertOk()->assertJsonPath('recordsFiltered', 1);

        // 3e. Sorting column 0 (id desc)
        $resSort0 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=6&order[0][column]=0&order[0][dir]=desc');
        $resSort0->assertOk();

        // 3f. Sorting column 1 (pump_name asc)
        $resSort1 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=7&order[0][column]=1&order[0][dir]=asc');
        $resSort1->assertOk();

        // 3g. Sorting column 2 (contact_name desc)
        $resSort2 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=8&order[0][column]=2&order[0][dir]=desc');
        $resSort2->assertOk();

        // 3h. Sorting column 3 (contact_number asc)
        $resSort3 = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=9&order[0][column]=3&order[0][dir]=asc');
        $resSort3->assertOk();

        // 3i. Sorting default column (e.g. column 99)
        $resSortDef = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/master/pumps?draw=10&order[0][column]=99&order[0][dir]=asc');
        $resSortDef->assertOk();

        // 4. show() method
        $showResponse = app(PumpController::class)->show($pump1);
        $this->assertSame($pump1->id, $showResponse->getData()->id);

        // 5. Bulk Upload
        // 5a. Validation failure (missing file)
        $resNoFile = $this->actingAs($admin)->postJson('/master/pumps/bulk-upload', []);
        $resNoFile->assertStatus(422)->assertJsonValidationErrors(['file']);

        // 5b. Valid file upload with valid row + invalid row (fails validation)
        $csvHeader = "Pump Name,Contact Name,Contact Number,Remark\n";
        $csvValidRow = "Reliance Highway Pump,Gopal Das,9811223344,Highway stop\n";
        $csvInvalidRow = "Invalid Pump,Only Contact,,\n";
        $file = UploadedFile::fake()->createWithContent('pumps.csv', $csvHeader . $csvValidRow . $csvInvalidRow);
        $resUpload = $this->actingAs($admin)->postJson('/master/pumps/bulk-upload', [
            'file' => $file,
        ]);
        $resUpload->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('failed', 1);

        $this->assertDatabaseHas('pumps', [
            'pump_name' => 'Reliance Highway Pump',
            'contact_number' => '9811223344',
            'user_id' => $company->user_id,
        ]);

        // 5c. Exception handling in bulk upload catch block (invalid corrupted xlsx file)
        $corruptFile = UploadedFile::fake()->createWithContent('corrupt.xlsx', 'invalid zip binary content');
        $resErr = $this->actingAs($admin)->postJson('/master/pumps/bulk-upload', [
            'file' => $corruptFile,
        ]);
        $resErr->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Bulk upload failed.');
    }

    public function test_pump_import_full_flow_and_all_edge_cases(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();
        $this->actingAs($admin);

        $import = new PumpImport();

        // 1. chunkSize
        $this->assertSame(500, $import->chunkSize());

        // 2. Direct collection() call with valid and invalid rows
        Pump::create([
            'pump_name' => 'Existing HP Pump',
            'contact_name' => 'Existing Operator',
            'contact_number' => '9999900001',
            'user_id' => $company->user_id,
        ]);

        $rows = collect([
            // Valid row with remark (numeric contact number to test string conversion)
            collect([
                'Pump Name' => 'Indian Oil Mega Plaza',
                'Contact Name' => 'Vikas Jain',
                'Contact Number' => 9876500001,
                'Remark' => '24x7 service',
            ]),
            // Valid row without remark
            collect([
                'pump_name' => 'Shell Expressway Station',
                'contact_name' => 'Anil Sharma',
                'contact_number' => '9876500002',
            ]),
            // Invalid row: missing pump_name, contact_name, contact_number
            collect([
                'pump_name' => '',
                'contact_name' => '',
                'contact_number' => '',
            ]),
            // Invalid row: duplicate contact_number
            collect([
                'pump_name' => 'HP Duplicate Contact',
                'contact_name' => 'Operator',
                'contact_number' => '9999900001',
            ]),
        ]);

        $import->collection($rows);

        $this->assertSame(2, $import->imported);
        $this->assertCount(2, $import->errors);
        $this->assertSame(4, $import->errors[0]['row']);
        $this->assertSame(5, $import->errors[1]['row']);

        // 3. Reflection tests for normalizeRow() with all alternative keys
        $refMethod = new \ReflectionMethod(PumpImport::class, 'normalizeRow');
        $refMethod->setAccessible(true);

        // Alternative key: contact_no
        $norm1 = $refMethod->invoke($import, [
            'name' => 'Pump One',
            'contact_no' => '9800000011',
            'contact_name' => 'Person 1',
        ]);
        $this->assertSame('Pump One', $norm1['pump_name']);
        $this->assertSame('9800000011', $norm1['contact_number']);

        // Alternative key: mobile_number
        $norm2 = $refMethod->invoke($import, [
            'pump_name' => 'Pump Two',
            'mobile_number' => '9800000012',
        ]);
        $this->assertSame('9800000012', $norm2['contact_number']);

        // Alternative key: mobile
        $norm3 = $refMethod->invoke($import, [
            'pump_name' => 'Pump Three',
            'mobile' => '9800000013',
        ]);
        $this->assertSame('9800000013', $norm3['contact_number']);

        // Alternative key: phone
        $norm4 = $refMethod->invoke($import, [
            'pump_name' => 'Pump Four',
            'phone' => '9800000014',
        ]);
        $this->assertSame('9800000014', $norm4['contact_number']);

        // Empty row
        $normEmpty = $refMethod->invoke($import, []);
        $this->assertNull($normEmpty['contact_number'] ?? null);
        $this->assertNull($normEmpty['pump_name'] ?? null);
    }

    public function test_pump_model_attributes_and_traits(): void
    {
        [$admin, $company] = $this->createAdminWithCompany();

        // 1. name attribute accessor with value
        $pump = new Pump([
            'pump_name' => 'HP Fuel Station',
            'contact_name' => 'Rajesh',
            'contact_number' => '9876543210',
            'user_id' => $company->user_id,
        ]);
        $this->assertSame('HP Fuel Station', $pump->name);

        // 2. name attribute accessor when pump_name is null
        $pumpEmpty = new Pump();
        $this->assertSame('', $pumpEmpty->name);

        // 3. Save, soft delete, and check trashed
        $pump->save();
        $this->assertFalse($pump->trashed());

        $pump->delete();
        $this->assertTrue($pump->trashed());
        $this->assertSoftDeleted('pumps', ['id' => $pump->id]);
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
