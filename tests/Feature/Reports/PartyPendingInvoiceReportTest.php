<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyPendingInvoiceReportTest extends TestCase
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
            'name' => 'Company '.$role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        $roleId = UserRole::where('name', $role)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    public function test_report_consolidates_pending_amounts_by_party(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Acme Customer',
        ]);
        $otherCustomer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Other Customer',
        ]);

        $pendingInvoice = Invoice::create([
            'invoice_number' => 'INV-1001',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 10000,
            'net_amount' => 10000,
            'payment_status' => 'partial',
            'company_id' => $admin->company_id,
            'memo_ids' => [],
            'lr_ids' => [],
        ]);

        InvoicePayment::create([
            'invoice_id' => $pendingInvoice->id,
            'mode' => 'cash',
            'amount' => 4000,
            'payment_date' => now()->toDateString(),
        ]);

        Invoice::create([
            'invoice_number' => 'INV-1002',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 5000,
            'net_amount' => 5000,
            'payment_status' => 'paid',
            'company_id' => $admin->company_id,
            'memo_ids' => [],
            'lr_ids' => [],
        ]);

        Invoice::create([
            'invoice_number' => 'INV-1003',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $otherCustomer->id,
            'total_amount' => 3000,
            'net_amount' => 3000,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'memo_ids' => [],
            'lr_ids' => [],
        ]);

        $this->actingAs($admin)
            ->get('/reports/party-pending-invoice')
            ->assertOk()
            ->assertSee('Acme Customer')
            ->assertSee('Other Customer')
            ->assertSee('9,000.00');
    }

    public function test_report_export_uses_view_export(): void
    {
        $accountant = $this->makeUser('accountant');
        $customer = Consignee::create([
            'company_id' => $accountant->company_id,
            'name' => 'Export Customer',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-EXP-1',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 2500,
            'net_amount' => 2500,
            'payment_status' => 'pending',
            'company_id' => $accountant->company_id,
            'memo_ids' => [],
            'lr_ids' => [],
        ]);

        $this->actingAs($accountant)
            ->get('/reports/party-pending-invoice-export')
            ->assertOk();
    }
}
