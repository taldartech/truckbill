<?php

namespace Tests\Feature\Master;

use App\Models\City;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateMasterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
    }

    private function makeUser(string $role): User
    {
        $roleId = UserRole::where('name', $role)->value('id');

        $company = Company::create([
            'name' => 'State Co ' . $role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 10,
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

        $user = User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);

        $company->user_id = $user->id;
        $company->save();

        return $user;
    }

    // -------------------------------------------------------------
    // MODEL TESTS
    // -------------------------------------------------------------

    public function test_state_model_attributes_and_relationships(): void
    {
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Maharashtra',
            'code' => 'MH',
        ]);

        // 1. Fillable attributes
        $this->assertEquals(['country_id', 'name', 'code'], $state->getFillable());
        $this->assertSame('Maharashtra', $state->name);
        $this->assertSame('MH', $state->code);

        // 2. BelongsTo Country relation
        $countryRelation = $state->country();
        $this->assertInstanceOf(BelongsTo::class, $countryRelation);
        $this->assertSame($country->id, $state->country->id);
        $this->assertSame('India', $state->country->name);

        // 3. HasMany Cities relation
        $cityRelation = $state->cities();
        $this->assertInstanceOf(HasMany::class, $cityRelation);

        $city1 = City::create([
            'state_id' => $state->id,
            'country_id' => $country->id,
            'name' => 'Mumbai',
        ]);
        $city2 = City::create([
            'state_id' => $state->id,
            'country_id' => $country->id,
            'name' => 'Pune',
        ]);

        $this->assertCount(2, $state->cities);
        $this->assertTrue($state->cities->contains($city1));
        $this->assertTrue($state->cities->contains($city2));
    }

    // -------------------------------------------------------------
    // CONTROLLER INDEX & DATATABLE AJAX TESTS
    // -------------------------------------------------------------

    public function test_admin_can_view_states_index_page(): void
    {
        $admin = $this->makeUser('admin');
        Country::create(['name' => 'India', 'code' => 'IN']);

        $response = $this->actingAs($admin)->get(route('master.states.index'));

        $response->assertOk()
            ->assertViewIs('master.states')
            ->assertViewHas('countries');
    }

    public function test_states_datatable_ajax_search_and_sorting(): void
    {
        $admin = $this->makeUser('admin');
        $c1 = Country::create(['name' => 'India', 'code' => 'IN']);
        $c2 = Country::create(['name' => 'United States', 'code' => 'US']);

        $s1 = State::create(['name' => 'Maharashtra', 'code' => 'MH', 'country_id' => $c1->id]);
        $s2 = State::create(['name' => 'Gujarat', 'code' => 'GJ', 'country_id' => $c1->id]);
        $s3 = State::create(['name' => 'California', 'code' => 'CA', 'country_id' => $c2->id]);

        // 1. Basic Ajax listing
        $resp = $this->actingAs($admin)->getJson(route('master.states.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $resp->assertOk()
            ->assertJsonPath('recordsTotal', 3)
            ->assertJsonPath('recordsFiltered', 3)
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data' => [
                    '*' => ['id', 'name', 'country', 'actions'],
                ],
            ]);
        // For admin, action buttons should be empty string
        $this->assertSame('', $resp->json('data.0.actions'));

        // 2. Global search by state name
        $searchState = $this->actingAs($admin)->getJson(route('master.states.index', [
            'search' => ['value' => 'Maha'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchState->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Maharashtra');

        // 3. Global search by country name
        $searchCountry = $this->actingAs($admin)->getJson(route('master.states.index', [
            'search' => ['value' => 'United States'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchCountry->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'California');

        // 4. Column-specific search
        // Column 1 (State name)
        $col1 = $this->actingAs($admin)->getJson(route('master.states.index', [
            'columns' => [
                1 => ['search' => ['value' => 'Gujarat']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $col1->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Gujarat');

        // Column 2 (Country name)
        $col2 = $this->actingAs($admin)->getJson(route('master.states.index', [
            'columns' => [
                2 => ['search' => ['value' => 'India']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $col2->assertJsonPath('recordsFiltered', 2);

        // 5. Order sorting
        // Case 0: ID desc
        $orderId = $this->actingAs($admin)->getJson(route('master.states.index', [
            'order' => [
                0 => ['column' => 0, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $orderId->assertJsonPath('data.0.id', $s3->id);

        // Case 1: Name asc
        $orderName = $this->actingAs($admin)->getJson(route('master.states.index', [
            'order' => [
                0 => ['column' => 1, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $orderName->assertJsonPath('data.0.name', 'California');

        // Case 2: Country name join
        $orderCountry = $this->actingAs($admin)->getJson(route('master.states.index', [
            'order' => [
                0 => ['column' => 2, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $orderCountry->assertOk();

        // Default order switch & no order
        $this->actingAs($admin)->getJson(route('master.states.index', [
            'order' => [
                0 => ['column' => 99, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        // Pagination
        $pageResp = $this->actingAs($admin)->getJson(route('master.states.index', [
            'start' => 1,
            'length' => 1,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $pageResp->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_master_admin_receives_action_buttons(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Punjab', 'code' => 'PB', 'country_id' => $country->id]);

        $response = $this->actingAs($masterAdmin)->getJson(route('master.states.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertOk();
        $actions = $response->json('data.0.actions');
        $this->assertStringContainsString('btn-action-edit', $actions);
        $this->assertStringContainsString('btn-action-delete', $actions);
        $this->assertStringContainsString(route('master.states.edit', $state->id), $actions);
        $this->assertStringContainsString(route('master.states.destroy', $state->id), $actions);
    }

    // -------------------------------------------------------------
    // CREATE, STORE & VALIDATION TESTS
    // -------------------------------------------------------------

    public function test_admin_can_view_create_form(): void
    {
        $admin = $this->makeUser('admin');

        // Test fallback when code is not 'IN' but name is 'India'
        $indiaAlt = Country::create(['name' => 'India', 'code' => 'IND']);
        $respAlt = $this->actingAs($admin)->get(route('master.states.create'));
        $respAlt->assertOk()
            ->assertViewIs('master.states-form')
            ->assertViewHas('selectedCountryId', $indiaAlt->id);

        $indiaAlt->delete();

        // Test normal when code is 'IN'
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $response = $this->actingAs($admin)->get(route('master.states.create'));

        $response->assertOk()
            ->assertViewIs('master.states-form')
            ->assertViewHas('countries')
            ->assertViewHas('selectedCountryId', $country->id);
    }

    public function test_store_creates_state_standard_and_validates(): void
    {
        $admin = $this->makeUser('admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);

        // 1. Valid store standard redirect
        $response = $this->actingAs($admin)->post(route('master.states.store'), [
            'name' => 'Rajasthan',
            'code' => 'RJ',
            'country_id' => $country->id,
        ]);

        $response->assertRedirect(route('master.states.index'));
        $this->assertDatabaseHas('states', [
            'name' => 'Rajasthan',
            'code' => 'RJ',
            'country_id' => $country->id,
        ]);

        // 2. Duplicate state in same country fails
        $dupResp = $this->actingAs($admin)->post(route('master.states.store'), [
            'name' => 'Rajasthan',
            'code' => 'RJ2',
            'country_id' => $country->id,
        ]);
        $dupResp->assertSessionHasErrors(['name']);

        // 3. Same state in different country succeeds
        $usa = Country::create(['name' => 'United States', 'code' => 'US']);
        $diffCountryResp = $this->actingAs($admin)->post(route('master.states.store'), [
            'name' => 'Rajasthan',
            'code' => 'RJ-US',
            'country_id' => $usa->id,
        ]);
        $diffCountryResp->assertRedirect(route('master.states.index'));
    }

    public function test_store_ajax_and_error_handling(): void
    {
        $admin = $this->makeUser('admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);

        // 1. Successful AJAX store
        $ajaxResp = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.states.store'), [
                'name' => 'Haryana',
                'code' => 'HR',
                'country_id' => $country->id,
            ]);

        $ajaxResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'State created successfully')
            ->assertJsonPath('state.name', 'Haryana');

        // 2. Successful store from modal
        $modalResp = $this->actingAs($admin)->post(route('master.states.store'), [
            'name' => 'Punjab',
            'code' => 'PB',
            'country_id' => $country->id,
            'from_modal' => 1,
        ]);
        $modalResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('state.name', 'Punjab');

        // 3. Validation failure via AJAX returns 422
        $valFail = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.states.store'), [
                'name' => '', // missing name
                'country_id' => $country->id,
            ]);

        $valFail->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonStructure(['errors' => ['name']]);

        // 4. Validation failure from modal returns 422 JSON
        $this->flushHeaders();
        $valModalFail = $this->actingAs($admin)->post(route('master.states.store'), [
            'name' => '',
            'country_id' => $country->id,
            'from_modal' => 1,
        ]);
        $valModalFail->assertStatus(422)
            ->assertJsonPath('success', false);

        // 5. Exception handling - Duplicate entry (AJAX)
        \App\Models\State::saving(function () {
            throw new \Exception('Duplicate entry for key states_name_unique');
        });

        $dupExAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.states.store'), [
                'name' => 'Goa',
                'country_id' => $country->id,
            ]);
        $dupExAjax->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'A state with this name already exists in the selected country');

        // 6. Exception handling - Duplicate entry (Web redirect)
        $this->flushHeaders();
        $dupExWeb = $this->actingAs($admin)
            ->from(route('master.states.create'))
            ->post(route('master.states.store'), [
                'name' => 'Goa',
                'country_id' => $country->id,
            ]);
        $dupExWeb->assertRedirect(route('master.states.create'));
        $dupExWeb->assertSessionHas('error', 'A state with this name already exists in the selected country');

        // Clear listeners
        \App\Models\State::flushEventListeners();

        // 7. General Exception handling (AJAX & Web)
        \App\Models\State::saving(function () {
            throw new \Exception('Database write connection failed');
        });

        $genExAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.states.store'), [
                'name' => 'Kerala',
                'country_id' => $country->id,
            ]);
        $genExAjax->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Failed to create state');

        $this->flushHeaders();
        $genExWeb = $this->actingAs($admin)
            ->from(route('master.states.create'))
            ->post(route('master.states.store'), [
                'name' => 'Kerala',
                'country_id' => $country->id,
            ]);
        $genExWeb->assertRedirect(route('master.states.create'));
        $genExWeb->assertSessionHas('error', 'Failed to create state');

        \App\Models\State::flushEventListeners();
    }

    // -------------------------------------------------------------
    // EDIT, UPDATE, SHOW & DESTROY TESTS
    // -------------------------------------------------------------

    public function test_master_admin_can_edit_and_update_state(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Madhya Pradesh', 'code' => 'MP', 'country_id' => $country->id]);

        // 1. Edit view
        $editResp = $this->actingAs($masterAdmin)->get(route('master.states.edit', $state->id));
        $editResp->assertOk()
            ->assertViewIs('master.states-form')
            ->assertViewHas('state')
            ->assertViewHas('selectedCountryId', $country->id);

        // 2. Show JSON via controller
        $controller = new \App\Http\Controllers\StateController();
        $showResp = $controller->show($state);
        $this->assertEquals($state->id, $showResp->getData()->id);

        // 3. Update state
        $updateResp = $this->actingAs($masterAdmin)->put(route('master.states.update', $state->id), [
            'name' => 'Madhya Pradesh Updated',
            'code' => 'MPU',
            'country_id' => $country->id,
        ]);

        $updateResp->assertRedirect(route('master.states.index'))
            ->assertSessionHas('success', 'State updated successfully');

        $this->assertDatabaseHas('states', [
            'id' => $state->id,
            'name' => 'Madhya Pradesh Updated',
            'code' => 'MPU',
        ]);

        // 4. Update keeping same name ignores self
        $sameNameResp = $this->actingAs($masterAdmin)->put(route('master.states.update', $state->id), [
            'name' => 'Madhya Pradesh Updated',
            'code' => 'MPU2',
            'country_id' => $country->id,
        ]);
        $sameNameResp->assertRedirect(route('master.states.index'));
    }

    public function test_master_admin_can_destroy_state(): void
    {
        $masterAdmin = $this->makeUser('master_admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Goa', 'code' => 'GA', 'country_id' => $country->id]);

        $response = $this->actingAs($masterAdmin)->delete(route('master.states.destroy', $state->id));

        $response->assertRedirect(route('master.states.index'))
            ->assertSessionHas('success', 'State deleted successfully');

        $this->assertDatabaseMissing('states', ['id' => $state->id]);
    }

    public function test_non_master_admin_cannot_edit_update_or_delete_state(): void
    {
        $admin = $this->makeUser('admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Kerala', 'code' => 'KL', 'country_id' => $country->id]);

        $this->actingAs($admin)->get(route('master.states.edit', $state->id))->assertForbidden();
        $this->actingAs($admin)->put(route('master.states.update', $state->id), [
            'name' => 'Kerala Mod',
            'country_id' => $country->id,
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('master.states.destroy', $state->id))->assertForbidden();
    }
}
