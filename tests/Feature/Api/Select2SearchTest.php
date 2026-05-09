<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertStringContainsString('Owner Alpha', $resultTexts);
        $this->assertStringNotContainsString('Owner Other', $resultTexts);
    }
}
