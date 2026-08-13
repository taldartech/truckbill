<?php

namespace Tests\Feature\Master;

use App\Models\Company;
use App\Models\Country;
use App\Models\GoodType;
use App\Models\Pump;
use App\Models\State;
use App\Models\City;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterFieldsTest extends TestCase
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

    public function test_country_state_city_create_admin(): void
    {
        $admin = $this->makeUser('admin');

        // Country create
        $this->actingAs($admin)->post('/master/countries', [
            'name' => 'India',
            'code' => 'IN',
        ])->assertRedirect(route('master.countries.index'));
        $country = Country::where('code', 'IN')->firstOrFail();

        // State create
        $this->actingAs($admin)->post('/master/states', [
            'name' => 'Gujarat',
            'country_id' => $country->id,
            'code' => 'GJ',
        ])->assertRedirect(route('master.states.index'));
        $state = State::where('code', 'GJ')->firstOrFail();

        // City create
        $this->actingAs($admin)->post('/master/cities', [
            'name' => 'Ahmedabad',
            'state_id' => $state->id,
            'country_id' => $country->id,
        ])->assertRedirect(route('master.cities.index'));

        $this->assertDatabaseHas('countries', ['code' => 'IN']);
        $this->assertDatabaseHas('states', ['code' => 'GJ']);
        $this->assertDatabaseHas('cities', ['name' => 'Ahmedabad', 'state_id' => $state->id]);
    }

    public function test_admin_cannot_edit_or_delete_country_state_city(): void
    {
        $admin = $this->makeUser('admin');
        [$country, $state, $city] = $this->seedCountryStateCity();

        $this->actingAs($admin)->get(route('master.countries.edit', $country))->assertForbidden();
        $this->actingAs($admin)->put(route('master.countries.update', $country), [
            'name' => 'Updated',
            'code' => 'UP',
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('master.countries.destroy', $country))->assertForbidden();

        $this->actingAs($admin)->get(route('master.states.edit', $state))->assertForbidden();
        $this->actingAs($admin)->put(route('master.states.update', $state), [
            'name' => 'Updated',
            'country_id' => $country->id,
            'code' => 'UP',
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('master.states.destroy', $state))->assertForbidden();

        $this->actingAs($admin)->get(route('master.cities.edit', $city))->assertForbidden();
        $this->actingAs($admin)->put(route('master.cities.update', $city), [
            'name' => 'Updated',
            'state_id' => $state->id,
            'country_id' => $country->id,
        ])->assertForbidden();
        $this->actingAs($admin)->delete(route('master.cities.destroy', $city))->assertForbidden();
    }

    public function test_master_admin_can_crud_country_state_city(): void
    {
        $master = $this->makeUser('master_admin');

        $this->actingAs($master)->post('/master/countries', [
            'name' => 'India',
            'code' => 'IN',
        ])->assertRedirect(route('master.countries.index'));
        $country = Country::where('code', 'IN')->firstOrFail();

        $this->actingAs($master)->post('/master/states', [
            'name' => 'Gujarat',
            'country_id' => $country->id,
            'code' => 'GJ',
        ])->assertRedirect(route('master.states.index'));
        $state = State::where('code', 'GJ')->firstOrFail();

        $this->actingAs($master)->post('/master/cities', [
            'name' => 'Ahmedabad',
            'state_id' => $state->id,
            'country_id' => $country->id,
        ])->assertRedirect(route('master.cities.index'));
        $city = City::where('name', 'Ahmedabad')->firstOrFail();

        $this->actingAs($master)->put(route('master.cities.update', $city), [
            'name' => 'Surat',
            'state_id' => $state->id,
            'country_id' => $country->id,
        ])->assertRedirect(route('master.cities.index'));

        $this->actingAs($master)->put(route('master.states.update', $state), [
            'name' => 'Maharashtra',
            'country_id' => $country->id,
            'code' => 'MH',
        ])->assertRedirect(route('master.states.index'));

        $this->actingAs($master)->put(route('master.countries.update', $country), [
            'name' => 'Bharat',
            'code' => 'IN',
        ])->assertRedirect(route('master.countries.index'));

        $this->actingAs($master)->delete(route('master.cities.destroy', $city))->assertRedirect(route('master.cities.index'));
        $this->actingAs($master)->delete(route('master.states.destroy', $state))->assertRedirect(route('master.states.index'));
        $this->actingAs($master)->delete(route('master.countries.destroy', $country))->assertRedirect(route('master.countries.index'));

        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
        $this->assertDatabaseMissing('states', ['id' => $state->id]);
        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }

    public function test_super_admin_can_create_but_not_edit_or_delete_country_state_city(): void
    {
        $super = $this->makeUser('super_admin');
        $this->assertFalse($super->canAccessCompanyPanel());

        $this->actingAs($super)->post('/master/countries', [
            'name' => 'India',
            'code' => 'IN',
        ])->assertRedirect(route('master.countries.index'));
        $country = Country::where('code', 'IN')->firstOrFail();

        $this->actingAs($super)->post('/master/states', [
            'name' => 'Gujarat',
            'country_id' => $country->id,
            'code' => 'GJ',
        ])->assertRedirect(route('master.states.index'));
        $state = State::where('code', 'GJ')->firstOrFail();

        $this->actingAs($super)->post('/master/cities', [
            'name' => 'Ahmedabad',
            'state_id' => $state->id,
            'country_id' => $country->id,
        ])->assertRedirect(route('master.cities.index'));
        $city = City::where('name', 'Ahmedabad')->firstOrFail();

        $this->assertDatabaseHas('cities', ['name' => 'Ahmedabad', 'state_id' => $state->id]);

        $this->actingAs($super)->get(route('master.countries.edit', $country))->assertForbidden();
        $this->actingAs($super)->delete(route('master.countries.destroy', $country))->assertForbidden();
        $this->actingAs($super)->get(route('master.states.edit', $state))->assertForbidden();
        $this->actingAs($super)->delete(route('master.states.destroy', $state))->assertForbidden();
        $this->actingAs($super)->get(route('master.cities.edit', $city))->assertForbidden();
        $this->actingAs($super)->delete(route('master.cities.destroy', $city))->assertForbidden();
    }

    /**
     * @return array{0: Country, 1: State, 2: City}
     */
    protected function seedCountryStateCity(): array
    {
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id, 'code' => 'GJ']);
        $city = City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);

        return [$country, $state, $city];
    }

    public function test_countries_index_disables_create_button(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get(route('master.countries.index'));

        $response->assertOk()
            ->assertSee('aria-label="Create country"', false)
            ->assertSee('disabled', false)
            ->assertDontSee('href="'.route('master.countries.create').'"', false);
    }

    public function test_accountant_can_view_masters_but_not_create(): void
    {
        $accountant = $this->makeUser('accountant');

        $this->actingAs($accountant)->get('/master/countries')->assertForbidden();
        $this->actingAs($accountant)->post('/master/countries', [
            'name' => 'USA',
            'code' => 'US',
        ])->assertForbidden();
    }

    public function test_pump_crud_admin(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('/master/pumps', [
            'pump_name' => 'HP Pump',
            'contact_name' => 'John',
            'contact_number' => '9999999999',
            'remark' => 'Test pump',
        ])->assertRedirect(route('master.pump.index'));

        $this->assertDatabaseHas('pumps', ['pump_name' => 'HP Pump']);
    }

    public function test_goods_master_admin(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->post('/master/goods-types', [
            'name' => 'Steel Rods',
        ])->assertRedirect(route('master.goods-types.index'));

        $this->assertDatabaseHas('good_types', ['name' => 'Steel Rods', 'user_id' => $admin->company->user_id]);
    }
}

