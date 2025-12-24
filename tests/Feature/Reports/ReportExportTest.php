<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
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
            'name' => 'Company ' . $role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $roleId = UserRole::where('name', $role)->value('id');

        return User::factory()->create([
            'user_role_id' => $roleId,
            'company_id' => $company->id,
        ]);
    }

    protected function seedLr(User $user): void
    {
        $customer = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Customer',
        ]);

        $transporter = \App\Models\Transporter::create([
            'company_id' => $user->company_id,
            'name' => 'Fast Logistics',
            'short_name' => 'FL',
            'pan_number' => 'ABCDE1234F',
            'mobile_number' => '9999999999',
            'is_owner' => 0,
            'pan_active' => true,
            'declaration_given' => false,
            'tds_percentage' => 20,
            'total_trucks' => 0,
            'advance_amount' => 0,
        ]);

        $truck = \App\Models\Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB9999',
            'truck_type' => 'Flatbed',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-RPT-1',
            'memo_id' => 5001,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1.234,
            'fr_rate' => 100,
            'amount' => 100,
            'gr_date' => now()->toDateString(),
        ]);

        FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 100,
            'advance_amount' => 0,
            'balance_amount' => 100,
            'tds_amount' => 0,
            'company_id' => $user->company_id,
            'voucher_number' => 9001,
        ]);
    }

    public function test_admin_and_accountant_can_view_reports(): void
    {
        $admin = $this->makeUser('admin');
        $accountant = $this->makeUser('accountant');
        $this->seedLr($admin);

        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($admin)->get('/reports/consignor-consignee?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/tds?' . http_build_query($params))->assertOk();
        $this->actingAs($accountant)->get('/reports/dispatch?' . http_build_query($params))->assertOk();
        $this->actingAs($accountant)->get('/reports/invoice?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-master')->assertOk();
        $this->actingAs($accountant)->get('/reports/transporter-master?type=owner')->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-report?' . http_build_query($params))->assertOk();
    }

    public function test_moderator_forbidden_from_reports(): void
    {
        $mod = $this->makeUser('moderator');
        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($mod)->get('/reports/consignor-consignee?' . http_build_query($params))->assertForbidden();
        $this->actingAs($mod)->get('/reports/tds?' . http_build_query($params))->assertForbidden();
        $this->actingAs($mod)->get('/reports/transporter-master')->assertForbidden();
        $this->actingAs($mod)->get('/reports/transporter-report?' . http_build_query($params))->assertForbidden();
    }

    public function test_exports_succeed_for_admin(): void
    {
        $admin = $this->makeUser('admin');
        $this->seedLr($admin);
        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($admin)->get('/reports/consignor-consignee-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/tds-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/dispatch-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/invoice-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/shortage-damage-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/eway-bill-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/pending-memo-export?' . http_build_query($params + ['status' => 'both']))->assertOk();
        $this->actingAs($admin)->get('/reports/pending-invoice-export')->assertOk();
        $this->actingAs($admin)->get('/reports/bank-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-report-export?' . http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-master-export?type=transporter')->assertOk();
    }

    public function test_exports_forbidden_for_moderator(): void
    {
        $mod = $this->makeUser('moderator');
        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($mod)->get('/reports/consignor-consignee-export?' . http_build_query($params))->assertForbidden();
        $this->actingAs($mod)->get('/reports/tds-export?' . http_build_query($params))->assertForbidden();
        $this->actingAs($mod)->get('/reports/transporter-report-export?' . http_build_query($params))->assertForbidden();
        $this->actingAs($mod)->get('/reports/transporter-master-export')->assertForbidden();
    }
}

