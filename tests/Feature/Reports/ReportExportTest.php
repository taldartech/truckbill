<?php

namespace Tests\Feature\Reports;

use App\Exports\ConsignorConsigneeExport;
use App\Exports\EwayBillExport;
use App\Exports\InvoiceExport;
use App\Exports\PendingInvoiceExport;
use App\Exports\PendingMemoExport;
use App\Exports\ShortageDamageExport;
use App\Exports\TransporterReportExport;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Carbon\Carbon;
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

    protected function seedLr(User $user): array
    {
        $customer = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Customer',
        ]);

        $transporter = \App\Models\Transporter::factory()
            ->forCompany($user->company_id)
            ->create([
                'short_name' => 'FL',
                'mobile_number' => '9999999999',
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

        $booking = FreightBooking::create([
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

        $lr->freight_booking_id = $booking->id;
        $lr->save();

        return [$lr, $booking];
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
        [$lr, $booking] = $this->seedLr($admin);
        $pump = Pump::create([
            'user_id' => $admin->company->user_id,
            'pump_name' => 'Pump Export',
            'contact_name' => 'Op',
            'contact_number' => '9000000000',
            'remark' => 'Test',
        ]);
        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-PUMP-EXP',
            'payment_type' => 'cash',
            'payment_mode' => 'bank transfer',
            'amount' => 300,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
        ]);
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
        $this->actingAs($admin)->get('/reports/pump-export?' . http_build_query($params + ['pump_id' => $pump->id]))->assertOk();
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

    public function test_pump_report_shows_payment_status(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);
        $pump = Pump::create([
            'user_id' => $admin->company->user_id,
            'pump_name' => 'HP Pump',
            'contact_name' => 'Operator',
            'contact_number' => '9999999999',
            'remark' => 'Test pump',
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-PUMP-1',
            'payment_type' => 'cash',
            'payment_mode' => 'bank transfer',
            'amount' => 500,
            'payment_status' => 'in-progress',
            'company_id' => $admin->company_id,
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
        ]);

        $params = [
            'start_date' => now()->subDays(1)->toDateString(),
            'end_date' => now()->toDateString(),
            'pump_id' => $pump->id,
        ];

        $this->actingAs($admin)->get('/reports/pump?' . http_build_query($params))
            ->assertOk()
            ->assertSee('HP Pump')
            ->assertSee('LR-RPT-1');
    }

    public function test_bank_report_shows_payment_status(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-BANK-1',
            'payment_type' => 'bank',
            'payment_mode' => 'bank transfer',
            'amount' => 700,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'date' => now()->toDateString(),
        ]);

        $params = [
            'start_date' => now()->subDays(1)->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $this->actingAs($admin)->get('/reports/bank?' . http_build_query($params))
            ->assertOk()
            ->assertSee('Pending');
    }

    public function test_report_exports_display_unloading_weight_with_three_decimals(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $lr->update(['unloading_weight' => 12.345]);

        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($admin)
            ->get('/reports/dispatch?' . http_build_query($params))
            ->assertOk()
            ->assertSee('12.345');

        $this->actingAs($admin)
            ->get('/reports/transporter-report?' . http_build_query($params))
            ->assertOk()
            ->assertSee('12.345');
    }

    public function test_invoice_report_displays_invoices_sorted_ascending_by_generated_date(): void
    {
        $admin = $this->makeUser('admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Invoice Customer',
            'state_id' => $state->id,
        ]);

        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-DATE-3',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-03',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-DATE-1',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-01',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-DATE-2',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-02',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);

        $params = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ];

        $response = $this->actingAs($admin)->get('/reports/invoice?' . http_build_query($params));

        $response->assertOk();
        $content = $response->getContent();
        $pos1 = strpos($content, 'INV-DATE-1');
        $pos2 = strpos($content, 'INV-DATE-2');
        $pos3 = strpos($content, 'INV-DATE-3');
        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertLessThan($pos2, $pos1, 'INV-DATE-1 should appear before INV-DATE-2');
        $this->assertLessThan($pos3, $pos2, 'INV-DATE-2 should appear before INV-DATE-3');
    }

    public function test_invoice_export_returns_invoices_sorted_ascending_by_generated_date(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Export Customer',
        ]);

        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-EXP-3',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-15',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-EXP-1',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-05',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-EXP-2',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-10',
            'total_amount' => 1000,
            'net_amount' => 1000,
        ]);

        $this->actingAs($admin);
        $export = new InvoiceExport('2025-01-01', '2025-01-31');
        $collection = $export->collection();

        $invoiceNumbers = $collection->pluck('Invoice No')->values()->all();
        $this->assertSame(['INV-EXP-1', 'INV-EXP-2', 'INV-EXP-3'], $invoiceNumbers);
    }

    public function test_invoice_export_displays_total_amount_for_reverse_and_net_amount_for_forward(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Amount Test Customer',
        ]);

        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-REVERSE',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-10',
            'total_amount' => 5000,
            'net_amount' => 4500,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-FORWARD',
            'invoice_type' => 'Forward',
            'generated_date' => '2025-01-11',
            'total_amount' => 3000,
            'net_amount' => 2700,
        ]);

        $this->actingAs($admin);
        $export = new InvoiceExport('2025-01-01', '2025-01-31');
        $collection = $export->collection();

        $reverseRow = $collection->firstWhere('Invoice No', 'INV-REVERSE');
        $forwardRow = $collection->firstWhere('Invoice No', 'INV-FORWARD');

        $this->assertNotNull($reverseRow);
        $this->assertNotNull($forwardRow);
        $this->assertSame(5000.0, $reverseRow['Amount'], 'Reverse invoice should display total_amount');
        $this->assertSame(5000.0, $reverseRow['Taxable Value'], 'Reverse invoice Taxable Value should match Amount');
        $this->assertSame(2700.0, $forwardRow['Amount'], 'Forward invoice should display net_amount');
        $this->assertSame(2700.0, $forwardRow['Taxable Value'], 'Forward invoice Taxable Value should match Amount');
    }

    public function test_invoice_report_displays_total_amount_for_reverse_and_net_amount_for_forward(): void
    {
        $admin = $this->makeUser('admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Report Amount Customer',
            'state_id' => $state->id,
        ]);

        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-RPT-REV',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-10',
            'total_amount' => 5000,
            'net_amount' => 4500,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-RPT-FWD',
            'invoice_type' => 'Forward',
            'generated_date' => '2025-01-11',
            'total_amount' => 3000,
            'net_amount' => 2700,
        ]);

        $params = ['start_date' => '2025-01-01', 'end_date' => '2025-01-31'];
        $response = $this->actingAs($admin)->get('/reports/invoice?' . http_build_query($params));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('5000', $content, 'Reverse invoice should display total_amount 5000');
        $this->assertStringContainsString('2700', $content, 'Forward invoice should display net_amount 2700');
    }

    public function test_export_weight_columns_use_string_format_for_consistency(): void
    {
        $start = Carbon::today()->subDays(7)->toDateString();
        $end = Carbon::today()->toDateString();
        $weightFormat = '0.000';

        $consignorConsignee = new ConsignorConsigneeExport($start, $end, null, null);
        $this->assertSame($weightFormat, $consignorConsignee->columnFormats()['F'] ?? null);

        $ewayBill = new EwayBillExport($start, $end);
        $this->assertSame($weightFormat, $ewayBill->columnFormats()['E'] ?? null);

        $pendingInvoice = new PendingInvoiceExport(null, $start, $end);
        $this->assertSame($weightFormat, $pendingInvoice->columnFormats()['I'] ?? null);

        $pendingMemo = new PendingMemoExport(null, $start, $end);
        $this->assertSame($weightFormat, $pendingMemo->columnFormats()['H'] ?? null);

        $shortageDamage = new ShortageDamageExport($start, $end);
        $formats = $shortageDamage->columnFormats();
        $this->assertSame($weightFormat, $formats['E'] ?? null);
        $this->assertSame($weightFormat, $formats['F'] ?? null);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        $transporterReport = new TransporterReportExport($start, $end, null, null, null);
        $formats = $transporterReport->columnFormats();
        $this->assertSame($weightFormat, $formats['F'] ?? null);
        $this->assertSame($weightFormat, $formats['G'] ?? null);
    }
}

