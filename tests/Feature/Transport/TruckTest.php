<?php

namespace Tests\Feature\Transport;

use App\Http\Controllers\TruckController;
use App\Imports\TruckImport;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\Driver;
use App\Models\LorryReceipt;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\TruckType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
        UserRole::firstOrCreate(['id' => 4], ['name' => 'moderator']);
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Main Co ' . uniqid(),
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

    protected function makeSuperAdmin(Company $company): User
    {
        return User::factory()->create([
            'user_role_id' => 2,
            'company_id' => $company->id,
        ]);
    }

    protected function makeModerator(Company $company): User
    {
        return User::factory()->create([
            'user_role_id' => 4,
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

    protected function makeTransporter(User $admin, City $city, bool $isOwner = false): Transporter
    {
        $master = TransporterMaster::factory()
            ->withLocation($city->id, $city->state_id, $city->country_id)
            ->create([
                'name' => 'Fast Logistics ' . uniqid(),
                'pan_number' => 'ABCDE' . rand(1000, 9999) . 'F',
            ]);

        return Transporter::factory()
            ->forCompany($admin->company_id)
            ->create([
                'transporter_master_id' => $master->id,
                'is_owner' => $isOwner ? 1 : 0,
                'mobile_number' => '99999' . rand(10000, 99999),
                'declaration_given' => false,
                'tds_percentage' => 20,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);
    }

    protected function makeDriver(User $admin): Driver
    {
        return Driver::create([
            'company_id' => $admin->company_id,
            'name' => 'Driver ' . uniqid(),
            'mobile' => '88888' . rand(10000, 99999),
            'license_number' => 'DL' . rand(100000, 999999),
        ]);
    }

    // ==========================================
    // 1. INDEX & DATATABLES & ACTION BUTTONS
    // ==========================================

    public function test_truck_index_view_loads(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $this->makeTransporter($admin, $city, isOwner: true);
        $this->makeTransporter($admin, $city, isOwner: false);

        $response = $this->actingAs($admin)->get('/trucks');

        $response->assertOk()
            ->assertViewIs('trucks.index')
            ->assertViewHas(['transporters', 'owners']);
    }

    public function test_truck_index_ajax_datatables_and_searching(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $owner = $this->makeTransporter($admin, $city, isOwner: true);
        $driver = $this->makeDriver($admin);

        TruckType::firstOrCreate(['name' => 'Flatbed']);
        TruckType::firstOrCreate(['name' => 'Trailer']);

        Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $owner->id,
            'driver_id' => $driver->id,
            'truck_number' => 'GJ01AB1111',
            'truck_type' => 'Flatbed',
            'registration_date' => '2025-01-15',
        ]);

        Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => null, // orphan truck to test assign-owner button
            'driver_id' => null,
            'truck_number' => 'MH02CD2222',
            'truck_type' => 'Trailer',
            'registration_date' => '2025-02-20',
        ]);

        $ajaxHeaders = [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        // Base datatable request
        $res = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=1&start=0&length=10');
        $res->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 2);

        $data = $res->json('data');
        $this->assertCount(2, $data);
        $this->assertStringContainsString('assign-driver-btn', $data[0]['actions']);
        $this->assertStringContainsString('assign-owner-btn', $data[1]['actions']);

        // Global search by truck number
        $resSearch = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=2&start=0&length=10&search[value]=GJ01AB');
        $resSearch->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Global search by truck type
        $resSearchType = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=3&start=0&length=10&search[value]=Trailer');
        $resSearchType->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Global search by registration date
        $resSearchDate = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=4&start=0&length=10&search[value]=2025-01-15');
        $resSearchDate->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Global search by transporter name
        $resSearchTransporter = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=5&start=0&length=10&search[value]=' . urlencode($owner->transporterMaster->name));
        $resSearchTransporter->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Global search by driver name
        $resSearchDriver = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=6&start=0&length=10&search[value]=' . urlencode($driver->name));
        $resSearchDriver->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Column specific searches:
        // Col 1: Truck Number
        $resCol1 = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=7&columns[1][search][value]=MH02CD');
        $resCol1->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Col 2: Truck Type
        $resCol2 = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=8&columns[2][search][value]=Flatbed');
        $resCol2->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Col 3: Registration date
        $resCol3 = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=9&columns[3][search][value]=2025-02-20');
        $resCol3->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Col 4: Transporter
        $resCol4 = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=10&columns[4][search][value]=' . urlencode($owner->transporterMaster->name));
        $resCol4->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Col 5: Driver
        $resCol5 = $this->actingAs($admin)->withHeaders($ajaxHeaders)->get('/trucks?draw=11&columns[5][search][value]=' . urlencode($driver->name));
        $resCol5->assertOk()->assertJsonPath('recordsFiltered', 1);

        // Sorting across columns
        foreach ([0, 1, 2, 3, 4, 5, 99] as $colIndex) {
            $this->actingAs($admin)->withHeaders($ajaxHeaders)->get("/trucks?draw=12&order[0][column]={$colIndex}&order[0][dir]=asc")->assertOk();
            $this->actingAs($admin)->withHeaders($ajaxHeaders)->get("/trucks?draw=13&order[0][column]={$colIndex}&order[0][dir]=desc")->assertOk();
        }

        // Non-admin user gets empty string actions
        $moderator = $this->makeModerator(Company::find($admin->company_id));
        $resMod = $this->actingAs($moderator)->withHeaders($ajaxHeaders)->get('/trucks?draw=14&start=0&length=10');
        $resMod->assertOk();
        $this->assertSame('', $resMod->json('data.0.actions'));
    }

    // ==========================================
    // 2. API ENDPOINTS (TRANSPORTER TRUCKS & OWNER TRANSPORTERS)
    // ==========================================

    public function test_get_trucks_for_transporter_api(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $owner = $this->makeTransporter($admin, $city, isOwner: true);
        $driver = $this->makeDriver($admin);

        Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $owner->id,
            'driver_id' => $driver->id,
            'truck_number' => 'GJ01AB9999',
            'truck_type' => 'Container',
        ]);

        // Own company transporter
        $res = $this->actingAs($admin)->getJson("/api/transporters/{$owner->id}/trucks");
        $res->assertOk();
        $this->assertCount(1, $res->json());
        $this->assertEquals('GJ01AB9999', $res->json('0.truck_number'));

        // Different company admin access to same transporter returns empty list
        $otherAdmin = $this->makeAdmin();
        $resOther = $this->actingAs($otherAdmin)->getJson("/api/transporters/{$owner->id}/trucks");
        $resOther->assertOk()->assertJson([]);
    }

    public function test_get_owner_transporters_api(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $owner = $this->makeTransporter($admin, $city, isOwner: true);
        $nonOwnerTransporter = $this->makeTransporter($admin, $city, isOwner: false);

        $truckWithOwner = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $owner->id,
            'truck_number' => 'GJ01OW1111',
            'truck_type' => 'Open',
        ]);

        $truckWithNonOwner = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $nonOwnerTransporter->id,
            'truck_number' => 'GJ01NO2222',
            'truck_type' => 'Open',
        ]);

        $orphanTruck = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => null,
            'truck_number' => 'GJ01OR3333',
            'truck_type' => 'Open',
        ]);

        // 1. Truck with owner returns owner array
        $res1 = $this->actingAs($admin)->getJson("/api/trucks/{$truckWithOwner->id}/owner-transporters");
        $res1->assertOk();
        $this->assertCount(1, $res1->json());
        $this->assertEquals($owner->id, $res1->json('0.id'));

        // 2. Truck with non-owner returns empty array
        $res2 = $this->actingAs($admin)->getJson("/api/trucks/{$truckWithNonOwner->id}/owner-transporters");
        $res2->assertOk()->assertExactJson([]);

        // 3. Orphan truck returns empty array
        $res3 = $this->actingAs($admin)->getJson("/api/trucks/{$orphanTruck->id}/owner-transporters");
        $res3->assertOk()->assertExactJson([]);

        // 4. Non-existent truck returns empty array
        $res4 = $this->actingAs($admin)->getJson('/api/trucks/999999/owner-transporters');
        $res4->assertOk()->assertExactJson([]);
    }

    // ==========================================
    // 3. CREATE VIEW & OLD INPUT HANDLING
    // ==========================================

    public function test_create_view_loads_and_handles_parameters(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $owner = $this->makeTransporter($admin, $city, isOwner: true);
        $driver = $this->makeDriver($admin);
        TruckType::firstOrCreate(['name' => 'Closed Body']);

        // Without parameters
        $this->actingAs($admin)->get('/trucks/create')
            ->assertOk()
            ->assertViewIs('trucks.create')
            ->assertViewHas('truckTypes');

        // With valid owner transporter_id parameter
        $resParam = $this->actingAs($admin)->get("/trucks/create/{$owner->id}");
        $resParam->assertOk()
            ->assertViewIs('trucks.create')
            ->assertViewHas('transporter', fn ($t) => $t->id === $owner->id);

        // With invalid transporter_id parameter (non-owner or cross company) -> 404
        $nonOwner = $this->makeTransporter($admin, $city, isOwner: false);
        $this->actingAs($admin)->get("/trucks/create/{$nonOwner->id}")->assertStatus(404);

        // With old input in session for transporter_id and driver_id
        $resOld = $this->actingAs($admin)->withSession([
            '_old_input' => [
                'transporter_id' => $owner->id,
                'driver_id' => $driver->id,
            ],
        ])->get('/trucks/create');
        $resOld->assertOk()->assertViewIs('trucks.create');
    }

    // ==========================================
    // 4. STORE & VALIDATION & DB EXCEPTION HANDLING
    // ==========================================

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
        $driver = $this->makeDriver($admin);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post('/trucks', [
                'transporter_id' => $transporter->id,
                'driver_id' => $driver->id,
                'truck_number' => 'GJ01AX1234',
                'truck_type' => 'Flatbed',
                'registration_date' => now()->toDateString(),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('truck.truck_number', 'GJ01AX1234')
            ->assertJsonPath('truck.transporter_id', $transporter->id)
            ->assertJsonPath('truck.driver_id', $driver->id);

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
            'company_id' => $admin->company_id,
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

    public function test_store_validation_regex_and_transporter_ownership(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $otherTransporter = $this->makeTransporter($otherAdmin, $city);

        // Invalid truck number format
        $resInvalidRegex = $this->actingAs($admin)->post('/trucks', [
            'truck_number' => 'INVALID_TRUCK',
            'truck_type' => 'Trailer',
        ]);
        $resInvalidRegex->assertSessionHasErrors(['truck_number']);

        // Transporter from another company
        $resOtherCo = $this->actingAs($admin)->post('/trucks', [
            'transporter_id' => $otherTransporter->id,
            'truck_number' => 'GJ01ZX1234',
            'truck_type' => 'Trailer',
        ]);
        $resOtherCo->assertSessionHasErrors(['transporter_id']);
    }

    public function test_store_query_exception_branches(): void
    {
        $admin = $this->makeAdmin();

        $mockQueryEx1062 = new QueryException('connection', 'insert into trucks...', [], new \Exception('Duplicate'));
        $mockQueryEx1062->errorInfo = ['23000', 1062, 'Duplicate entry'];

        // 1. Normal request with 1062 duplicate entry
        Truck::saving(function () use ($mockQueryEx1062) {
            throw $mockQueryEx1062;
        });

        $res1062Normal = $this->actingAs($admin)->from('/trucks/create')->post('/trucks', [
            'truck_number' => 'GJ01QQ1234',
            'truck_type' => 'Tanker',
        ]);
        $res1062Normal->assertRedirect('/trucks/create')->assertSessionHasErrors(['truck_number']);

        // 2. AJAX with 1062 duplicate entry
        $res1062Ajax = $this->actingAs($admin)->postJson('/trucks', [
            'truck_number' => 'GJ01QQ1234',
            'truck_type' => 'Tanker',
        ]);
        $res1062Ajax->assertStatus(422)->assertJsonPath('success', false);

        // 3. Other QueryException (e.g. connection error)
        Truck::flushEventListeners();
        $mockQueryExOther = new QueryException('connection', 'insert into trucks...', [], new \Exception('DB down'));
        $mockQueryExOther->errorInfo = ['HY000', 2002, 'Connection refused'];

        Truck::saving(function () use ($mockQueryExOther) {
            throw $mockQueryExOther;
        });

        $resOtherNormal = $this->actingAs($admin)->from('/trucks/create')->post('/trucks', [
            'truck_number' => 'GJ01QQ1234',
            'truck_type' => 'Tanker',
        ]);
        $resOtherNormal->assertRedirect('/trucks/create')->assertSessionHasErrors(['general']);

        $resOtherAjax = $this->actingAs($admin)->postJson('/trucks', [
            'truck_number' => 'GJ01QQ1234',
            'truck_type' => 'Tanker',
        ]);
        $resOtherAjax->assertStatus(500)->assertJsonPath('success', false);

        Truck::flushEventListeners();
    }

    // ==========================================
    // 5. CHECK TRUCK NUMBER API & CONTROLLER METHOD
    // ==========================================

    public function test_check_truck_number_api_flags_existing_number(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)->get('/check-truck-number?truck_number=GJ01AB1234');
        $response->assertOk();
        $this->assertTrue($response->json('exists'));

        $responseNonExisting = $this->actingAs($admin)->get('/check-truck-number?truck_number=GJ01AB9999');
        $responseNonExisting->assertOk();
        $this->assertFalse($responseNonExisting->json('exists'));

        // Direct method call in TruckController::checkTruckNumber()
        $controller = app(TruckController::class);
        $reqDirect = Request::create('/check-truck-number', 'GET', ['truck_number' => 'GJ01AB1234']);
        $reqDirect->setUserResolver(fn () => $admin);
        Auth::setUser($admin);
        $resDirect = $controller->checkTruckNumber($reqDirect);
        $this->assertTrue(json_decode($resDirect->getContent(), true)['exists']);

        // Super Admin with super_admin_company_id session
        $superAdmin = $this->makeSuperAdmin(Company::find($admin->company_id));
        session(['super_admin_company_id' => $admin->company_id]);
        Auth::setUser($superAdmin);
        $reqSuper = Request::create('/check-truck-number', 'GET', ['truck_number' => 'GJ01AB1234']);
        $reqSuper->setUserResolver(fn () => $superAdmin);
        $resSuper = $controller->checkTruckNumber($reqSuper);
        $this->assertTrue(json_decode($resSuper->getContent(), true)['exists']);
    }

    // ==========================================
    // 6. EDIT & UPDATE & DB EXCEPTION HANDLING
    // ==========================================

    public function test_edit_view_loads_with_relations_and_old_input(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city, isOwner: true);
        $driver = $this->makeDriver($admin);
        TruckType::firstOrCreate(['name' => 'Tanker']);

        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $transporter->id,
            'driver_id' => $driver->id,
            'truck_number' => 'GJ01ED1234',
            'truck_type' => 'Tanker',
        ]);

        $response = $this->actingAs($admin)->get("/trucks/{$truck->id}/edit");
        $response->assertOk()
            ->assertViewIs('trucks.edit')
            ->assertViewHas(['truck', 'transporters', 'drivers', 'truckTypes']);

        // Soft-deleted driver can still be resolved on edit
        $driver->delete();
        $resTrashedDriver = $this->actingAs($admin)->get("/trucks/{$truck->id}/edit");
        $resTrashedDriver->assertOk();

        // With old input in session
        $resOld = $this->actingAs($admin)->withSession([
            '_old_input' => [
                'transporter_id' => $transporter->id,
                'driver_id' => $driver->id,
            ],
        ])->get("/trucks/{$truck->id}/edit");
        $resOld->assertOk();
    }

    public function test_update_changes_truck_and_enforces_uniqueness(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        // 1. Valid update (Normal POST redirect)
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

        // 2. Valid update (AJAX response)
        $resAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->put("/trucks/{$truck->id}", [
                'transporter_id' => $transporter->id,
                'truck_number' => 'GJ01XY8888',
                'truck_type' => 'Container',
            ]);

        $resAjax->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('truck.truck_number', 'GJ01XY8888');

        // Flush headers so next request is non-AJAX
        $this->flushHeaders();

        // 3. Duplicate truck number within company
        Truck::create([
            'company_id' => $admin->company_id,
            'truck_number' => 'GJ01DU7777',
            'truck_type' => 'Trailer',
        ]);

        // Normal duplicate update -> redirect back with errors
        $resDup = $this->actingAs($admin)->from("/trucks/{$truck->id}/edit")->put("/trucks/{$truck->id}", [
            'truck_number' => 'GJ01DU7777',
            'truck_type' => 'Container',
        ]);
        $resDup->assertSessionHasErrors(['truck_number']);

        // AJAX duplicate update -> 422 JSON
        $resDupAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->put("/trucks/{$truck->id}", [
                'truck_number' => 'GJ01DU7777',
                'truck_type' => 'Container',
            ]);
        $resDupAjax->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.truck_number.0', 'The truck number must be unique within the company.');
    }

    public function test_update_query_exception_branches(): void
    {
        $admin = $this->makeAdmin();
        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'truck_number' => 'GJ01UP1111',
            'truck_type' => 'Flatbed',
        ]);

        $mockQueryEx1062 = new QueryException('connection', 'update trucks...', [], new \Exception('Duplicate'));
        $mockQueryEx1062->errorInfo = ['23000', 1062, 'Duplicate entry'];

        Truck::saving(function () use ($mockQueryEx1062) {
            throw $mockQueryEx1062;
        });

        $res1062Normal = $this->actingAs($admin)->from("/trucks/{$truck->id}/edit")->put("/trucks/{$truck->id}", [
            'truck_number' => 'GJ01UP2222',
            'truck_type' => 'Flatbed',
        ]);
        $res1062Normal->assertRedirect("/trucks/{$truck->id}/edit")->assertSessionHasErrors(['truck_number']);

        $res1062Ajax = $this->actingAs($admin)->putJson("/trucks/{$truck->id}", [
            'truck_number' => 'GJ01UP2222',
            'truck_type' => 'Flatbed',
        ]);
        $res1062Ajax->assertStatus(422)->assertJsonPath('success', false);

        // 2. Simulate other DB error
        Truck::flushEventListeners();
        $mockQueryExOther = new QueryException('connection', 'update trucks...', [], new \Exception('Generic DB'));
        $mockQueryExOther->errorInfo = ['HY000', 2002, 'Generic error'];

        Truck::saving(function () use ($mockQueryExOther) {
            throw $mockQueryExOther;
        });

        $resOtherNormal = $this->actingAs($admin)->from("/trucks/{$truck->id}/edit")->put("/trucks/{$truck->id}", [
            'truck_number' => 'GJ01UP2222',
            'truck_type' => 'Flatbed',
        ]);
        $resOtherNormal->assertRedirect("/trucks/{$truck->id}/edit")->assertSessionHasErrors(['general']);

        $resOtherAjax = $this->actingAs($admin)->putJson("/trucks/{$truck->id}", [
            'truck_number' => 'GJ01UP2222',
            'truck_type' => 'Flatbed',
        ]);
        $resOtherAjax->assertStatus(500)->assertJsonPath('success', false);

        Truck::flushEventListeners();
    }

    // ==========================================
    // 7. REMOVE TRANSPORTER & LR DEPENDENCY
    // ==========================================

    public function test_remove_transporter_flows_and_authorization(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01RM1111',
            'truck_type' => 'Open',
        ]);

        // Cross company access -> 403
        $this->actingAs($otherAdmin)->patchJson("/trucks/{$truck->id}/remove-transporter")->assertStatus(403);

        // Truck has undelivered Lorry Receipt -> cannot remove
        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-' . uniqid(),
            'consignor_id' => 1,
            'consignee_id' => 1,
            'lane' => 'Lane 1',
            'source_city' => 'City A',
            'destination_city' => 'City B',
            'source_state' => 'State A',
            'destination_state' => 'State B',
            'weight' => 10.0,
            'no_of_units' => 1,
            'good_type' => 'Goods',
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
            'gr_number' => 'GR-' . uniqid(),
            'delivery_status' => 'Pending',
        ]);

        $resInUse = $this->actingAs($admin)->patchJson("/trucks/{$truck->id}/remove-transporter");
        $resInUse->assertOk()->assertJsonStructure(['error']);
        $this->assertNotNull($truck->fresh()->transporter_id);

        // Mark LR delivered -> now transporter can be removed
        $lr->update(['delivery_status' => 'delivered']);

        $resSuccess = $this->actingAs($admin)->patchJson("/trucks/{$truck->id}/remove-transporter");
        $resSuccess->assertOk()->assertJsonStructure(['success']);
        $this->assertNull($truck->fresh()->transporter_id);
    }

    // ==========================================
    // 8. DESTROY TRUCK & SOFT DELETE
    // ==========================================

    public function test_destroy_truck_soft_delete_and_authorization(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'truck_number' => 'GJ01DE1111',
            'truck_type' => 'Trailer',
        ]);

        // Cross-company delete -> 403
        $this->actingAs($otherAdmin)->delete("/trucks/{$truck->id}", [
            'delete_remark' => 'Cross delete',
        ])->assertStatus(403);

        // Missing delete_remark -> validation error
        $this->actingAs($admin)->delete("/trucks/{$truck->id}", [])->assertSessionHasErrors(['delete_remark']);

        // Valid delete -> soft deleted
        $res = $this->actingAs($admin)->delete("/trucks/{$truck->id}", [
            'delete_remark' => 'Truck sold',
        ]);

        $res->assertRedirect(route('trucks.index'))->assertSessionHas('success');
        $this->assertSoftDeleted('trucks', ['id' => $truck->id]);
        $this->assertEquals('Truck sold', $truck->fresh()->deleted_remark);
        $this->assertEquals($admin->id, $truck->fresh()->deleted_by);

        // Exception branch in destroy
        $truck2 = Truck::create([
            'company_id' => $admin->company_id,
            'truck_number' => 'GJ01DE2222',
            'truck_type' => 'Trailer',
        ]);

        Truck::deleting(function () {
            throw new \Exception('Delete blocked');
        });

        $resErr = $this->actingAs($admin)->delete("/trucks/{$truck2->id}", [
            'delete_remark' => 'Error delete',
        ]);
        $resErr->assertSessionHas('error');

        Truck::flushEventListeners();
    }

    // ==========================================
    // 9. BULK UPLOAD & TEMPLATE DOWNLOAD
    // ==========================================

    public function test_bulk_upload_validation_success_and_errors(): void
    {
        $admin = $this->makeAdmin();

        // 1. Validation failure: missing file
        $resNoFile = $this->actingAs($admin)->postJson('/trucks/bulk-upload', []);
        $resNoFile->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'File validation failed.');

        // 2. Company could not be identified (lines 681-689 in TruckController)
        $superAdmin = $this->makeSuperAdmin(Company::find($admin->company_id));
        Auth::setUser($superAdmin);
        session(['super_admin_company_id' => null]);
        $dummyFile = UploadedFile::fake()->createWithContent('trucks.csv', "Truck Number,Truck Type,Registration Date,Truck Owner,Driver\n");
        $reqDirect = Request::create('/trucks/bulk-upload', 'POST', [], [], ['file' => $dummyFile]);
        $reqDirect->headers->set('Accept', 'application/json');
        $reqDirect->setUserResolver(fn () => $superAdmin);
        $resNoCo = app(TruckController::class)->bulkUpload($reqDirect);
        $this->assertSame(422, $resNoCo->getStatusCode());
        $this->assertSame('Company could not be identified.', json_decode($resNoCo->getContent(), true)['message']);

        // 3. Valid file with all valid rows
        $csvHeader = "Truck Number,Truck Type,Registration Date,Truck Owner,Driver\n";
        $csvValidRow = "GJ01ZZ1234,Flatbed,15-01-2025,,\n";
        $csvFile = UploadedFile::fake()->createWithContent('trucks_valid.csv', $csvHeader . $csvValidRow);

        $resUpload = $this->actingAs($admin)->postJson('/trucks/bulk-upload', [
            'file' => $csvFile,
        ]);
        $resUpload->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('trucks', [
            'company_id' => $admin->company_id,
            'truck_number' => 'GJ01ZZ1234',
        ]);

        // 3b. Partial success: 1 valid row and 1 invalid row (covers line 710)
        $csvPartialContent = $csvHeader . "GJ01ZZ5678,Flatbed,15-01-2025,,\n" . "BAD_TRUCK,Flatbed,invalid,,\n";
        $csvPartialFile = UploadedFile::fake()->createWithContent('trucks_partial.csv', $csvPartialContent);
        $resPartial = $this->actingAs($admin)->postJson('/trucks/bulk-upload', [
            'file' => $csvPartialFile,
        ]);
        $resPartial->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('partial_success', true)
            ->assertJsonPath('imported', 1);

        // 4. File with only invalid rows (zero success)
        $csvInvalidRow = "INVALID_NUMBER,Flatbed,invalid-date,,\n";
        $csvZero = UploadedFile::fake()->createWithContent('trucks_zero.csv', $csvHeader . $csvInvalidRow);

        $resErr = $this->actingAs($admin)->postJson('/trucks/bulk-upload', [
            'file' => $csvZero,
        ]);
        $resErr->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('imported', 0);

        // 5. Catch block \Throwable in bulkUpload
        $shouldFail = true;
        DB::listen(function ($query) use (&$shouldFail) {
            if ($shouldFail && stripos($query->sql, 'trucks') !== false) {
                throw new \Exception('Simulated Bulk Upload failure');
            }
        });

        $resException = $this->actingAs($admin)->postJson('/trucks/bulk-upload', [
            'file' => UploadedFile::fake()->createWithContent('trucks_fail.csv', $csvHeader . "GJ01ZZ9999,Flatbed,15-01-2025,,\n"),
        ]);
        $shouldFail = false;
        $resException->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to process the file. Please check the file and try again.');
    }

    public function test_bulk_upload_template_download(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/trucks/bulk-upload/template');
        $response->assertOk();

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Truck Number', $content);
        $this->assertStringContainsString('Truck Type', $content);
        $this->assertStringContainsString('Registration Date', $content);
        $this->assertStringContainsString('Truck Owner', $content);
        $this->assertStringContainsString('Driver', $content);
    }

    // ==========================================
    // 10. SHOW TRUCK JSON
    // ==========================================

    public function test_show_truck_json_and_authorization(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city, isOwner: true);
        $driver = $this->makeDriver($admin);

        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => $transporter->id,
            'driver_id' => $driver->id,
            'truck_number' => 'GJ01SH1111',
            'truck_type' => 'Container',
            'registration_date' => '2025-03-10',
        ]);

        // Cross company access -> 403
        $this->actingAs($otherAdmin)->getJson("/trucks/{$truck->id}")
            ->assertStatus(403)
            ->assertJson(['error' => 'Unauthorized']);

        // Valid show with transporter and driver
        $res = $this->actingAs($admin)->getJson("/trucks/{$truck->id}");
        $res->assertOk()
            ->assertJsonPath('truck_number', 'GJ01SH1111')
            ->assertJsonPath('transporter.id', $transporter->id)
            ->assertJsonPath('driver.id', $driver->id)
            ->assertJsonPath('registration_date', '10-03-2025');

        // Valid show without transporter and driver
        $orphanTruck = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => null,
            'driver_id' => null,
            'truck_number' => 'GJ01SH2222',
            'truck_type' => 'Open',
        ]);

        $resOrphan = $this->actingAs($admin)->getJson("/trucks/{$orphanTruck->id}");
        $resOrphan->assertOk()
            ->assertJsonPath('transporter', null)
            ->assertJsonPath('driver', null)
            ->assertJsonPath('registration_date', null);
    }

    // ==========================================
    // 11. ASSIGN DRIVER
    // ==========================================

    public function test_assign_driver_flows_and_authorization(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();
        $driver = $this->makeDriver($admin);

        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'truck_number' => 'GJ01DR1111',
            'truck_type' => 'Flatbed',
        ]);

        // Cross-company assign driver -> 403
        $this->actingAs($otherAdmin)->putJson("/trucks/{$truck->id}/assign-driver", [
            'driver_id' => $driver->id,
        ])->assertStatus(403);

        // Assign driver (AJAX)
        $resAssign = $this->actingAs($admin)->putJson("/trucks/{$truck->id}/assign-driver", [
            'driver_id' => $driver->id,
        ]);
        $resAssign->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Driver assigned successfully!')
            ->assertJsonPath('truck.driver_id', $driver->id);
        $this->assertEquals($driver->id, $truck->fresh()->driver_id);

        // Unassign driver (driver_id = null) (AJAX)
        $resUnassign = $this->actingAs($admin)->putJson("/trucks/{$truck->id}/assign-driver", [
            'driver_id' => null,
        ]);
        $resUnassign->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Driver removed successfully!')
            ->assertJsonPath('truck.driver_id', null);
        $this->assertNull($truck->fresh()->driver_id);

        // Normal (non-AJAX) assign driver -> redirect back
        $resRedirect = $this->actingAs($admin)->put("/trucks/{$truck->id}/assign-driver", [
            'driver_id' => $driver->id,
        ]);
        $resRedirect->assertRedirect();
        $this->assertEquals($driver->id, $truck->fresh()->driver_id);
    }

    // ==========================================
    // 12. ASSIGN OWNER
    // ==========================================

    public function test_assign_owner_flows_and_edge_cases(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $owner = $this->makeTransporter($admin, $city, isOwner: true);
        $otherOwner = $this->makeTransporter($admin, $city, isOwner: true);

        $truck = Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => null,
            'truck_number' => 'GJ01OW9999',
            'truck_type' => 'Container',
        ]);

        // Validation failure (missing fields)
        $this->actingAs($admin)->postJson('/assign-owner', [])->assertStatus(422);

        // Assign new owner
        $resSuccess = $this->actingAs($admin)->postJson('/assign-owner', [
            'truck_id' => $truck->id,
            'transporter_id' => $owner->id,
        ]);
        $resSuccess->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Truck owner assigned successfully.');
        $this->assertEquals($owner->id, $truck->fresh()->transporter_id);

        // Assigning same owner again returns warning
        $resSame = $this->actingAs($admin)->postJson('/assign-owner', [
            'truck_id' => $truck->id,
            'transporter_id' => $owner->id,
        ]);
        $resSame->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This truck is already assigned to the selected owner.');

        // Exception branch in assignOwner
        Truck::saving(function () {
            throw new \Exception('Assign owner exception');
        });

        $resErr = $this->actingAs($admin)->postJson('/assign-owner', [
            'truck_id' => $truck->id,
            'transporter_id' => $otherOwner->id,
        ]);
        $resErr->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Failed to assign truck owner. Please try again.');

        Truck::flushEventListeners();
    }

    // ==========================================
    // 13. TRUCK IMPORT FULL FLOW & ALL EDGE CASES
    // ==========================================

    public function test_truck_import_full_flow_and_all_edge_cases(): void
    {
        $admin = $this->makeAdmin();
        $companyId = $admin->company_id;
        [, , $city] = $this->seedGeo();

        $owner = $this->makeTransporter($admin, $city, isOwner: true);

        $driver = Driver::create([
            'company_id' => $companyId,
            'name' => 'Ramesh Kumar',
            'mobile' => '9876543210',
        ]);

        $import = new TruckImport($companyId);

        // 1. chunkSize
        $this->assertSame(500, $import->chunkSize());

        // 2. Validation failures in row (validateRow)
        // Missing truck_number, invalid regex, missing truck_type, missing registration_date
        $invalidRows = collect([
            collect(['truck_number' => '', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025']),
            collect(['truck_number' => 'INVALID_NUM', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025']),
            collect(['truck_number' => 'MH12AB1001', 'truck_type' => '', 'registration_date' => '15-01-2025']),
            collect(['truck_number' => 'MH12AB1002', 'truck_type' => 'Flatbed', 'registration_date' => '']),
        ]);
        $import->collection($invalidRows);
        $this->assertCount(4, $import->errors);

        // 3. Duplicate truck number in the SAME uploaded collection
        $duplicateRows = collect([
            collect(['truck_number' => 'MH12CD1234', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025']),
            collect(['truck_number' => 'MH12CD1234', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025']),
        ]);
        $importDuplicate = new TruckImport($companyId);
        $importDuplicate->collection($duplicateRows);
        $this->assertSame(1, $importDuplicate->successCount);
        $this->assertCount(1, $importDuplicate->errors);
        $this->assertStringContainsString('Duplicate truck number in uploaded file', $importDuplicate->errors[0]['errors'][0]);

        // 4. Truck already exists in DB (direct company_id AND via transporter)
        // Existing by company_id:
        $importExists = new TruckImport($companyId);
        $importExists->collection(collect([
            collect(['truck_number' => 'MH12CD1234', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025']),
        ]));
        $this->assertSame(0, $importExists->successCount);
        $this->assertStringContainsString('Truck number already exists', $importExists->errors[0]['errors'][0]);

        // Existing via transporter company_id (company_id is null on truck, but transporter has company_id)
        Truck::create([
            'company_id' => null,
            'transporter_id' => $owner->id,
            'truck_number' => 'MH12EE5555',
            'truck_type' => 'Trailer',
        ]);
        $importViaTransporter = new TruckImport($companyId);
        $importViaTransporter->collection(collect([
            collect(['truck_number' => 'MH12EE5555', 'truck_type' => 'Trailer', 'registration_date' => '15-01-2025']),
        ]));
        $this->assertSame(0, $importViaTransporter->successCount);
        $this->assertStringContainsString('Truck number already exists', $importViaTransporter->errors[0]['errors'][0]);

        // 5. TruckType auto-create when not pre-existing
        $importAutoType = new TruckImport($companyId);
        $uniqueTypeName = 'AutoType' . uniqid();
        $this->assertNull(TruckType::whereRaw('LOWER(name) = ?', [strtolower($uniqueTypeName)])->first());
        $importAutoType->collection(collect([
            collect(['truck_number' => 'MH12FF1111', 'truck_type' => $uniqueTypeName, 'registration_date' => '15-01-2025']),
        ]));
        $this->assertSame(1, $importAutoType->successCount);
        $this->assertNotNull(TruckType::whereRaw('LOWER(name) = ?', [strtolower($uniqueTypeName)])->first());

        // 6. Invalid registration date format (! $registrationDate)
        $importBadDate = new TruckImport($companyId);
        $importBadDate->collection(collect([
            collect(['truck_number' => 'MH12GG2222', 'truck_type' => 'Flatbed', 'registration_date' => 'not-a-valid-date']),
        ]));
        $this->assertSame(0, $importBadDate->successCount);
        $this->assertStringContainsString('Invalid registration date format', $importBadDate->errors[0]['errors'][0]);

        // 7. Owner edge cases:
        // 7a. Owner not found
        $importNoOwner = new TruckImport($companyId);
        $importNoOwner->collection(collect([
            collect(['truck_number' => 'MH12HH3333', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025', 'truck_owner' => 'NonExistentOwner']),
        ]));
        $this->assertSame(0, $importNoOwner->successCount);
        $this->assertStringContainsString('Truck owner not found', $importNoOwner->errors[0]['errors'][0]);

        // 7b. Multiple owners found
        // Create another owner with same short_name in same company
        $dupOwner1 = $this->makeTransporter($admin, $city, isOwner: true);
        $dupOwner1->update(['short_name' => 'DUP_OWNER_SHORT']);
        $dupOwner2 = $this->makeTransporter($admin, $city, isOwner: true);
        $dupOwner2->update(['short_name' => 'DUP_OWNER_SHORT']);

        $importMultiOwner = new TruckImport($companyId);
        $importMultiOwner->collection(collect([
            collect(['truck_number' => 'MH12II4444', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025', 'truck_owner' => 'DUP_OWNER_SHORT']),
        ]));
        $this->assertSame(0, $importMultiOwner->successCount);
        $this->assertStringContainsString('Multiple truck owners found with name', $importMultiOwner->errors[0]['errors'][0]);

        // 7c. Valid owner lookup by short_name, by name, by pan_number
        $ownerSingle = $this->makeTransporter($admin, $city, isOwner: true);
        $ownerSingle->update(['short_name' => 'SINGLE_SHORT']);
        $importOwnerShort = new TruckImport($companyId);
        $importOwnerShort->collection(collect([
            collect(['truck_number' => 'MH12JJ5555', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025', 'truck_owner' => 'SINGLE_SHORT']),
        ]));
        $this->assertSame(1, $importOwnerShort->successCount);

        // 8. Driver edge cases:
        // 8a. Driver not found
        $importNoDriver = new TruckImport($companyId);
        $importNoDriver->collection(collect([
            collect(['truck_number' => 'MH12KK6666', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025', 'driver' => 'UnknownDriverXYZ']),
        ]));
        $this->assertSame(0, $importNoDriver->successCount);
        $this->assertStringContainsString('Driver not found', $importNoDriver->errors[0]['errors'][0]);

        // 8b. Multiple drivers found
        Driver::create(['company_id' => $companyId, 'name' => 'DuplicateDriver', 'mobile' => '9000000001']);
        Driver::create(['company_id' => $companyId, 'name' => 'DuplicateDriver', 'mobile' => '9000000002']);
        $importMultiDriver = new TruckImport($companyId);
        $importMultiDriver->collection(collect([
            collect(['truck_number' => 'MH12LL7777', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025', 'driver' => 'DuplicateDriver']),
        ]));
        $this->assertSame(0, $importMultiDriver->successCount);
        $this->assertStringContainsString('Multiple drivers found with name', $importMultiDriver->errors[0]['errors'][0]);

        // 8c. Valid driver by mobile & by name
        $importDriverValid = new TruckImport($companyId);
        $importDriverValid->collection(collect([
            collect(['truck_number' => 'MH12MM8888', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025', 'driver' => '9876543210']),
        ]));
        $this->assertSame(1, $importDriverValid->successCount);

        // 9. Catch \Throwable during Truck::create
        Truck::saving(function () {
            throw new \Exception('Simulated Import DB Error');
        });
        $importThrow = new TruckImport($companyId);
        $importThrow->collection(collect([
            collect(['truck_number' => 'MH12NN9999', 'truck_type' => 'Flatbed', 'registration_date' => '15-01-2025']),
        ]));
        $this->assertSame(0, $importThrow->successCount);
        $this->assertCount(1, $importThrow->errors);
        $this->assertStringContainsString('Unable to create truck: Simulated Import DB Error', $importThrow->errors[0]['errors'][0]);
        Truck::flushEventListeners();

        // 10. Reflection tests on private parseDate()
        $importReflect = new TruckImport($companyId);
        $refMethod = new \ReflectionMethod(TruckImport::class, 'parseDate');
        $refMethod->setAccessible(true);

        // null and empty
        $this->assertNull($refMethod->invoke($importReflect, null));
        $this->assertNull($refMethod->invoke($importReflect, ''));

        // DateTimeInterface: valid year and out-of-range year
        $this->assertSame('2025-06-15', $refMethod->invoke($importReflect, new \DateTime('2025-06-15')));
        $this->assertNull($refMethod->invoke($importReflect, new \DateTime('1850-01-01')));
        $this->assertNull($refMethod->invoke($importReflect, new \DateTime('2150-01-01')));

        // is_numeric: valid Excel timestamp and out-of-range
        $this->assertNotNull($refMethod->invoke($importReflect, 45000));
        $this->assertNull($refMethod->invoke($importReflect, 10000000));

        // Formats:
        // 'd-m-Y', 'd/m/Y', 'Y-m-d', 'd.m.Y', 'Y/m/d', 'm/d/Y'
        $this->assertSame('2025-05-20', $refMethod->invoke($importReflect, '20-05-2025'));
        $this->assertSame('2025-05-20', $refMethod->invoke($importReflect, '20/05/2025'));
        $this->assertSame('2025-05-20', $refMethod->invoke($importReflect, '2025-05-20'));
        $this->assertSame('2025-05-20', $refMethod->invoke($importReflect, '20.05.2025'));
        $this->assertSame('2025-05-20', $refMethod->invoke($importReflect, '2025/05/20'));
        $this->assertSame('2025-05-20', $refMethod->invoke($importReflect, '05/20/2025'));

        // Year out of range in string format (<1900 or >2100)
        $this->assertNull($refMethod->invoke($importReflect, '20-05-1850'));
        $this->assertNull($refMethod->invoke($importReflect, '20-05-2150'));

        // Unparseable string
        $this->assertNull($refMethod->invoke($importReflect, 'invalid-date'));

        // Trigger catch (\Throwable $e)
        $throwableObj = new class {
            public function __toString()
            {
                throw new \Exception('Cannot convert to string');
            }
        };
        $this->assertNull($refMethod->invoke($importReflect, $throwableObj));

        // 11. Reflection tests on private normalizeRow()
        $normMethod = new \ReflectionMethod(TruckImport::class, 'normalizeRow');
        $normMethod->setAccessible(true);

        // Alternative headers
        $testRow1 = [
            'vehicle-no' => 'MH-12-AB-1234',
            'type' => 'Container',
            'reg_date' => '2025-01-01',
            'owner_name' => 'Owner Test',
            'driver_name' => 'Driver Test',
            'non_string_val' => 12345,
        ];
        $normalized1 = $normMethod->invoke($importReflect, $testRow1);
        $this->assertSame('MH12AB1234', $normalized1['truck_number']);
        $this->assertSame('Container', $normalized1['truck_type']);
        $this->assertSame('2025-01-01', $normalized1['registration_date']);
        $this->assertSame('Owner Test', $normalized1['truck_owner']);
        $this->assertSame('Driver Test', $normalized1['driver']);

        $testRow2 = [
            'truck_no' => 'MH 12.CD_5678',
            'transporter' => 'Transporter Co',
            'date' => '2025-02-02',
        ];
        $normalized2 = $normMethod->invoke($importReflect, $testRow2);
        $this->assertSame('MH12CD5678', $normalized2['truck_number']);
        $this->assertSame('Transporter Co', $normalized2['truck_owner']);

        $testRow3 = [
            'vehicle_number' => 'MH 12.CD_9999',
            'owner' => 'Owner Simple',
        ];
        $normalized3 = $normMethod->invoke($importReflect, $testRow3);
        $this->assertSame('MH12CD9999', $normalized3['truck_number']);
        $this->assertSame('Owner Simple', $normalized3['truck_owner']);

        // Empty row
        $normalizedEmpty = $normMethod->invoke($importReflect, []);
        $this->assertSame('', $normalizedEmpty['truck_number']);
        $this->assertSame('', $normalizedEmpty['truck_type']);
        $this->assertNull($normalizedEmpty['registration_date']);
        $this->assertNull($normalizedEmpty['truck_owner']);
        $this->assertNull($normalizedEmpty['driver']);
    }

    // ==========================================
    // 14. TRUCK MODEL RELATIONS & LIFECYCLE
    // ==========================================

    public function test_truck_model_relations_and_lifecycle(): void
    {
        $admin = $this->makeAdmin();
        $company = Company::find($admin->company_id);
        [, , $city] = $this->seedGeo();

        $transporter = $this->makeTransporter($admin, $city, isOwner: true);
        $driver = Driver::create([
            'company_id' => $company->id,
            'name' => 'Driver Singh',
            'mobile' => '9988776655',
        ]);

        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'MH12AA1111',
            'truck_type' => 'Container',
            'driver_id' => $driver->id,
            'registration_date' => '2025-01-01',
        ]);

        // 1. BelongsTo Company relation
        $this->assertInstanceOf(Company::class, $truck->company);
        $this->assertSame($company->id, $truck->company->id);

        // 2. BelongsTo Transporter relation
        $this->assertInstanceOf(Transporter::class, $truck->transporter);
        $this->assertSame($transporter->id, $truck->transporter->id);

        // 3. BelongsTo Driver relation
        $this->assertInstanceOf(Driver::class, $truck->driver);
        $this->assertSame($driver->id, $truck->driver->id);

        // 4. Booted saving hook: when transporter_id is present, company_id is auto-populated
        $this->assertSame($company->id, $truck->company_id);

        // Booted saving hook: when transporter_id is null, company_id remains as provided
        $truckNoTransporter = Truck::create([
            'company_id' => $company->id,
            'transporter_id' => null,
            'truck_number' => 'MH12AA2222',
            'truck_type' => 'Trailer',
        ]);
        $this->assertSame($company->id, $truckNoTransporter->company_id);
        $this->assertNull($truckNoTransporter->transporter);
    }
}


