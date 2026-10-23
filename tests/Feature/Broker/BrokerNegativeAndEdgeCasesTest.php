<?php

namespace Tests\Feature\Broker;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokerNegativeAndEdgeCasesTest extends TestCase
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

    private function createBrokerUser(string $subStatus = 'active', int $lrCredits = 50, ?Carbon $endsOn = null): array
    {
        $admin = User::factory()->create(['user_role_id' => 3]);
        $company = Company::create([
            'name' => 'Edge Logistics',
            'short_name' => 'EDG',
            'user_id' => $admin->id,
            'is_blocked' => false,
        ]);
        $admin->company_id = $company->id;
        $admin->save();

        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'status' => $subStatus,
            'starts_on' => now()->subMonths(6),
            'ends_on' => $endsOn ?? ($subStatus === 'expired' ? now()->subDays(10) : now()->addMonths(6)),
            'included_branches' => 2,
            'users_per_branch' => 3,
            'lr_credits_remaining' => $lrCredits,
        ]);

        return [$admin, $company, $subscription];
    }

    public function test_expired_subscription_blocks_mutations_via_middleware(): void
    {
        [$admin, $company] = $this->createBrokerUser(subStatus: 'expired');

        // Mutating request should be redirected or blocked
        $response = $this->actingAs($admin)->post('/lorry_receipts', [
            'lr_number' => 'LR-FAIL-01',
        ]);

        // Returns subscription expired response (302 redirect to subscription page or 402/403)
        $this->assertContains($response->status(), [302, 402, 403]);
    }

    public function test_grace_period_blocks_report_exports(): void
    {
        // Grace period: ends_on within grace window (e.g. subDays(2))
        [$admin, $company] = $this->createBrokerUser(
            subStatus: 'active',
            lrCredits: 10,
            endsOn: now()->subDays(2)
        );

        // Export route should be blocked during grace read-only
        $response = $this->actingAs($admin)->get('/reports/consignor-consignee-export');
        $this->assertContains($response->status(), [302, 402, 403]);
    }

    public function test_lorry_receipt_creation_fails_when_credits_exhausted(): void
    {
        [$admin, $company, $subscription] = $this->createBrokerUser(lrCredits: 0);

        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Party 1', 'address' => 'Addr']);
        $master = TransporterMaster::factory()->create(['name' => 'Trans 1']);
        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '9999900000',
        ]);
        $truck = Truck::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'MH01AB9999',
            'truck_type' => 'Open',
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'lr_number' => 'LR-NO-CREDIT',
            'gr_number' => 'GR-NO-CREDIT',
            'source_city' => 'Mumbai',
            'source_state' => 'MH',
            'destination_city' => 'Pune',
            'destination_state' => 'MH',
            'lane' => 'Mumbai-Pune',
            'weight' => 10,
            'no_of_units' => 1,
            'good_type' => 'Goods',
            'fr_rate' => 100,
            'amount' => 1000,
            'actual_amount' => 1000,
            'gr_date' => now()->toDateString(),
            'company_id' => $company->id,
            'delivery_status' => 'Pending',
            'transporter_type' => 'agency',
        ];

        // Should return 422 or fail with insufficient credits
        $response = $this->actingAs($admin)->post('/lorry_receipts', $payload);
        $this->assertContains($response->status(), [422, 302]);
        $this->assertDatabaseMissing('lorry_receipts', ['lr_number' => 'LR-NO-CREDIT']);
    }

    public function test_lorry_receipt_validation_rejects_missing_mandatory_fields(): void
    {
        [$admin] = $this->createBrokerUser();

        $response = $this->actingAs($admin)->post('/lorry_receipts', []);
        $response->assertSessionHasErrors([
            'customer_id',
            'lr_number',
            'consignor_id',
            'consignee_id',
            'source_city',
            'destination_city',
            'weight',
            'fr_rate',
            'amount',
        ]);
    }

    public function test_truck_validation_rejects_invalid_registration_format(): void
    {
        [$admin] = $this->createBrokerUser();

        $response = $this->actingAs($admin)->post('/trucks', [
            'truck_number' => 'INVALID_FORMAT_TRUCK_NUMBER!!!',
            'truck_type' => 'Flatbed',
        ]);

        $response->assertSessionHasErrors(['truck_number']);
    }

    public function test_deleting_delivered_lorry_receipt_is_prevented(): void
    {
        [$admin, $company] = $this->createBrokerUser();

        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Cust A', 'address' => 'A']);
        $master = TransporterMaster::factory()->create(['name' => 'Tr A']);
        $transporter = Transporter::factory()->forCompany($company->id)->create(['transporter_master_id' => $master->id, 'mobile_number' => '9999911111']);
        $truck = Truck::create(['company_id' => $company->id, 'transporter_id' => $transporter->id, 'truck_number' => 'MH12AA5555', 'truck_type' => 'Open']);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-DELIVERED-01',
            'gr_number' => 'GR-DELIVERED-01',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'weight' => 10,
            'no_of_units' => 1,
            'good_type' => 'Cement',
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Delivered', // Already delivered
        ]);

        $response = $this->actingAs($admin)->delete("/lorry_receipts/{$lr->id}", [
            'delete_remark' => 'Attempt delete delivered LR',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        // LR must not be deleted
        $this->assertDatabaseHas('lorry_receipts', [
            'id' => $lr->id,
            'deleted_at' => null,
        ]);
    }
}
