<?php

namespace Tests\Unit\Models;

use App\Models\AccountGroup;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceReminderLog;
use App\Models\Ledger;
use App\Models\LorryReceipt;
use App\Models\OnboardingProvisioningLog;
use App\Models\Pump;
use App\Models\Referral;
use App\Models\State;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\UpcomingSubscription;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationsAndBillingModelsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
    }

    /**
     * 1. InvoicePayment Model Test
     */
    public function test_invoice_payment_relations_casts_accessors_and_audit(): void
    {
        $company = Company::create(['name' => 'Invoice Payment Co']);
        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Test Customer',
            'contact_number' => '9999988888',
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $consignee->id,
            'invoice_number' => 'INV-9001',
            'invoice_type' => 'customer',
            'generated_date' => now()->toDateString(),
            'total_amount' => 5000.00,
        ]);

        $group = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Bank Accounts',
            'code' => 'BANK',
            'primary_type' => 'asset',
        ]);
        $bankLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $group->id,
            'name' => 'HDFC Bank',
        ]);

        $payment = new InvoicePayment([
            'invoice_id' => $invoice->id,
            'mode' => 'neft',
            'bank_ledger_id' => $bankLedger->id,
            'amount' => '4500.50',
            'tds_amount' => '200.00',
            'deduction_amount' => '50.25',
            'deduction_reason' => 'Late delivery fee',
            'remark' => 'Settled via NEFT',
            'payment_date' => '2026-10-25',
        ]);
        $payment->save();

        // Casts & Types
        $this->assertEquals('4500.50', $payment->amount);
        $this->assertEquals('200.00', $payment->tds_amount);
        $this->assertEquals('50.25', $payment->deduction_amount);
        $this->assertInstanceOf(Carbon::class, $payment->payment_date);

        // Relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment->invoice());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment->bankLedger());
        $this->assertEquals($invoice->id, $payment->invoice->id);
        $this->assertEquals($bankLedger->id, $payment->bankLedger->id);

        // Total Settled Amount Accessor
        $this->assertEquals(4750.75, $payment->total_settled_amount);

        // Accessor with null TDS & deduction
        $simplePayment = new InvoicePayment(['amount' => 100.00]);
        $this->assertEquals(100.0, $simplePayment->total_settled_amount);

        // Audit Tracked Attributes
        $tracked = $payment->auditTrackedAttributes();
        $this->assertContains('amount', $tracked);
        $this->assertContains('tds_amount', $tracked);
        $this->assertContains('deduction_amount', $tracked);
        $this->assertContains('deduction_reason', $tracked);
        $this->assertContains('mode', $tracked);
        $this->assertContains('remark', $tracked);
        $this->assertContains('payment_date', $tracked);

        // Audit Subject Type Name
        $this->assertEquals('Invoice payment', $payment->auditSubjectTypeName());

        // Audit Subject Label - Case A: Relation loaded invoice
        $payment->load('invoice');
        $this->assertEquals("Payment #{$payment->id} (Invoice INV-9001)", $payment->auditSubjectLabel());

        // Audit Subject Label - Case B: Relation NOT loaded, queries by invoice_id
        $freshPayment = InvoicePayment::findOrFail($payment->id);
        $this->assertFalse($freshPayment->relationLoaded('invoice'));
        $this->assertEquals("Payment #{$payment->id} (Invoice INV-9001)", $freshPayment->auditSubjectLabel());

        // Audit Subject Label - Case C: No invoice_id
        $orphanPayment = new InvoicePayment(['amount' => 200]);
        $orphanPayment->id = 77;
        $this->assertEquals('Payment #77', $orphanPayment->auditSubjectLabel());

        // Resolve Audit Company ID
        $this->assertEquals($company->id, $payment->resolveAuditCompanyId());
        $this->assertNull($orphanPayment->resolveAuditCompanyId());
    }

    /**
     * 2. InvoiceReminderLog Model Test
     */
    public function test_invoice_reminder_log_fillable_and_casts(): void
    {
        $log = new InvoiceReminderLog([
            'invoice_id' => 10,
            'company_id' => 5,
            'reminder_key' => 'reminder_before_3_days',
            'sent_on' => '2026-10-25',
        ]);

        $this->assertInstanceOf(Carbon::class, $log->sent_on);
        $this->assertEquals(10, $log->invoice_id);
        $this->assertEquals(5, $log->company_id);
        $this->assertEquals('reminder_before_3_days', $log->reminder_key);
    }

    /**
     * 3. Ledger Model Test
     */
    public function test_ledger_relations_casts_calculated_balance_and_group_display(): void
    {
        $company = Company::create(['name' => 'Ledger Testing Logistics']);
        $rootGroup = AccountGroup::create([
            'company_id' => $company->id,
            'name' => 'Current Assets',
            'code' => 'CA',
            'primary_type' => 'asset',
        ]);
        $subGroup = AccountGroup::create([
            'company_id' => $company->id,
            'parent_id' => $rootGroup->id,
            'name' => 'Bank Accounts',
            'code' => 'BANK',
            'primary_type' => 'asset',
        ]);

        $ledger = new Ledger([
            'company_id' => $company->id,
            'account_group_id' => $subGroup->id,
            'name' => 'Axis Bank Main',
            'code' => 'AXIS01',
            'opening_balance' => '1000.50',
            'opening_balance_type' => 'debit',
            'opening_balance_date' => '2026-10-01',
            'opening_balance_reference' => 'REF-001',
            'current_balance' => '2500.75',
            'tds_rate' => '2.00',
            'is_builtin' => 1,
            'is_active' => 1,
        ]);
        $ledger->save();

        // Casts
        $this->assertEquals('1000.50', $ledger->opening_balance);
        $this->assertEquals('2500.75', $ledger->current_balance);
        $this->assertEquals('2.00', $ledger->tds_rate);
        $this->assertTrue($ledger->is_builtin);
        $this->assertTrue($ledger->is_active);
        $this->assertInstanceOf(Carbon::class, $ledger->opening_balance_date);

        // Relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ledger->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $ledger->accountGroup());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $ledger->entity());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $ledger->voucherItems());

        // Group Head Display
        $this->assertEquals('Current Assets › Bank Accounts (Asset)', $ledger->getGroupHeadDisplay());

        // Group Head Display with fallback to 'Ledger'
        $fallbackGroup = new AccountGroup(['name' => 'Other Group']);
        $fallbackLedger = new Ledger();
        $fallbackLedger->setRelation('accountGroup', $fallbackGroup);
        $this->assertEquals('Other Group (Ledger)', $fallbackLedger->getGroupHeadDisplay());

        // Group Head Display without group
        $bareLedger = new Ledger();
        $this->assertEquals('General Ledger', $bareLedger->getGroupHeadDisplay());

        // Calculated Balance - Debit >= Credit
        $voucher1 = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JV-DEBIT-01',
            'voucher_date' => '2026-10-15',
            'total_amount' => 500.00,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher1->id,
            'ledger_id' => $ledger->id,
            'type' => 'debit',
            'amount' => 500.00,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher1->id,
            'ledger_id' => $ledger->id,
            'type' => 'credit',
            'amount' => 200.00,
        ]);

        // Opening debit = 1000.50 + debit 500 - credit 200 = 1300.50 Dr
        $calcDebit = $ledger->getCalculatedBalance();
        $this->assertEquals('debit', $calcDebit['type']);
        $this->assertEquals(1300.50, $calcDebit['amount']);
        $this->assertEquals('₹1,300.50 Dr', $calcDebit['formatted']);

        // With toDate before voucher date (filters out voucher items, only opening balance remains)
        $calcBefore = $ledger->getCalculatedBalance('2026-10-10');
        $this->assertEquals('debit', $calcBefore['type']);
        $this->assertEquals(1000.50, $calcBefore['amount']);

        // Calculated Balance - Credit > Debit
        $creditLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $subGroup->id,
            'name' => 'Supplier Creditor',
            'opening_balance' => 2000.00,
            'opening_balance_type' => 'credit',
        ]);
        $voucher2 = AccountingVoucher::create([
            'company_id' => $company->id,
            'voucher_type' => 'journal',
            'voucher_number' => 'JV-CREDIT-01',
            'voucher_date' => '2026-10-15',
            'total_amount' => 1000.00,
            'is_posted' => true,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher2->id,
            'ledger_id' => $creditLedger->id,
            'type' => 'credit',
            'amount' => 500.00,
        ]);
        AccountingVoucherItem::create([
            'company_id' => $company->id,
            'accounting_voucher_id' => $voucher2->id,
            'ledger_id' => $creditLedger->id,
            'type' => 'debit',
            'amount' => 100.00,
        ]);

        // Opening credit = 2000 + credit 500 - debit 100 = 2400.00 Cr
        $calcCredit = $creditLedger->getCalculatedBalance();
        $this->assertEquals('credit', $calcCredit['type']);
        $this->assertEquals(2400.00, $calcCredit['amount']);
        $this->assertEquals('₹2,400.00 Cr', $calcCredit['formatted']);

        // Opening balance with neither debit nor credit
        $neutralLedger = new Ledger([
            'opening_balance' => 100,
            'opening_balance_type' => 'none',
        ]);
        $calcNeutral = $neutralLedger->getCalculatedBalance();
        $this->assertEquals('debit', $calcNeutral['type']);
        $this->assertEquals(0.0, $calcNeutral['amount']);
    }

    /**
     * 4. LorryReceipt Model Test
     */
    public function test_lorry_receipt_relations_sync_status_deleting_and_audit(): void
    {
        $company = Company::create(['name' => 'LR Transport Co']);
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Cust Consignee', 'contact_number' => '9876543210']);
        $consignor = Consignee::create(['company_id' => $company->id, 'name' => 'Consignor A', 'contact_number' => '9876543211']);
        $consignee = Consignee::create(['company_id' => $company->id, 'name' => 'Consignee B', 'contact_number' => '9876543212']);
        $truck = Truck::create([
            'company_id' => $company->id,
            'truck_number' => 'RJ14GB9999',
            'truck_type' => 'Open',
        ]);
        $master = TransporterMaster::create(['name' => 'Fast Transporter Master', 'pan_number' => 'ABCDE1111A']);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'name' => 'Fast Transporter',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-9999',
            'gr_date' => now()->toDateString(),
            'source_city' => 'Jaipur',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Delhi',
            'destination_state' => 'Delhi',
            'lane' => 'L1',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 10000.00,
        ]);

        // Default attributes
        $this->assertEquals('Pending', $lr->delivery_status);

        // Relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->truck());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->transporter());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->consignor());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->consignee());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->customer());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->reference());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $lr->freightBookings());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $lr->freightBooking());

        // syncStatusWithFreightBooking - Case 1: No freight booking
        $this->assertFalse($lr->syncStatusWithFreightBooking());

        // Create associated FreightBooking
        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 5000.00,
            'advance_amount' => 1000.00,
            'balance_amount' => 4000.00,
            'is_cancelled' => '0',
            'is_delivered' => '0',
        ]);

        // syncStatusWithFreightBooking - Case 2: Neither cancelled nor delivered => Pending
        $this->assertTrue($lr->syncStatusWithFreightBooking());
        $this->assertEquals('Pending', $lr->fresh()->delivery_status);

        // syncStatusWithFreightBooking - Case 3: Cancelled
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, true);
        $booking->is_cancelled = '1';
        $booking->save();
        $this->assertTrue($lr->syncStatusWithFreightBooking());
        $this->assertEquals('Cancelled', $lr->fresh()->delivery_status);

        // syncStatusWithFreightBooking - Case 4: Delivered
        $booking->is_cancelled = '0';
        $booking->is_delivered = '1';
        $booking->save();
        $this->assertTrue($lr->syncStatusWithFreightBooking());
        $this->assertEquals('Delivered', $lr->fresh()->delivery_status);
        DB::connection()->getPdo()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);

        // Audit methods
        $tracked = $lr->auditTrackedAttributes();
        $this->assertContains('lr_number', $tracked);
        $this->assertContains('delivery_status', $tracked);
        $this->assertContains('amount', $tracked);
        $this->assertEquals('LR-9999', $lr->auditSubjectLabel());
        $this->assertEquals('Good Receipts (Bilty)', $lr->auditSubjectTypeName());

        // Audit subject label fallback
        $lrNoNum = new LorryReceipt();
        $lrNoNum->id = 88;
        $this->assertEquals('LR #88', $lrNoNum->auditSubjectLabel());

        // Booted deleting hook cascades delete to freightBookings
        $lr->delete();
        $this->assertSoftDeleted('lorry_receipts', ['id' => $lr->id]);
        $this->assertSoftDeleted('freight_bookings', ['id' => $booking->id]);
    }

    /**
     * 5. OnboardingProvisioningLog Model Test
     */
    public function test_onboarding_provisioning_log_casts_and_relations(): void
    {
        $user = User::create([
            'name' => 'Log User',
            'email' => 'loguser@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $log = new OnboardingProvisioningLog([
            'idempotency_key' => 'idemp-12345',
            'action' => 'setup_company',
            'user_id' => $user->id,
            'request_payload' => ['company_name' => 'Logistics Inc'],
            'response_payload' => ['status' => 'success', 'code' => 200],
        ]);
        $log->save();

        $this->assertIsArray($log->request_payload);
        $this->assertEquals('Logistics Inc', $log->request_payload['company_name']);
        $this->assertIsArray($log->response_payload);
        $this->assertEquals(200, $log->response_payload['code']);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $log->user());
        $this->assertEquals($user->id, $log->user->id);
    }

    /**
     * 6. Pump Model Test
     */
    public function test_pump_model_attributes_and_accessors(): void
    {
        $user = User::create([
            'name' => 'Pump Owner',
            'email' => 'pumpowner@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $pump = new Pump([
            'user_id' => $user->id,
            'pump_name' => 'Indian Oil Highway Hub',
            'contact_name' => 'Ramesh Kumar',
            'contact_number' => '9829012345',
            'remark' => '24x7 Diesel available',
        ]);
        $pump->save();

        $this->assertEquals('Indian Oil Highway Hub', $pump->name);
        $this->assertEquals('Indian Oil Highway Hub', $pump->pump_name);

        // Fallback when pump_name is null
        $emptyPump = new Pump();
        $this->assertEquals('', $emptyPump->name);

        $pump->delete();
        $this->assertSoftDeleted('pumps', ['id' => $pump->id]);
    }

    /**
     * 7. Referral Model Test
     */
    public function test_referral_model_casts_and_relations(): void
    {
        $referrer = User::create([
            'name' => 'Referrer User',
            'email' => 'referrer@example.com',
            'password' => bcrypt('secret123'),
        ]);
        $referred = User::create([
            'name' => 'Referred User',
            'email' => 'referred@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $referral = new Referral([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referred->id,
            'referral_code' => 'REF2026',
            'reward_status' => 'pending',
        ]);
        $referral->created_at = now();
        $referral->save();

        $this->assertFalse($referral->timestamps);
        $this->assertInstanceOf(Carbon::class, $referral->created_at);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $referral->referrer());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $referral->referredUser());
        $this->assertEquals($referrer->id, $referral->referrer->id);
        $this->assertEquals($referred->id, $referral->referredUser->id);
    }

    /**
     * 8. State Model Test
     */
    public function test_state_model_relations_and_fillable(): void
    {
        $country = Country::create(['name' => 'India', 'code' => 'IN']);
        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Rajasthan',
            'code' => 'RJ',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $state->country());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $state->cities());
        $this->assertEquals($country->id, $state->country->id);
    }

    /**
     * 9. SubscriptionEvent Model Test
     */
    public function test_subscription_event_casts_and_relations(): void
    {
        $company = Company::create(['name' => 'Sub Event Co']);
        $payment = SubscriptionPayment::create([
            'company_id' => $company->id,
            'type' => 'subscription',
            'status' => 'paid',
            'subtotal' => 2999.00,
            'total_amount' => 2999.00,
        ]);

        $event = new SubscriptionEvent([
            'company_id' => $company->id,
            'event_type' => 'payment.captured',
            'payment_id' => $payment->id,
            'actor_user_id' => null,
            'metadata' => ['gateway' => 'razorpay', 'tier' => 'pro'],
        ]);
        $event->save();

        $this->assertIsArray($event->metadata);
        $this->assertEquals('razorpay', $event->metadata['gateway']);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $event->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $event->payment());
        $this->assertEquals($company->id, $event->company->id);
        $this->assertEquals($payment->id, $event->payment->id);
    }

    /**
     * 10. SubscriptionPayment Model Test
     */
    public function test_subscription_payment_casts_and_relations(): void
    {
        $company = Company::create(['name' => 'Sub Payment Co']);

        $payment = new SubscriptionPayment([
            'company_id' => $company->id,
            'type' => 'annual',
            'status' => 'authorized',
            'razorpay_order_id' => 'order_xyz123',
            'razorpay_payment_id' => 'pay_abc456',
            'razorpay_signature' => 'sig_test_789',
            'promo_code' => 'FESTIVE10',
            'quantities' => ['branches' => 2, 'trucks' => 5],
            'subtotal' => '2500.00',
            'promo_discount' => '250.00',
            'gst_amount' => '405.00',
            'total_amount' => '2655.00',
            'catalog_snapshot' => ['plan' => 'Enterprise'],
            'usage_snapshot_at_purchase' => ['active_trucks' => 3],
            'razorpay_response' => ['id' => 'pay_abc456', 'currency' => 'INR'],
        ]);
        $payment->save();

        // Casts
        $this->assertIsArray($payment->quantities);
        $this->assertIsArray($payment->catalog_snapshot);
        $this->assertIsArray($payment->usage_snapshot_at_purchase);
        $this->assertIsArray($payment->razorpay_response);
        $this->assertEquals('2500.00', $payment->subtotal);
        $this->assertEquals('250.00', $payment->promo_discount);
        $this->assertEquals('405.00', $payment->gst_amount);
        $this->assertEquals('2655.00', $payment->total_amount);

        // Relations
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $payment->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $payment->upcomingSubscription());
        $this->assertEquals($company->id, $payment->company->id);

        // UpcomingSubscription relation resolution
        $upcoming = new UpcomingSubscription([
            'company_id' => $company->id,
            'payment_id' => $payment->id,
            'included_branches' => 1,
            'users_per_branch' => 5,
            'status' => 'pending',
        ]);
        $upcoming->starts_on = now()->toDateString();
        $upcoming->ends_on = now()->addYear()->toDateString();
        $upcoming->save();
        $this->assertEquals($upcoming->id, $payment->fresh()->upcomingSubscription->id);
    }
}
