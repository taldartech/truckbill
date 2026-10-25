<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryMasterTest extends TestCase
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
            'name' => 'Country Co ' . $role,
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

        $company->update(['user_id' => $user->id]);

        return $user;
    }

    // -------------------------------------------------------------
    // MODEL & RELATIONSHIP TESTS
    // -------------------------------------------------------------

    public function test_country_model_attributes_and_relationships(): void
    {
        $country = new Country();
        $this->assertEquals(['name', 'code'], $country->getFillable());

        $country = Country::create([
            'name' => 'India',
            'code' => 'IN',
        ]);

        $this->assertInstanceOf(HasMany::class, $country->states());
        $this->assertCount(0, $country->states);

        $state1 = State::create(['name' => 'Maharashtra', 'code' => 'MH', 'country_id' => $country->id]);
        $state2 = State::create(['name' => 'Gujarat', 'code' => 'GJ', 'country_id' => $country->id]);

        $country->refresh();
        $this->assertCount(2, $country->states);
        $this->assertTrue($country->states->contains($state1));
        $this->assertTrue($country->states->contains($state2));
    }

    // -------------------------------------------------------------
    // INDEX & DATATABLES TESTS
    // -------------------------------------------------------------

    public function test_admin_can_view_countries_index_page(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('master.countries.index'));

        $response->assertOk()
            ->assertViewIs('master.countries');
    }

    public function test_countries_datatable_ajax_search_and_sorting(): void
    {
        $admin = $this->makeUser('admin');

        $c1 = Country::create(['name' => 'India', 'code' => 'IN']);
        $c2 = Country::create(['name' => 'United States', 'code' => 'US']);
        $c3 = Country::create(['name' => 'Canada', 'code' => 'CA']);

        // 1. Base AJAX listing
        $baseResp = $this->actingAs($admin)->getJson(route('master.countries.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $baseResp->assertOk()
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data' => [
                    '*' => ['id', 'name', 'code', 'actions'],
                ],
            ])
            ->assertJsonPath('recordsTotal', 3)
            ->assertJsonPath('recordsFiltered', 3);

        // 2. Global search by name
        $searchName = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'search' => ['value' => 'India'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchName->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'India');

        // 3. Global search by code
        $searchCode = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'search' => ['value' => 'US'],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $searchCode->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'United States');

        // 4. Column-specific search
        // Column 1: Name
        $colName = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'columns' => [
                1 => ['search' => ['value' => 'Canada']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $colName->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Canada');

        // Column 2: Code
        $colCode = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'columns' => [
                2 => ['search' => ['value' => 'CA']],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $colCode->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.code', 'CA');

        // 5. Order sorting
        // Case 0: ID desc
        $orderId = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'order' => [
                0 => ['column' => 0, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $orderId->assertJsonPath('data.0.id', $c3->id);

        // Case 1: Name asc
        $orderName = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'order' => [
                0 => ['column' => 1, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $orderName->assertJsonPath('data.0.name', 'Canada');

        // Case 2: Code desc
        $orderCode = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'order' => [
                0 => ['column' => 2, 'dir' => 'desc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $orderCode->assertJsonPath('data.0.code', 'US');

        // Default order switch & no order
        $this->actingAs($admin)->getJson(route('master.countries.index', [
            'order' => [
                0 => ['column' => 99, 'dir' => 'asc'],
            ],
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $this->actingAs($admin)->getJson(route('master.countries.index'))
            ->assertOk();

        // 6. Pagination
        $pageResp = $this->actingAs($admin)->getJson(route('master.countries.index', [
            'start' => 1,
            'length' => 1,
        ]), ['X-Requested-With' => 'XMLHttpRequest']);
        $pageResp->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_master_admin_receives_action_buttons(): void
    {
        $country = Country::create(['name' => 'Germany', 'code' => 'DE']);

        // 1. Non-master_admin gets empty actions
        $admin = $this->makeUser('admin');
        $respAdmin = $this->actingAs($admin)->getJson(route('master.countries.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $respAdmin->assertJsonPath('data.0.actions', '');

        // 2. Master_admin gets edit and delete action buttons
        $master = $this->makeUser('master_admin');
        $respMaster = $this->actingAs($master)->getJson(route('master.countries.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $actions = $respMaster->json('data.0.actions');
        $this->assertStringContainsString(route('master.countries.edit', $country->id), $actions);
        $this->assertStringContainsString(route('master.countries.destroy', $country->id), $actions);
    }

    // -------------------------------------------------------------
    // CREATE, STORE & VALIDATION TESTS
    // -------------------------------------------------------------

    public function test_admin_can_view_create_form(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('master.countries.create'));

        $response->assertOk()
            ->assertViewIs('master.countries-form');
    }

    public function test_store_creates_country_standard_and_validates(): void
    {
        $admin = $this->makeUser('admin');

        // 1. Valid store standard redirect
        $response = $this->actingAs($admin)->post(route('master.countries.store'), [
            'name' => 'Japan',
            'code' => 'JP',
        ]);

        $response->assertRedirect(route('master.countries.index'));
        $this->assertDatabaseHas('countries', [
            'name' => 'Japan',
            'code' => 'JP',
        ]);

        // 2. Missing name and code validation failure
        $valResp = $this->actingAs($admin)->post(route('master.countries.store'), []);
        $valResp->assertSessionHasErrors(['name', 'code']);

        // 3. Unique name validation failure
        $dupName = $this->actingAs($admin)->post(route('master.countries.store'), [
            'name' => 'Japan',
            'code' => 'JP2',
        ]);
        $dupName->assertSessionHasErrors(['name']);

        // 4. Unique code validation failure
        $dupCode = $this->actingAs($admin)->post(route('master.countries.store'), [
            'name' => 'Japan Two',
            'code' => 'JP',
        ]);
        $dupCode->assertSessionHasErrors(['code']);
    }

    public function test_store_ajax_and_modal_success(): void
    {
        $admin = $this->makeUser('admin');

        // 1. AJAX store success
        $ajaxResp = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.countries.store'), [
                'name' => 'Australia',
                'code' => 'AU',
            ]);

        $ajaxResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Country created successfully')
            ->assertJsonPath('country.name', 'Australia');

        // 2. Store from modal success
        $this->flushHeaders();
        $modalResp = $this->actingAs($admin)->post(route('master.countries.store'), [
            'name' => 'Brazil',
            'code' => 'BR',
            'from_modal' => 1,
        ]);

        $modalResp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('country.name', 'Brazil');
    }

    public function test_store_validation_errors_ajax_and_modal(): void
    {
        $admin = $this->makeUser('admin');

        // 1. AJAX validation failure returns 422 JSON
        $ajaxFail = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.countries.store'), [
                'name' => '',
                'code' => '',
            ]);

        $ajaxFail->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonStructure(['errors' => ['name', 'code']]);

        // 2. Modal validation failure returns 422 JSON
        $this->flushHeaders();
        $modalFail = $this->actingAs($admin)->post(route('master.countries.store'), [
            'name' => '',
            'code' => '',
            'from_modal' => 1,
        ]);

        $modalFail->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed');
    }

    public function test_store_exception_handling_branches(): void
    {
        $admin = $this->makeUser('admin');

        // 1. Duplicate entry name unique (AJAX & Web)
        Country::saving(function () {
            throw new \Exception('Duplicate entry for key countries_name_unique');
        });

        $dupNameAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.countries.store'), [
                'name' => 'France',
                'code' => 'FR',
            ]);
        $dupNameAjax->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'A country with this name already exists');

        $this->flushHeaders();
        $dupNameWeb = $this->actingAs($admin)
            ->from(route('master.countries.create'))
            ->post(route('master.countries.store'), [
                'name' => 'France',
                'code' => 'FR',
            ]);
        $dupNameWeb->assertRedirect(route('master.countries.create'));
        $dupNameWeb->assertSessionHas('error', 'A country with this name already exists');

        Country::flushEventListeners();

        // 2. Duplicate entry code unique (AJAX & Web)
        Country::saving(function () {
            throw new \Exception('Duplicate entry for key countries_code_unique');
        });

        $dupCodeAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.countries.store'), [
                'name' => 'Italy',
                'code' => 'IT',
            ]);
        $dupCodeAjax->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'A country with this code already exists');

        $this->flushHeaders();
        $dupCodeWeb = $this->actingAs($admin)
            ->from(route('master.countries.create'))
            ->post(route('master.countries.store'), [
                'name' => 'Italy',
                'code' => 'IT',
            ]);
        $dupCodeWeb->assertRedirect(route('master.countries.create'));
        $dupCodeWeb->assertSessionHas('error', 'A country with this code already exists');

        Country::flushEventListeners();

        // 3. Duplicate entry generic (AJAX & Web)
        Country::saving(function () {
            throw new \Exception('Duplicate entry other generic message');
        });

        $dupGenAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.countries.store'), [
                'name' => 'Spain',
                'code' => 'ES',
            ]);
        $dupGenAjax->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This country already exists');

        $this->flushHeaders();
        $dupGenWeb = $this->actingAs($admin)
            ->from(route('master.countries.create'))
            ->post(route('master.countries.store'), [
                'name' => 'Spain',
                'code' => 'ES',
            ]);
        $dupGenWeb->assertRedirect(route('master.countries.create'));
        $dupGenWeb->assertSessionHas('error', 'This country already exists');

        Country::flushEventListeners();

        // 4. General exception (AJAX & Web)
        Country::saving(function () {
            throw new \Exception('Something completely unexpected occurred');
        });

        $genAjax = $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->postJson(route('master.countries.store'), [
                'name' => 'Norway',
                'code' => 'NO',
            ]);
        $genAjax->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Failed to create country');

        $this->flushHeaders();
        $genWeb = $this->actingAs($admin)
            ->from(route('master.countries.create'))
            ->post(route('master.countries.store'), [
                'name' => 'Norway',
                'code' => 'NO',
            ]);
        $genWeb->assertRedirect(route('master.countries.create'));
        $genWeb->assertSessionHas('error', 'Failed to create country');

        Country::flushEventListeners();
    }

    // -------------------------------------------------------------
    // EDIT, UPDATE & DESTROY TESTS
    // -------------------------------------------------------------

    public function test_master_admin_can_edit_and_update_country(): void
    {
        $master = $this->makeUser('master_admin');
        $country = Country::create(['name' => 'South Korea', 'code' => 'KR']);

        // 1. View edit form
        $editResp = $this->actingAs($master)->get(route('master.countries.edit', $country->id));
        $editResp->assertOk()
            ->assertViewIs('master.countries-form')
            ->assertViewHas('country');

        // 2. Update country
        $updateResp = $this->actingAs($master)->put(route('master.countries.update', $country->id), [
            'name' => 'Republic of Korea',
            'code' => 'KOR',
        ]);

        $updateResp->assertRedirect(route('master.countries.index'));
        $this->assertDatabaseHas('countries', [
            'id' => $country->id,
            'name' => 'Republic of Korea',
            'code' => 'KOR',
        ]);

        // 3. Validation failure on update
        $valUpdate = $this->actingAs($master)->put(route('master.countries.update', $country->id), [
            'name' => '',
            'code' => '',
        ]);
        $valUpdate->assertSessionHasErrors(['name', 'code']);
    }

    public function test_master_admin_can_destroy_country(): void
    {
        $master = $this->makeUser('master_admin');
        $country = Country::create(['name' => 'Sweden', 'code' => 'SE']);

        $deleteResp = $this->actingAs($master)->delete(route('master.countries.destroy', $country->id));

        $deleteResp->assertRedirect(route('master.countries.index'));
        $this->assertDatabaseMissing('countries', [
            'id' => $country->id,
        ]);
    }

    public function test_non_master_admin_cannot_edit_update_or_delete_country(): void
    {
        $country = Country::create(['name' => 'Mexico', 'code' => 'MX']);

        foreach (['admin', 'moderator', 'super_admin'] as $role) {
            $user = $this->makeUser($role);

            // Edit forbidden
            $this->actingAs($user)->get(route('master.countries.edit', $country->id))
                ->assertForbidden();

            // Update forbidden
            $this->actingAs($user)->put(route('master.countries.update', $country->id), [
                'name' => 'Mexico Updated',
                'code' => 'MX2',
            ])->assertForbidden();

            // Destroy forbidden
            $this->actingAs($user)->delete(route('master.countries.destroy', $country->id))
                ->assertForbidden();
        }
    }
}
