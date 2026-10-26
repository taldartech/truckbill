<?php

namespace Tests\Feature\Drivers;

use App\Imports\DriverImport;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Driver;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DriverTest extends TestCase
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
        UserRole::create(['id' => 5, 'name' => 'accountant']);
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
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    protected function makeUserWithRole(string $role): User
    {
        $roleId = UserRole::where('name', $role)->value('id');
        $company = Company::create([
            'name' => 'Company '.$role,
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
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    public function test_index_returns_view(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/drivers');

        $response->assertOk()->assertViewIs('drivers.index');
        $response->assertViewHas('company_id', $admin->company_id);
    }

    public function test_create_and_edit_views(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Sukhwinder Singh',
            'mobile' => '9876543210',
            'company_id' => $admin->company_id,
        ]);

        $this->actingAs($admin)->get('/drivers/create')
            ->assertOk()
            ->assertViewIs('drivers.create');

        $this->actingAs($admin)->get("/drivers/{$driver->id}/edit")
            ->assertOk()
            ->assertViewIs('drivers.edit')
            ->assertViewHas('driver');
    }

    public function test_store_creates_driver_with_company_scope(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post('/drivers', [
            'name' => 'John Driver',
            'mobile' => '9876543210',
            'license_number' => 'LIC12345',
            'address' => '123 Street',
        ]);

        $response->assertRedirect(route('drivers.index'));
        $this->assertDatabaseHas('drivers', [
            'name' => 'John Driver',
            'mobile' => '9876543210',
            'license_number' => 'LIC12345',
            'company_id' => $admin->company_id,
        ]);
    }

    public function test_store_ajax_returns_json(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson('/drivers', [
                'name' => 'Ajax Driver',
                'mobile' => '9811223344',
                'license_number' => 'LIC981122',
                'address' => 'Ajax Hub',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Driver created successfully!')
            ->assertJsonPath('driver.name', 'Ajax Driver');
    }

    public function test_store_rejects_duplicate_mobile_and_license_in_company(): void
    {
        $admin = $this->makeAdmin();

        Driver::create([
            'name' => 'Existing',
            'mobile' => '9876543210',
            'license_number' => 'DL-99999',
            'company_id' => $admin->company_id,
        ]);

        // Duplicate mobile
        $response = $this->actingAs($admin)->from('/drivers/create')->post('/drivers', [
            'name' => 'New Driver',
            'mobile' => '9876543210',
        ]);

        $response->assertRedirect('/drivers/create');
        $response->assertSessionHasErrors(['mobile']);

        // Duplicate license_number
        $resLic = $this->actingAs($admin)->from('/drivers/create')->post('/drivers', [
            'name' => 'Another Driver',
            'mobile' => '9111222333',
            'license_number' => 'DL-99999',
        ]);
        $resLic->assertSessionHasErrors(['license_number']);
    }

    public function test_update_changes_driver_and_uniqueness_rules(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Old Name',
            'mobile' => '9876543210',
            'company_id' => $admin->company_id,
            'license_number' => 'LIC123',
        ]);

        $response = $this->actingAs($admin)->put("/drivers/{$driver->id}", [
            'name' => 'New Name',
            'mobile' => '9998887776',
            'license_number' => 'LIC999',
            'address' => 'New Address',
        ]);

        $response->assertRedirect(route('drivers.index'));
        $driver->refresh();
        $this->assertSame('New Name', $driver->name);
        $this->assertSame('9998887776', $driver->mobile);
        $this->assertSame('LIC999', $driver->license_number);
        $this->assertSame('New Address', $driver->address);
    }

    public function test_update_ajax_returns_json(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Initial Driver',
            'mobile' => '9870001111',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->putJson("/drivers/{$driver->id}", [
                'name' => 'Updated Driver Name',
                'mobile' => '9870001111', // keeping same mobile ignores self
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Driver updated successfully!')
            ->assertJsonPath('driver.name', 'Updated Driver Name');
    }

    public function test_destroy_soft_deletes_driver(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Delete Me',
            'mobile' => '9990001111',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)->delete("/drivers/{$driver->id}", [
            'delete_remark' => 'Driver left',
        ]);

        $response->assertRedirect(route('drivers.index'));
        $this->assertSoftDeleted('drivers', [
            'id' => $driver->id,
            'deleted_by' => $admin->id,
            'deleted_remark' => 'Driver left',
        ]);
    }

    public function test_destroy_unauthorized_when_company_mismatch(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $driver = Driver::create([
            'name' => 'Other Driver',
            'mobile' => '9990002222',
            'company_id' => $otherAdmin->company_id,
        ]);

        $response = $this->actingAs($admin)->delete("/drivers/{$driver->id}", [
            'delete_remark' => 'Hacking',
        ]);

        $response->assertStatus(403);
    }

    public function test_destroy_validation_requires_delete_remark(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Delete Test',
            'mobile' => '9990003333',
            'company_id' => $admin->company_id,
        ]);

        $response = $this->actingAs($admin)->delete("/drivers/{$driver->id}", []);
        $response->assertSessionHasErrors(['delete_remark']);
    }

    public function test_get_drivers_api_returns_company_scoped_list(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeAdmin();

        Driver::create([
            'name' => 'In Company',
            'mobile' => '9000000001',
            'company_id' => $admin->company_id,
        ]);

        Driver::create([
            'name' => 'Other Company',
            'mobile' => '9000000002',
            'company_id' => $other->company_id,
        ]);

        $response = $this->actingAs($admin)->get('/api/drivers');

        $response->assertOk();
        $names = collect($response->json())->pluck('name');
        $this->assertTrue($names->contains('In Company'));
        $this->assertFalse($names->contains('Other Company'));
    }

    public function test_get_drivers_datatable_ajax_search_ordering_and_pagination(): void
    {
        $admin = $this->makeAdmin();
        $d1 = Driver::create([
            'name' => 'Amrik Singh',
            'mobile' => '9810011111',
            'license_number' => 'DL-AMR-01',
            'address' => 'Delhi Transport Nagar',
            'company_id' => $admin->company_id,
        ]);
        $d2 = Driver::create([
            'name' => 'Bant Singh',
            'mobile' => '9820022222',
            'license_number' => 'DL-BNT-02',
            'address' => 'Chandigarh Hub',
            'company_id' => $admin->company_id,
        ]);

        // 1. Basic listing
        $resp = $this->actingAs($admin)->getJson('/drivers/data?draw=1&start=0&length=10');
        $resp->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonCount(2, 'data');
        $this->assertStringContainsString('edit-driver-btn', $resp->json('data.0.actions'));

        // 2. Global search
        $searchResp = $this->actingAs($admin)->getJson('/drivers/data?draw=2&search[value]=Chandigarh');
        $searchResp->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Bant Singh');

        // 3. Column searches
        // Column 1 (name)
        $this->actingAs($admin)->getJson('/drivers/data?columns[1][search][value]=Amrik')
            ->assertJsonPath('recordsFiltered', 1);
        // Column 2 (mobile)
        $this->actingAs($admin)->getJson('/drivers/data?columns[2][search][value]=9820022222')
            ->assertJsonPath('recordsFiltered', 1);
        // Column 3 (license_number)
        $this->actingAs($admin)->getJson('/drivers/data?columns[3][search][value]=DL-AMR-01')
            ->assertJsonPath('recordsFiltered', 1);
        // Column 4 (address)
        $this->actingAs($admin)->getJson('/drivers/data?columns[4][search][value]=Transport')
            ->assertJsonPath('recordsFiltered', 1);

        // 4. Order sorting
        // Case 0: Sr. No
        $this->actingAs($admin)->getJson('/drivers/data?order[0][column]=0&order[0][dir]=asc')
            ->assertJsonPath('data.0.name', 'Amrik Singh');
        // Case 1: Name desc
        $this->actingAs($admin)->getJson('/drivers/data?order[0][column]=1&order[0][dir]=desc')
            ->assertJsonPath('data.0.name', 'Bant Singh');
        // Case 2: Mobile
        $this->actingAs($admin)->getJson('/drivers/data?order[0][column]=2&order[0][dir]=desc')
            ->assertJsonPath('data.0.mobile', '9820022222');
        // Case 3: License
        $this->actingAs($admin)->getJson('/drivers/data?order[0][column]=3&order[0][dir]=desc')
            ->assertJsonPath('data.0.license_number', 'DL-BNT-02');
        // Case 4: Address
        $this->actingAs($admin)->getJson('/drivers/data?order[0][column]=4&order[0][dir]=asc')
            ->assertJsonPath('data.0.address', 'Chandigarh Hub');
        // Default switch
        $this->actingAs($admin)->getJson('/drivers/data?order[0][column]=99&order[0][dir]=asc')
            ->assertOk();
    }

    // -----------------------------------------------------------------
    // DRIVER IMPORT & BULK UPLOAD TESTS
    // -----------------------------------------------------------------

    public function test_driver_bulk_upload_template_download(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get('/drivers/bulk-upload-template');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('Name,Mobile,"License Number",Address', $content);
    }

    public function test_driver_import_direct_collection_all_branches_and_edge_cases(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $import = new DriverImport();

        // 1. chunkSize method check
        $this->assertSame(500, $import->chunkSize());

        // Pre-create existing driver for DB duplicate check
        Driver::create([
            'name' => 'Existing Driver',
            'mobile' => '9800000001',
            'license_number' => 'DL-EXIST-01',
            'company_id' => $admin->company_id,
        ]);

        // Pre-seed in-file tracking arrays via reflection to test in-file duplication branch
        $refMobiles = new \ReflectionProperty(DriverImport::class, 'uploadedMobiles');
        $refMobiles->setAccessible(true);
        $refMobiles->setValue($import, ['9800000077']);

        $refLicenses = new \ReflectionProperty(DriverImport::class, 'uploadedLicenses');
        $refLicenses->setAccessible(true);
        $refLicenses->setValue($import, ['DL-FILE-DUP-99']);

        $rows = collect([
            // Row 2 (index 0): Valid driver with all fields
            [
                'name' => 'Harjit Singh',
                'mobile' => '9800000002',
                'license_number' => 'DL-HARJIT-02',
                'address' => 'Transport Nagar Amritsar',
            ],
            // Row 3 (index 1): Valid driver with empty optional license & address
            [
                'name' => 'Kuldeep Kumar',
                'mobile' => '9800000003',
                'license_number' => '',
                'address' => '',
            ],
            // Row 4 (index 2): Validation failure - missing name
            [
                'name' => '',
                'mobile' => '9800000004',
                'license_number' => 'DL-FAIL-04',
            ],
            // Row 5 (index 3): Validation failure - invalid mobile (not 10 digits)
            [
                'name' => 'Bad Mobile',
                'mobile' => '12345',
            ],
            // Row 6 (index 4): DB duplicate mobile (exists in DB)
            [
                'name' => 'Duplicate Mobile DB',
                'mobile' => '9800000001',
                'license_number' => 'DL-UNIQUE-06',
            ],
            // Row 7 (index 5): In-file duplicate mobile (tracked in uploadedMobiles)
            [
                'name' => 'Duplicate Mobile InFile',
                'mobile' => '9800000077',
                'license_number' => 'DL-UNIQUE-07',
            ],
            // Row 8 (index 6): DB duplicate license (exists in DB)
            [
                'name' => 'Duplicate License DB',
                'mobile' => '9800000008',
                'license_number' => 'DL-EXIST-01',
            ],
            // Row 9 (index 7): In-file duplicate license (tracked in uploadedLicenses)
            [
                'name' => 'Duplicate License InFile',
                'mobile' => '9800000009',
                'license_number' => 'DL-FILE-DUP-99',
            ],
        ]);

        $import->collection($rows);

        // 2 valid drivers should be created
        $this->assertSame(2, $import->successCount);
        $this->assertCount(6, $import->errors);

        $this->assertDatabaseHas('drivers', [
            'name' => 'Harjit Singh',
            'mobile' => '9800000002',
            'license_number' => 'DL-HARJIT-02',
            'company_id' => $admin->company_id,
        ]);

        $this->assertDatabaseHas('drivers', [
            'name' => 'Kuldeep Kumar',
            'mobile' => '9800000003',
            'license_number' => null,
            'address' => null,
            'company_id' => $admin->company_id,
        ]);

        // Error message assertions
        $this->assertSame(4, $import->errors[0]['row']); // Row 4 (index 2)
        $this->assertSame(5, $import->errors[1]['row']); // Row 5 (index 3)
        $this->assertStringContainsString("Mobile number '9800000001' already exists.", $import->errors[2]['errors'][0]);
        $this->assertStringContainsString("Mobile number '9800000077' is duplicated in the uploaded file.", $import->errors[3]['errors'][0]);
        $this->assertStringContainsString("License number 'DL-EXIST-01' already exists.", $import->errors[4]['errors'][0]);
        $this->assertStringContainsString("License number 'DL-FILE-DUP-99' is duplicated in the uploaded file.", $import->errors[5]['errors'][0]);
    }


    public function test_driver_bulk_upload_http_endpoint_flows(): void
    {
        $admin = $this->makeAdmin();

        // 1. Validation error: Missing file
        $resNoFile = $this->actingAs($admin)->postJson('/drivers/bulk-upload', []);
        $resNoFile->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'File validation failed.');

        // 2. Successful bulk upload via CSV file
        $csvContent = "Name,Mobile,License Number,Address\n" .
                      "Malkit Singh,9812345678,DL-MLK-01,Bathinda Yard\n" .
                      "Paramjit Singh,9812345679,,Ludhiana\n";

        $file = UploadedFile::fake()->createWithContent('drivers.csv', $csvContent);

        $resSuccess = $this->actingAs($admin)->postJson('/drivers/bulk-upload', [
            'file' => $file,
        ]);

        $resSuccess->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('success_count', 2)
            ->assertJsonPath('imported', 2);

        $this->assertDatabaseHas('drivers', [
            'name' => 'Malkit Singh',
            'mobile' => '9812345678',
            'company_id' => $admin->company_id,
        ]);

        // 3. Partial success / errors returned via bulk upload
        $csvWithErrors = "Name,Mobile,License Number,Address\n" .
                         "Valid New Driver,9855566677,DL-NEW-01,Delhi\n" .
                         ",9855566678,,Invalid missing name\n"; // Missing name

        $filePartial = UploadedFile::fake()->createWithContent('partial_drivers.csv', $csvWithErrors);
        $resPartial = $this->actingAs($admin)->postJson('/drivers/bulk-upload', [
            'file' => $filePartial,
        ]);

        $resPartial->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('partial_success', true)
            ->assertJsonPath('success_count', 1)
            ->assertJsonCount(1, 'errors');

        // 4. Zero success - all rows invalid
        $csvAllBad = "Name,Mobile,License Number,Address\n" .
                     ",9855566699,,Missing name\n";
        $fileAllBad = UploadedFile::fake()->createWithContent('bad_drivers.csv', $csvAllBad);
        $resAllBad = $this->actingAs($admin)->postJson('/drivers/bulk-upload', [
            'file' => $fileAllBad,
        ]);
        $resAllBad->assertOk()
            ->assertJsonPath('success', false)
            ->assertJsonPath('partial_success', false)
            ->assertJsonPath('message', 'No driver records were imported.');

        // 5. Corrupt file triggers catch (\Throwable $e) handler -> 500
        $corruptFile = UploadedFile::fake()->createWithContent('corrupt.xlsx', 'invalid zip binary content');
        $resCorrupt = $this->actingAs($admin)->postJson('/drivers/bulk-upload', [
            'file' => $corruptFile,
        ]);
        $resCorrupt->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unable to process the file. Please check the file and try again.');
    }

    public function test_driver_model_relationships(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Relation Driver',
            'mobile' => '9899999999',
            'company_id' => $admin->company_id,
        ]);

        $this->assertEquals($admin->company_id, $driver->company->id);
    }

    public function test_destroy_catches_exception_and_redirects_with_error(): void
    {
        $admin = $this->makeAdmin();
        $driver = Driver::create([
            'name' => 'Error Driver',
            'mobile' => '9999900001',
            'company_id' => $admin->company_id,
        ]);

        Driver::deleting(function () {
            throw new \Exception('Database failure simulation');
        });

        $response = $this->actingAs($admin)->delete("/drivers/{$driver->id}", [
            'delete_remark' => 'Deleting for test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Unable to delete driver. Please try again.');
    }

    public function test_accountant_cannot_access_driver_routes(): void
    {
        $accountant = $this->makeUserWithRole('accountant');

        $this->actingAs($accountant)->get('/drivers')->assertForbidden();
        $this->actingAs($accountant)->get('/drivers/create')->assertForbidden();
        $this->actingAs($accountant)->post('/drivers', [
            'name' => 'Blocked',
            'mobile' => '9000000000',
        ])->assertForbidden();
    }

    public function test_moderator_can_view_and_create_but_cannot_destroy_drivers(): void
    {
        $moderator = $this->makeUserWithRole('moderator');
        $driver = Driver::create([
            'name' => 'Mod Driver',
            'mobile' => '9111111111',
            'company_id' => $moderator->company_id,
        ]);

        $this->actingAs($moderator)->get('/drivers')->assertOk();
        $this->actingAs($moderator)->post('/drivers', [
            'name' => 'New Mod Driver',
            'mobile' => '9222222222',
        ])->assertRedirect();

        $this->actingAs($moderator)
            ->delete("/drivers/{$driver->id}", ['delete_remark' => 'Should be forbidden'])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_drivers(): void
    {
        $this->get('/drivers')->assertRedirect(route('login'));
    }
}
