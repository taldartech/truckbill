<?php

namespace Tests\Feature\Master;

use App\Http\Controllers\CityController;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CityMasterTest extends TestCase
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
        UserRole::firstOrCreate(['id' => 5], ['name' => 'accountant']);
    }

    protected function makeUser(string $role): User
    {
        $company = Company::create([
            'name' => 'Company ' . $role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $roleId = UserRole::where('name', $role)->value('id');

        $user = User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);

        $company->user_id = $user->id;
        $company->save();

        return $user;
    }

    public function test_city_index_non_ajax_returns_view(): void
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin)->get(route('master.cities.index'));
        $response->assertOk()
            ->assertViewIs('master.cities')
            ->assertViewHasAll(['states', 'countries']);
    }

    public function test_city_index_datatables_ajax_search_column_filters_and_sorting(): void
    {
        $admin = $this->makeUser('admin');
        $master = $this->makeUser('master_admin');

        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $usa = Country::create(['name' => 'United States', 'code' => 'US']);

        $gujarat = State::create(['name' => 'Gujarat', 'country_id' => $india->id]);
        $california = State::create(['name' => 'California', 'country_id' => $usa->id]);

        $ahmedabad = City::create(['name' => 'Ahmedabad', 'state_id' => $gujarat->id, 'country_id' => $india->id]);
        $surat = City::create(['name' => 'Surat', 'state_id' => $gujarat->id, 'country_id' => $india->id]);
        $la = City::create(['name' => 'Los Angeles', 'state_id' => $california->id, 'country_id' => $usa->id]);

        // 1. AJAX as admin (non-master_admin gets empty action buttons)
        $adminResp = $this->actingAs($admin)->getJson(route('master.cities.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $adminResp->assertOk();
        $this->assertSame('', $adminResp->json('data.0.actions'));

        // 2. AJAX as master_admin (has edit & delete action buttons)
        $masterResp = $this->actingAs($master)->getJson(route('master.cities.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $masterResp->assertOk();
        $this->assertStringContainsString('btn-action-edit', $masterResp->json('data.0.actions'));
        $this->assertStringContainsString('btn-action-delete', $masterResp->json('data.0.actions'));

        // 3. Search query: global search matching city name
        $searchCity = $this->actingAs($master)->getJson(route('master.cities.index') . '?' . http_build_query([
            'search' => ['value' => 'Ahmedabad'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchCity->assertOk();
        $this->assertCount(1, $searchCity->json('data'));

        // Search matching state name
        $searchState = $this->actingAs($master)->getJson(route('master.cities.index') . '?' . http_build_query([
            'search' => ['value' => 'California'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchState->assertOk();
        $this->assertCount(1, $searchState->json('data'));

        // Search matching country name
        $searchCountry = $this->actingAs($master)->getJson(route('master.cities.index') . '?' . http_build_query([
            'search' => ['value' => 'United States'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchCountry->assertOk();
        $this->assertCount(1, $searchCountry->json('data'));

        // 4. Column specific search
        // Column 1: name
        $col1Resp = $this->actingAs($master)->getJson(route('master.cities.index') . '?' . http_build_query([
            'columns' => [
                1 => ['search' => ['value' => 'Surat']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $col1Resp->assertOk();
        $this->assertCount(1, $col1Resp->json('data'));

        // Column 2: state
        $col2Resp = $this->actingAs($master)->getJson(route('master.cities.index') . '?' . http_build_query([
            'columns' => [
                2 => ['search' => ['value' => 'Gujarat']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $col2Resp->assertOk();
        $this->assertCount(2, $col2Resp->json('data'));

        // Column 3: country
        $col3Resp = $this->actingAs($master)->getJson(route('master.cities.index') . '?' . http_build_query([
            'columns' => [
                3 => ['search' => ['value' => 'India']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $col3Resp->assertOk();
        $this->assertCount(2, $col3Resp->json('data'));

        // 5. Sorting: columns 0, 1, 2, 3, default, and no order
        foreach ([0, 1, 2, 3, 99] as $col) {
            $sortResp = $this->actingAs($master)->getJson(route('master.cities.index') . '?' . http_build_query([
                'order' => [['column' => $col, 'dir' => 'asc']],
                'start' => 0,
                'length' => 2,
            ]), ['X-Requested-With' => 'XMLHttpRequest']);
            $sortResp->assertOk();
        }

        // Without order
        $noOrderResp = $this->actingAs($master)->getJson(route('master.cities.index') . '?start=0&length=2', [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $noOrderResp->assertOk();
    }

    public function test_city_create_view_rendering(): void
    {
        $admin = $this->makeUser('admin');
        Country::create(['name' => 'India', 'code' => 'IND']);

        $response = $this->actingAs($admin)->get(route('master.cities.create'));
        $response->assertOk()
            ->assertViewIs('master.cities-form')
            ->assertViewHasAll(['countries', 'selectedCountryId', 'selectedStateId']);
    }

    public function test_city_store_successful_and_validation(): void
    {
        $admin = $this->makeUser('admin');
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $gujarat = State::create(['name' => 'Gujarat', 'country_id' => $india->id]);

        // 1. Successful non-AJAX store
        $respNonAjax = $this->actingAs($admin)->post(route('master.cities.store'), [
            'name' => 'Vadodara',
            'state_id' => $gujarat->id,
        ]);
        $respNonAjax->assertRedirect(route('master.cities.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('cities', ['name' => 'Vadodara', 'state_id' => $gujarat->id]);

        // 2. Successful AJAX store
        $respAjax = $this->actingAs($admin)->postJson(route('master.cities.store'), [
            'name' => 'Rajkot',
            'state_id' => $gujarat->id,
            'from_modal' => 1,
        ]);
        $respAjax->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'City created successfully',
            ]);
        $this->assertDatabaseHas('cities', ['name' => 'Rajkot', 'state_id' => $gujarat->id]);

        // 3. Validation failure non-AJAX
        $failNonAjax = $this->actingAs($admin)->from(route('master.cities.create'))->post(route('master.cities.store'), [
            'name' => '',
            'state_id' => 999999,
        ]);
        $failNonAjax->assertRedirect(route('master.cities.create'))
            ->assertSessionHasErrors(['name', 'state_id']);

        // 4. Validation failure AJAX
        $failAjax = $this->actingAs($admin)->postJson(route('master.cities.store'), [
            'name' => '',
            'state_id' => 999999,
            'from_modal' => 1,
        ]);
        $failAjax->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
    }

    public function test_city_store_catches_generic_and_duplicate_exceptions(): void
    {
        $admin = $this->makeUser('admin');
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $india->id]);

        // 1. Duplicate entry exception via AJAX/modal
        City::saving(function () {
            throw new \Exception('SQLSTATE[23000]: Duplicate entry "test" for key');
        });

        $respDup = $this->actingAs($admin)->postJson(route('master.cities.store'), [
            'name' => 'Exceptional City',
            'state_id' => $state->id,
            'from_modal' => 1,
        ]);
        $respDup->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'A city with this name already exists in the selected state',
            ]);

        // Clear listeners
        City::flushEventListeners();

        // 2. Generic exception via non-AJAX
        City::saving(function () {
            throw new \Exception('Generic database failure occurred');
        });

        $respGeneric = $this->actingAs($admin)->from(route('master.cities.create'))->post(route('master.cities.store'), [
            'name' => 'Generic City',
            'state_id' => $state->id,
        ]);
        $respGeneric->assertRedirect(route('master.cities.create'))
            ->assertSessionHas('error', 'Failed to create city');

        City::flushEventListeners();
    }

    public function test_city_edit_update_show_destroy(): void
    {
        $master = $this->makeUser('master_admin');
        $india = Country::create(['name' => 'India', 'code' => 'IN']);
        $gujarat = State::create(['name' => 'Gujarat', 'country_id' => $india->id]);
        $city = City::create(['name' => 'Bhavnagar', 'state_id' => $gujarat->id, 'country_id' => $india->id]);

        // Edit view
        $editResp = $this->actingAs($master)->get(route('master.cities.edit', $city));
        $editResp->assertOk()
            ->assertViewIs('master.cities-form')
            ->assertViewHasAll(['city', 'countries', 'selectedCountryId', 'selectedStateId']);

        // Update
        $updateResp = $this->actingAs($master)->put(route('master.cities.update', $city), [
            'name' => 'Bhavnagar Port',
            'state_id' => $gujarat->id,
        ]);
        $updateResp->assertRedirect(route('master.cities.index'))
            ->assertSessionHas('success', 'City updated successfully');
        $this->assertDatabaseHas('cities', ['id' => $city->id, 'name' => 'Bhavnagar Port']);

        // Show method directly on controller
        $showResp = app(CityController::class)->show($city);
        $this->assertSame(200, $showResp->getStatusCode());
        $this->assertSame($city->id, $showResp->getData()->id);

        // Destroy
        $destroyResp = $this->actingAs($master)->delete(route('master.cities.destroy', $city));
        $destroyResp->assertRedirect(route('master.cities.index'))
            ->assertSessionHas('success', 'City deleted successfully');
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }
}
