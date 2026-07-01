<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyLedgerReportTest extends TestCase
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

    public function test_report_shows_invoice_details_for_invoiced_lrs(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Ledger Customer',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-LEDGER-1',
            'memo_id' => 7001,
            'invoice_generated' => 1,
            'delivery_status' => 'Delivered',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'gr_date' => now()->toDateString(),
        ]);

        Invoice::create([
            'invoice_number' => 'INV-LEDGER-1',
            'manual_invoice_number' => 'REF-1',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'partial',
            'company_id' => $admin->company_id,
            'memo_ids' => [$lr->memo_id],
            'lr_ids' => [$lr->id],
        ]);

        $params = [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'customer_id' => $customer->id,
        ];

        $this->actingAs($admin)
            ->get('/reports/party-ledger?'.http_build_query($params))
            ->assertOk()
            ->assertSee('INV-LEDGER-1')
            ->assertSee('REF-1')
            ->assertSee('Partial');

        $this->actingAs($admin)
            ->get('/reports/party-ledger-export?'.http_build_query($params))
            ->assertOk();
    }
}
