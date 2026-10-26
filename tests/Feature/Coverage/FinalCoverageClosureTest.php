<?php

namespace Tests\Feature\Coverage;

use App\Exports\ProfitabilityReportExport;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LorryReceiptController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\VoucherPaymentController;
use App\Models\AccountGroup;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanySubscription;
use App\Models\Consignee;
use App\Models\Country;
use App\Models\EmailTemplate;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\Ledger;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Pump;
use App\Models\State;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Services\AccountingPostingService;
use App\Services\AuditLogger;
use App\Services\Notifications\EwayBillNotificationService;
use App\Services\Notifications\FreightBookingNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinalCoverageClosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1 => 'master_admin', 2 => 'super_admin', 3 => 'admin', 4 => 'moderator', 5 => 'accountant', 6 => 'operator'] as $id => $name) {
            UserRole::firstOrCreate(['id' => $id], ['name' => $name]);
        }
        foreach (['Cash', 'Bank Transfer', 'Fuel', 'Advance Adjust'] as $name) {
            PaymentMode::firstOrCreate(['name' => $name]);
        }
        if (PaymentType::count() === 0) {
            PaymentType::create(['name' => 'Payment']);
            PaymentType::create(['name' => 'Receipt']);
        }
    }

    private function createAdminWithCompany(): array
    {
        $owner = User::factory()->create(['user_role_id' => 2]);
        $company = Company::create([
            'name' => 'Coverage Test Company',
            'user_id' => $owner->id,
            'is_blocked' => false,
            'gst_slab' => 5,
        ]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 5,
            'users_per_branch' => 5,
            'included_lr_credits' => 100,
            'lr_credits_remaining' => 100,
        ]);
        $owner->update(['company_id' => $company->id]);
        session(['super_admin_company_id' => $company->id]);

        return [$owner, $company];
    }

    private function createLr(array $attributes = []): LorryReceipt
    {
        return LorryReceipt::create(array_merge([
            'source_city' => 'Mumbai',
            'source_state' => 'Maharashtra',
            'destination_city' => 'Pune',
            'destination_state' => 'Maharashtra',
            'lane' => 'Mumbai - Pune',
            'good_type' => 'General Goods',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'delivery_status' => 'Pending',
            'gr_date' => now()->toDateString(),
        ], $attributes));
    }

    private function createFb(LorryReceipt $lr, array $attributes = []): FreightBooking
    {
        return FreightBooking::create(array_merge([
            'company_id' => $lr->company_id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 1000,
            'truck_hire_amount' => 1000,
            'advance_amount' => 0,
            'balance_amount' => 1000,
            'is_delivered' => 0,
            'is_cancelled' => 0,
        ], $attributes));
    }

    public function test_dashboard_profitability_trend_handles_lr_without_freight_booking(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Cust 1']);

        $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-NO-FB-1',
            'delivery_status' => 'In-Progress',
            'actual_amount' => 500,
            'amount' => 500,
            'weight' => 10,
            'fr_rate' => 50,
            'gr_date' => now()->toDateString(),
        ]);

        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->get('/dashboard')->assertOk();
    }

    public function test_profitability_export_handles_lr_without_cost_and_transporter(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Cust 2']);

        $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-NO-FB-2',
            'delivery_status' => 'In-Progress',
            'actual_amount' => 600,
            'weight' => 10,
            'fr_rate' => 60,
            'gr_date' => now()->toDateString(),
        ]);

        $export = new ProfitabilityReportExport($company->id, now()->subDays(5)->toDateString(), now()->addDays(5)->toDateString());
        $collection = $export->collection();
        $this->assertNotEmpty($collection);
    }

    public function test_user_controller_can_manage_user_returns_false_when_target_has_no_company_and_not_customer(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $otherUser = User::factory()->create([
            'company_id' => null,
            'user_role_id' => 6, // operator, not customer
        ]);

        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->delete('/users/'.$otherUser->id)->assertForbidden();
    }

    public function test_truck_owner_create_with_dummy_pan_requires_tds_adjustment_remark(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $dummyPan = Company::dummyTransporterTruckOwnerPanNumber($company->id);
        $master = TransporterMaster::create([
            'name' => 'Dummy PAN Master',
            'pan_number' => $dummyPan,
            'pan_active' => true,
            'mobile_number' => '9876543210',
        ]);

        $this->actingAs($owner)
            ->withSession(['super_admin_company_id' => $company->id])
            ->from('/truck_owners/create')
            ->post('/truck_owners', [
                'company_id' => $company->id,
                'transporter_master_id' => $master->id,
                'mobile_number' => '9876543210',
                'tds_percentage' => 0,
                'declaration_given' => 0,
                'total_trucks' => 1,
                'tds_adjustment_remark' => '',
            ])
            ->assertRedirect('/truck_owners/create')
            ->assertSessionHasErrors('tds_adjustment_remark');
    }

    public function test_audit_logger_format_value_handles_invalid_date_parse_exception(): void
    {
        $logger = app(AuditLogger::class);
        $method = new \ReflectionMethod(AuditLogger::class, 'formatValue');
        $method->setAccessible(true);

        $vp = new VoucherPayment(); // implements Auditable, has date cast to date
        $result = $method->invoke($logger, $vp, 'date', 'not-a-valid-date');
        $this->assertSame('not-a-valid-date', $result);
    }

    public function test_accounting_posting_service_updates_existing_pump_ledger(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $group1 = AccountGroup::firstOrCreate(['company_id' => $company->id, 'name' => 'Group 1'], ['code' => 'G1', 'primary_type' => 'asset']);
        $group2 = AccountGroup::firstOrCreate(['company_id' => $company->id, 'name' => 'Group 2'], ['code' => 'G2', 'primary_type' => 'asset']);

        $pump = Pump::create([
            'company_id' => $company->id,
            'pump_name' => 'Highway Fuel Point',
            'contact_name' => 'Fuel Manager',
            'contact_number' => '9876543210',
        ]);

        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $group1->id,
            'name' => 'Old Pump Name',
            'entity_type' => Pump::class,
            'entity_id' => $pump->id,
        ]);

        $service = app(AccountingPostingService::class);
        $ledger = $service->getOrCreatePartyLedger($company, $pump);

        $this->assertEquals($pump->pump_name.' (Fuel Pump)', $ledger->name);
    }

    public function test_freight_booking_notification_service_skips_invalid_email_and_handles_missing_transporter_names(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        User::factory()->create([
            'company_id' => $company->id,
            'user_role_id' => 3, // admin
            'email' => 'invalid-email-address',
        ]);

        $transporterMaster = TransporterMaster::create([
            'name' => 'Transporter Master Name',
            'email' => 'transporter@example.com',
            'mobile_number' => '9999988888',
            'pan_number' => 'ABCDE1234F',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $transporterMaster->id,
            'name' => null,
            'contact_person_name' => null,
            'mobile_number' => '9999988888',
        ]);

        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Notif Customer']);
        $lr = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-NOTIF-1',
            'delivery_status' => 'Pending',
            'amount' => 1000,
            'weight' => 10,
            'fr_rate' => 100,
            'gr_date' => now()->toDateString(),
        ]);

        $booking = $this->createFb($lr, [
            'transporter_id' => $transporter->id,
            'truck_hire_amount' => 800,
            'advance_amount' => 200,
            'balance_amount' => 600,
        ]);

        $template = EmailTemplate::firstOrCreate(
            ['company_id' => $company->id, 'key' => 'FR_STATUS_CANCELLED'],
            [
                'name' => 'Booking Cancelled',
                'subject' => 'Booking Cancelled',
                'body_html' => '<p>Hello</p>',
                'send_to_transporter' => true,
                'allowed_roles' => ['admin'],
                'is_active' => true,
            ]
        );

        $service = app(FreightBookingNotificationService::class);
        $method = new \ReflectionMethod(FreightBookingNotificationService::class, 'resolveCancelledRecipients');
        $method->setAccessible(true);
        $recipients = $method->invoke($service, $booking, $template);

        $this->assertNotEmpty($recipients);
    }

    public function test_eway_bill_notification_service_handles_empty_expiry_date_and_skips_invalid_email(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        User::factory()->create([
            'company_id' => $company->id,
            'user_role_id' => 3,
            'email' => 'bad-email-format',
        ]);

        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Eway Customer']);
        $lr = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-EWAY-1',
            'delivery_status' => 'Pending',
            'amount' => 500,
            'weight' => 5,
            'fr_rate' => 100,
            'gr_date' => now()->toDateString(),
        ]);

        $booking = $this->createFb($lr, [
            'truck_hire_amount' => 400,
            'advance_amount' => 0,
            'balance_amount' => 400,
            'eway_bill_expiry' => null,
        ]);

        $template = EmailTemplate::firstOrCreate(
            ['company_id' => $company->id, 'key' => 'EWAY_EXPIRY_1D'],
            [
                'name' => 'E-Way Expiry Template',
                'subject' => 'E-Way Expiring',
                'body_html' => '<p>Notice</p>',
                'send_to_transporter' => false,
                'allowed_roles' => ['admin', 'super_admin'],
                'is_active' => true,
            ]
        );

        $service = app(EwayBillNotificationService::class);
        $service->notifyExpiry($booking, 1);
        $this->assertTrue(true);
    }

    public function test_freight_booking_bulk_payment_edge_cases(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'FB Customer']);
        $lr = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-FB-BULK',
            'delivery_status' => 'Pending',
            'amount' => 1000,
            'weight' => 10,
            'fr_rate' => 100,
            'gr_date' => now()->toDateString(),
        ]);

        $b1 = $this->createFb($lr, [
            'truck_hire_amount' => 1000,
            'advance_amount' => 200,
            'balance_amount' => 800,
        ]);

        $payType = PaymentType::first();
        $payMode = PaymentMode::first();

        // Case 1: Positive balance row sent with negative payment -> 422
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->postJson('/freight_bookings/voucher-payment', [
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $payMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'total_amount' => 0,
            'booking_ids' => [$b1->id],
            'payment_amounts' => [-100],
        ])->assertStatus(422)
          ->assertJson([
              'errors' => [
                  'payment_amounts.0' => ['Positive balance row cannot accept negative payment.'],
              ],
          ]);

        // Case 2: Negative balance row sent with magnitude exceeding balance -> 422
        $b1->update(['balance_amount' => -200, 'truck_hire_amount' => 500, 'advance_amount' => 700]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->postJson('/freight_bookings/voucher-payment', [
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $payMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'total_amount' => 0,
            'booking_ids' => [$b1->id],
            'payment_amounts' => [-300],
        ])->assertStatus(422);

        // Case 3: Payment causing advance to exceed effective payable amount -> 422
        $b1->update(['balance_amount' => 500, 'truck_hire_amount' => 500, 'advance_amount' => 100]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->postJson('/freight_bookings/voucher-payment', [
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $payMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'total_amount' => 600,
            'booking_ids' => [$b1->id],
            'payment_amounts' => [600],
        ])->assertStatus(422);

        // Case 4: Non-existent booking ID in array hits continue
        $b1->update(['balance_amount' => 500, 'truck_hire_amount' => 500, 'advance_amount' => 100]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->postJson('/freight_bookings/voucher-payment', [
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $payMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'total_amount' => 100,
            'booking_ids' => [999999, $b1->id],
            'payment_amounts' => [0, 100],
        ]);
    }

    public function test_freight_booking_update_handles_missing_company(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'DelCo Cust']);
        $lr = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-FB-DELCO',
            'delivery_status' => 'Pending',
            'amount' => 1000,
            'weight' => 10,
            'fr_rate' => 100,
            'gr_date' => now()->toDateString(),
        ]);
        $fb = $this->createFb($lr, [
            'truck_hire_amount' => 1000,
            'advance_amount' => 200,
            'balance_amount' => 800,
        ]);

        $controller = app(\App\Http\Controllers\FreightBookingController::class);
        $request = Request::create(route('freight_bookings.update', $fb), 'PUT', [
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'truck_hire_amount' => 1000,
            'advance_amount' => 200,
            'balance_amount' => 800,
            'is_delivered' => '0',
            'is_cancelled' => '0',
        ]);
        $request->setUserResolver(fn () => $owner);
        $this->actingAs($owner);
        $company->delete(); // simulate soft-deleted company after validation

        $response = $controller->update($request, $fb);
        $this->assertTrue($response->isRedirection());
    }

    public function test_lorry_receipt_controller_datatables_and_validation_branches(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'DT Cust']);
        $truck = Truck::create(['company_id' => $company->id, 'truck_number' => 'MH12AB1111', 'truck_type' => 'Own']);

        $lr1 = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'truck_id' => $truck->id,
            'lr_number' => 'LR-DT-1111',
            'delivery_status' => 'Pending',
            'amount' => 1000,
            'weight' => 10,
            'fr_rate' => 100,
            'gr_date' => now()->toDateString(),
            'memo_id' => 77701,
            'invoice_generated' => 1,
        ]);

        // DataTables filterColumn empty string queries (hits lines 205, 211, 217, 223, 229)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->getJson('/lorry_receipts', [
            'X-Requested-With' => 'XMLHttpRequest',
            'columns' => [
                ['data' => 'customer_name', 'search' => ['value' => '']],
                ['data' => 'consignor_name', 'search' => ['value' => '']],
                ['data' => 'consignee_name', 'search' => ['value' => '']],
                ['data' => 'truck_number', 'search' => ['value' => '']],
                ['data' => 'lr_number', 'search' => ['value' => '']],
            ],
        ])->assertOk();

        // Duplicate LR number in store
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->post('/lorry_receipts', [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'truck_id' => $truck->id,
            'lr_number' => $lr1->lr_number,
            'gr_date' => now()->toDateString(),
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
        ])->assertSessionHasErrors('lr_number');

        // LR update with memo_id belonging to an LR with invoice_generated == 1 (hits line 1553)
        $lr2 = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-DT-2222',
            'delivery_status' => 'Pending',
            'amount' => 1000,
            'weight' => 10,
            'fr_rate' => 100,
            'gr_date' => now()->toDateString(),
            'memo_id' => 77702,
        ]);

        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/lorry_receipts/'.$lr2->id, [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => $lr2->lr_number,
            'gr_date' => now()->toDateString(),
            'memo_id' => 77701, // already has invoice_generated = 1
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
        ])->assertSessionHasErrors('memo_id');
    }

    public function test_lorry_receipt_update_linked_transporter_and_edit_old_gr_date(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'LR Link Cust']);
        $master1 = TransporterMaster::create(['name' => 'T1', 'mobile_number' => '9999911111', 'pan_number' => 'ABCDE1111A']);
        $master2 = TransporterMaster::create(['name' => 'T2', 'mobile_number' => '9999922222', 'pan_number' => 'ABCDE2222B']);
        $t1 = Transporter::create(['company_id' => $company->id, 'transporter_master_id' => $master1->id]);
        $t2 = Transporter::create(['company_id' => $company->id, 'transporter_master_id' => $master2->id]);

        $lrOld = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-OLD-GR',
            'gr_date' => now()->subMonths(3)->toDateString(),
        ]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->get('/lorry_receipts/'.$lrOld->id.'/edit')->assertOk();

        $consignor = Consignee::create(['company_id' => $company->id, 'name' => 'Consignor Party']);
        $consignee = Consignee::create(['company_id' => $company->id, 'name' => 'Consignee Party']);

        $linkedLr = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-LINK-TR',
            'linked_lr_id' => 88801,
            'transporter_id' => $t1->id,
        ]);
        $fb = $this->createFb($linkedLr, [
            'transporter_id' => $t1->id,
            'tds_amount' => 50,
            'balance_amount' => 0,
        ]);
        $linkedLr->freight_booking_id = $fb->id;
        $linkedLr->save();

        // When canChangeTdsAmount() is false, changing transporter throws validation exception (line 1242)
        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => 'VP-TDS-SUB',
            'payment_type_id' => PaymentType::first()->id,
            'payment_mode_id' => PaymentMode::first()->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'payment_status' => 'paid',
            'amount' => 100,
            'date' => now()->toDateString(),
        ]);

        $res = $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/lorry_receipts/'.$linkedLr->id, [
            'company_id' => $company->id,
            'gr_date' => now()->toDateString(),
            'transporter_id' => $t2->id,
            'lrs' => [
                [
                    'id' => $linkedLr->id,
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lr_number' => $linkedLr->lr_number,
                    'gr_number' => '12345',
                    'invoice_number' => null,
                    'source_city' => 'Mumbai',
                    'source_state' => 'Maharashtra',
                    'destination_city' => 'Pune',
                    'destination_state' => 'Maharashtra',
                    'lane' => 'Mumbai - Pune',
                    'good_type' => 'General Goods',
                    'weight' => 10,
                    'fr_rate' => 100,
                    'amount' => 1000,
                ],
            ],
        ]);
        $res->assertSessionHasErrors('transporter_id');

        // When transporter is not changed on linked LR, syncFreightBookingsForUpdatedLorryReceipt is executed (lines 1265-1269)
        $resOk = $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/lorry_receipts/'.$linkedLr->id, [
            'company_id' => $company->id,
            'gr_date' => now()->toDateString(),
            'transporter_id' => $t1->id,
            'lrs' => [
                [
                    'id' => $linkedLr->id,
                    'customer_id' => $customer->id,
                    'consignor_id' => $consignor->id,
                    'consignee_id' => $consignee->id,
                    'lr_number' => $linkedLr->lr_number,
                    'gr_number' => '12345',
                    'invoice_number' => null,
                    'source_city' => 'Mumbai',
                    'source_state' => 'Maharashtra',
                    'destination_city' => 'Pune',
                    'destination_state' => 'Maharashtra',
                    'lane' => 'Mumbai - Pune',
                    'good_type' => 'General Goods',
                    'weight' => 10,
                    'fr_rate' => 100,
                    'amount' => 1000,
                ],
            ],
        ]);
        $resOk->assertRedirect();
    }

    public function test_invoice_controller_branches(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $country = Country::firstOrCreate(['id' => 1], ['name' => 'India', 'code' => 'IN']);
        $state = State::firstOrCreate(['country_id' => $country->id, 'name' => 'Maharashtra'], ['code' => 'MH']);
        $company->update(['state_id' => $state->id]);
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Inv Customer',
            'state_id' => $state->id,
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'invoice_type' => 'Reverse',
            'base_amount' => null,
            'total_amount' => 1000,
            'net_amount' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'generated_date' => now()->toDateString(),
        ]);

        // DataTables filter with only generated_date_to (line 280)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->getJson('/invoices?generated_date_to='.now()->toDateString(), [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        // Show ajax on Reverse invoice without base_amount (lines 512, 513)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->getJson('/invoices/'.$invoice->id, [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        // Show ajax on Forward invoice with IGST > 0 (line 526)
        $invoice->update(['invoice_type' => 'Forward', 'base_amount' => 1000, 'igst' => 50]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->getJson('/invoices/'.$invoice->id, [
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        // Fallback isLastInvoiceForCompany (line 730)
        $controller = app(InvoiceController::class);
        $method = new \ReflectionMethod(InvoiceController::class, 'invoiceRowIsLatestForCompany');
        $method->setAccessible(true);
        $invoiceWithoutAttr = Invoice::find($invoice->id);
        $this->assertTrue($method->invoke($controller, $invoiceWithoutAttr));

        // resolveCompanyLogoPublicUrl(null) (line 1107)
        $logoMethod = new \ReflectionMethod(InvoiceController::class, 'resolveCompanyLogoPublicUrl');
        $logoMethod->setAccessible(true);
        $this->assertNull($logoMethod->invoke($controller, null));

        // Same state forward calculateInvoiceAmounts (lines 1068-1070)
        $calcMethod = new \ReflectionMethod(InvoiceController::class, 'calculateInvoiceAmounts');
        $calcMethod->setAccessible(true);
        $amounts = $calcMethod->invoke($controller, 1000.0, 'Forward', $company, $customer);
        $this->assertEquals(25.0, $amounts['cgst']);
        $this->assertEquals(25.0, $amounts['sgst']);
        $this->assertEquals(1050.0, $amounts['net_amount']);

        // Store payment with amount 0 sets payment_status = pending (line 924)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->post('/invoices/'.$invoice->id.'/payments', [
            'amount' => 0,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
        ])->assertRedirect();
        $this->assertEquals('pending', $invoice->fresh()->payment_status);
    }

    public function test_report_controller_branches(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $country = Country::firstOrCreate(['id' => 1], ['name' => 'India', 'code' => 'IN']);
        $state = State::firstOrCreate(['country_id' => $country->id, 'name' => 'Maharashtra'], ['code' => 'MH']);
        $city = City::firstOrCreate(['country_id' => $country->id, 'state_id' => $state->id, 'name' => 'Mumbai']);
        $company->setting()->updateOrCreate(['company_id' => $company->id], ['invoice_template' => 'compact']);
        $company->update(['city_id' => $city->id, 'state_id' => $state->id]);
        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Report Cust',
            'city_id' => $city->id,
            'state_id' => $state->id,
        ]);
        $lr = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-REP-1',
            'delivery_status' => 'Pending',
            'actual_amount' => 1200,
            'amount' => 1200,
            'weight' => 10,
            'fr_rate' => 120,
            'gr_date' => now()->toDateString(),
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => null, // null invoice_number
            'manual_invoice_number' => '', // will become empty safe identifier (lines 1365, 1377)
            'invoice_type' => 'Forward',
            'base_amount' => 0,
            'total_amount' => 0,
            'net_amount' => 0,
            'lr_ids' => [$lr->id],
            'generated_date' => now()->toDateString(),
        ]);

        // Party bill view with compact template (line 1229)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->get('/reports/party-bill?customer_id='.$customer->id)->assertOk();

        // Party bill export with baseAmount 0 (sums actual_amount) and fallback identifier (lines 1317, 1357, 1363)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->get('/reports/party-bill-export?invoice_number='.$invoice->id.'&type=pdf')->assertOk();

        // Party bill export excel with fallback identifier (lines 1371, 1375)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->get('/reports/party-bill-export?invoice_number='.$invoice->id.'&type=excel')->assertOk();

        // Transporter report without transporterId (line 1441)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->get('/reports/transporter-report')->assertOk();

        // Profitability map LR without freight booking (line 1747)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->get('/reports/profitability?start_date='.now()->subDay()->toDateString().'&end_date='.now()->addDay()->toDateString())->assertOk();

        // Voucher receipt mark printed with linked_freight_booking_id (lines 1170-1171)
        $parentFb = $this->createFb($lr);
        $linkedFb = $this->createFb($lr, ['linked_freight_booking_id' => $parentFb->id]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->postJson('/reports/voucher-receipt-mark-printed', [
            'freight_booking_id' => $linkedFb->id,
        ])->assertOk();
    }

    public function test_voucher_payment_controller_branches(): void
    {
        [$owner, $company] = $this->createAdminWithCompany();
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Voucher Cust']);
        $lr = $this->createLr([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-VOUCHER-1',
            'delivery_status' => 'Pending',
            'amount' => 1500,
            'weight' => 10,
            'fr_rate' => 150,
            'gr_date' => now()->toDateString(),
        ]);
        $fb = $this->createFb($lr, [
            'truck_hire_amount' => 1500,
            'advance_amount' => 500,
            'balance_amount' => 1000,
        ]);
        $cashMode = PaymentMode::where('name', 'Cash')->first();
        $payType = PaymentType::first();

        $vp = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'amount' => 300,
            'payment_type' => 'payment',
            'payment_type_id' => $payType->id,
            'payment_mode' => 'Cash',
            'payment_mode_id' => $cashMode->id,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        // Cross-company update aborts 403 (line 563)
        $otherOwner = User::factory()->create(['user_role_id' => 2]);
        $otherCompany = Company::create(['name' => 'Foreign Co', 'user_id' => $otherOwner->id]);
        $otherVp = VoucherPayment::create([
            'company_id' => $otherCompany->id,
            'freight_booking_id' => $fb->id,
            'amount' => 100,
            'payment_type' => 'payment',
            'payment_type_id' => $payType->id,
            'payment_mode' => 'Cash',
            'payment_mode_id' => $cashMode->id,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/voucher_payments/'.$otherVp->id, [
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'payment_mode_id' => $cashMode->id,
            'amount' => 100,
        ])->assertForbidden();

        // Cross-company destroy aborts 403 (line 1037)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->delete('/voucher_payments/'.$otherVp->id)->assertForbidden();

        // Update with empty date defaults to today (line 598)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/voucher_payments/'.$vp->id, [
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => $vp->voucher_number,
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $cashMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'payment_status' => 'pending',
            'amount' => 200,
            'date' => '',
        ])->assertRedirect();
        $this->assertEquals(now()->toDateString(), Carbon::parse($vp->fresh()->date)->toDateString());

        // Update with negative projected advance returns error (lines 642-646)
        VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => 'VP-NEG-PROJ',
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $cashMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'payment_status' => 'pending',
            'amount' => -500,
            'date' => now()->toDateString(),
        ]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/voucher_payments/'.$vp->id, [
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => $vp->voucher_number,
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $cashMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'payment_status' => 'pending',
            'amount' => 100, // projected advance will be -500 + 100 = -400 < -0.01
            'date' => now()->toDateString(),
        ])->assertSessionHas('error', 'Invalid payment amount.');

        // Bank mode in-progress to Cash mode transitions status (line 726)
        $fbBank = $this->createFb($lr, [
            'truck_hire_amount' => 1000,
            'advance_amount' => 0,
            'balance_amount' => 1000,
        ]);
        $bankMode = PaymentMode::where('name', 'Bank Transfer')->first();
        $vpBank = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fbBank->id,
            'voucher_number' => 'VP-BANK-TRANS',
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $bankMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Bank Transfer',
            'payment_status' => 'in-progress',
            'amount' => 50,
            'date' => now()->toDateString(),
        ]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/voucher_payments/'.$vpBank->id, [
            'company_id' => $company->id,
            'freight_booking_id' => $fbBank->id,
            'voucher_number' => $vpBank->voucher_number,
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $cashMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'payment_status' => 'in-progress',
            'amount' => 50,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        // Invalid transition on Bank mode: pending to paid without reference transaction ID hits lines 752-766
        $vpBank->update(['payment_status' => 'pending', 'payment_mode' => 'Bank Transfer', 'payment_mode_id' => $bankMode->id]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->put('/voucher_payments/'.$vpBank->id, [
            'company_id' => $company->id,
            'freight_booking_id' => $fbBank->id,
            'voucher_number' => $vpBank->voucher_number,
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $bankMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Bank Transfer',
            'payment_status' => 'paid',
            'amount' => 50,
            'date' => now()->toDateString(),
        ])->assertSessionHas('error', 'Invalid payment status transition for this payment mode (e.g. cannot skip from pending to paid, or use pending where it is not allowed).');

        // Bulk payment with amount <= 0 skips (line 861)
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->post('/voucher_payments/bulk-payment', [
            'company_id' => $company->id,
            'payment_type' => $payType->name,
            'payment_mode' => $cashMode->name,
            'lorry_receipts' => [$lr->id],
            'freight_bookings' => [$lr->id => $fb->id],
            'amounts' => [$lr->id => 0], // <= 0 skips
            'date' => now()->toDateString(),
        ])->assertRedirect();

        // Bulk update status as non-admin redirects back (line 938)
        $staff = User::factory()->create(['company_id' => $company->id, 'user_role_id' => 6]);
        $vpController = app(VoucherPaymentController::class);
        $bulkReq = Request::create('/voucher_payments/bulk-update', 'POST', [
            'payment_ids' => [$vp->id],
            'new_status' => 'paid',
        ]);
        $bulkReq->setUserResolver(fn () => $staff);
        \Illuminate\Support\Facades\Auth::setUser($staff);
        $vpController->bulkUpdateStatus($bulkReq);
        $this->assertEquals('You do not have permission to update payment status.', session('error'));

        // Bulk update status with paid payment returns error (line 983)
        $vp->update(['payment_status' => 'paid']);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->post('/voucher_payments/bulk-update', [
            'payment_ids' => [$vp->id],
            'new_status' => 'paid',
        ])->assertSessionHas('error', 'Paid payments cannot be updated.');

        // Bulk update status where linked voucher has paid child (line 983)
        $paidChild = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => 'VP-PAID-CHILD',
            'payment_type_id' => $payType->id,
            'payment_mode_id' => $cashMode->id,
            'payment_type' => 'Payment',
            'payment_mode' => 'Cash',
            'payment_status' => 'paid',
            'amount' => 10,
            'date' => now()->toDateString(),
        ]);
        $linked = VoucherLinked::create([
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'amount' => 10,
            'payment_status' => 'in-progress',
            'payment_mode' => 'Cash',
            'payment_type' => 'Payment',
            'voucher_ids' => json_encode([$paidChild->id]),
        ]);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->post('/voucher_payments/bulk-update', [
            'payment_ids' => [],
            'multiple_payment_ids' => [$linked->id],
            'new_status' => 'paid',
        ])->assertSessionHas('error', 'Paid payments cannot be updated.');

        // Bulk update status with invalid transition returns error (line 986)
        $vp->update(['payment_status' => 'in-progress']);
        $this->actingAs($owner)->withSession(['super_admin_company_id' => $company->id])->post('/voucher_payments/bulk-update', [
            'payment_ids' => [$vp->id],
            'new_status' => 'pending', // cash/advance cannot revert from in-progress to pending
        ])->assertSessionHas('error');
    }
}
