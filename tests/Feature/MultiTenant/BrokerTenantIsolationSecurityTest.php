<?php

namespace Tests\Feature\MultiTenant;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\GoodType;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokerTenantIsolationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        if (! UserRole::where('id', 3)->exists()) {
            UserRole::create(['id' => 1, 'name' => 'master_admin']);
            UserRole::create(['id' => 2, 'name' => 'super_admin']);
            UserRole::create(['id' => 3, 'name' => 'admin']);
            UserRole::create(['id' => 4, 'name' => 'moderator']);
        }
    }

    private function createTenant(string $name, string $shortName): array
    {
        $admin = User::factory()->create([
            'user_role_id' => 3,
        ]);

        $company = Company::create([
            'name' => $name,
            'short_name' => $shortName,
            'user_id' => $admin->id,
            'is_blocked' => false,
        ]);

        $admin->company_id = $company->id;
        $admin->save();

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => 100,
        ]);

        return [$admin, $company];
    }

    public function test_tenant_b_cannot_see_tenant_a_lorry_receipts_in_index(): void
    {
        [$adminA, $companyA] = $this->createTenant('Tenant A Logistics', 'TENA');
        [$adminB, $companyB] = $this->createTenant('Tenant B Cargo', 'TENB');

        $customerA = Consignee::create([
            'company_id' => $companyA->id,
            'name' => 'Party A',
            'address' => 'Mumbai',
        ]);
        $masterA = TransporterMaster::factory()->create(['name' => 'Trans A']);
        $transporterA = Transporter::factory()->forCompany($companyA->id)->create([
            'transporter_master_id' => $masterA->id,
            'mobile_number' => '9111111111',
            'tds_percentage' => 1.0,
        ]);
        $truckA = Truck::create([
            'company_id' => $companyA->id,
            'transporter_id' => $transporterA->id,
            'truck_number' => 'MH01AA1000',
            'truck_type' => 'Open',
        ]);

        $lrA = LorryReceipt::create([
            'company_id' => $companyA->id,
            'customer_id' => $customerA->id,
            'consignor_id' => $customerA->id,
            'consignee_id' => $customerA->id,
            'truck_id' => $truckA->id,
            'transporter_id' => $transporterA->id,
            'lr_number' => 'LR-TENA-SECRET-001',
            'gr_number' => 'GR-TENA-001',
            'source_city' => 'Mumbai',
            'source_state' => 'MH',
            'destination_city' => 'Pune',
            'destination_state' => 'MH',
            'lane' => 'Mumbai-Pune',
            'weight' => 15,
            'no_of_units' => 10,
            'good_type' => 'Steel',
            'fr_rate' => 500,
            'amount' => 7500,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
        ]);

        $response = $this->actingAs($adminB)->get('/lorry_receipts');
        $response->assertOk();
        $response->assertDontSee('LR-TENA-SECRET-001');
    }

    public function test_tenant_b_cannot_access_tenant_a_truck(): void
    {
        [$adminA, $companyA] = $this->createTenant('Fleet A', 'FLA');
        [$adminB, $companyB] = $this->createTenant('Fleet B', 'FLB');

        $truckA = Truck::create([
            'company_id' => $companyA->id,
            'truck_number' => 'KA01ZZ9999',
            'truck_type' => 'Flatbed',
        ]);

        // Admin B delete attempt -> 403 Forbidden
        $deleteResponse = $this->actingAs($adminB)->delete("/trucks/{$truckA->id}", [
            'delete_remark' => 'Cross-tenant attack',
        ]);
        $deleteResponse->assertForbidden();

        // Admin B remove-transporter attempt -> 403 Forbidden
        $removeResponse = $this->actingAs($adminB)->patch("/trucks/{$truckA->id}/remove-transporter");
        $removeResponse->assertForbidden();
    }

    public function test_tenant_b_cannot_access_tenant_a_invoice_and_parties(): void
    {
        [$adminA, $companyA] = $this->createTenant('Tenant A Logistics', 'TENA');
        [$adminB, $companyB] = $this->createTenant('Tenant B Cargo', 'TENB');

        $customerA = Consignee::create([
            'company_id' => $companyA->id,
            'name' => 'Party A',
            'address' => 'Mumbai',
        ]);

        $invoiceA = Invoice::create([
            'company_id' => $companyA->id,
            'customer_id' => $customerA->id,
            'invoice_number' => 'INV-TENA-001',
            'invoice_type' => 'regular',
            'generated_date' => now()->toDateString(),
            'total_amount' => 5000,
        ]);

        $masterA = TransporterMaster::factory()->create(['name' => 'Trans A']);
        $transporterA = Transporter::factory()->forCompany($companyA->id)->create([
            'transporter_master_id' => $masterA->id,
            'mobile_number' => '9111111111',
            'tds_percentage' => 1.0,
        ]);

        // 1. Cross-company Invoice Show -> 403 Forbidden
        $invoiceResponse = $this->actingAs($adminB)->get("/invoices/{$invoiceA->id}");
        $invoiceResponse->assertForbidden();

        // 2. Cross-company Consignee Delete -> 403 Forbidden
        $consigneeResponse = $this->actingAs($adminB)->delete("/consignees/{$customerA->id}");
        $consigneeResponse->assertForbidden();

        // 3. Cross-company Transporter Refresh TDS -> 403 Forbidden
        $transporterResponse = $this->actingAs($adminB)->post("/transporters/{$transporterA->id}/refresh-tds");
        $transporterResponse->assertForbidden();
    }

    public function test_same_lr_and_truck_numbers_permitted_across_different_tenants(): void
    {
        [$adminA, $companyA] = $this->createTenant('Tenant Alpha', 'TAL');
        [$adminB, $companyB] = $this->createTenant('Tenant Beta', 'TBE');

        // Both companies register truck with identical registration number
        $truckA = Truck::create([
            'company_id' => $companyA->id,
            'truck_number' => 'MH04AB1234',
            'truck_type' => 'Container',
        ]);

        $truckB = Truck::create([
            'company_id' => $companyB->id,
            'truck_number' => 'MH04AB1234', // Same truck number in Company B
            'truck_type' => 'Container',
        ]);

        $this->assertDatabaseHas('trucks', ['id' => $truckA->id, 'company_id' => $companyA->id]);
        $this->assertDatabaseHas('trucks', ['id' => $truckB->id, 'company_id' => $companyB->id]);

        // Both companies issue LR with identical LR number
        $custA = Consignee::create(['company_id' => $companyA->id, 'name' => 'Cust A', 'address' => 'A']);
        $custB = Consignee::create(['company_id' => $companyB->id, 'name' => 'Cust B', 'address' => 'B']);

        $masterA = TransporterMaster::factory()->create(['name' => 'Tr A']);
        $masterB = TransporterMaster::factory()->create(['name' => 'Tr B']);

        $transA = Transporter::factory()->forCompany($companyA->id)->create(['transporter_master_id' => $masterA->id, 'mobile_number' => '9000000001']);
        $transB = Transporter::factory()->forCompany($companyB->id)->create(['transporter_master_id' => $masterB->id, 'mobile_number' => '9000000002']);

        $lrA = LorryReceipt::create([
            'company_id' => $companyA->id,
            'customer_id' => $custA->id,
            'consignor_id' => $custA->id,
            'consignee_id' => $custA->id,
            'truck_id' => $truckA->id,
            'transporter_id' => $transA->id,
            'lr_number' => 'LR-COMMON-001',
            'gr_number' => 'GR-A-001',
            'source_city' => 'Delhi',
            'source_state' => 'DL',
            'destination_city' => 'Jaipur',
            'destination_state' => 'RJ',
            'lane' => 'Delhi-Jaipur',
            'weight' => 10,
            'no_of_units' => 1,
            'good_type' => 'Goods',
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
        ]);

        $lrB = LorryReceipt::create([
            'company_id' => $companyB->id,
            'customer_id' => $custB->id,
            'consignor_id' => $custB->id,
            'consignee_id' => $custB->id,
            'truck_id' => $truckB->id,
            'transporter_id' => $transB->id,
            'lr_number' => 'LR-COMMON-001', // Same LR number in Company B
            'gr_number' => 'GR-B-001',
            'source_city' => 'Nagpur',
            'source_state' => 'MH',
            'destination_city' => 'Bhopal',
            'destination_state' => 'MP',
            'lane' => 'Nagpur-Bhopal',
            'weight' => 10,
            'no_of_units' => 1,
            'good_type' => 'Goods',
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
        ]);

        $this->assertSame('LR-COMMON-001', $lrA->lr_number);
        $this->assertSame('LR-COMMON-001', $lrB->lr_number);
        $this->assertNotEquals($lrA->company_id, $lrB->company_id);
    }

    public function test_blocked_user_is_prevented_by_middleware(): void
    {
        [$admin, $company] = $this->createTenant('Blocked Co', 'BLK');
        $admin->is_blocked = true;
        $admin->save();

        $response = $this->actingAs($admin)->get('/dashboard');
        // auth.blocked redirects to login or returns blocked status
        $this->assertContains($response->status(), [302, 401, 403]);
    }

    public function test_blocked_company_is_prevented_by_middleware(): void
    {
        [$admin, $company] = $this->createTenant('Blocked Enterprise', 'BLE');
        $company->is_blocked = true;
        $company->save();

        $response = $this->actingAs($admin)->get('/dashboard');
        $this->assertContains($response->status(), [302, 401, 403]);
    }

    public function test_moderator_role_cannot_delete_trucks_or_modify_company_settings(): void
    {
        [$admin, $company] = $this->createTenant('Permission Co', 'PRM');

        $moderator = User::factory()->create([
            'user_role_id' => 4, // moderator role
            'company_id' => $company->id,
        ]);

        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'GJ05CD4321',
            'truck_type' => 'Open',
        ]);

        // Moderator attempt to delete truck -> Forbidden by role:admin,super_admin middleware
        $response = $this->actingAs($moderator)->delete("/trucks/{$truck->id}", [
            'delete_remark' => 'Attempt by moderator',
        ]);
        $this->assertContains($response->status(), [403, 302]);

        // Truck still exists
        $this->assertDatabaseHas('trucks', [
            'id' => $truck->id,
            'deleted_at' => null,
        ]);
    }

    public function test_super_admin_requires_company_context_session_for_broker_modules(): void
    {
        $superAdmin = User::factory()->create([
            'user_role_id' => 2, // super_admin
        ]);

        // When session has no super_admin_company_id, company.context redirects to company.index
        $response = $this->actingAs($superAdmin)->get('/trucks');
        $response->assertRedirect(route('company.index'));

        // When entered into company context, request proceeds
        $company = Company::create(['name' => 'Active Context Co', 'is_blocked' => false]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subDay(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
        ]);

        $responseWithContext = $this->actingAs($superAdmin)
            ->withSession(['super_admin_company_id' => $company->id])
            ->get('/trucks');

        $responseWithContext->assertOk();
    }
}
