<?php

namespace Tests\Feature\Consignee;

use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    protected function makeUser(string $role = 'admin'): User
    {
        $roleId = UserRole::where('name', $role)->value('id');
        $company = Company::create([
            'name' => 'Company ' . $role,
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

    public function test_index_returns_view(): void
    {
        $user = $this->makeUser('admin');

        $response = $this->actingAs($user)->get('/consignees');

        $response->assertOk()->assertViewIs('consignees.index');
    }

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
        ]);

        $response = $this->actingAs($user)->from('/consignees/create')->post('/consignees', [
            'name' => 'New One',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9999999999', // duplicate
            'email' => 'dupe@example.com',   // duplicate
        ]);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['contact_number']);
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
        ]);

        $response = $this->actingAs($user)->delete("/consignees/{$consignee->id}");

        $response->assertRedirect(route('consignees.index'));
        $this->assertSoftDeleted('consignees', ['id' => $consignee->id]);
    }

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
}

