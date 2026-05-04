<?php

namespace Tests\Feature\Customer;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
        UserRole::create(['id' => 6, 'name' => 'transporter']);
        UserRole::create(['id' => 7, 'name' => 'customer']);
    }

    public function test_customer_sees_all_linked_consignee_data_globally(): void
    {
        $companyA = $this->createCompany('A');
        $companyB = $this->createCompany('B');
        $customerRoleId = UserRole::query()->where('name', 'customer')->value('id');

        $user = User::factory()->create([
            'user_role_id' => $customerRoleId,
            'company_id' => null,
        ]);

        $consigneeA = Consignee::create(['name' => 'Customer A', 'company_id' => $companyA->id]);
        $consigneeB = Consignee::create(['name' => 'Customer B', 'company_id' => $companyB->id]);
        $user->consignees()->sync([$consigneeA->id, $consigneeB->id]);

        LorryReceipt::create($this->lrPayload($companyA->id, $consigneeA->id, 'LR-A-1'));
        LorryReceipt::create($this->lrPayload($companyB->id, $consigneeB->id, 'LR-B-1'));

        $this->actingAs($user)
            ->get(route('customer.lorry-receipts.index'))
            ->assertOk()
            ->assertSee('LR-A-1')
            ->assertSee('LR-B-1');
    }

    public function test_customer_can_open_portal_without_session_switching_context(): void
    {
        $companyA = $this->createCompany('A');
        $companyB = $this->createCompany('B');
        $customerRoleId = UserRole::query()->where('name', 'customer')->value('id');

        $user = User::factory()->create([
            'user_role_id' => $customerRoleId,
            'company_id' => null,
        ]);

        $consigneeA = Consignee::create(['name' => 'Allowed A', 'company_id' => $companyA->id]);
        $consigneeB = Consignee::create(['name' => 'Allowed B', 'company_id' => $companyB->id]);
        $user->consignees()->sync([$consigneeA->id, $consigneeB->id]);

        LorryReceipt::create($this->lrPayload($companyA->id, $consigneeA->id, 'LR-A-2'));
        LorryReceipt::create($this->lrPayload($companyB->id, $consigneeB->id, 'LR-B-2'));

        $this->actingAs($user)
            ->get(route('customer.lorry-receipts.index'))
            ->assertOk()
            ->assertSee('LR-A-2')
            ->assertSee('LR-B-2');
    }

    public function test_customer_pages_do_not_expose_freight_booking_terms(): void
    {
        $company = $this->createCompany('A');
        $customerRoleId = UserRole::query()->where('name', 'customer')->value('id');

        $user = User::factory()->create([
            'user_role_id' => $customerRoleId,
            'company_id' => null,
        ]);

        $consignee = Consignee::create(['name' => 'Customer A', 'company_id' => $company->id]);
        $user->consignees()->sync([$consignee->id]);

        Invoice::create([
            'manual_invoice_number' => 'INV-CUST-1',
            'company_id' => $company->id,
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $consignee->id,
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
            'memo_ids' => [],
            'lr_ids' => [],
        ]);

        $this->actingAs($user)
            ->withSession(['portal_consignee_id' => $consignee->id])
            ->get(route('customer.invoices.index'))
            ->assertOk()
            ->assertDontSee('Good Hires')
            ->assertDontSee('tr_amount')
            ->assertDontSee('voucher');
    }

    private function createCompany(string $suffix): Company
    {
        return Company::create([
            'name' => 'Company '.$suffix,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function lrPayload(int $companyId, int $consigneeId, string $lrNumber): array
    {
        return [
            'company_id' => $companyId,
            'customer_id' => $consigneeId,
            'consignor_id' => $consigneeId,
            'consignee_id' => $consigneeId,
            'lr_number' => $lrNumber,
            'delivery_status' => 'Pending',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ];
    }
}
