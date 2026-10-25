<?php

namespace Tests\Unit\Models;

use App\Models\City;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\CustomInvoiceDetail;
use App\Models\Driver;
use App\Models\EmailDispatchLog;
use App\Models\EmailTemplate;
use App\Models\EwayBill;
use App\Models\EwayBillReminderLog;
use App\Models\FreightBooking;
use App\Models\FreightBookingCustomAmount;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceAndFreightModelsTest extends TestCase
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
     * 1. CustomInvoiceDetail model tests
     */
    public function test_custom_invoice_detail_relations_and_attributes(): void
    {
        $detail = new CustomInvoiceDetail([
            'portal_rate' => 1500.00,
            'diff' => 50.00,
            'damage_qty' => 2,
            'damage_rate' => 100.00,
            'shortage_qty' => 1,
            'amount' => 1450.00,
            'remark' => 'Test detail',
            'sort_order' => 1,
        ]);

        $this->assertEquals(1500.00, $detail->portal_rate);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $detail->invoice());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $detail->lorryReceipt());
    }

    /**
     * 2. EmailDispatchLog model tests
     */
    public function test_email_dispatch_log_relations_and_casts(): void
    {
        $log = new EmailDispatchLog([
            'event_key' => 'invoice_created',
            'recipient_email' => 'client@example.com',
            'queued_at' => '2026-10-25 10:00:00',
            'sent_at' => '2026-10-25 10:05:00',
            'delivered_at' => '2026-10-25 10:06:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $log->queued_at);
        $this->assertInstanceOf(Carbon::class, $log->sent_at);
        $this->assertInstanceOf(Carbon::class, $log->delivered_at);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $log->template());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $log->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $log->triggeredBy());
    }

    /**
     * 3. FreightBookingCustomAmount model tests
     */
    public function test_freight_booking_custom_amount_relations_and_casts(): void
    {
        $custom = new FreightBookingCustomAmount([
            'description' => 'Unloading charges',
            'amount' => '350.50',
        ]);

        $this->assertEquals('350.50', $custom->amount);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $custom->freightBooking());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $custom->company());
    }

    /**
     * 4. Driver model tests
     */
    public function test_driver_model_relations_and_soft_deletes(): void
    {
        $driver = new Driver([
            'name' => 'Rajesh Kumar',
            'mobile' => '9876543210',
            'license_number' => 'RJ1420200012345',
        ]);

        $this->assertEquals('Rajesh Kumar', $driver->name);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $driver->company());
    }

    /**
     * 5. EmailTemplate model tests
     */
    public function test_email_template_relations_and_casts(): void
    {
        $template = new EmailTemplate([
            'key' => 'bilty_shared',
            'name' => 'Bilty Shared Notification',
            'allowed_variables' => ['company_name', 'lr_number'],
            'allowed_roles' => ['admin', 'operator'],
            'send_to_transporter' => true,
            'send_to_customer' => false,
            'is_active' => true,
        ]);

        $this->assertIsArray($template->allowed_variables);
        $this->assertContains('lr_number', $template->allowed_variables);
        $this->assertIsArray($template->allowed_roles);
        $this->assertTrue($template->send_to_transporter);
        $this->assertFalse($template->send_to_customer);
        $this->assertTrue($template->is_active);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $template->company());
    }

    /**
     * 6. EwayBill and EwayBillReminderLog model tests
     */
    public function test_eway_bill_and_reminder_log_relations_and_casts(): void
    {
        $eway = new EwayBill([
            'eway_bill_number' => '121234345656',
            'eway_bill_expiry' => '2026-11-05',
        ]);

        $this->assertInstanceOf(Carbon::class, $eway->eway_bill_expiry);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $eway->freightBooking());

        $reminder = new EwayBillReminderLog([
            'reminder_key' => 'expiring_soon',
            'sent_on' => '2026-10-25',
        ]);

        $this->assertInstanceOf(Carbon::class, $reminder->sent_on);
    }

    /**
     * 7. FreightBooking relations
     */
    public function test_freight_booking_relations(): void
    {
        $booking = new FreightBooking();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->lr());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->company());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOneThrough::class, $booking->truck());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->consignee());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->linkedFreightBooking());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->sourceCity());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->destinationCity());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $booking->transporter());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $booking->voucherPayments());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $booking->payments());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $booking->customAmounts());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $booking->voucherLinkeds());
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $booking->ewayBills());
    }

    private function createLr(Company $company): LorryReceipt
    {
        $party = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Test Party '.uniqid(),
            'address' => 'Plot 10',
        ]);

        return LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $party->id,
            'consignor_id' => $party->id,
            'consignee_id' => $party->id,
            'lr_number' => 'LR-U-'.uniqid(),
            'gr_number' => 'GR-U-'.uniqid(),
            'gr_date' => now()->toDateString(),
            'invoice_number' => 'INV-U-'.uniqid(),
            'source_city' => 'Jaipur',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Delhi',
            'destination_state' => 'Delhi',
            'lane' => 'L1',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
        ]);
    }

    /**
     * 8. FreightBooking calculations, balance, scope, and payment helpers
     */
    public function test_freight_booking_calculations_and_dispatch_summaries(): void
    {
        $company = Company::create(['name' => 'FB Calc Co']);
        $lr = $this->createLr($company);
        $booking = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 10000.00,
            'tds_amount' => 100.00,
            'damage_amount' => 200.00,
            'shortage_amount' => 150.00,
            'detention' => 300.00,
            'deviation' => 100.00,
            'misc_expense' => 50.00,
            'negative_adjusted_amount' => 25.00,
            'advance_amount' => 2000.00,
            'balance_amount' => 7925.00,
            'linked_lr_id' => null,
            'voucher_printed' => true,
            'voucher_last_printed_at' => now(),
        ]);

        // Casts
        $this->assertTrue($booking->voucher_printed);
        $this->assertInstanceOf(Carbon::class, $booking->voucher_last_printed_at);

        // isLinked
        $this->assertFalse($booking->isLinked());
        $booking->linked_lr_id = 999;
        $booking->save();
        $this->assertTrue($booking->isLinked());

        // scopeLinkedGroup
        $scoped = FreightBooking::linkedGroup(999, $company->id)->get();
        $this->assertTrue($scoped->contains($booking));

        // customAmountsTotal when relation is not loaded vs loaded
        $booking->unsetRelation('customAmounts');
        $this->assertEquals(0.0, $booking->customAmountsTotal());

        $customItem = FreightBookingCustomAmount::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $company->id,
            'description' => 'Labour cess',
            'amount' => 100.00,
        ]);

        $booking->unsetRelation('customAmounts');
        $this->assertEquals(100.0, $booking->customAmountsTotal());
        $booking->setRelation('customAmounts', collect([$customItem]));
        $this->assertEquals(100.0, $booking->customAmountsTotal());

        // effectivePayableAmount = 10000 - 100 - 200 - 150 + 300 + 100 - 50 + 100 + 25 = 10025.00
        $this->assertEquals(10025.00, $booking->effectivePayableAmount());

        // balanceFromAdvance
        $this->assertEquals(8025.00, $booking->balanceFromAdvance());
        $this->assertEquals(7025.00, $booking->balanceFromAdvance(3000.00));

        // Voucher payments for summary
        $pCash = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'payment_type' => 'balance',
            'payment_mode' => 'cash',
            'amount' => 1000.00,
            'date' => '2026-10-20',
        ]);
        $pBank = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'payment_type' => 'balance',
            'payment_mode' => 'bank transfer',
            'amount' => 2000.00,
            'date' => '2026-10-22',
        ]);
        $pFuel = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'payment_type' => 'balance',
            'payment_mode' => 'fuel(paid to pump)',
            'amount' => 500.00,
            'date' => null, // tests fallback to created_at
        ]);
        $pAdv = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'payment_type' => 'balance',
            'payment_mode' => 'adjust in advance',
            'amount' => 400.00,
            'date' => '2026-10-24',
        ]);

        // Null date and null created_at edge case for paymentOccursOnOrBefore
        $pNoDate = new VoucherPayment([
            'payment_mode' => 'cash',
            'amount' => 50.00,
        ]);
        $pNoDate->date = null;
        $pNoDate->created_at = null;

        // dispatchPaymentSummary without asOnDate (using database relation)
        $summary = $booking->dispatchPaymentSummary();
        $this->assertEquals(1000.00, $summary['cash']);
        $this->assertEquals(2000.00, $summary['bank']);
        $this->assertEquals(500.00, $summary['fuel']);
        $this->assertEquals(400.00, $summary['advance']);
        $this->assertEquals(3900.00, $summary['total_paid']);
        $this->assertEquals(7925.00, $summary['balance']);

        // dispatchPaymentSummary with loaded 'payments' relation
        $booking->setRelation('payments', collect([$pCash, $pBank]));
        $summaryPaymentsRel = $booking->dispatchPaymentSummary();
        $this->assertEquals(3000.00, $summaryPaymentsRel['total_paid']);

        // dispatchPaymentSummary with loaded 'voucherPayments' relation including edge payment
        $booking->setRelation('voucherPayments', collect([$pCash, $pBank, $pFuel, $pAdv, $pNoDate]));
        $summaryVoucherRel = $booking->dispatchPaymentSummary();
        $this->assertEquals(3950.00, $summaryVoucherRel['total_paid']);

        // dispatchPaymentSummary with asOnDate string and Carbon
        $summaryAsOn = $booking->dispatchPaymentSummary('2026-10-21');
        $this->assertEquals(1000.00, $summaryAsOn['total_paid']);
        $this->assertEquals(10025.00 - 1000.00, $summaryAsOn['balance']);

        // balanceAsOnDate
        $this->assertEquals(9025.00, $booking->balanceAsOnDate('2026-10-21'));
        $this->assertEquals(9025.00, $booking->balanceAsOnDate(Carbon::parse('2026-10-21')));

        // voucherPaymentsUpToDate with Carbon
        $upToDateCarbon = $booking->voucherPaymentsUpToDate(Carbon::parse('2026-10-21'));
        $this->assertCount(1, $upToDateCarbon);
    }

    /**
     * 9. FreightBooking booted hooks, allocateNextVoucherNumber, TDS permission, and audit methods
     */
    public function test_freight_booking_hooks_tds_rules_and_audit(): void
    {
        $company = Company::create(['name' => 'FB Lifecycle Co']);
        $lr1 = $this->createLr($company);
        $lr2 = $this->createLr($company);

        // allocateNextVoucherNumber
        $nextVoucher = FreightBooking::allocateNextVoucherNumber();
        $this->assertGreaterThanOrEqual(1, $nextVoucher);

        // creating hook assigns voucher_number when empty
        $fb1 = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr1->id,
            'tr_fr_rate' => 100.00,
            'voucher_number' => null,
            'tr_amount' => 5000,
            'advance_amount' => 1000,
            'balance_amount' => 4000,
        ]);
        $this->assertNotNull($fb1->voucher_number);

        // creating hook preserves voucher_number when provided
        $fb2 = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr2->id,
            'tr_fr_rate' => 100.00,
            'voucher_number' => 8888,
            'tr_amount' => 6000,
            'advance_amount' => 1000,
            'balance_amount' => 5000,
        ]);
        $this->assertEquals(8888, $fb2->voucher_number);

        // deleting hook deletes related customAmounts, voucherPayments, voucherLinkeds
        $custom = FreightBookingCustomAmount::create([
            'freight_booking_id' => $fb2->id,
            'company_id' => $company->id,
            'amount' => 200,
            'description' => 'Toll',
        ]);
        $vPay = VoucherPayment::create([
            'freight_booking_id' => $fb2->id,
            'company_id' => $company->id,
            'amount' => 500,
            'payment_type' => 'balance',
            'payment_mode' => 'cash',
        ]);
        $vLink = VoucherLinked::create([
            'freight_booking_id' => $fb2->id,
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'voucher_ids' => json_encode([1]),
            'payment_type' => 'balance',
            'payment_mode' => 'cash',
            'amount' => 500,
        ]);

        $fb2->delete();
        $this->assertSoftDeleted('freight_bookings', ['id' => $fb2->id]);
        $this->assertDatabaseMissing('freight_booking_custom_amounts', ['id' => $custom->id]);
        $this->assertSoftDeleted('voucher_payments', ['id' => $vPay->id]);
        $this->assertSoftDeleted('voucher_linkeds', ['id' => $vLink->id]);

        // canChangeTdsAmount rules
        // Rule 1: balance <= 0.01 -> false
        $fbPaid = new FreightBooking(['balance_amount' => 0.00]);
        $this->assertFalse($fbPaid->canChangeTdsAmount());

        // Rule 2: no lr or no gr_date -> true
        $fbNoLr = new FreightBooking(['balance_amount' => 500.00]);
        $fbNoLr->setRelation('lr', null);
        $this->assertTrue($fbNoLr->canChangeTdsAmount());

        $lrNoDate = new LorryReceipt(['gr_date' => null]);
        $fbNoGrDate = new FreightBooking(['balance_amount' => 500.00]);
        $fbNoGrDate->setRelation('lr', $lrNoDate);
        $this->assertTrue($fbNoGrDate->canChangeTdsAmount());

        // Rule 3: GR date with submission cutoff
        $lrWithDate = new LorryReceipt(['gr_date' => '2026-05-10']);
        $fbWithDate = new FreightBooking(['balance_amount' => 500.00]);
        $fbWithDate->setRelation('lr', $lrWithDate);

        // Submission date is 2026-06-03
        Carbon::setTestNow('2026-05-20');
        $this->assertTrue($fbWithDate->canChangeTdsAmount());

        Carbon::setTestNow('2026-06-15');
        $this->assertFalse($fbWithDate->canChangeTdsAmount());
        Carbon::setTestNow(); // reset test now

        // Audit methods
        $this->assertIsArray($fb1->auditTrackedAttributes());
        $this->assertEquals('Good Hires (Lorry Hire)', $fb1->auditSubjectTypeName());

        // auditSubjectLabel with lr loaded having lr_number
        $lrWithNo = new LorryReceipt(['lr_number' => 'LR-9988']);
        $fb1->setRelation('lr', $lrWithNo);
        $this->assertEquals('FB for LR LR-9988', $fb1->auditSubjectLabel());

        // auditSubjectLabel without lr (falls back to ID)
        $fbUnlinked = new FreightBooking(['id' => 77]);
        $fbUnlinked->id = 77;
        $this->assertEquals('Good Hires (Lorry Hire) #77', $fbUnlinked->auditSubjectLabel());
    }
}
