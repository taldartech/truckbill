<?php

namespace Tests\Feature\Broker;

use App\Models\AccountingVoucher;
use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Ledger;
use App\Models\LorryReceipt;
use App\Models\Pump;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use App\Services\AccountingPostingService;
use App\Services\ChartOfAccountsProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokerEndToEndWorkflowTest extends TestCase
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

    private function setupBrokerEnvironment(): array
    {
        $admin = User::factory()->create(['user_role_id' => 3]);
        $company = Company::create([
            'name' => 'Apex Brokerage Logistics',
            'short_name' => 'ABL',
            'user_id' => $admin->id,
            'is_blocked' => false,
            'gst_slab' => '18%',
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

        app(ChartOfAccountsProvisioner::class)->provisionForCompany($company);

        return [$admin, $company];
    }

    public function test_complete_broker_lifecycle_journey(): void
    {
        [$admin, $company] = $this->setupBrokerEnvironment();

        // 1. Onboard Customer & Consignor
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Reliance Petrochem Ltd',
            'address' => 'Jamnagar, Gujarat',
            'gst_number' => '24ABCDE1234F1Z8',
            'pan_number' => 'ABCDE1234F',
        ]);

        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Wholesale Polymer Depot',
            'address' => 'Bhiwandi, Maharashtra',
        ]);

        // 2. Onboard Transporter Partner & Truck
        $master = TransporterMaster::factory()->create([
            'name' => 'National Highway Transport',
            'pan_number' => 'AAAP1234A', // 1% TDS
            'pan_active' => true,
        ]);
        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'transporter_master_id' => $master->id,
            'mobile_number' => '9822334455',
            'declaration_given' => false,
            'tds_percentage' => 1.0,
            'advance_amount' => 0.0,
        ]);
        $truck = Truck::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ10TX7890',
            'truck_type' => 'Trailer',
        ]);

        // 3. Register Fuel Pump for diesel advance slips
        $pump = Pump::create([
            'pump_name' => 'Indian Oil Highway Depot NH8',
            'contact_name' => 'Satish Kumar',
            'contact_number' => '9898001122',
            'user_id' => $company->user_id,
        ]);

        // 4. Issue Lorry Receipt (LR) via HTTP POST
        $lrPayload = [
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $consignee->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'lr_number' => 'LR-E2E-1001',
            'gr_number' => 'GR-E2E-1001',
            'invoice_number' => 'CHL-1001',
            'memo_id' => 'MEMO-E2E-501',
            'source_city' => 'Jamnagar',
            'source_state' => 'Gujarat',
            'destination_city' => 'Bhiwandi',
            'destination_state' => 'Maharashtra',
            'lane' => 'Jamnagar-Bhiwandi',
            'weight' => 20.0,
            'no_of_units' => 500,
            'good_type' => 'Polymer Granules',
            'fr_rate' => 2500.0,
            'amount' => 50000.0,
            'actual_amount' => 50000.0,
            'gr_date' => now()->toDateString(),
            'company_id' => $company->id,
            'delivery_status' => 'Pending',
            'transporter_type' => 'agency',
        ];

        $lrResponse = $this->actingAs($admin)->post('/lorry_receipts', $lrPayload);
        $lrResponse->assertRedirect();
        $this->assertDatabaseHas('lorry_receipts', [
            'lr_number' => 'LR-E2E-1001',
            'company_id' => $company->id,
        ]);

        $lr = LorryReceipt::where('lr_number', 'LR-E2E-1001')->firstOrFail();

        // 5. Dispatch Freight Booking (Lorry Hire Challan) with Advance Cash & Diesel
        // Transporter Rate: 2200/Tonne => 2200 * 20 = 44,000. TDS 1% = 440.
        // Advance Diesel: 10,000. Advance Cash: 5,000. Total advance: 15,000.
        // Net Balance Payable to Transporter: 44,000 - 440 - 15,000 = 28,560.
        $fbPayload = [
            'lr_id' => $lr->id,
            'company_id' => $company->id,
            'tr_fr_rate' => 2200.0,
            'tr_amount' => 44000.0,
            'advance_amount' => 15000.0,
            'tds_amount' => 440.0,
            'balance_amount' => 28560.0,
            'good_type' => 'Polymer Granules',
            'eway_bill_number' => 'EWB-99887766',
            'eway_bill_expiry' => now()->addDays(7)->toDateString(),
            'pan_verified' => true,
            'is_delivered' => false,
            'is_cancelled' => false,
            'confirmed' => true,
        ];

        $fbResponse = $this->actingAs($admin)->post('/freight_bookings', $fbPayload);
        $fbResponse->assertRedirect();
        $this->assertDatabaseHas('freight_bookings', [
            'lr_id' => $lr->id,
            'tr_amount' => 44000.0,
            'balance_amount' => 43560.0,
        ]);

        $booking = FreightBooking::where('lr_id', $lr->id)->firstOrFail();

        // 6. Generate Customer Invoice
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'ABL/2026/0001',
            'invoice_type' => 'regular',
            'generated_date' => now()->toDateString(),
            'base_amount' => 50000.0,
            'cgst' => 4500.0,
            'sgst' => 4500.0,
            'net_amount' => 59000.0,
            'total_amount' => 59000.0,
        ]);

        // Post accounting voucher for Sales
        $postingService = app(AccountingPostingService::class);
        $salesVoucher = $postingService->postInvoice($invoice);
        $this->assertNotNull($salesVoucher);

        // 7. Receive Customer Payment against Invoice
        $bankLedger = Ledger::where('company_id', $company->id)->where('code', 'BANK_PRIMARY')->first()
            ?? Ledger::where('company_id', $company->id)->where('code', 'CASH')->first();

        $payment = InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 59000.0,
            'mode' => 'bank',
            'bank_ledger_id' => $bankLedger->id,
            'payment_date' => now()->toDateString(),
        ]);
        $receiptVoucher = $postingService->postInvoicePayment($payment);
        $this->assertNotNull($receiptVoucher);

        // 8. Deliver LR and Settle Transporter Final Payment
        $lr->update([
            'delivery_status' => 'Delivered',
            'unloading_weight' => 20.0,
        ]);

        $voucherPayment = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'payment_type' => 'balance',
            'payment_mode' => 'bank',
            'amount' => 28560.0,
            'date' => now()->toDateString(),
            'payment_status' => 'paid',
        ]);

        // 9. Assert End-to-End Integrity
        $this->assertSame('Delivered', $lr->fresh()->delivery_status);
        $this->assertSame(0.0, (float) ($invoice->total_amount - $payment->amount));
        $this->assertDatabaseHas('voucher_payments', [
            'id' => $voucherPayment->id,
            'payment_status' => 'paid',
            'amount' => 28560.0,
        ]);
    }
}
