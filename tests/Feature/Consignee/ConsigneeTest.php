<?php

namespace Tests\Feature\Consignee;

use App\Http\Middleware\EnsureCustomerConsigneeContext;
use App\Imports\ConsigneeImport;
use App\Mail\CustomerPortalAccessMail;
use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ConsigneeTest extends TestCase
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
        UserRole::firstOrCreate(['id' => 7, 'name' => 'customer']);
    }

    protected function makeUser(string $role = 'admin', ?Company $company = null): User
    {
        $roleId = UserRole::where('name', $role)->value('id');
        $company = $company ?? Company::create([
            'name' => 'Company ' . $role . ' ' . uniqid(),
            'user_id' => null,
            'is_blocked' => false,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);

        return User::factory()->create([
            'user_role_id' => $roleId,
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

    /*
    |--------------------------------------------------------------------------
    | INDEX & DATATABLES
    |--------------------------------------------------------------------------
    */

    public function test_index_returns_view(): void
    {
        $user = $this->makeUser('admin');

        $response = $this->actingAs($user)->get('/consignees');

        $response->assertOk()->assertViewIs('consignees.index');
    }

    public function test_index_ajax_returns_datatables_json_with_action_buttons(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Alpha Express',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'address' => '123 Alpha Road',
        ]);

        // Consignee from other company should not appear
        $otherCompany = Company::create([
            'name' => 'Other Co',
            'is_blocked' => false,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        Consignee::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Co Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9111111111',
        ]);

        $headers = ['X-Requested-With' => 'XMLHttpRequest'];

        $response = $this->actingAs($user)->getJson('/consignees?draw=1&start=0&length=10', $headers);

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'DT_RowIndex',
                    'id',
                    'name',
                    'city',
                    'state',
                    'country',
                    'contact_number',
                    'actions',
                ],
            ],
        ]);

        $data = $response->json();
        $this->assertEquals(1, $data['recordsTotal']);
        $this->assertEquals(1, $data['recordsFiltered']);
        $this->assertCount(1, $data['data']);
        $this->assertEquals($consignee->id, $data['data'][0]['id']);
        $this->assertStringContainsString('Alpha Express', $data['data'][0]['name']);
        $this->assertStringContainsString(route('consignees.edit', $consignee->id), $data['data'][0]['actions']);
        $this->assertStringContainsString(route('consignees.destroy', $consignee->id), $data['data'][0]['actions']);
    }

    public function test_index_ajax_filters_with_global_search(): void
    {
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Target Customer',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'gst_number' => '24AAAAA0000A1Z5',
            'pan_number' => 'AAAAA0000A',
            'address' => 'Unique Address Road',
        ]);

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Other Guy',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9123456789',
        ]);

        // Search by name
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=Target', $headers);
        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Search by address
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=Unique Address', $headers);
        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Search by contact
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=9876543210', $headers);
        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Search by GST
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=24AAAAA0000A1Z5', $headers);
        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Search by PAN
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=AAAAA0000A', $headers);
        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Search by City name
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=Ahmedabad', $headers);
        $response->assertOk();
        $this->assertEquals(2, $response->json('recordsFiltered'));

        // Search by State name
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=Gujarat', $headers);
        $response->assertOk();
        $this->assertEquals(2, $response->json('recordsFiltered'));

        // Search by Country name
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&search[value]=India', $headers);
        $response->assertOk();
        $this->assertEquals(2, $response->json('recordsFiltered'));
    }

    public function test_index_ajax_filters_with_column_specific_search(): void
    {
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Alpha Express',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Beta Logistics',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9123456789',
        ]);

        // Column 1: Name search
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&columns[1][search][value]=Alpha', $headers);
        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Column 2: City search
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&columns[2][search][value]=Ahmedabad', $headers);
        $response->assertOk();
        $this->assertEquals(2, $response->json('recordsFiltered'));

        // Column 3: State search
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&columns[3][search][value]=Gujarat', $headers);
        $response->assertOk();
        $this->assertEquals(2, $response->json('recordsFiltered'));

        // Column 4: Country search
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&columns[4][search][value]=India', $headers);
        $response->assertOk();
        $this->assertEquals(2, $response->json('recordsFiltered'));

        // Column 5: Contact number search
        $response = $this->actingAs($user)->getJson('/consignees?draw=1&columns[5][search][value]=9876543210', $headers);
        $response->assertOk();
        $this->assertEquals(1, $response->json('recordsFiltered'));
    }

    public function test_index_ajax_sorts_by_various_columns(): void
    {
        $headers = ['X-Requested-With' => 'XMLHttpRequest'];
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Alpha',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '1111111111',
        ]);

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Beta',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '2222222222',
        ]);

        // Column 0: Sr No (break / no specific sort applied)
        $this->actingAs($user)->getJson('/consignees?draw=1&order[0][column]=0&order[0][dir]=asc', $headers)->assertOk();

        // Column 1: Name sort
        $this->actingAs($user)->getJson('/consignees?draw=1&order[0][column]=1&order[0][dir]=desc', $headers)->assertOk();

        // Column 2: City sort with join
        $this->actingAs($user)->getJson('/consignees?draw=1&order[0][column]=2&order[0][dir]=asc', $headers)->assertOk();

        // Column 3: State sort with join
        $this->actingAs($user)->getJson('/consignees?draw=1&order[0][column]=3&order[0][dir]=asc', $headers)->assertOk();

        // Column 4: Country sort with join
        $this->actingAs($user)->getJson('/consignees?draw=1&order[0][column]=4&order[0][dir]=asc', $headers)->assertOk();

        // Column 5: Contact sort
        $this->actingAs($user)->getJson('/consignees?draw=1&order[0][column]=5&order[0][dir]=desc', $headers)->assertOk();

        // Default branch
        $this->actingAs($user)->getJson('/consignees?draw=1&order[0][column]=99&order[0][dir]=asc', $headers)->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE VIEW
    |--------------------------------------------------------------------------
    */

    public function test_create_returns_view_with_countries(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($user)->get(route('consignees.create'));

        $response->assertOk();
        $response->assertViewIs('consignees.create');
        $response->assertViewHas('countries');
        $response->assertViewHas('states', []);
        $response->assertViewHas('cities', []);
    }

    public function test_create_view_with_old_country_and_state_populates_dropdowns(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($user)
            ->withSession([
                '_old_input' => [
                    'country_id' => $country->id,
                    'state_id' => $state->id,
                ],
            ])
            ->get(route('consignees.create'));

        $response->assertOk();
        $response->assertViewHas('states');
        $response->assertViewHas('cities');
        $this->assertNotEmpty($response->viewData('states'));
        $this->assertNotEmpty($response->viewData('cities'));
    }

    /*
    |--------------------------------------------------------------------------
    | BULK UPLOAD TEMPLATE
    |--------------------------------------------------------------------------
    */

    public function test_bulk_upload_template_downloads_csv(): void
    {
        $user = $this->makeUser('admin');

        $response = $this->actingAs($user)->get(route('consignees.bulkUploadTemplate'));

        $response->assertOk();
        $this->assertTrue(
            str_contains((string) $response->headers->get('content-disposition'), 'customer-bulk-upload-template.csv')
        );

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Name', $content);
        $this->assertStringContainsString('Contact Number', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('GST Number', $content);
        $this->assertStringContainsString('PAN Number', $content);
        $this->assertStringContainsString('Country', $content);
        $this->assertStringContainsString('State', $content);
        $this->assertStringContainsString('City', $content);
        $this->assertStringContainsString('Address', $content);
    }

    /*
    |--------------------------------------------------------------------------
    | BULK UPLOAD
    |--------------------------------------------------------------------------
    */

    public function test_bulk_upload_fails_when_file_is_invalid(): void
    {
        $user = $this->makeUser('admin');

        $file = UploadedFile::fake()->create('invalid.txt', 10, 'text/plain');

        $response = $this->actingAs($user)->postJson(route('consignees.bulkUpload'), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'File validation failed.',
        ]);
    }

    public function test_bulk_upload_successful_import(): void
    {
        $user = $this->makeUser('admin');

        $mockImport = new ConsigneeImport();
        $mockImport->successCount = 2;
        $mockImport->errors = [];
        $this->app->instance(ConsigneeImport::class, $mockImport);

        Excel::shouldReceive('import')->once();

        $file = UploadedFile::fake()->create('customers.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($user)->postJson(route('consignees.bulkUpload'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'imported' => 2,
            'success_count' => 2,
            'total' => 2,
            'errors' => [],
        ]);
        $this->assertStringContainsString('2 customer(s) imported successfully.', $response->json('message'));
    }

    public function test_bulk_upload_partial_success_with_errors(): void
    {
        $user = $this->makeUser('admin');

        $mockImport = new ConsigneeImport();
        $mockImport->successCount = 1;
        $mockImport->errors = ['Row 2: Contact number is invalid.'];
        $this->app->instance(ConsigneeImport::class, $mockImport);

        Excel::shouldReceive('import')->once();

        $file = UploadedFile::fake()->create('customers.csv', 50, 'text/csv');

        $response = $this->actingAs($user)->postJson(route('consignees.bulkUpload'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'partial_success' => true,
            'imported' => 1,
            'total' => 2,
            'errors' => ['Row 2: Contact number is invalid.'],
        ]);
        $this->assertStringContainsString('1 customer(s) imported successfully. Some rows have errors.', $response->json('message'));
    }

    public function test_bulk_upload_zero_records_imported_with_errors(): void
    {
        $user = $this->makeUser('admin');

        $mockImport = new ConsigneeImport();
        $mockImport->successCount = 0;
        $mockImport->errors = ['Row 1: Contact number is required.'];
        $this->app->instance(ConsigneeImport::class, $mockImport);

        Excel::shouldReceive('import')->once();

        $file = UploadedFile::fake()->create('customers.csv', 50, 'text/csv');

        $response = $this->actingAs($user)->postJson(route('consignees.bulkUpload'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'partial_success' => false,
            'message' => 'No customer records were imported.',
            'imported' => 0,
            'total' => 1,
            'errors' => ['Row 1: Contact number is required.'],
        ]);
    }

    public function test_bulk_upload_handles_exception_cleanly(): void
    {
        $user = $this->makeUser('admin');

        Excel::shouldReceive('import')->andThrow(new \Exception('File processing crashed'));

        $file = UploadedFile::fake()->create('customers.csv', 50, 'text/csv');

        $response = $this->actingAs($user)->postJson(route('consignees.bulkUpload'), [
            'file' => $file,
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'message' => 'Unable to process the file. Please check the file and try again.',
            'errors' => ['File processing crashed'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE & CUSTOMER PORTAL
    |--------------------------------------------------------------------------
    */

    public function test_store_creates_consignee_with_company_scope(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $payload = [
            'name' => 'ACME Ltd',
            'address' => '123 Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'email' => 'acme@example.com',
        ];

        $response = $this->actingAs($user)->post('/consignees', $payload);

        $response->assertRedirect(route('consignees.index'));
        $this->assertDatabaseHas('consignees', [
            'name' => 'ACME Ltd',
            'company_id' => $user->company_id,
            'city_id' => $city->id,
        ]);
    }

    public function test_store_via_ajax_returns_json_response(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $payload = [
            'name' => 'Ajax Customer',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9988776655',
        ];

        $response = $this->actingAs($user)->postJson('/consignees', $payload);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Customer created successfully!',
            'customer' => [
                'name' => 'Ajax Customer',
            ],
        ]);
    }

    public function test_store_enforces_uniqueness_per_company(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Existing',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9999999999',
            'email' => 'dupe@example.com',
            'pan_number' => 'ABCDE1234F',
        ]);

        // Duplicate contact number
        $response = $this->actingAs($user)->from('/consignees/create')->post('/consignees', [
            'name' => 'New One',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9999999999',
        ]);
        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['contact_number']);

        // Duplicate email
        $response = $this->actingAs($user)->from('/consignees/create')->post('/consignees', [
            'name' => 'New One',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9888888888',
            'email' => 'dupe@example.com',
        ]);
        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['email']);

        // Duplicate PAN
        $response = $this->actingAs($user)->from('/consignees/create')->post('/consignees', [
            'name' => 'New One',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9777777777',
            'pan_number' => 'ABCDE1234F',
        ]);
        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['pan_number']);
    }

    public function test_store_validates_state_and_city_relation(): void
    {
        $user = $this->makeUser('admin');
        [$country1, $state1, $city1] = $this->seedGeo();

        $country2 = Country::create(['name' => 'USA', 'code' => 'US']);
        $state2 = State::create(['name' => 'California', 'country_id' => $country2->id]);
        $city2 = City::create(['name' => 'Los Angeles', 'state_id' => $state2->id, 'country_id' => $country2->id]);

        // State doesn't belong to country
        $response = $this->actingAs($user)->from('/consignees/create')->post('/consignees', [
            'name' => 'Mismatch State',
            'country_id' => $country1->id,
            'state_id' => $state2->id, // belongs to USA
            'city_id' => $city1->id,
            'contact_number' => '9111111111',
        ]);
        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['state_id']);

        // City doesn't belong to state
        $response = $this->actingAs($user)->from('/consignees/create')->post('/consignees', [
            'name' => 'Mismatch City',
            'country_id' => $country1->id,
            'state_id' => $state1->id,
            'city_id' => $city2->id, // belongs to California
            'contact_number' => '9111111112',
        ]);
        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['city_id']);
    }

    public function test_store_without_email_does_not_create_portal_user(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $response = $this->actingAs($user)->post('/consignees', [
            'name' => 'No Email Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'email' => null,
        ]);

        $response->assertRedirect(route('consignees.index'));
        $response->assertSessionHas('success', function ($msg) {
            return str_contains($msg, 'Customer login not created because email is empty.');
        });
    }

    public function test_store_with_existing_non_customer_user_skips_portal_creation(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        // Create user with admin role
        $adminRole = UserRole::where('name', 'admin')->first();
        User::factory()->create([
            'email' => 'admin_user@example.com',
            'user_role_id' => $adminRole->id,
        ]);

        $response = $this->actingAs($user)->post('/consignees', [
            'name' => 'Admin Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'email' => 'admin_user@example.com',
        ]);

        $response->assertRedirect(route('consignees.index'));
        $response->assertSessionHas('success', function ($msg) {
            return str_contains($msg, 'Consignee created. Login not auto-created because email is linked to a non-customer user.');
        });
    }

    public function test_store_with_existing_customer_user_links_login(): void
    {
        Mail::fake();
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $customerRole = UserRole::where('name', 'customer')->first();
        $existingCustomer = User::factory()->create([
            'email' => 'portal_customer@example.com',
            'user_role_id' => $customerRole->id,
        ]);

        $response = $this->actingAs($user)->post('/consignees', [
            'name' => 'Linked Customer',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'email' => 'portal_customer@example.com',
        ]);

        $response->assertRedirect(route('consignees.index'));
        $response->assertSessionHas('success', function ($msg) {
            return str_contains($msg, 'Existing customer login linked and email sent.');
        });

        $consignee = Consignee::where('contact_number', '9876543210')->first();
        $this->assertTrue($existingCustomer->consignees->contains($consignee->id));

        Mail::assertSent(CustomerPortalAccessMail::class);
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT & UPDATE
    |--------------------------------------------------------------------------
    */

    public function test_edit_returns_view_with_consignee_data(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'To Edit',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($user)->get(route('consignees.edit', $consignee->id));

        $response->assertOk();
        $response->assertViewIs('consignees.edit');
        $response->assertViewHas('consignee', $consignee);
        $response->assertViewHas('countries');
        $response->assertViewHas('states');
        $response->assertViewHas('cities');
    }

    public function test_edit_aborts_403_for_other_company(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'is_blocked' => false,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        $consignee = Consignee::create([
            'company_id' => $otherCompany->id,
            'name' => 'Not Mine',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($user)->get(route('consignees.edit', $consignee->id));

        $response->assertStatus(403);
    }

    public function test_update_changes_fields_and_uppercases_ids(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Old Name',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'pan_number' => 'abcde1234f',
            'gst_number' => '12abcde1234f1z5',
        ]);

        $response = $this->actingAs($user)->put("/consignees/{$consignee->id}", [
            'name' => 'New Name',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '1234567890',
            'pan_number' => 'PQRST4321L',
            'gst_number' => '22PQRST4321L1Z9',
        ]);

        $response->assertRedirect(route('consignees.index'));
        $consignee->refresh();
        $this->assertSame('New Name', $consignee->name);
        $this->assertSame('PQRST4321L', $consignee->pan_number);
        $this->assertSame('22PQRST4321L1Z9', $consignee->gst_number);
    }

    public function test_update_aborts_403_for_other_company(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'is_blocked' => false,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        $consignee = Consignee::create([
            'company_id' => $otherCompany->id,
            'name' => 'Not Mine',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($user)->put(route('consignees.update', $consignee->id), [
            'name' => 'Attempt Update',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_validates_duplicate_email_and_pan_against_other_records(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee1 = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Consignee One',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9000000001',
            'email' => 'one@example.com',
            'pan_number' => 'ABCDE1111F',
        ]);

        $consignee2 = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Consignee Two',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9000000002',
            'email' => 'two@example.com',
            'pan_number' => 'ABCDE2222F',
        ]);

        // Attempt duplicate email from consignee1
        $response = $this->actingAs($user)->put(route('consignees.update', $consignee2->id), [
            'name' => 'Consignee Two Updated',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9000000002',
            'email' => 'one@example.com',
        ]);
        $response->assertSessionHasErrors(['email']);

        // Attempt duplicate pan from consignee1
        $response = $this->actingAs($user)->put(route('consignees.update', $consignee2->id), [
            'name' => 'Consignee Two Updated',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9000000002',
            'pan_number' => 'ABCDE1111F',
        ]);
        $response->assertSessionHasErrors(['pan_number']);

        // Keeping own email and pan should succeed
        $response = $this->actingAs($user)->put(route('consignees.update', $consignee2->id), [
            'name' => 'Consignee Two Renamed',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9000000002',
            'email' => 'two@example.com',
            'pan_number' => 'ABCDE2222F',
        ]);
        $response->assertRedirect(route('consignees.index'));
    }

    public function test_update_validates_state_and_city_relation(): void
    {
        $user = $this->makeUser('admin');
        [$country1, $state1, $city1] = $this->seedGeo();

        $country2 = Country::create(['name' => 'USA', 'code' => 'US']);
        $state2 = State::create(['name' => 'California', 'country_id' => $country2->id]);
        $city2 = City::create(['name' => 'Los Angeles', 'state_id' => $state2->id, 'country_id' => $country2->id]);

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'My Consignee',
            'country_id' => $country1->id,
            'state_id' => $state1->id,
            'city_id' => $city1->id,
            'contact_number' => '9876543210',
        ]);

        // State mismatch with country
        $response = $this->actingAs($user)->put(route('consignees.update', $consignee->id), [
            'name' => 'My Consignee',
            'country_id' => $country1->id,
            'state_id' => $state2->id,
            'city_id' => $city1->id,
            'contact_number' => '9876543210',
        ]);
        $response->assertSessionHasErrors(['state_id']);

        // City mismatch with state
        $response = $this->actingAs($user)->put(route('consignees.update', $consignee->id), [
            'name' => 'My Consignee',
            'country_id' => $country1->id,
            'state_id' => $state1->id,
            'city_id' => $city2->id,
            'contact_number' => '9876543210',
        ]);
        $response->assertSessionHasErrors(['city_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function test_destroy_soft_deletes_consignee(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'To Delete',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($user)->delete("/consignees/{$consignee->id}", [
            'delete_remark' => 'Duplicate entry',
        ]);

        $response->assertRedirect(route('consignees.index'));
        $this->assertSoftDeleted('consignees', ['id' => $consignee->id, 'deleted_remark' => 'Duplicate entry']);
    }

    public function test_destroy_aborts_403_for_other_company(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'is_blocked' => false,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        $consignee = Consignee::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($user)->delete(route('consignees.destroy', $consignee->id), [
            'delete_remark' => 'Delete attempt',
        ]);

        $response->assertStatus(403);
    }

    public function test_destroy_requires_delete_remark(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'To Delete',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($user)->delete(route('consignees.destroy', $consignee->id), []);

        $response->assertSessionHasErrors(['delete_remark']);
    }

    /*
    |--------------------------------------------------------------------------
    | API & HELPERS
    |--------------------------------------------------------------------------
    */

    public function test_get_consignee_details_returns_json(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Detail',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'email' => 'detail@example.com',
        ]);

        $response = $this->actingAs($user)->get("/api/consignees/{$consignee->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $consignee->id,
            'name' => 'Detail',
            'email' => 'detail@example.com',
        ]);
    }

    public function test_get_consignee_api_returns_404_when_not_found(): void
    {
        $user = $this->makeUser('admin');

        $response = $this->actingAs($user)->get('/api/consignees/999999');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Consignee not found']);
    }

    public function test_get_consignee_api_returns_404_for_other_company(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $otherCompany = Company::create([
            'name' => 'Other Co',
            'is_blocked' => false,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        $consignee = Consignee::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $response = $this->actingAs($user)->get("/api/consignees/{$consignee->id}");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Consignee not found']);
    }

    public function test_is_consignee_unique_constraint_error_helper(): void
    {
        $controller = new \App\Http\Controllers\ConsigneeController();
        $method = new \ReflectionMethod($controller, 'isConsigneeUniqueConstraintError');
        $method->setAccessible(true);

        // Matching 1062 duplicate error
        $matchingException = new QueryException(
            'default',
            'insert into consignees',
            [],
            new \Exception('Duplicate entry for key consignees_company_pan_unique')
        );
        $matchingException->errorInfo = ['23000', '1062', 'Duplicate entry for key consignees_company_pan_unique'];

        // Non-matching error
        $nonMatchingException = new QueryException(
            'default',
            'insert into consignees',
            [],
            new \Exception('General database error')
        );
        $nonMatchingException->errorInfo = ['HY000', '1000', 'General database error'];

        $this->assertTrue($method->invoke($controller, $matchingException));
        $this->assertFalse($method->invoke($controller, $nonMatchingException));
    }

    public function test_store_handles_query_exception_duplicate_pan(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        Consignee::saving(function () {
            $exception = new QueryException(
                'default',
                'insert into consignees',
                [],
                new \Exception('Duplicate entry for key consignees_company_pan_unique')
            );
            $exception->errorInfo = ['23000', '1062', 'Duplicate entry for key consignees_company_pan_unique'];
            throw $exception;
        });

        try {
            $payload = [
                'name' => 'Catch Block Test',
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'contact_number' => '9988112233',
            ];

            $response = $this->actingAs($user)->from('/consignees/create')->post('/consignees', $payload);
            $response->assertRedirect('/consignees/create');
            $response->assertSessionHasErrors(['pan_number']);
        } finally {
            Consignee::flushEventListeners();
        }
    }

    public function test_store_rethrows_non_unique_query_exception(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        Consignee::saving(function () {
            $exception = new QueryException(
                'default',
                'insert into consignees',
                [],
                new \Exception('General database failure')
            );
            $exception->errorInfo = ['HY000', '2000', 'General database failure'];
            throw $exception;
        });

        $this->withoutExceptionHandling();
        $this->expectException(QueryException::class);

        try {
            $payload = [
                'name' => 'Rethrow Test',
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'contact_number' => '9988112244',
            ];

            $this->actingAs($user)->post('/consignees', $payload);
        } finally {
            Consignee::flushEventListeners();
        }
    }

    public function test_create_or_attach_customer_portal_user_updates_mismatched_role_id(): void
    {
        Mail::fake();
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $customerRole = UserRole::firstOrCreate(['name' => 'customer']);

        $existingUser = User::factory()->create([
            'email' => 'mismatch_role@example.com',
            'user_role_id' => $customerRole->id,
            'company_id' => $user->company_id,
        ]);

        User::retrieved(function ($model) use ($customerRole) {
            if ($model->email === 'mismatch_role@example.com') {
                $model->user_role_id = 9999;
                $model->setRelation('userRole', $customerRole);
            }
        });

        try {
            $response = $this->actingAs($user)->post('/consignees', [
                'name' => 'Mismatch Consignee',
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'contact_number' => '9876543219',
                'email' => 'mismatch_role@example.com',
            ]);

            $response->assertRedirect(route('consignees.index'));
            User::flushEventListeners();
            $existingUser->refresh();
            $this->assertEquals($customerRole->id, $existingUser->user_role_id);
            $this->assertNull($existingUser->company_id);
        } finally {
            User::flushEventListeners();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE-BASED ACCESS CONTROL (RBAC) & AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    public function test_super_admin_can_access_and_manage_consignees(): void
    {
        $superAdmin = $this->makeUser('super_admin');
        $company = Company::find($superAdmin->company_id);
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Super Admin Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'address' => 'Super Admin Street',
        ]);

        // super_admin with active company session can view index
        $indexResponse = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $company->id])
            ->get('/consignees');
        $indexResponse->assertOk();

        // super_admin can access create
        $createResponse = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $company->id])
            ->get('/consignees/create');
        $createResponse->assertOk();

        // super_admin can access edit
        $editResponse = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $company->id])
            ->get('/consignees/' . $consignee->id . '/edit');
        $editResponse->assertOk();

        // super_admin can update consignee
        $updateResponse = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $company->id])
            ->put('/consignees/' . $consignee->id, [
                'name' => 'Super Admin Updated Name',
                'country_id' => $country->id,
                'state_id' => $state->id,
                'city_id' => $city->id,
                'contact_number' => '9876543210',
            ]);
        $updateResponse->assertRedirect(route('consignees.index'));

        // super_admin can delete consignee
        $deleteResponse = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $company->id])
            ->delete('/consignees/' . $consignee->id, [
                'delete_remark' => 'Super admin soft deleting',
            ]);
        $deleteResponse->assertRedirect(route('consignees.index'));
        $this->assertSoftDeleted('consignees', ['id' => $consignee->id]);
    }

    public function test_super_admin_without_active_company_session_is_redirected(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        $response = $this->actingAs($superAdmin)->get('/consignees');

        $response->assertRedirect(route('company.index'));
    }

    public function test_moderator_can_view_and_create_consignees(): void
    {
        $moderator = $this->makeUser('moderator');
        [$country, $state, $city] = $this->seedGeo();

        // moderator can view index
        $indexResponse = $this->actingAs($moderator)->get('/consignees');
        $indexResponse->assertOk();

        // moderator can view create form
        $createResponse = $this->actingAs($moderator)->get('/consignees/create');
        $createResponse->assertOk();

        // moderator can store a consignee
        $storeResponse = $this->actingAs($moderator)->post('/consignees', [
            'name' => 'Moderator Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543211',
            'address' => 'Moderator Lane',
        ]);
        $storeResponse->assertRedirect(route('consignees.index'));
        $this->assertDatabaseHas('consignees', [
            'name' => 'Moderator Consignee',
            'company_id' => $moderator->company_id,
        ]);
    }

    public function test_moderator_is_blocked_with_403_from_editing_consignee(): void
    {
        $moderator = $this->makeUser('moderator');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $moderator->company_id,
            'name' => 'Existing Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        // edit page access -> 403 Forbidden
        $editResponse = $this->actingAs($moderator)->get('/consignees/' . $consignee->id . '/edit');
        $editResponse->assertForbidden();

        // update request -> 403 Forbidden
        $updateResponse = $this->actingAs($moderator)->put('/consignees/' . $consignee->id, [
            'name' => 'Moderator Hacked Name',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);
        $updateResponse->assertForbidden();

        // Verify data was not altered
        $consignee->refresh();
        $this->assertEquals('Existing Consignee', $consignee->name);
    }

    public function test_moderator_is_blocked_with_403_from_deleting_consignee(): void
    {
        $moderator = $this->makeUser('moderator');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $moderator->company_id,
            'name' => 'Consignee To Delete',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        // delete request -> 403 Forbidden
        $deleteResponse = $this->actingAs($moderator)->delete('/consignees/' . $consignee->id, [
            'delete_remark' => 'Moderator trying to delete',
        ]);
        $deleteResponse->assertForbidden();

        // Verify record is NOT deleted
        $this->assertNotSoftDeleted('consignees', ['id' => $consignee->id]);
    }

    public function test_master_admin_is_blocked_from_company_consignee_routes(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $masterAdmin->company_id,
            'name' => 'Master Admin Test Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        // index -> 403
        $indexResponse = $this->actingAs($masterAdmin)->get('/consignees');
        $indexResponse->assertForbidden();

        // create -> 403
        $createResponse = $this->actingAs($masterAdmin)->get('/consignees/create');
        $createResponse->assertForbidden();

        // store -> 403
        $storeResponse = $this->actingAs($masterAdmin)->post('/consignees', [
            'name' => 'Master Blocked',
        ]);
        $storeResponse->assertForbidden();

        // edit -> 403
        $editResponse = $this->actingAs($masterAdmin)->get('/consignees/' . $consignee->id . '/edit');
        $editResponse->assertForbidden();

        // delete -> 403
        $deleteResponse = $this->actingAs($masterAdmin)->delete('/consignees/' . $consignee->id);
        $deleteResponse->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER CONSIGNEE CONTEXT MIDDLEWARE (EnsureCustomerConsigneeContext)
    |--------------------------------------------------------------------------
    */

    public function test_customer_consignee_context_middleware_blocks_guest(): void
    {
        $middleware = new EnsureCustomerConsigneeContext();
        $request = Request::create('/customer/lorry-receipts', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_customer_consignee_context_middleware_blocks_non_customer_role(): void
    {
        $middleware = new EnsureCustomerConsigneeContext();
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $request = Request::create('/customer/lorry-receipts', 'GET');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, fn () => response('ok'));
    }

    public function test_customer_consignee_context_middleware_blocks_customer_with_no_assigned_consignees(): void
    {
        $customerRoleId = UserRole::where('name', 'customer')->value('id');
        $customer = User::factory()->create([
            'user_role_id' => $customerRoleId,
            'company_id' => null,
        ]);
        $this->actingAs($customer);

        $middleware = new EnsureCustomerConsigneeContext();
        $request = Request::create('/customer/lorry-receipts', 'GET');

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected HttpException 403 for no assigned customer access');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('No customer access assigned.', $e->getMessage());
        }
    }

    public function test_customer_consignee_context_middleware_blocks_and_logs_out_when_all_companies_blocked(): void
    {
        $blockedCompany = Company::create([
            'name' => 'Blocked Co',
            'is_blocked' => true,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $blockedCompany->id,
            'name' => 'Consignee in Blocked Co',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $customerRoleId = UserRole::where('name', 'customer')->value('id');
        $customer = User::factory()->create([
            'user_role_id' => $customerRoleId,
            'company_id' => null,
        ]);
        $customer->consignees()->attach($consignee->id);
        $this->actingAs($customer);

        $middleware = new EnsureCustomerConsigneeContext();
        $request = Request::create('/customer/lorry-receipts', 'GET');
        $request->setLaravelSession(app('session.store'));

        try {
            $middleware->handle($request, fn () => response('ok'));
            $this->fail('Expected HttpException 403 for blocked company');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('All linked customer accounts are currently blocked.', $e->getMessage());
            $this->assertFalse(\Illuminate\Support\Facades\Auth::check());
        }
    }

    public function test_customer_consignee_context_middleware_allows_and_sets_attributes_for_active_consignee(): void
    {
        $activeCompany = Company::create([
            'name' => 'Active Co',
            'is_blocked' => false,
            'gst_slab' => config('payments.gst_slabs')[0],
        ]);
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $activeCompany->id,
            'name' => 'Consignee in Active Co',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        $customerRoleId = UserRole::where('name', 'customer')->value('id');
        $customer = User::factory()->create([
            'user_role_id' => $customerRoleId,
            'company_id' => null,
        ]);
        $customer->consignees()->attach($consignee->id);
        $this->actingAs($customer);

        $middleware = new EnsureCustomerConsigneeContext();
        $request = Request::create('/customer/lorry-receipts', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return response()->json([
                'portal_allowed_consignee_ids' => $req->attributes->get('portal_allowed_consignee_ids'),
                'portal_consignees_count' => count($req->attributes->get('portal_consignees')),
            ]);
        });

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals([$consignee->id], $data['portal_allowed_consignee_ids']);
        $this->assertSame(1, $data['portal_consignees_count']);
    }

    /*
    |--------------------------------------------------------------------------
    | CONSIGNEE IMPORT CLASS DIRECT COVERAGE (ConsigneeImport)
    |--------------------------------------------------------------------------
    */

    public function test_consignee_import_chunk_size(): void
    {
        $import = new ConsigneeImport();
        $this->assertSame(500, $import->chunkSize());
    }

    public function test_consignee_import_processes_valid_row_with_normalization(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);
        [$country, $state, $city] = $this->seedGeo();

        $import = new ConsigneeImport();
        $rows = collect([
            [
                'name' => '  Imported Consignee Ltd  ',
                'contact_number' => '9876543210',
                'email' => '  TEST@EXAMPLE.COM  ',
                'gst_number' => '  24abcde1234f1z5  ',
                'pan_number' => '  abcde1234f  ',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
                'address' => '  123 Import Lane  ',
            ],
            [
                // Row with optional empty fields and numeric contact_number
                'name' => 'Optional Empty Consignee',
                'contact_number' => 9876543219,
                'email' => '   ',
                'gst_number' => '',
                'pan_number' => null,
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
                'address' => '   ',
            ],
        ]);

        $import->collection($rows);

        $this->assertSame(2, $import->successCount);
        $this->assertEmpty($import->errors);

        $this->assertDatabaseHas('consignees', [
            'company_id' => $user->company_id,
            'name' => 'Imported Consignee Ltd',
            'email' => 'test@example.com',
            'gst_number' => '24ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'address' => '123 Import Lane',
        ]);

        $this->assertDatabaseHas('consignees', [
            'company_id' => $user->company_id,
            'name' => 'Optional Empty Consignee',
            'contact_number' => '9876543219',
            'email' => null,
            'gst_number' => null,
            'pan_number' => null,
            'address' => null,
        ]);
    }

    public function test_consignee_import_validates_required_fields_and_formats(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);

        $import = new ConsigneeImport();
        $rows = collect([
            [
                // Missing required fields
                'name' => '',
                'contact_number' => '',
                'country' => '',
                'state' => '',
                'city' => '',
            ],
            [
                // Invalid formats
                'name' => 'Invalid Format Consignee',
                'contact_number' => '12345',
                'email' => 'not-an-email',
                'gst_number' => 'INVALIDGST',
                'pan_number' => 'BADPAN',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
        ]);

        $import->collection($rows);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(2, $import->errors);
        $this->assertSame(2, $import->errors[0]['row']);
        $this->assertStringContainsString('Consignee Name is required.', $import->errors[0]['error']);
        $this->assertStringContainsString('Contact Number must be exactly 10 digits.', $import->errors[1]['error']);
        $this->assertStringContainsString('Email must be a valid email address.', $import->errors[1]['error']);
        $this->assertStringContainsString('GST Number must be exactly 15 characters.', $import->errors[1]['error']);
        $this->assertStringContainsString('PAN Number must be exactly 10 characters.', $import->errors[1]['error']);
    }

    public function test_consignee_import_resolves_location_hierarchy_errors(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);
        [$country, $state] = $this->seedGeo();

        $import = new ConsigneeImport();
        $rows = collect([
            [
                // Non-existent country
                'name' => 'Bad Country',
                'contact_number' => '9876543211',
                'country' => 'NonExistentCountry',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            [
                // Non-existent state in valid country
                'name' => 'Bad State',
                'contact_number' => '9876543212',
                'country' => 'India',
                'state' => 'NonExistentState',
                'city' => 'Ahmedabad',
            ],
            [
                // Non-existent city in valid state
                'name' => 'Bad City',
                'contact_number' => '9876543213',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'NonExistentCity',
            ],
        ]);

        $import->collection($rows);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(3, $import->errors);
        $this->assertStringContainsString('Country not found: NonExistentCountry', $import->errors[0]['error']);
        $this->assertStringContainsString('State not found for selected country: NonExistentState', $import->errors[1]['error']);
        $this->assertStringContainsString('City not found for selected state: NonExistentCity', $import->errors[2]['error']);
    }

    public function test_consignee_import_detects_in_file_and_database_duplicates(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);
        [$country, $state, $city] = $this->seedGeo();

        // Seed existing consignee in DB
        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Already In DB',
            'contact_number' => '9876543000',
            'email' => 'existing@example.com',
            'gst_number' => '24ABCDE0000F1Z0',
            'pan_number' => 'ABCDE0000F',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);

        $import = new ConsigneeImport();
        $rows = collect([
            // Row 1: Valid initial row
            [
                'name' => 'Valid Row 1',
                'contact_number' => '9876543111',
                'email' => 'filedup@example.com',
                'gst_number' => '24ABCDE1111F1Z1',
                'pan_number' => 'ABCDE1111F',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 2: In-file duplicate contact
            [
                'name' => 'Dup Contact In File',
                'contact_number' => '9876543111',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 3: In-file duplicate email
            [
                'name' => 'Dup Email In File',
                'contact_number' => '9876543222',
                'email' => 'filedup@example.com',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 4: In-file duplicate GST
            [
                'name' => 'Dup GST In File',
                'contact_number' => '9876543333',
                'gst_number' => '24ABCDE1111F1Z1',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 5: In-file duplicate PAN
            [
                'name' => 'Dup PAN In File',
                'contact_number' => '9876543444',
                'pan_number' => 'ABCDE1111F',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 6: DB duplicate contact
            [
                'name' => 'DB Dup Contact',
                'contact_number' => '9876543000',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 7: DB duplicate email
            [
                'name' => 'DB Dup Email',
                'contact_number' => '9876543555',
                'email' => 'existing@example.com',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 8: DB duplicate GST
            [
                'name' => 'DB Dup GST',
                'contact_number' => '9876543666',
                'gst_number' => '24ABCDE0000F1Z0',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
            // Row 9: DB duplicate PAN
            [
                'name' => 'DB Dup PAN',
                'contact_number' => '9876543777',
                'pan_number' => 'ABCDE0000F',
                'country' => 'India',
                'state' => 'Gujarat',
                'city' => 'Ahmedabad',
            ],
        ]);

        $import->collection($rows);

        $this->assertSame(1, $import->successCount);
        $this->assertCount(8, $import->errors);
        $this->assertStringContainsString('Duplicate contact number in uploaded file.', $import->errors[0]['error']);
        $this->assertStringContainsString('Duplicate email in uploaded file.', $import->errors[1]['error']);
        $this->assertStringContainsString('Duplicate GST number in uploaded file.', $import->errors[2]['error']);
        $this->assertStringContainsString('Duplicate PAN number in uploaded file.', $import->errors[3]['error']);
        $this->assertStringContainsString('Contact number already exists for this company.', $import->errors[4]['error']);
        $this->assertStringContainsString('Email already exists for this company.', $import->errors[5]['error']);
        $this->assertStringContainsString('GST number already exists for this company.', $import->errors[6]['error']);
        $this->assertStringContainsString('PAN number already exists for this company.', $import->errors[7]['error']);
    }

    public function test_consignee_import_catches_row_exceptions_gracefully(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user);
        [$country, $state, $city] = $this->seedGeo();

        $import = new ConsigneeImport();

        Consignee::creating(function ($model) {
            if ($model->name === 'Simulated Throw') {
                throw new \RuntimeException('Database disk error simulation');
            }
        });

        try {
            $rowsWithThrow = collect([
                [
                    'name' => 'Simulated Throw',
                    'contact_number' => '9876543299',
                    'country' => 'India',
                    'state' => 'Gujarat',
                    'city' => 'Ahmedabad',
                ],
            ]);
            $import->collection($rowsWithThrow);

            $this->assertSame(0, $import->successCount);
            $this->assertCount(1, $import->errors);
            $this->assertSame('Database disk error simulation', $import->errors[0]['error']);
        } finally {
            Consignee::flushEventListeners();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONSIGNEE MODEL RELATIONSHIPS (Consignee Model Coverage)
    |--------------------------------------------------------------------------
    */

    public function test_consignee_model_relationships(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Relation Test Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);

        // 1. Verify relationship method instances
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $consignee->lorryReceipts());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $consignee->invoices());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $consignee->portalUsers());

        // 2. Verify dynamic property access
        $this->assertCount(0, $consignee->lorryReceipts);
        $this->assertCount(0, $consignee->invoices);
        $this->assertCount(0, $consignee->portalUsers);

        // 3. Attach a portal user and verify
        $customerRoleId = UserRole::where('name', 'customer')->value('id');
        $portalUser = User::factory()->create([
            'user_role_id' => $customerRoleId,
            'company_id' => null,
        ]);
        $consignee->portalUsers()->attach($portalUser->id);
        $consignee->refresh();
        $this->assertTrue($consignee->portalUsers->contains($portalUser));
    }

    public function test_consignee_display_name_with_city(): void
    {
        $user = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedGeo();

        // 1. With city relation
        $consignee = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Vikas Trading Co',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
        ]);
        $this->assertSame('Vikas Trading Co (Ahmedabad)', $consignee->displayNameWithCity());

        // 2. With city_name attribute override
        $consignee->setAttribute('city_name', 'Barmer');
        $this->assertSame('Vikas Trading Co (Barmer)', $consignee->displayNameWithCity());

        // 3. Without city (city is empty)
        $noCityConsignee = new Consignee(['name' => 'Plain Name Ltd']);
        $this->assertSame('Plain Name Ltd', $noCityConsignee->displayNameWithCity());

        // 4. Without name ('Not Set')
        $noNameConsignee = new Consignee(['name' => '']);
        $this->assertSame('Not Set', $noNameConsignee->displayNameWithCity());
    }
}
