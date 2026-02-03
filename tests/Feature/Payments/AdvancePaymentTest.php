<?php

namespace Tests\Feature\Payments;

use App\Models\AdvancePayment;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\Transporter;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancePaymentTest extends TestCase
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
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Main Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
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

    protected function makeTransporter(User $admin, City $city): Transporter
    {
        $master = \App\Models\TransporterMaster::factory()
            ->withLocation($city->id, $city->state_id, $city->country_id)
            ->create(['name' => 'Fast Logistics', 'pan_number' => 'ABCDE1234F']);

        return Transporter::factory()
            ->forCompany($admin->company_id)
            ->create([
                'transporter_master_id' => $master->id,
                'mobile_number' => '9999999999',
                'declaration_given' => false,
                'tds_percentage' => 20,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);
    }

    public function test_store_creates_advance_payment_and_updates_transporter_balance(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/advance-payments', [
            'transporter_id' => $transporter->id,
            'advance_amount' => 5000,
            'payment_date' => now()->toDateString(),
            'remark' => 'Initial payment',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);
        $this->assertDatabaseHas('advance_payments', [
            'transporter_id' => $transporter->id,
            'advance_amount' => 5000,
            'company_id' => $admin->company_id,
        ]);

        $this->assertEquals(5000, $transporter->fresh()->advance_amount);
    }

    public function test_store_returns_json_when_ajax(): void
    {
        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $response = $this->actingAs($admin)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/advance-payments', [
            'transporter_id' => $transporter->id,
            'advance_amount' => 2500,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['success' => true]);
        $this->assertDatabaseHas('advance_payments', [
            'transporter_id' => $transporter->id,
            'advance_amount' => 2500,
        ]);
    }
}

