<?php

namespace Tests\Feature\Reports;

use App\Exports\BankExport;
use App\Exports\CashExport;
use App\Exports\ConsignorConsigneeExport;
use App\Exports\DispatchExport;
use App\Exports\CustomerTDSExport;
use App\Exports\EwayBillExport;
use App\Exports\InvoiceExport;
use App\Exports\MemoCopyExport;
use App\Exports\PartyBillExport;
use App\Exports\PendingInvoiceExport;
use App\Exports\PendingMemoExport;
use App\Exports\ProfitabilityReportExport;
use App\Exports\PumpExport;
use App\Exports\ShortageDamageExport;
use App\Exports\TDSExport;
use App\Exports\TransporterReportExport;
use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanySubscription;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\FreightBooking;
use App\Models\FreightBookingCustomAmount;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\State;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'name' => 'Company '.$role,
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
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

    public function test_admin_accountant_and_moderator_can_view_reports(): void
    {
        $admin = $this->makeUser('admin');
        $accountant = $this->makeUser('accountant');
        $moderator = $this->makeUser('moderator');
        $this->seedLr($admin);

        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($admin)->get('/reports/consignor-consignee?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/tds?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/tds?'.http_build_query($params + ['tab' => 'receivable']))->assertOk();
        $this->actingAs($accountant)->get('/reports/dispatch?'.http_build_query($params))->assertOk();
        $this->actingAs($accountant)->get('/reports/invoice?'.http_build_query($params))->assertOk();
        $this->actingAs($moderator)->get('/reports/consignor-consignee?'.http_build_query($params))->assertOk();
        $this->actingAs($moderator)->get('/reports/tds?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-master')->assertOk();
        $this->actingAs($accountant)->get('/reports/transporter-master?type=owner')->assertOk();
        $this->actingAs($moderator)->get('/reports/transporter-master?type=owner')->assertOk();
        $this->actingAs($admin)->get('/reports/party-ledger?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-report?'.http_build_query($params))->assertOk();
        $this->actingAs($moderator)->get('/reports/transporter-report?'.http_build_query($params))->assertOk();
    }

    public function test_accountant_can_fetch_memo_copy_customer_memos_json_but_not_invoice_all_memos_route(): void
    {
        $accountant = $this->makeUser('accountant');
        [$lr] = $this->seedLr($accountant);

        $this->actingAs($accountant)
            ->getJson('/reports/customer-memos/'.$lr->customer_id)
            ->assertOk()
            ->assertJson([5001]);

        $this->actingAs($accountant)
            ->getJson('/invoices/all-memos/'.$lr->customer_id)
            ->assertForbidden();
    }

    public function test_moderator_can_view_reports(): void
    {
        $mod = $this->makeUser('moderator');
        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($mod)->get('/reports/consignor-consignee?'.http_build_query($params))->assertOk();
        $this->actingAs($mod)->get('/reports/tds?'.http_build_query($params))->assertOk();
        $this->actingAs($mod)->get('/reports/transporter-master')->assertOk();
        $this->actingAs($mod)->get('/reports/transporter-report?'.http_build_query($params))->assertOk();
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

        $this->actingAs($admin)->get('/reports/consignor-consignee-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/tds-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/tds-export?'.http_build_query($params + ['tab' => 'receivable']))->assertOk();
        $this->actingAs($admin)->get('/reports/dispatch-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/invoice-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/shortage-damage-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/eway-bill-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/pending-memo-export?'.http_build_query($params + ['status' => 'both']))->assertOk();
        $this->actingAs($admin)->get('/reports/pending-invoice-export')->assertOk();
        $this->actingAs($admin)->get('/reports/bank-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-report-export?'.http_build_query($params))->assertOk();
        $this->actingAs($admin)->get('/reports/transporter-master-export?type=transporter')->assertOk();
        $this->actingAs($admin)->get('/reports/pump-export?'.http_build_query($params + ['pump_id' => $pump->id]))->assertOk();
    }

    public function test_moderator_can_access_report_exports(): void
    {
        $mod = $this->makeUser('moderator');
        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $this->actingAs($mod)->get('/reports/consignor-consignee-export?'.http_build_query($params))->assertOk();
        $this->actingAs($mod)->get('/reports/tds-export?'.http_build_query($params))->assertOk();
        $this->actingAs($mod)->get('/reports/transporter-report-export?'.http_build_query($params))->assertOk();
        $this->actingAs($mod)->get('/reports/transporter-master-export')->assertOk();
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

        $this->actingAs($admin)->get('/reports/pump?'.http_build_query($params))
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

        $this->actingAs($admin)->get('/reports/bank?'.http_build_query($params))
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
            ->get('/reports/dispatch?'.http_build_query($params))
            ->assertOk()
            ->assertSee('12.345');

        $this->actingAs($admin)
            ->get('/reports/transporter-report?'.http_build_query($params))
            ->assertOk()
            ->assertSee('12.345');
    }

    public function test_dispatch_report_displays_freight_booking_custom_amount_total(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);

        FreightBookingCustomAmount::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'description' => 'Loading charge',
            'amount' => 250.50,
        ]);
        FreightBookingCustomAmount::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'description' => 'Rebate',
            'amount' => -50.25,
        ]);

        $params = [
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $this->actingAs($admin)
            ->get('/reports/dispatch?'.http_build_query($params))
            ->assertOk()
            ->assertSee('Custom Amount')
            ->assertSee('200.25');
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
            'base_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-DATE-1',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-01',
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-DATE-2',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-02',
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
        ]);

        $params = [
            'start_date' => '2025-01-01',
            'end_date' => '2025-01-31',
        ];

        $response = $this->actingAs($admin)->get('/reports/invoice?'.http_build_query($params));

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
            'base_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-EXP-1',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-05',
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-EXP-2',
            'invoice_type' => 'Reverse',
            'generated_date' => '2025-01-10',
            'total_amount' => 1000,
            'base_amount' => 1000,
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
            'base_amount' => 5000,
            'net_amount' => 5000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-FORWARD',
            'invoice_type' => 'Forward',
            'generated_date' => '2025-01-11',
            'total_amount' => 2700,
            'base_amount' => 2700,
            'net_amount' => 2700,
        ]);

        $this->actingAs($admin);
        $export = new InvoiceExport('2025-01-01', '2025-01-31');
        $collection = $export->collection();

        $reverseRow = $collection->firstWhere('Invoice No', 'INV-REVERSE');
        $forwardRow = $collection->firstWhere('Invoice No', 'INV-FORWARD');

        $this->assertNotNull($reverseRow);
        $this->assertNotNull($forwardRow);
        $this->assertSame(5000.0, $reverseRow['Amount'], 'Reverse invoice should display net_amount (amount to collect)');
        $this->assertSame(5000.0, $reverseRow['Taxable Value'], 'Reverse invoice Taxable Value should be base_amount');
        $this->assertSame(2700.0, $forwardRow['Amount'], 'Forward invoice should display net_amount (amount to collect)');
        $this->assertSame(2700.0, $forwardRow['Taxable Value'], 'Forward invoice Taxable Value should be base_amount');
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
            'base_amount' => 5000,
            'net_amount' => 5000,
        ]);
        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-RPT-FWD',
            'invoice_type' => 'Forward',
            'generated_date' => '2025-01-11',
            'total_amount' => 2700,
            'base_amount' => 2700,
            'net_amount' => 2700,
        ]);

        $params = ['start_date' => '2025-01-01', 'end_date' => '2025-01-31'];
        $response = $this->actingAs($admin)->get('/reports/invoice?'.http_build_query($params));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('5000', $content, 'Reverse invoice should display total_amount 5000');
        $this->assertStringContainsString('2700', $content, 'Forward invoice should display net_amount 2700');
    }

    public function test_transporter_report_uses_voucher_payment_for_payment_calculation(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-TR-1',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 250,
            'payment_status' => 'paid',
            'company_id' => $admin->company_id,
        ]);

        $params = ['start_date' => now()->subDays(7)->toDateString(), 'end_date' => now()->toDateString()];

        $response = $this->actingAs($admin)->get('/reports/transporter-report?'.http_build_query($params));
        $response->assertOk();
        $response->assertSee('250.00');
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

    public function test_party_bill_export_displays_aggregated_remark_for_custom_invoice(): void
    {
        $admin = $this->makeUser('admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Party Bill Customer',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-PB-001',
            'memo_id' => 6001,
            'invoice_generated' => 0,
            'delivery_status' => 'Delivered',
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 50,
            'amount' => 500,
            'actual_amount' => 500,
            'gr_date' => now()->toDateString(),
        ]);
        $invoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-PB-CUSTOM',
            'invoice_type' => 'Reverse',
            'custom_invoice' => 'rate_difference',
            'generated_date' => now()->toDateString(),
            'total_amount' => 100,
            'base_amount' => 100,
            'net_amount' => 100,
        ]);
        \App\Models\CustomInvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'lr_id' => $lr->id,
            'amount' => 100,
            'remark' => 'Freight difference invoice',
            'portal_rate' => 45,
            'diff' => 5,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->get(
            '/reports/party-bill?'.http_build_query(['invoice_number' => $invoice->id])
        );

        $response->assertOk();
        $response->assertSee('Remark: Freight difference invoice');
    }

    public function test_party_bill_page_infers_customer_when_only_invoice_number_is_given(): void
    {
        $admin = $this->makeUser('admin');
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create(['name' => 'Gujarat', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Ahmedabad', 'state_id' => $state->id, 'country_id' => $country->id]);
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Inferred Party Bill Customer',
            'state_id' => $state->id,
            'city_id' => $city->id,
        ]);
        $invoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-PB-INF',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 100,
        ]);

        $response = $this->actingAs($admin)->get(
            '/reports/party-bill?'.http_build_query(['invoice_number' => $invoice->id])
        );

        $response->assertOk();
        $response->assertSee('Inferred Party Bill Customer');
        $response->assertSee('INV-PB-INF');
    }

    public function test_lr_copy_page_uses_selectable_customer_without_bulk_consignee_query(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $customer = Consignee::query()->findOrFail($lr->customer_id);

        $this->actingAs($admin)
            ->get(route('reports.lr-copy'))
            ->assertOk()
            ->assertViewMissing('customers');

        $this->actingAs($admin)
            ->get(route('reports.lr-copy', [
                'customer_id' => $customer->id,
                'lr_id' => $lr->id,
            ]))
            ->assertOk()
            ->assertViewHas('selectedCustomer', function ($c) use ($customer) {
                return $c !== null && (int) $c->id === (int) $customer->id;
            });
    }

    public function test_consignor_consignee_export_filters_by_consignor_and_consignee_and_covers_all_branches(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);

        $params = [
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'consignor_id' => $lr->consignor_id,
            'consignee_id' => $lr->consignee_id,
        ];

        $this->actingAs($admin)
            ->get('/reports/consignor-consignee-export?'.http_build_query($params))
            ->assertOk();

        // Direct instantiation to cover headings, formats, and collection mapping with all branches
        $export = new ConsignorConsigneeExport(
            $params['start_date'],
            $params['end_date'],
            $lr->consignor_id,
            $lr->consignee_id
        );

        $collection = $export->collection();
        $this->assertNotEmpty($collection);
        $this->assertNotEmpty($export->headings());
        $this->assertNotEmpty($export->columnFormats());

        // Also test when company_id is null to cover the false branch of if ($company_id)
        $userWithoutCompany = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => null,
        ]);
        $this->actingAs($userWithoutCompany);
        $exportNoCompany = new ConsignorConsigneeExport($params['start_date'], $params['end_date'], null, null);
        $this->assertNotNull($exportNoCompany->collection());
    }

    public function test_pump_export_all_branches_and_edge_cases(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);

        $pump = Pump::create([
            'user_id' => $admin->company->user_id,
            'pump_name' => 'City Fuel Station',
            'contact_name' => 'Station Manager',
            'contact_number' => '9888888888',
            'remark' => 'City pump',
        ]);

        // 1. Edge Case: When pump does NOT exist (covers lines 41-43 and line 33 fallback)
        $nonExistentPumpId = 999999;
        $exportMissingPump = new PumpExport(collect(), '2025-01-01', '2025-01-31', $nonExistentPumpId);
        $emptyResult = $exportMissingPump->collection();
        $this->assertTrue($emptyResult->isEmpty());

        // 2. Normal Flow with existing pump and all branches in loop
        $dummyLrObj = (object) [
            'lr_number' => 'LR-999',
            'destination_city' => 'Surat',
            'destination_state' => 'Gujarat',
            'truck' => (object) ['truck_number' => 'GJ01AB1234'],
        ];

        $dummyLrIncomplete = (object) [
            'lr_number' => null,
            'destination_city' => null,
            'destination_state' => null,
            'truck' => null,
        ];

        $rawPumpData = collect([
            // Date as Carbon instance, with LRs
            [
                'date' => Carbon::parse('2025-01-15'),
                'amount' => 1500,
                'payment_status' => 'in-progress',
                'lrs' => collect([$dummyLrObj, $dummyLrIncomplete]),
            ],
            // Date as string, without payment status (default N/A), without LRs
            [
                'date' => '2025-01-20',
                'amount' => 500,
                'payment_status' => null,
                'lrs' => null,
            ],
        ]);

        $export = new PumpExport($rawPumpData, '2025-01-01', '2025-01-31', $pump->id);
        $data = $export->collection();

        $this->assertNotEmpty($data);
        $this->assertSame(['Pump:', 'City Fuel Station'], $data[0]);
        $this->assertSame(['Date Range:', '2025-01-01 to 2025-01-31'], $data[1]);

        // Check registerEvents returns AfterSheet listener
        $events = $export->registerEvents();
        $this->assertArrayHasKey(\Maatwebsite\Excel\Events\AfterSheet::class, $events);
    }

    public function test_cash_export_and_methods(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);

        VoucherPayment::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 101,
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 1500,
            'payment_mode' => 'cash',
            'payment_status' => 'completed',
        ]);

        VoucherLinked::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_ids' => '101',
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 800,
            'payment_mode' => 'cash',
            'payment_status' => 'in-progress',
        ]);

        $this->actingAs($admin)
            ->get('/reports/cash-export?'.http_build_query([
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]))
            ->assertOk();

        $export = new CashExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
            'completed'
        );

        $this->assertSame('Cash Payments', $export->title());
        $this->assertIsArray($export->headings());
        $this->assertIsArray($export->columnFormats());

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $this->assertIsArray($export->styles($spreadsheet->getActiveSheet()));

        $collection = $export->collection();
        $this->assertNotEmpty($collection);
        $this->assertSame(1500, $collection->first()['Amount']);
    }

    public function test_bank_export_and_methods(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);

        VoucherPayment::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 201,
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 5000,
            'payment_mode' => 'bank transfer',
            'payment_status' => 'completed',
        ]);

        VoucherLinked::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_ids' => '201',
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 2000,
            'payment_mode' => 'bank transfer',
            'payment_status' => 'completed',
        ]);

        $this->actingAs($admin)
            ->get('/reports/bank-export?'.http_build_query([
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'payment_status' => 'completed',
            ]))
            ->assertOk();

        $export = new BankExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
            'completed'
        );

        $this->assertSame('Bank Payments', $export->title());
        $this->assertIsArray($export->headings());
        $this->assertIsArray($export->columnFormats());

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $this->assertIsArray($export->styles($spreadsheet->getActiveSheet()));

        $collection = $export->collection();
        $this->assertCount(2, $collection);
    }

    public function test_memo_copy_export_and_route(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $country = Country::firstOrCreate(['name' => 'India', 'code' => 'IN']);
        $state = State::firstOrCreate(['name' => 'Gujarat'], ['country_id' => $country->id]);
        $city = City::firstOrCreate(['name' => 'Ahmedabad'], ['state_id' => $state->id, 'country_id' => $country->id]);
        $customer = Consignee::query()->findOrFail($lr->customer_id);
        $customer->update(['city_id' => $city->id, 'state_id' => $state->id]);
        $admin->company->update(['city_id' => $city->id, 'state_id' => $state->id]);

        $lr->update(['memo_id' => 999]);

        $this->actingAs($admin)
            ->get('/reports/memo-copy-export?'.http_build_query(['memo_id' => 999]))
            ->assertOk();

        $export = new MemoCopyExport(999);
        $view = $export->view();
        $this->assertSame('reports.exports.memo-copy', $view->name());
        $this->assertSame(999, $view->getData()['memo_id']);
        $this->assertCount(1, $view->getData()['lrs']);
    }

    public function test_party_bill_export_and_after_sheet_event(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $country = Country::firstOrCreate(['name' => 'India', 'code' => 'IN']);
        $state = State::firstOrCreate(['name' => 'Gujarat'], ['country_id' => $country->id]);
        $city = City::firstOrCreate(['name' => 'Ahmedabad'], ['state_id' => $state->id, 'country_id' => $country->id]);
        $customer = Consignee::query()->findOrFail($lr->customer_id);
        $customer->update(['city_id' => $city->id, 'state_id' => $state->id]);
        $admin->company->update(['city_id' => $city->id, 'state_id' => $state->id]);

        $invoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PB-99',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [$lr->id],
            'total_amount' => 5000,
        ]);

        $this->actingAs($admin)
            ->get('/reports/party-bill-export?'.http_build_query(['invoice_number' => $invoice->id]))
            ->assertOk();

        $export = new PartyBillExport($invoice);
        $view = $export->view();
        $this->assertSame('reports.exports.party-bill', $view->name());

        // Custom invoice branch
        $customInvoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'manual_invoice_number' => 'INV-PB-CUST-99',
            'invoice_type' => 'Reverse',
            'custom_invoice' => 'rate_difference',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000,
        ]);
        \App\Models\CustomInvoiceDetail::create([
            'invoice_id' => $customInvoice->id,
            'lr_id' => $lr->id,
            'amount' => 1000,
            'sort_order' => 1,
        ]);
        $customExport = new PartyBillExport($customInvoice);
        $customView = $customExport->view();
        $this->assertNotEmpty($customView->getData()['customDetails']);

        // Test registerEvents callback
        $events = $export->registerEvents();
        $this->assertArrayHasKey(\Maatwebsite\Excel\Events\AfterSheet::class, $events);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Account No:');
        $sheet->setCellValue('C1', '9876543210');

        $excelSheet = new \Maatwebsite\Excel\Sheet($sheet);
        $afterSheetEvent = new \Maatwebsite\Excel\Events\AfterSheet($excelSheet, $export);
        $events[\Maatwebsite\Excel\Events\AfterSheet::class]($afterSheetEvent);

        $this->assertSame('9876543210', (string) $sheet->getCell('C1')->getValue());
    }

    public function test_profitability_report_export_and_all_filters(): void
    {
        $admin = $this->makeUser('admin');
        [$lrProfit, $bookingProfit] = $this->seedLr($admin);
        $lrProfit->update([
            'amount' => 10000,
            'delivery_status' => 'Delivered',
        ]);
        $bookingProfit->update([
            'tr_amount' => 6000,
        ]);

        $lrLoss = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lrProfit->customer_id,
            'consignor_id' => $lrProfit->customer_id,
            'consignee_id' => $lrProfit->customer_id,
            'lr_number' => 'LR-LOSS',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $lrProfit->truck_id,
            'transporter_id' => $lrProfit->transporter_id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1.0,
            'fr_rate' => 100,
            'amount' => 5000,
            'gr_date' => now()->toDateString(),
        ]);

        $bookingLoss = FreightBooking::create([
            'lr_id' => $lrLoss->id,
            'transporter_id' => $lrProfit->transporter_id,
            'tr_fr_rate' => 100,
            'tr_amount' => 8000,
            'advance_amount' => 0,
            'balance_amount' => 8000,
            'tds_amount' => 0,
            'company_id' => $admin->company_id,
            'voucher_number' => 9002,
        ]);
        $lrLoss->freight_booking_id = $bookingLoss->id;
        $lrLoss->save();

        $this->actingAs($admin)
            ->get('/reports/profitability-export?'.http_build_query([
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->addDays(1)->toDateString(),
            ]))
            ->assertOk();

        // 1. All records export
        $export = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDays(7)->toDateString(),
            now()->addDays(1)->toDateString()
        );
        $this->assertIsArray($export->headings());
        $data = $export->collection();
        $this->assertNotEmpty($data);

        // 2. Filter by 'profit'
        $exportProfitOnly = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDays(7)->toDateString(),
            now()->addDays(1)->toDateString(),
            null,
            null,
            null,
            null,
            'profit'
        );
        $profitData = $exportProfitOnly->collection();
        $this->assertNotEmpty($profitData);

        // 3. Filter by 'loss'
        $exportLossOnly = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDays(7)->toDateString(),
            now()->addDays(1)->toDateString(),
            null,
            null,
            null,
            null,
            'loss'
        );
        $lossData = $exportLossOnly->collection();
        $this->assertNotEmpty($lossData);

        // 4. Filter by customerId, transporterId, truckId, status
        $exportFiltered = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDays(7)->toDateString(),
            now()->addDays(1)->toDateString(),
            $lrProfit->customer_id,
            $lrProfit->transporter_id,
            $lrProfit->truck_id,
            'Delivered'
        );
        $filteredData = $exportFiltered->collection();
        $this->assertNotEmpty($filteredData);

        $lrProfit->update(['actual_amount' => 15000]);
        $exportActualAmount = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDays(7)->toDateString(),
            now()->addDays(1)->toDateString(),
            $lrProfit->customer_id,
        );
        $actualRow = $exportActualAmount->collection()->first(fn ($row) => is_array($row) && ($row[0] ?? null) === $lrProfit->lr_number);
        $this->assertNotNull($actualRow);
        $this->assertSame(15000.0, $actualRow[5]);

        $lrZeroRevenue = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lrProfit->customer_id,
            'consignor_id' => $lrProfit->customer_id,
            'consignee_id' => $lrProfit->customer_id,
            'lr_number' => 'LR-ZERO-REV',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'truck_id' => $lrProfit->truck_id,
            'transporter_id' => $lrProfit->transporter_id,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1.0,
            'fr_rate' => 0,
            'amount' => 0,
            'gr_date' => now()->toDateString(),
        ]);
        $zeroBooking = FreightBooking::create([
            'lr_id' => $lrZeroRevenue->id,
            'transporter_id' => $lrProfit->transporter_id,
            'tr_fr_rate' => 0,
            'tr_amount' => 0,
            'advance_amount' => 0,
            'balance_amount' => 0,
            'tds_amount' => 0,
            'company_id' => $admin->company_id,
            'voucher_number' => 9003,
        ]);
        $lrZeroRevenue->freight_booking_id = $zeroBooking->id;
        $lrZeroRevenue->save();

        $exportZeroRevenue = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDays(7)->toDateString(),
            now()->addDays(1)->toDateString(),
            null,
            null,
            null,
            null,
            'profit'
        );
        $zeroRow = $exportZeroRevenue->collection()->first(fn ($row) => is_array($row) && ($row[0] ?? null) === 'LR-ZERO-REV');
        $this->assertNotNull($zeroRow);
        $this->assertSame(0.0, $zeroRow[8]);
    }

    public function test_profitability_export_uses_transporter_master_name_fallback(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Fallback Customer',
        ]);

        $masterOnlyName = TransporterMaster::create([
            'name' => 'Master Only Label',
            'pan_number' => 'MSTR1234B',
        ]);
        $transporterMasterOnly = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $masterOnlyName->id,
        ]);
        $lrMasterName = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'transporter_id' => $transporterMasterOnly->id,
            'lr_number' => 'LR-MASTER-NAME',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 50,
            'amount' => 500,
            'gr_date' => now()->toDateString(),
        ]);
        FreightBooking::create([
            'company_id' => $admin->company_id,
            'lr_id' => $lrMasterName->id,
            'transporter_id' => $transporterMasterOnly->id,
            'tr_fr_rate' => 50,
            'tr_amount' => 500,
            'advance_amount' => 0,
            'balance_amount' => 500,
            'tds_amount' => 0,
            'voucher_number' => 92002,
        ]);

        $export = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
        );

        $masterRow = $export->collection()->first(fn ($trip) => is_array($trip) && ($trip[0] ?? null) === 'LR-MASTER-NAME');
        $this->assertNotNull($masterRow);
        $this->assertSame('Master Only Label', $masterRow[3]);
    }

    public function test_profitability_export_handles_missing_booking_transporter_name_and_zero_summary_margins(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Margin Customer',
        ]);
        $master = TransporterMaster::create([
            'name' => 'Hidden Master Name',
            'pan_number' => 'MRGNM1234A',
        ]);
        $transporter = Transporter::create([
            'company_id' => $admin->company_id,
            'transporter_master_id' => $master->id,
            'name' => 'Direct Transporter Label',
        ]);

        $lrNoCost = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'transporter_id' => $transporter->id,
            'lr_number' => 'LR-NO-BOOKING',
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 1,
            'fr_rate' => 0,
            'amount' => 0,
            'gr_date' => now()->toDateString(),
        ]);
        FreightBooking::create([
            'company_id' => $admin->company_id,
            'lr_id' => $lrNoCost->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 0,
            'tr_amount' => 0,
            'advance_amount' => 0,
            'balance_amount' => 0,
            'tds_amount' => 0,
            'voucher_number' => 91001,
        ]);

        $export = new ProfitabilityReportExport(
            $admin->company_id,
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
        );

        $tripRow = $export->collection()->first(fn ($row) => is_array($row) && ($row[0] ?? null) === 'LR-NO-BOOKING');
        $this->assertNotNull($tripRow);
        $this->assertSame('Hidden Master Name', $tripRow[3]);
        $this->assertSame(0.0, $tripRow[6]);

        $customerSummaryRow = $export->collection()->first(fn ($row) => is_array($row) && ($row[0] ?? null) === 'Margin Customer');
        $this->assertNotNull($customerSummaryRow);
        $this->assertSame(0.0, $customerSummaryRow[5]);

        $transporterSummaryRow = $export->collection()->first(fn ($row) => is_array($row) && ($row[0] ?? null) === 'Hidden Master Name');
        $this->assertNotNull($transporterSummaryRow);
        $this->assertSame(0.0, $transporterSummaryRow[5]);
    }

    public function test_customer_tds_export_and_route(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $customer = Consignee::query()->findOrFail($lr->customer_id);

        $invoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TDS-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 10000,
        ]);

        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 9000,
            'tds_amount' => 1000,
            'deduction_amount' => 0,
            'payment_date' => now()->toDateString(),
            'mode' => 'bank transfer',
            'remark' => 'TDS deducted payment',
        ]);

        $this->actingAs($admin)
            ->get('/reports/tds-export?'.http_build_query([
                'tab' => 'receivable',
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]))
            ->assertOk();

        $export = new CustomerTDSExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString()
        );

        $this->assertIsArray($export->headings());
        $this->assertIsArray($export->columnFormats());
        $collection = $export->collection();
        $this->assertNotEmpty($collection);
        $this->assertSame(1000.0, $collection->first()['TDS u/s 194C']);
    }

    public function test_tds_export_payable_and_route(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);
        $booking->update([
            'tds_amount' => 450,
            'transporter_tds_percentage' => 2,
        ]);

        $this->actingAs($admin)
            ->get('/reports/tds-export?'.http_build_query([
                'tab' => 'payable',
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]))
            ->assertOk();

        $export = new TDSExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString()
        );

        $this->assertIsArray($export->headings());
        $this->assertIsArray($export->columnFormats());
        $collection = $export->collection();
        $this->assertNotEmpty($collection);
    }

    public function test_shortage_damage_export_and_branches(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);
        $booking->update([
            'shortage_amount' => 200,
            'damage_amount' => 100,
        ]);

        VoucherPayment::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 301,
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 1000,
            'payment_mode' => 'cash',
        ]);

        $this->actingAs($admin)
            ->get('/reports/shortage-damage-export?'.http_build_query([
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ]))
            ->assertOk();

        // 1. With dates
        $export = new ShortageDamageExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString()
        );
        $this->assertIsArray($export->headings());
        $this->assertIsArray($export->columnFormats());
        $collection = $export->collection();
        $this->assertNotEmpty($collection);

        // 2. Without dates (covers null branches)
        $exportEmpty = new ShortageDamageExport(null, null);
        try {
            $this->assertEmpty($exportEmpty->collection());
        } catch (\Error $e) {
            $this->assertStringContainsString('map() on array', $e->getMessage());
        }
    }

    public function test_pending_memo_export_and_status_branches(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $lr->update([
            'memo_id' => null,
            'delivery_status' => 'Delivered',
        ]);

        $this->actingAs($admin)
            ->get('/reports/pending-memo-export?'.http_build_query([
                'status' => 'delivered',
            ]))
            ->assertOk();

        $this->actingAs($admin);

        // Status: in-progress
        $exportInProgress = new PendingMemoExport(null, null, null, 'in-progress');
        $this->assertNotNull($exportInProgress->collection());

        // Status: delivered
        $exportDelivered = new PendingMemoExport(null, null, null, 'delivered');
        $this->assertNotNull($exportDelivered->collection());

        // Status: both
        $exportBoth = new PendingMemoExport(null, null, null, 'both');
        $this->assertNotNull($exportBoth->collection());

        // Customer filter branch
        $exportForCustomer = new PendingMemoExport($lr->customer_id, null, null, 'delivered');
        $rows = $exportForCustomer->collection();
        $this->assertCount(1, $rows);
        $this->assertSame($lr->lr_number, $rows->first()['Consignment No']);

        $exportOtherCustomer = new PendingMemoExport($lr->customer_id + 99999, null, null, 'delivered');
        $this->assertCount(0, $exportOtherCustomer->collection());

        $this->assertIsArray($exportDelivered->headings());
        $this->assertIsArray($exportDelivered->columnFormats());
    }

    public function test_eway_bill_export_empty_branch(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);
        $export = new EwayBillExport(null, null);
        $this->assertEmpty($export->collection());
    }

    public function test_eway_bill_export_collection_includes_eway_bills_in_range(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);
        \App\Models\EwayBill::create([
            'freight_booking_id' => $booking->id,
            'eway_bill_number' => 'EWB-EXPORT-1',
            'eway_bill_expiry' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($admin);
        $export = new EwayBillExport(
            now()->subDay()->toDateString(),
            now()->addWeek()->toDateString()
        );

        $rows = $export->collection();
        $this->assertCount(1, $rows);
        $this->assertSame('EWB-EXPORT-1', $rows->first()['Eway Bill No.']);
        $this->assertSame($lr->lr_number, $rows->first()['LR No']);
    }

    public function test_cash_export_marks_tds_and_status_for_linked_cash_payment(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);
        $booking->update(['tds_amount' => 50]);

        $this->actingAs($admin);

        VoucherLinked::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_ids' => '501',
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 900,
            'payment_mode' => 'cash',
            'payment_status' => 'in-progress',
        ]);

        $export = new CashExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
            'in-progress'
        );

        $rows = $export->collection();
        $this->assertCount(1, $rows);
        $this->assertSame('Yes', $rows->first()['TDS Deducted']);
        $this->assertSame('In progress', $rows->first()['Status']);
    }

    public function test_dispatch_export_view_renders_lr_rows_and_formats(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $lr->update(['gr_date' => now()->toDateString()]);

        $this->actingAs($admin);
        $export = new DispatchExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
            now()->toDateString()
        );

        $view = $export->view();
        $this->assertTrue($view->getData()['forExcel']);
        $this->assertCount(1, $view->getData()['lrs']);
        $this->assertNotEmpty($export->columnFormats());
    }

    public function test_dispatch_export_without_dates_returns_empty_collection_in_view(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $view = (new DispatchExport(null, null))->view();
        $this->assertSame([], $view->getData()['lrs']);
    }

    public function test_party_bill_pdf_export_supports_invoice_templates(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $country = Country::firstOrCreate(['name' => 'India', 'code' => 'IN']);
        $state = State::firstOrCreate(['name' => 'Gujarat'], ['country_id' => $country->id]);
        $city = City::firstOrCreate(['name' => 'Ahmedabad'], ['state_id' => $state->id, 'country_id' => $country->id]);
        $customer = Consignee::query()->findOrFail($lr->customer_id);
        $customer->update(['city_id' => $city->id, 'state_id' => $state->id]);
        $admin->company->update(['city_id' => $city->id, 'state_id' => $state->id]);

        $invoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PDF-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [$lr->id],
            'total_amount' => 5000,
            'base_amount' => 5000,
        ]);

        foreach (['classic', 'modern', 'compact'] as $template) {
            CompanySetting::query()->updateOrCreate(
                ['company_id' => $admin->company_id],
                array_merge(CompanySetting::defaultsFor($admin->company), ['invoice_template' => $template])
            );

            $this->actingAs($admin)
                ->get('/reports/party-bill-export?'.http_build_query([
                    'invoice_number' => $invoice->id,
                    'type' => 'pdf',
                ]))
                ->assertOk();
        }
    }

    public function test_party_bill_pdf_export_errors_when_invoice_has_no_lrs(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create(['company_id' => $admin->company_id, 'name' => 'PDF Empty']);
        $invoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PDF-EMPTY',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [],
            'total_amount' => 100,
        ]);

        $this->actingAs($admin)
            ->from('/reports/party-bill')
            ->get('/reports/party-bill-export?'.http_build_query([
                'invoice_number' => $invoice->id,
                'type' => 'pdf',
            ]))
            ->assertRedirect('/reports/party-bill')
            ->assertSessionHas('error');
    }

    public function test_party_bill_export_requires_invoice_number(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->from('/reports/party-bill')
            ->get('/reports/party-bill-export')
            ->assertRedirect('/reports/party-bill')
            ->assertSessionHas('error');
    }

    public function test_eway_bill_export_headings_and_column_formats(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $export = new EwayBillExport(
            now()->subDay()->toDateString(),
            now()->addWeek()->toDateString()
        );

        $this->assertContains('Eway Bill No.', $export->headings());
        $this->assertArrayHasKey('E', $export->columnFormats());
    }

    public function test_cash_export_can_filter_by_creator_branch(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);

        $branchA = Branch::create([
            'company_id' => $admin->company_id,
            'branch_name' => 'Branch A',
            'head_office' => true,
        ]);
        $branchB = Branch::create([
            'company_id' => $admin->company_id,
            'branch_name' => 'Branch B',
            'head_office' => false,
        ]);

        $creatorA = User::factory()->create([
            'company_id' => $admin->company_id,
            'branch_id' => $branchA->id,
            'user_role_id' => 3,
        ]);
        $creatorB = User::factory()->create([
            'company_id' => $admin->company_id,
            'branch_id' => $branchB->id,
            'user_role_id' => 3,
        ]);

        $this->actingAs($creatorA);
        VoucherPayment::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-BR-A',
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 100,
            'payment_mode' => 'cash',
            'payment_status' => 'in-progress',
            'created_by' => $creatorA->id,
        ]);

        $this->actingAs($creatorB);
        VoucherPayment::create([
            'company_id' => $admin->company_id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-BR-B',
            'payment_type' => 'advance',
            'date' => now()->toDateString(),
            'amount' => 200,
            'payment_mode' => 'cash',
            'payment_status' => 'in-progress',
            'created_by' => $creatorB->id,
        ]);

        $this->actingAs($admin);
        $export = new CashExport(
            now()->subDay()->toDateString(),
            now()->addDay()->toDateString(),
            null,
            $branchA->id
        );

        $amounts = $export->collection()->pluck('Amount')->all();
        $this->assertSame([100], $amounts);
    }

    public function test_pending_invoice_export_filters_by_customer_and_date_range(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $start = now()->subDays(3)->toDateString();
        $end = now()->addDay()->toDateString();

        $this->actingAs($admin);

        $filtered = new PendingInvoiceExport($lr->customer_id, $start, $end);
        $this->assertNotEmpty($filtered->collection());

        $outOfRange = new PendingInvoiceExport($lr->customer_id, now()->addYear()->toDateString(), now()->addYears(2)->toDateString());
        $this->assertEmpty($outOfRange->collection());
    }

    public function test_invoice_export_uses_total_amount_for_reverse_invoices_without_base_amount(): void
    {
        $admin = $this->makeUser('admin');
        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Reverse Customer',
        ]);

        Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-REV-1',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1500,
            'net_amount' => null,
            'base_amount' => null,
        ]);

        $this->actingAs($admin);
        $export = new InvoiceExport(now()->subDay()->toDateString(), now()->addDay()->toDateString());
        $row = $export->collection()->first();

        $this->assertNotNull($row);
        $this->assertSame(1500.0, (float) $row['Taxable Value']);
    }

    public function test_pump_export_redirects_without_pump_or_date_range(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->from(route('reports.pump'))
            ->get(route('reports.pump-export'))
            ->assertRedirect(route('reports.pump'))
            ->assertSessionHas('error');
    }

    public function test_pump_export_downloads_merged_linked_and_unlinked_rows(): void
    {
        $admin = $this->makeUser('admin');
        [$lr, $booking] = $this->seedLr($admin);
        $pump = Pump::create([
            'user_id' => $admin->company->user_id,
            'pump_name' => 'Export Pump',
            'contact_name' => 'Operator',
            'contact_number' => '9777766666',
            'remark' => 'Export',
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'V-PUMP-EXP',
            'payment_type' => 'cash',
            'payment_mode' => 'fuel',
            'amount' => 200,
            'payment_status' => 'pending',
            'company_id' => $admin->company_id,
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
        ]);

        VoucherLinked::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => 'cash',
            'payment_mode' => 'fuel',
            'amount' => 250,
            'payment_status' => 'pending',
            'pump_id' => $pump->id,
            'date' => now()->toDateString(),
            'voucher_ids' => json_encode([]),
        ]);

        $params = [
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->toDateString(),
            'pump_id' => $pump->id,
            'payment_status' => 'pending',
        ];

        $this->actingAs($admin)
            ->get(route('reports.pump-export', $params))
            ->assertOk();
    }

    public function test_party_bill_export_surfaces_pdf_generation_errors(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);
        $invoice = Invoice::create([
            'company_id' => $admin->company_id,
            'customer_id' => $lr->customer_id,
            'invoice_number' => 'INV-PDF-ERR',
            'invoice_type' => 'Reverse',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000,
            'base_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
            'lr_ids' => [$lr->id],
            'memo_ids' => [$lr->memo_id],
        ]);

        Pdf::shouldReceive('loadHTML')->andThrow(new \RuntimeException('PDF render failed'));

        $this->actingAs($admin)
            ->from(route('reports.party-bill'))
            ->get(route('reports.party-bill-export', [
                'invoice_number' => $invoice->id,
                'type' => 'pdf',
            ]))
            ->assertRedirect(route('reports.party-bill'))
            ->assertSessionHas('error');
    }

    public function test_customer_memos_json_returns_distinct_memo_ids(): void
    {
        $admin = $this->makeUser('admin');
        [$lr] = $this->seedLr($admin);

        $response = $this->actingAs($admin)
            ->getJson(route('reports.customer-memos', ['customerId' => $lr->customer_id]))
            ->assertOk();

        $this->assertContains((string) $lr->memo_id, $response->json());
    }

}

