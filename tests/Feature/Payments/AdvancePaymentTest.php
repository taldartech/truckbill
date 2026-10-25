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

    public function test_store_redirects_on_non_ajax_request(): void
    {
        $this->app['router']->get('/advance-payments-index', fn () => 'index')->name('advance-payments.index');
        $this->app['router']->getRoutes()->refreshNameLookups();

        $admin = $this->makeAdmin();
        [, , $city] = $this->seedGeo();
        $transporter = $this->makeTransporter($admin, $city);

        $response = $this->actingAs($admin)
            ->post('/advance-payments', [
                'transporter_id' => $transporter->id,
                'advance_amount' => 1200,
                'payment_date' => now()->toDateString(),
            ]);

        $response->assertRedirect(route('advance-payments.index'));
        $response->assertSessionHas('success', 'Advance Payment created successfully.');
    }

    public function test_store_resolves_super_admin_session_company_and_fallback(): void
    {
        [, , $city] = $this->seedGeo();
        $targetCompany = Company::create([
            'name' => 'Target Co',
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $master = \App\Models\TransporterMaster::factory()
            ->withLocation($city->id, $city->state_id, $city->country_id)
            ->create(['name' => 'Super Logistics', 'pan_number' => 'ZZZZZ1234F']);

        $transporter = Transporter::factory()
            ->forCompany($targetCompany->id)
            ->create([
                'transporter_master_id' => $master->id,
                'mobile_number' => '8888888888',
                'declaration_given' => false,
                'tds_percentage' => 20,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);

        // Case 1: super_admin with session('super_admin_company_id')
        $response = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $targetCompany->id])
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/advance-payments', [
                'transporter_id' => $transporter->id,
                'advance_amount' => 3000,
                'payment_date' => now()->toDateString(),
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('advance_payments', [
            'transporter_id' => $transporter->id,
            'company_id' => $targetCompany->id,
            'advance_amount' => 3000,
        ]);

        // Case 2: User with no company_id and no session (falls back to transporter->company_id)
        $noCompanyUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => null,
        ]);

        $response2 = $this->actingAs($noCompanyUser)
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->post('/advance-payments', [
                'transporter_id' => $transporter->id,
                'advance_amount' => 1500,
                'payment_date' => now()->toDateString(),
            ]);

        $response2->assertOk();
        $this->assertDatabaseHas('advance_payments', [
            'transporter_id' => $transporter->id,
            'company_id' => $targetCompany->id,
            'advance_amount' => 1500,
        ]);
    }
}


