<?php

namespace Tests\Feature\Api;

use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\Driver;
use App\Models\State;
use App\Models\Truck;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class Select2SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    private function makeAdmin(): User
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

    public function test_drivers_search_returns_select2_shape_and_company_scoped_results(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        Driver::create([
            'name' => 'Alpha Driver',
            'mobile' => '9000000001',
            'company_id' => $admin->company_id,
        ]);

        Driver::create([
            'name' => 'Alpha Other',
            'mobile' => '9000000002',
            'company_id' => $otherAdmin->company_id,
        ]);

        $response = $this->actingAs($admin)->get('/api/drivers/search?q=Alpha&limit=20');

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
                'pagination' => ['more'],
            ]);

        $resultTexts = collect($response->json('results'))->pluck('text')->implode(' ');
        $this->assertStringContainsString('Alpha Driver', $resultTexts);
        $this->assertStringNotContainsString('Alpha Other', $resultTexts);
    }

    public function test_truck_owners_search_returns_select2_shape_and_company_scoped_results(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $ownerMaster = TransporterMaster::factory()->create([
            'name' => 'Owner Alpha',
            'pan_number' => 'ABCDE1234F',
        ]);
        $otherOwnerMaster = TransporterMaster::factory()->create([
            'name' => 'Owner Other',
            'pan_number' => 'PQRSX1234Z',
        ]);

        Transporter::create([
            'transporter_master_id' => $ownerMaster->id,
            'company_id' => $admin->company_id,
            'is_owner' => 1,
            'short_name' => 'OWNR',
            'mobile_number' => '9888888888',
            'declaration_given' => 0,
            'total_trucks' => 0,
            'advance_amount' => 0,
            'tds_percentage' => 20,
        ]);

        Transporter::create([
            'transporter_master_id' => $otherOwnerMaster->id,
            'company_id' => $otherAdmin->company_id,
            'is_owner' => 1,
            'mobile_number' => '9777777777',
            'declaration_given' => 0,
            'total_trucks' => 0,
            'advance_amount' => 0,
            'tds_percentage' => 20,
        ]);

        $response = $this->actingAs($admin)->get('/api/truck-owners/search?q=Owner&limit=20');

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
                'pagination' => ['more'],
            ]);

        $resultTexts = collect($response->json('results'))->pluck('text')->implode(' ');
        $this->assertStringContainsString('Owner Alpha (OWNR)', $resultTexts);
        $this->assertStringNotContainsString('Owner Other', $resultTexts);
    }

    public function test_trucks_search_returns_select2_shape_and_company_scoped_results(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $transporter = Transporter::factory()->forCompany($admin->company_id)->create([
            'mobile_number' => '9555555555',
            'declaration_given' => false,
            'tds_percentage' => 20,
            'total_trucks' => 0,
            'advance_amount' => 0,
        ]);

        $otherTransporter = Transporter::factory()->forCompany($otherAdmin->company_id)->create([
            'mobile_number' => '9444444444',
            'declaration_given' => false,
            'tds_percentage' => 20,
            'total_trucks' => 0,
            'advance_amount' => 0,
        ]);

        Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ11TRUCK1',
            'truck_type' => 'Flatbed',
        ]);

        Truck::create([
            'transporter_id' => $otherTransporter->id,
            'truck_number' => 'GJ11TRUCK2',
            'truck_type' => 'Flatbed',
        ]);

        $response = $this->actingAs($admin)->get('/api/trucks/search?q=GJ11TRUCK&limit=20');

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
                'pagination' => ['more'],
            ]);

        $resultTexts = collect($response->json('results'))->pluck('text')->implode(' ');
        $this->assertStringContainsString('GJ11TRUCK1', $resultTexts);
        $this->assertStringNotContainsString('GJ11TRUCK2', $resultTexts);
    }

    public function test_agencies_search_includes_short_name_in_brackets(): void
    {
        $admin = $this->makeAdmin();

        $agencyMaster = TransporterMaster::factory()->create([
            'name' => 'Agency Alpha',
            'pan_number' => 'ABCDE5678G',
        ]);

        Transporter::create([
            'transporter_master_id' => $agencyMaster->id,
            'company_id' => $admin->company_id,
            'is_owner' => 0,
            'short_name' => 'AGCY',
            'mobile_number' => '9666666666',
            'declaration_given' => 0,
            'total_trucks' => 0,
            'advance_amount' => 0,
            'tds_percentage' => 20,
        ]);

        $response = $this->actingAs($admin)->get('/api/agencies/search?q=Agency&limit=20');

        $response->assertOk();

        $resultTexts = collect($response->json('results'))->pluck('text')->implode(' ');
        $this->assertStringContainsString('Agency Alpha (AGCY)', $resultTexts);
    }

    public function test_voucher_transporters_search_returns_owners_and_agencies_company_scoped(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $masterA = TransporterMaster::factory()->create(['name' => 'VoucherSearch Alpha Co']);
        $masterB = TransporterMaster::factory()->create(['name' => 'VoucherSearch Beta Co']);
        $masterOther = TransporterMaster::factory()->create(['name' => 'VoucherSearch Other Co']);

        Transporter::create([
            'transporter_master_id' => $masterA->id,
            'company_id' => $admin->company_id,
            'is_owner' => 1,
            'mobile_number' => '9111111111',
            'declaration_given' => 0,
            'total_trucks' => 0,
            'advance_amount' => 0,
            'tds_percentage' => 20,
        ]);
        Transporter::create([
            'transporter_master_id' => $masterB->id,
            'company_id' => $admin->company_id,
            'is_owner' => 0,
            'mobile_number' => '9222222222',
            'declaration_given' => 0,
            'total_trucks' => 0,
            'advance_amount' => 0,
            'tds_percentage' => 20,
        ]);
        Transporter::create([
            'transporter_master_id' => $masterOther->id,
            'company_id' => $otherAdmin->company_id,
            'is_owner' => 0,
            'mobile_number' => '9333333333',
            'declaration_given' => 0,
            'total_trucks' => 0,
            'advance_amount' => 0,
            'tds_percentage' => 20,
        ]);

        $response = $this->actingAs($admin)->get('/api/transporters/search?q=VoucherSearch&limit=20');

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
                'pagination' => ['more'],
            ]);

        $texts = collect($response->json('results'))->pluck('text')->implode(' | ');
        $this->assertStringContainsString('VoucherSearch Alpha Co', $texts);
        $this->assertStringContainsString('(Owner)', $texts);
        $this->assertStringContainsString('VoucherSearch Beta Co', $texts);
        $this->assertStringContainsString('(Agency)', $texts);
        $this->assertStringNotContainsString('VoucherSearch Other Co', $texts);
    }

    public function test_cities_search_returns_select2_shape_and_filters_by_name(): void
    {
        $admin = $this->makeAdmin();

        $country = Country::query()->create([
            'name' => 'Select2 City Country '.uniqid(),
            'code' => 'S2'.substr(uniqid(), 0, 6),
        ]);
        $state = State::query()->create([
            'country_id' => $country->id,
            'name' => 'Select2 City State '.uniqid(),
            'code' => 'ST'.substr(uniqid(), 0, 6),
        ]);
        City::query()->create([
            'state_id' => $state->id,
            'country_id' => $country->id,
            'name' => 'VoucherFilterCity Alpha',
        ]);
        City::query()->create([
            'state_id' => $state->id,
            'country_id' => $country->id,
            'name' => 'VoucherFilterCity Beta',
        ]);

        $response = $this->actingAs($admin)->get('/api/cities/search?q=VoucherFilterCity&limit=20');

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
                'pagination' => ['more'],
            ]);

        $names = collect($response->json('results'))->pluck('text')->all();
        $this->assertContains('VoucherFilterCity Alpha', $names);
        $this->assertContains('VoucherFilterCity Beta', $names);
    }

    public function test_consignees_search_matches_name_address_and_city_without_cross_company_leaks(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $country = Country::query()->create([
            'name' => 'ConsigneeSearchLand '.uniqid(),
            'code' => 'C'.substr(uniqid(), 0, 6),
        ]);
        $state = State::query()->create([
            'country_id' => $country->id,
            'name' => 'ConsigneeSearchState '.uniqid(),
            'code' => 'S'.substr(uniqid(), 0, 6),
        ]);
        $city = City::query()->create([
            'state_id' => $state->id,
            'country_id' => $country->id,
            'name' => 'UniqueVikasNagar',
        ]);

        Consignee::query()->create([
            'company_id' => $admin->company_id,
            'name' => 'Vikas Trading Co',
            'address' => 'Plot 1',
            'city_id' => $city->id,
            'state_id' => $state->id,
            'country_id' => $country->id,
            'contact_number' => '9000000001',
            'gst_number' => '27AAAAA0000A1Z5',
            'pan_number' => 'AAAAA0000A',
            'email' => 'vikas@example.com',
        ]);

        Consignee::query()->create([
            'company_id' => $admin->company_id,
            'name' => 'Plain Name Ltd',
            'address' => 'Near UniqueVikasNagar warehouse',
            'city_id' => null,
            'state_id' => null,
            'country_id' => null,
            'contact_number' => '9000000002',
            'gst_number' => '27BBBBB0000B1Z5',
            'pan_number' => 'BBBBB0000B',
            'email' => 'plain@example.com',
        ]);

        Consignee::query()->create([
            'company_id' => $otherAdmin->company_id,
            'name' => 'Vikas Other Co',
            'address' => 'Elsewhere',
            'city_id' => null,
            'state_id' => null,
            'country_id' => null,
            'contact_number' => '9000000003',
            'gst_number' => '27CCCCC0000C1Z5',
            'pan_number' => 'CCCCC0000C',
            'email' => 'other@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/api/consignees/search?q=Vikas&limit=20');

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
                'pagination' => ['more'],
            ]);

        $texts = collect($response->json('results'))->pluck('text')->all();
        $this->assertContains('Vikas Trading Co (UniqueVikasNagar)', $texts);
        $this->assertContains('Plain Name Ltd', $texts);
        $this->assertNotContains('Vikas Other Co', $texts);
    }

    public function test_drivers_search_returns_empty_results_for_blank_query(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/api/drivers/search?q=')
            ->assertOk()
            ->assertJson([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
    }

    public function test_truck_owners_search_returns_empty_results_for_blank_query(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get('/api/truck-owners/search?q=')
            ->assertOk()
            ->assertJson([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
    }

    public function test_trucks_search_labels_orphan_truck_and_driver_in_text(): void
    {
        $admin = $this->makeAdmin();

        $driver = Driver::create([
            'name' => 'Ravi Driver',
            'mobile' => '9000000099',
            'company_id' => $admin->company_id,
        ]);

        Truck::create([
            'company_id' => $admin->company_id,
            'transporter_id' => null,
            'driver_id' => $driver->id,
            'truck_number' => 'ORPHANTRK9',
            'truck_type' => 'Open',
        ]);

        $response = $this->actingAs($admin)->get('/api/trucks/search?q=ORPHANTRK9&limit=20');

        $response->assertOk();
        $text = (string) collect($response->json('results'))->pluck('text')->first();
        $this->assertStringContainsString('ORPHANTRK9', $text);
        $this->assertStringContainsString('Driver: Ravi Driver', $text);
        $this->assertStringContainsString('No Truck Owner', $text);
    }

    public function test_voucher_transporters_returns_empty_when_company_unresolved(): void
    {
        $adminWithoutCompany = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => null,
        ]);

        $this->actingAs($adminWithoutCompany)
            ->get('/api/transporters/search?q=Any')
            ->assertOk()
            ->assertJson([
                'results' => [],
                'pagination' => ['more' => false],
            ]);
    }

    public function test_voucher_transporters_search_uses_super_admin_company_session(): void
    {
        $admin = $this->makeAdmin();
        $super = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $master = TransporterMaster::create([
            'name' => 'Session Scoped Owner',
            'pan_number' => 'PANSESS001',
        ]);
        Transporter::factory()->forCompany($admin->company_id)->create([
            'transporter_master_id' => $master->id,
            'short_name' => 'SCOPE',
            'mobile_number' => '9111111111',
            'is_owner' => 1,
        ]);

        $otherAdmin = $this->makeAdmin();
        Transporter::factory()->forCompany($otherAdmin->company_id)->create([
            'transporter_master_id' => TransporterMaster::create([
                'name' => 'Other Company Owner',
                'pan_number' => 'PANOTHER001',
            ])->id,
            'short_name' => 'OTHER',
            'mobile_number' => '9222222222',
            'is_owner' => 1,
        ]);

        $response = $this->actingAs($super)
            ->withSession(['super_admin_company_id' => $admin->company_id])
            ->get('/api/transporters/search?q=Session');

        $response->assertOk();
        $texts = collect($response->json('results'))->pluck('text')->all();
        $this->assertContains('Session Scoped Owner (Owner)', $texts);
        $this->assertNotContains('Other Company Owner (Owner)', $texts);
    }

    public function test_agencies_search_supports_pagination_more_flag(): void
    {
        $admin = $this->makeAdmin();

        for ($i = 1; $i <= 3; $i++) {
            $master = TransporterMaster::create([
                'name' => "Paginated Agency {$i}",
                'pan_number' => 'PAG'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
            ]);
            Transporter::factory()->forCompany($admin->company_id)->create([
                'transporter_master_id' => $master->id,
                'short_name' => "PA{$i}",
                'mobile_number' => '933333333'.$i,
                'is_owner' => 0,
            ]);
        }

        $response = $this->actingAs($admin)->get('/api/agencies/search?page=1&limit=2');

        $response->assertOk()
            ->assertJsonPath('pagination.more', true);
        $this->assertCount(2, $response->json('results'));
    }

    public function test_cities_search_returns_paginated_results_and_supports_query_filter(): void
    {
        $admin = $this->makeAdmin();
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        City::create(['name' => 'Anand', 'state_id' => $state->id, 'country_id' => $country->id]);
        City::create(['name' => 'Surat', 'state_id' => $state->id, 'country_id' => $country->id]);

        $this->actingAs($admin)
            ->get('/api/cities/search?q=Ahmed&page=1&limit=1')
            ->assertOk()
            ->assertJsonPath('pagination.more', false)
            ->assertJsonCount(1, 'results');

        $this->actingAs($admin)
            ->get('/api/cities/search?page=1&limit=10')
            ->assertOk()
            ->assertJsonPath('pagination.more', false)
            ->assertJsonCount(3, 'results');
    }

    public function test_truck_owners_search_matches_master_and_mobile_fields(): void
    {
        $admin = $this->makeAdmin();
        $master = TransporterMaster::create([
            'name' => 'Unique Owner Logistics',
            'pan_number' => 'PANOWN1234',
        ]);
        Transporter::factory()->forCompany($admin->company_id)->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '9555123456',
            'contact_person_name' => 'Ravi Owner',
            'is_owner' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/api/truck-owners/search?q=Unique')
            ->assertOk()
            ->assertJsonPath('results.0.text', 'Unique Owner Logistics');

        $this->actingAs($admin)
            ->get('/api/truck-owners/search?q=9555')
            ->assertOk()
            ->assertJsonCount(1, 'results');

        $this->actingAs($admin)
            ->get('/api/truck-owners/search?q=')
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    public function test_select2_searches_return_empty_results_when_queries_fail(): void
    {
        Log::spy();
        $admin = $this->makeAdmin();

        $endpoints = [
            '/api/cities/search?q=Surat' => 'from "cities"',
            '/api/truck-owners/search?q=Owner' => 'transporter_masters',
            '/api/transporters/search?q=Agency' => 'from "transporters"',
            '/api/agencies/search?q=Agency' => 'from "transporters"',
            '/api/trucks/search?q=MH' => 'from "trucks"',
            '/api/consignees/search?q=Party' => 'from "consignees"',
        ];

        foreach ($endpoints as $url => $sqlNeedle) {
            $connection = DB::connection();
            $dispatcher = $connection->getEventDispatcher();
            $shouldFail = true;
            $connection->listen(function ($query) use (&$shouldFail, $sqlNeedle) {
                if ($shouldFail && str_contains($query->sql, $sqlNeedle)) {
                    $shouldFail = false;
                    throw new \RuntimeException('Simulated Select2 failure');
                }
            });

            $this->actingAs($admin)
                ->getJson($url)
                ->assertOk()
                ->assertJsonPath('results', []);

            $connection->setEventDispatcher($dispatcher);
        }

        Log::shouldHaveReceived('error')->atLeast()->once();
    }
}
