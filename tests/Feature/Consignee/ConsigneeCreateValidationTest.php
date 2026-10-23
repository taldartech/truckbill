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

class ConsigneeCreateValidationTest extends TestCase
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
        $country = Country::create([
            'name' => 'India',
            'code' => 'IN',
        ]);

        $state = State::create([
            'name' => 'Gujarat',
            'country_id' => $country->id,
        ]);

        $city = City::create([
            'name' => 'Ahmedabad',
            'state_id' => $state->id,
            'country_id' => $country->id,
        ]);

        return [$country, $state, $city];
    }

    protected function validPayload(Country $country, State $state, City $city): array
    {
        return [
            'name' => 'Test Consignee',
            'address' => '123 Test Street',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9876543210',
            'gst_number' => '12ABCDE1234F1Z5',
            'pan_number' => 'ABCDE1234F',
            'email' => 'test@example.com',
        ];
    }

    public function test_name_is_required(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $payload = $this->validPayload($country, $state, $city);
        unset($payload['name']);

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['name']);
    }

    public function test_contact_number_is_required(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $payload = $this->validPayload($country, $state, $city);
        unset($payload['contact_number']);

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['contact_number']);
    }

    public function test_contact_number_must_be_exactly_10_digits(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $payload = $this->validPayload($country, $state, $city);
        $payload['contact_number'] = '12345';

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['contact_number']);
    }

    public function test_email_must_be_valid(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $payload = $this->validPayload($country, $state, $city);
        $payload['email'] = 'not-an-email';

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_pan_number_must_have_valid_format(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $payload = $this->validPayload($country, $state, $city);
        $payload['pan_number'] = 'ABCDE123';

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['pan_number']);
    }

    public function test_gst_number_must_have_valid_format(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $payload = $this->validPayload($country, $state, $city);
        $payload['gst_number'] = 'INVALID-GST';

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['gst_number']);
    }

    public function test_email_must_be_unique_within_company(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9999999999',
            'email' => 'existing@example.com',
        ]);

        $payload = $this->validPayload($country, $state, $city);
        $payload['email'] = 'existing@example.com';

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['email']);
    }

    public function test_pan_number_must_be_unique_within_company(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Existing Consignee',
            'country_id' => $country->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'contact_number' => '9999999999',
            'pan_number' => 'ABCDE1234F',
        ]);

        $payload = $this->validPayload($country, $state, $city);
        $payload['pan_number'] = 'ABCDE1234F';

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['pan_number']);
    }

    public function test_state_must_belong_to_selected_country(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $otherCountry = Country::create([
            'name' => 'Other Country',
            'code' => 'OT',
        ]);

        $payload = $this->validPayload($country, $state, $city);
        $payload['country_id'] = $otherCountry->id;

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['state_id']);
    }

    public function test_city_must_belong_to_selected_state(): void
    {
        $user = $this->makeUser();
        [$country, $state, $city] = $this->seedGeo();

        $otherState = State::create([
            'name' => 'Other State',
            'country_id' => $country->id,
        ]);

        $payload = $this->validPayload($country, $state, $city);
        $payload['state_id'] = $otherState->id;

        $response = $this->actingAs($user)
            ->from('/consignees/create')
            ->post('/consignees', $payload);

        $response->assertRedirect('/consignees/create');
        $response->assertSessionHasErrors(['city_id']);
    }
}
