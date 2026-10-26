<?php

namespace Tests\Unit\Services;

use App\Models\AdvancePayment;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\EmailTemplate;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\LorryReceipt;
use App\Models\SubscriptionPayment;
use App\Models\Transporter;
use App\Models\TransporterMaster;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use App\Services\EmailTemplateMailSender;
use App\Services\Notifications\AdvancePaymentNotificationService;
use App\Services\Notifications\CompanyLifecycleNotificationService;
use App\Services\Notifications\EwayBillNotificationService;
use App\Services\Notifications\FreightBookingNotificationService;
use App\Services\Notifications\InvoiceNotificationService;
use App\Services\Notifications\LorryReceiptNotificationService;
use App\Services\Notifications\TransporterOnboardingNotificationService;
use App\Services\Notifications\VoucherPaymentNotificationService;
use InvalidArgumentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NotificationServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::firstOrCreate(['id' => 1], ['name' => 'master_admin']);
        UserRole::firstOrCreate(['id' => 2], ['name' => 'super_admin']);
        UserRole::firstOrCreate(['id' => 3], ['name' => 'admin']);
        UserRole::firstOrCreate(['id' => 4], ['name' => 'moderator']);
        UserRole::firstOrCreate(['id' => 5], ['name' => 'accountant']);
    }

    private function createCompanyWithOwner(string $companyName = 'Acme Logistics', string $ownerEmail = 'owner@example.com'): array
    {
        $superRole = UserRole::where('name', 'super_admin')->first();
        $owner = User::create([
            'name' => 'Company Owner',
            'email' => $ownerEmail,
            'password' => bcrypt('password'),
            'user_role_id' => $superRole->id,
        ]);

        $company = Company::create([
            'name' => $companyName,
            'user_id' => $owner->id,
        ]);

        $owner->update(['company_id' => $company->id]);

        return [$company, $owner];
    }

    private function createTemplate(
        string $key,
        ?array $allowedRoles = ['super_admin', 'admin'],
        bool $sendToTransporter = false,
        bool $sendToCustomer = false,
        bool $isActive = true
    ): EmailTemplate {
        return EmailTemplate::create([
            'key' => $key,
            'name' => $key.' Template',
            'subject' => 'Notice: {{companyName}}',
            'body_html' => '<p>Hello {{name}}</p>',
            'allowed_roles' => $allowedRoles,
            'send_to_transporter' => $sendToTransporter,
            'send_to_customer' => $sendToCustomer,
            'is_active' => $isActive,
        ]);
    }

    private function createTestLr(Company $company, ?int $truckId = null, ?int $transporterId = null, ?int $customerId = null, string $lrNumber = 'LR-101'): LorryReceipt
    {
        $cust = $customerId ?? Consignee::create(['company_id' => $company->id, 'name' => 'Default Party', 'address' => 'Station Road'])->id;

        return LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $cust,
            'consignor_id' => $cust,
            'consignee_id' => $cust,
            'truck_id' => $truckId,
            'transporter_id' => $transporterId,
            'source_city' => 'Mumbai',
            'source_state' => 'MH',
            'destination_city' => 'Delhi',
            'destination_state' => 'DL',
            'lane' => 'Mumbai-Delhi',
            'good_type' => 'General Goods',
            'fr_rate' => 150.00,
            'amount' => 1500.00,
            'no_of_units' => 10,
            'lr_number' => $lrNumber,
            'gr_date' => now()->toDateString(),
            'weight' => 12.5,
        ]);
    }

    // =========================================================================
    // 1. AdvancePaymentNotificationService Tests
    // =========================================================================

    public function test_advance_payment_amount_zero_or_negative_returns_early(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');

        $service = new AdvancePaymentNotificationService($mailSender);

        $advZero = new AdvancePayment(['advance_amount' => 0.00]);
        $service->notifyAdvancePaid($advZero);

        $advNeg = new AdvancePayment(['advance_amount' => -50.00]);
        $service->notifyAdvancePaid($advNeg);

        $this->assertTrue(true);
    }

    public function test_advance_payment_missing_or_inactive_template_returns_early(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');

        $service = new AdvancePaymentNotificationService($mailSender);

        // No template
        $advance = new AdvancePayment(['advance_amount' => 500.00]);
        $service->notifyAdvancePaid($advance);

        // Inactive template
        $this->createTemplate('ADVANCE_PAYMENT_PAID', ['super_admin'], false, false, false);
        $service->notifyAdvancePaid($advance);

        $this->assertTrue(true);
    }

    public function test_advance_payment_null_company_or_empty_recipients_returns_early(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');

        $service = new AdvancePaymentNotificationService($mailSender);
        $this->createTemplate('ADVANCE_PAYMENT_PAID', ['super_admin'], false, false, true);

        // Null company
        $advNoCo = new AdvancePayment(['advance_amount' => 500.00, 'company_id' => null]);
        $service->notifyAdvancePaid($advNoCo);

        // Company with no matching role users and send_to_transporter false
        [$company] = $this->createCompanyWithOwner('Solo Co', 'solo@example.test');
        $m = TransporterMaster::create(['name' => 'T', 'pan_number' => 'ABCDE1111A']);
        $t = Transporter::create(['company_id' => $company->id, 'transporter_master_id' => $m->id]);
        $advNoRecipients = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $t->id,
            'advance_amount' => 500.00,
            'payment_date' => now()->toDateString(),
        ]);

        EmailTemplate::where('key', 'ADVANCE_PAYMENT_PAID')->update(['allowed_roles' => ['moderator']]);
        $service->notifyAdvancePaid($advNoRecipients);

        $this->assertTrue(true);
    }

    public function test_advance_payment_successful_notification(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Express Logistics', 'owner@express.test');

        $transMaster = TransporterMaster::create([
            'name' => 'Speedline Fleet',
            'email' => 'speedline@transporter.test',
            'pan_number' => 'ABCDE1234F',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $transMaster->id,
            'contact_person_name' => 'Rajesh Sharma',
        ]);

        $advance = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 2500.00,
            'payment_date' => '2026-10-24',
            'remark' => 'Diesel advance for trip #45',
        ]);

        $this->createTemplate('ADVANCE_PAYMENT_PAID', null, true, false, true);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $sentEmails = [];
        $mailSender->shouldReceive('sendTemplate')
            ->twice()
            ->andReturnUsing(function ($tmpl, $email, $vars, $meta) use (&$sentEmails, $company, $owner) {
                $sentEmails[] = $email;
                $this->assertSame($company->id, $meta['company_id']);
                $this->assertSame('advance_payment_paid', $meta['source']);
                $this->assertSame('user', $meta['triggered_by_type']);
                $this->assertSame($owner->id, $meta['triggered_by_id']);
                $this->assertSame('Express Logistics', $vars['companyName']);
                $this->assertSame('2,500.00', $vars['amount']);
                $this->assertSame('24 Oct 2026', $vars['paymentDate']);
                $this->assertSame('Diesel advance for trip #45', $vars['remark']);
            });

        $service = new AdvancePaymentNotificationService($mailSender);
        $service->notifyAdvancePaid($advance, $owner);

        $this->assertContains('owner@express.test', $sentEmails);
        $this->assertContains('speedline@transporter.test', $sentEmails);
    }

    // =========================================================================
    // 2. CompanyLifecycleNotificationService Tests
    // =========================================================================

    public function test_company_registered_notification_branches(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new CompanyLifecycleNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('New Registered Co', 'reg@company.test');

        // Template missing
        $service->notifyCompanyRegistered($company);

        // Template exists
        $this->createTemplate('COMPANY_REGISTERED');

        // Normal flow
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner, $company) {
                $this->assertSame($owner->email, $email);
                $this->assertSame($owner->name, $vars['name']);
                $this->assertSame('New Registered Co', $vars['companyName']);
                $this->assertSame($company->id, $meta['company_id']);
                $this->assertSame('company_registered', $meta['source']);
                return true;
            });

        $service->notifyCompanyRegistered($company);

        // Invalid owner email
        $owner->update(['email' => 'invalid-email-string']);
        $company->setRelation('user', $owner);
        $service->notifyCompanyRegistered($company);

        // No owner user
        $companyNoOwner = Company::create(['name' => 'No Owner Co', 'user_id' => null]);
        $service->notifyCompanyRegistered($companyNoOwner);

        $this->assertTrue(true);
    }

    public function test_subscription_renewed_notification(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Sub Co', 'sub@company.test');
        $payment = new SubscriptionPayment([
            'total_amount' => 4999.00,
        ]);
        $payment->tenure = '1 Year';

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new CompanyLifecycleNotificationService($mailSender);

        // Template missing
        $service->notifySubscriptionRenewed($company, $payment);

        $this->createTemplate('COMPANY_SUBSCRIPTION_RENEWED', ['super_admin']);

        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('4,999.00', $vars['amount']);
                $this->assertSame('1 Year', $vars['tenure']);
                $this->assertSame('company_subscription_renewed', $meta['source']);
                return true;
            });

        $service->notifySubscriptionRenewed($company, $payment, $owner);
        $this->assertTrue(true);
    }

    public function test_plan_expiring_soon_and_expired_notifications(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Expiry Co', 'expiry@company.test');

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new CompanyLifecycleNotificationService($mailSender);

        // Plan expiring soon
        $this->createTemplate('COMPANY_PLAN_EXPIRING_SOON', null);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('7', $vars['daysLeft']);
                $this->assertSame('company_plan_expiring_soon', $meta['source']);
                return true;
            });
        $service->notifyPlanExpiringSoon($company, 7);

        // Plan expired
        $this->createTemplate('COMPANY_PLAN_EXPIRED', null);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('company_plan_expired', $meta['source']);
                return true;
            });
        $service->notifyPlanExpired($company);

        $this->assertTrue(true);
    }

    public function test_credit_low_notification(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Credit Co', 'credit@company.test');

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new CompanyLifecycleNotificationService($mailSender);

        // Template missing
        $service->notifyCreditLow($company, 20, 5);

        // Template exists
        $this->createTemplate('COMPANY_CREDIT_LOW', ['super_admin']);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('20', $vars['creditThreshold']);
                $this->assertSame('5', $vars['lrCredits']);
                $this->assertSame('company_credit_low', $meta['source']);
                return true;
            });
        $service->notifyCreditLow($company, 20, 5);

        $this->assertTrue(true);
    }

    // =========================================================================
    // 3. EwayBillNotificationService Tests
    // =========================================================================

    public function test_eway_bill_notification_branches(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new EwayBillNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('Eway Logistics', 'eway@company.test');

        $truck = Truck::create(['company_id' => $company->id, 'truck_number' => 'HR55AB1234', 'truck_type' => 'Flatbed']);
        $lr = $this->createTestLr($company, $truck->id, null, null, 'LR-9988');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
        ]);
        $fb->eway_bill_number = 'EWB-123456789012';
        $fb->eway_bill_expiry = '2026-10-25';

        // Template missing
        $service->notifyExpiry($fb, 1);

        // Template created
        $this->createTemplate('EWAY_EXPIRY_1D', null);

        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('LR-9988', $vars['lrNumber']);
                $this->assertSame('HR55AB1234', $vars['truckNumber']);
                $this->assertSame('EWB-123456789012', $vars['ewayBillNumber']);
                $this->assertSame('25 Oct 2026', $vars['ewayBillExpiryDate']);
                $this->assertSame('1', $vars['expiryInDays']);
                $this->assertSame('eway_expiry_1d', $meta['source']);
                return true;
            });

        $service->notifyExpiry($fb, 1);

        // Null company early return
        $fbNoCo = new FreightBooking(['company_id' => null]);
        $service->notifyExpiry($fbNoCo, 1);

        $this->assertTrue(true);
    }

    // =========================================================================
    // 4. FreightBookingNotificationService Tests
    // =========================================================================

    public function test_freight_booking_cancelled_notification(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new FreightBookingNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('Cancel FB Co', 'fb_cancel@company.test');

        $truck = Truck::create(['company_id' => $company->id, 'truck_number' => 'PB10XY1111', 'truck_type' => 'Flatbed']);
        $lr = $this->createTestLr($company, $truck->id, null, null, 'LR-CAN-01');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
            'voucher_number' => 'VCH-55',
            'is_cancelled' => true,
        ]);

        // Template missing
        $service->notifyCancelled($fb);

        // Template active with super_admin role
        $this->createTemplate('FR_STATUS_CANCELLED', ['super_admin']);

        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('LR-CAN-01', $vars['lrNumber']);
                $this->assertSame('PB10XY1111', $vars['truckNumber']);
                $this->assertSame('VCH-55', $vars['voucherNumber']);
                $this->assertSame('Cancelled', $vars['status']);
                $this->assertSame('freight_booking_cancelled', $meta['source']);
                return true;
            });

        $service->notifyCancelled($fb);

        $this->assertTrue(true);
    }

    // =========================================================================
    // 5. InvoiceNotificationService Tests
    // =========================================================================

    public function test_invoice_notify_current_status_all_branches(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new InvoiceNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('Invoice Co', 'inv@company.test');

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Tata Steel Ltd',
            'email' => 'tata@customer.test',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-2026-001',
            'manual_invoice_number' => 'MAN-001',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 10000.00,
            'net_amount' => 10000.00,
            'paid_amount' => 4000.00,
            'remaining_amount' => 6000.00,
            'payment_status' => 'partial',
        ]);

        \App\Models\InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 4000.00,
            'mode' => 'bank',
            'payment_date' => now()->toDateString(),
        ]);
        $invoice->refresh();

        // Unknown status returns early
        $invoice->payment_status = 'draft';
        $service->notifyForCurrentStatus($invoice);

        // Inactive/missing template returns early
        $invoice->payment_status = 'partial';
        $service->notifyForCurrentStatus($invoice);

        // Partial template with customer
        $this->createTemplate('INVOICE_PAYMENT_PARTIAL', ['super_admin'], false, true);

        $sentEmails = [];
        $mailSender->shouldReceive('sendTemplate')
            ->twice()
            ->andReturnUsing(function ($tmpl, $email, $vars, $meta) use (&$sentEmails) {
                $sentEmails[] = $email;
                $this->assertSame('invoice_payment_partial', $meta['source']);
                $this->assertSame('INV-2026-001', $vars['invoiceNumber']);
                $this->assertSame('Tata Steel Ltd', $vars['customerName']);
                $this->assertSame('4,000.00', $vars['paidAmount']);
                $this->assertSame('6,000.00', $vars['dueAmount']);
            });

        $service->notifyForCurrentStatus($invoice);
        $this->assertContains('inv@company.test', $sentEmails);
        $this->assertContains('tata@customer.test', $sentEmails);

        // Pending & Paid status templates
        $this->createTemplate('INVOICE_PAYMENT_PENDING', ['super_admin']);
        $this->createTemplate('INVOICE_PAYMENT_PAID', ['super_admin']);

        $invoice->payment_status = 'pending';
        $mailSender->shouldReceive('sendTemplate')->once()->withArgs(fn ($tmpl, $e, $v, $meta) => $meta['source'] === 'invoice_payment_pending');
        $service->notifyForCurrentStatus($invoice);

        $invoice->payment_status = 'paid';
        $mailSender->shouldReceive('sendTemplate')->once()->withArgs(fn ($tmpl, $e, $v, $meta) => $meta['source'] === 'invoice_payment_paid');
        $service->notifyForCurrentStatus($invoice, extraVariables: ['paymentAmount' => 6000.00, 'paymentDate' => '2026-10-24']);

        $this->assertTrue(true);
    }

    public function test_invoice_notify_due_reminder_1d_and_3d(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new InvoiceNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('Due Invoice Co', 'due_inv@company.test');

        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Due Client']);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DUE-01',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 5000.00,
            'net_amount' => 5000.00,
            'paid_amount' => 0.00,
            'remaining_amount' => 5000.00,
            'payment_status' => 'pending',
        ]);

        // Template missing
        $service->notifyDueReminder($invoice, 1);

        // Template active
        $this->createTemplate('INVOICE_DUE_REMINDER', ['super_admin']);

        // 1 day reminder
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('1', $vars['dueInDays']);
                $this->assertSame('invoice_due_reminder_1d', $meta['source']);
                return true;
            });
        $service->notifyDueReminder($invoice, 1);

        // 3 days reminder
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                $this->assertSame($owner->email, $email);
                $this->assertSame('3', $vars['dueInDays']);
                $this->assertSame('invoice_due_reminder_3d', $meta['source']);
                return true;
            });
        $service->notifyDueReminder($invoice, 3);

        $this->assertTrue(true);
    }

    // =========================================================================
    // 6. LorryReceiptNotificationService Tests
    // =========================================================================

    public function test_lorry_receipt_notify_status_change_branches(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new LorryReceiptNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('LR Notify Co', 'lr_notify@company.test');

        $truck = Truck::create(['company_id' => $company->id, 'truck_number' => 'RJ14XY9999', 'truck_type' => 'Flatbed']);
        $customer = Consignee::create(['company_id' => $company->id, 'name' => 'Birla Cement', 'email' => 'birla@client.test']);
        $master = TransporterMaster::create(['name' => 'Rajasthan Roadways', 'email' => 'rr@transporter.test', 'pan_number' => 'ABCDE1234G']);
        $transporter = Transporter::create(['company_id' => $company->id, 'transporter_master_id' => $master->id]);

        $lr = $this->createTestLr($company, $truck->id, $transporter->id, $customer->id, 'LR-STATUS-77');

        // Unknown status returns early
        $service->notifyStatusChange($lr, 'Cancelled');

        // Template missing returns early
        $service->notifyStatusChange($lr, 'Pending');

        // 'Pending' has allow_internal_roles=false, transporter=false, customer=false -> resolveRecipients returns empty
        $this->createTemplate('LR_STATUS_PENDING', ['super_admin']);
        $service->notifyStatusChange($lr, 'Pending');

        // 'In-Progress' template with transporter & customer enabled
        $this->createTemplate('LR_STATUS_IN_PROGRESS', ['super_admin'], true, true);
        $inProgressEmails = [];
        $mailSender->shouldReceive('sendTemplate')
            ->times(3)
            ->andReturnUsing(function ($tmpl, $email, $vars, $meta) use (&$inProgressEmails) {
                $inProgressEmails[] = $email;
                $this->assertSame('lr_status_change', $meta['source']);
                $this->assertSame('LR-STATUS-77', $vars['lrNumber']);
                $this->assertSame('RJ14XY9999', $vars['truckNumber']);
                $this->assertSame('In-Progress', $vars['status']);
            });

        $service->notifyStatusChange($lr, 'In-Progress');
        $this->assertContains('lr_notify@company.test', $inProgressEmails);
        $this->assertContains('rr@transporter.test', $inProgressEmails);
        $this->assertContains('birla@client.test', $inProgressEmails);

        // 'Delivered' status
        $this->createTemplate('LR_STATUS_DELIVERED', ['super_admin'], true, true);
        $mailSender->shouldReceive('sendTemplate')
            ->times(3)
            ->andReturnUsing(function ($tmpl, $email, $vars, $meta) {
                $this->assertSame('Delivered', $vars['status']);
            });
        $service->notifyStatusChange($lr, 'Delivered');

        $this->assertTrue(true);
    }

    // =========================================================================
    // 7. VoucherPaymentNotificationService Tests
    // =========================================================================

    public function test_voucher_payment_notification_branches(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new VoucherPaymentNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('Voucher Co', 'vch@company.test');

        $master = TransporterMaster::create(['name' => 'National Express', 'email' => 'national@transporter.test', 'pan_number' => 'ABCDE1234H']);
        $transporter = Transporter::create(['company_id' => $company->id, 'transporter_master_id' => $master->id]);

        $lr = $this->createTestLr($company, null, $transporter->id, null, 'LR-VCH-88');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
        ]);

        $vp = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => 'VP-1001',
            'amount' => 1500.00,
            'payment_mode' => 'bank',
            'payment_type' => 'advance',
            'payment_status' => 'paid',
            'date' => '2026-10-24',
            'remark' => 'Advance trip voucher',
        ]);

        // Unknown status returns early
        $vp->payment_status = 'cancelled';
        $service->notifyForCurrentStatus($vp);

        // Missing template returns early
        $vp->payment_status = 'paid';
        $service->notifyForCurrentStatus($vp);

        // Paid status template with send_to_transporter
        $this->createTemplate('VOUCHER_PAYMENT_PAID', ['super_admin'], true);

        $sentEmails = [];
        $mailSender->shouldReceive('sendTemplate')
            ->twice()
            ->andReturnUsing(function ($tmpl, $email, $vars, $meta) use (&$sentEmails) {
                $sentEmails[] = $email;
                $this->assertSame('voucher_payment_paid', $meta['source']);
                $this->assertSame('VP-1001', $vars['voucherNumber']);
                $this->assertSame('LR-VCH-88', $vars['lrNumber']);
                $this->assertSame('1,500.00', $vars['amount']);
                $this->assertSame('National Express', $vars['transporterName']);
                $this->assertSame('24 Oct 2026', $vars['paymentDate']);
            });

        $service->notifyForCurrentStatus($vp);
        $this->assertContains('vch@company.test', $sentEmails);
        $this->assertContains('national@transporter.test', $sentEmails);

        // Pending & In-progress statuses
        $this->createTemplate('VOUCHER_PAYMENT_PENDING', ['super_admin']);
        $this->createTemplate('VOUCHER_PAYMENT_IN_PROGRESS', ['super_admin']);

        $vp->payment_status = 'pending';
        $mailSender->shouldReceive('sendTemplate')->once()->withArgs(fn ($tmpl, $e, $v, $meta) => $meta['source'] === 'voucher_payment_pending');
        $service->notifyForCurrentStatus($vp);

        $vp->payment_status = 'in-progress';
        $mailSender->shouldReceive('sendTemplate')->once()->withArgs(fn ($tmpl, $e, $v, $meta) => $meta['source'] === 'voucher_payment_in_progress');
        $service->notifyForCurrentStatus($vp);

        // Null company returns early
        $vpNoCo = new VoucherPayment(['company_id' => null, 'payment_status' => 'paid']);
        $service->notifyForCurrentStatus($vpNoCo);

        $this->assertTrue(true);
    }

    public function test_transporter_onboarding_notification_sends_or_skips_missing_template(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new TransporterOnboardingNotificationService($mailSender);

        $mailSender->shouldReceive('sendByKey')
            ->once()
            ->withArgs(function ($key, $email, $vars, $meta) {
                return $key === 'TRANSPORTER_ACCOUNT_CREATED'
                    && $email === 'driver@transporter.test'
                    && $vars['name'] === 'Driver One'
                    && $meta['source'] === 'transporter_account_created';
            });

        $service->sendAccountCreated('driver@transporter.test', 'Driver One');

        $mailSender->shouldReceive('sendByKey')
            ->once()
            ->andThrow(new InvalidArgumentException('Template missing'));

        $service->sendAccountCreated('silent@transporter.test', 'Silent User');
        $this->assertTrue(true);
    }

    public function test_freight_booking_cancelled_inactive_template_and_empty_recipients(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');
        $service = new FreightBookingNotificationService($mailSender);

        [$company] = $this->createCompanyWithOwner('Inactive FB Co', 'inactive_fb@company.test');
        $lr = $this->createTestLr($company, null, null, null, 'LR-INACT');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
            'is_cancelled' => true,
        ]);

        $this->createTemplate('FR_STATUS_CANCELLED', ['admin'], false, false, false);
        $service->notifyCancelled($fb);

        EmailTemplate::where('key', 'FR_STATUS_CANCELLED')->delete();
        $this->createTemplate('FR_STATUS_CANCELLED', ['moderator'], false, false, true);
        $service->notifyCancelled($fb);

        $this->assertTrue(true);
    }

    public function test_freight_booking_cancelled_default_roles_skip_invalid_email_and_notify_transporter(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('FB Roles Co', 'bad-owner-email');
        $owner->update(['email' => 'not-a-valid-email']);

        $accountantRole = UserRole::where('name', 'accountant')->first();
        User::create([
            'name' => 'Company Accountant',
            'email' => 'accountant@fbroles.test',
            'password' => bcrypt('password'),
            'user_role_id' => $accountantRole->id,
            'company_id' => $company->id,
        ]);

        $master = TransporterMaster::create([
            'name' => 'Fleet Line',
            'email' => 'also-invalid-email',
            'pan_number' => 'ABCDE2222B',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);

        $lr = $this->createTestLr($company, null, $transporter->id, null, 'LR-FB-ROLES');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
            'is_cancelled' => true,
        ]);

        $this->createTemplate('FR_STATUS_CANCELLED', null, true, false, true);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) {
                return $email === 'accountant@fbroles.test'
                    && $meta['source'] === 'freight_booking_cancelled'
                    && $meta['triggered_by_type'] === 'system';
            });

        $service = new FreightBookingNotificationService($mailSender);
        $service->notifyCancelled($fb);
    }

    public function test_freight_booking_cancelled_transporter_fallback_name_and_triggered_by_user(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('FB Trigger Co', 'owner@fbtrigger.test');

        $master = TransporterMaster::create([
            'name' => 'Master Fleet Name',
            'email' => 'fleet@fbtrigger.test',
            'pan_number' => 'ABCDE6666F',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'contact_person_name' => 'Dispatch Contact',
        ]);

        $lr = $this->createTestLr($company, null, $transporter->id, null, 'LR-FB-TRIG');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
            'is_cancelled' => true,
        ]);

        $this->createTemplate('FR_STATUS_CANCELLED', ['moderator'], true, false, true);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(function ($tmpl, $email, $vars, $meta) use ($owner) {
                return $email === 'fleet@fbtrigger.test'
                    && $vars['name'] === 'Master Fleet Name'
                    && $meta['triggered_by_type'] === 'user'
                    && $meta['triggered_by_id'] === $owner->id;
            });

        $service = new FreightBookingNotificationService($mailSender);
        $service->notifyCancelled($fb, $owner);
    }

    public function test_eway_bill_inactive_template_empty_recipients_and_invalid_emails(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');
        $service = new EwayBillNotificationService($mailSender);

        [$company, $owner] = $this->createCompanyWithOwner('Eway Edge Co', 'bad-eway@');
        $owner->update(['email' => 'bad-eway@']);

        $lr = $this->createTestLr($company, null, null, null, 'LR-EWAY-EDGE');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
        ]);
        $fb->eway_bill_number = 'EWB-EDGE';
        $fb->eway_bill_expiry = now()->addDay()->toDateString();

        $this->createTemplate('EWAY_EXPIRY_1D', ['accountant'], false, false, false);
        $service->notifyExpiry($fb, 1);

        EmailTemplate::where('key', 'EWAY_EXPIRY_1D')->delete();
        $this->createTemplate('EWAY_EXPIRY_1D', ['accountant'], false, false, true);
        $service->notifyExpiry($fb, 1);

        $this->assertTrue(true);
    }

    public function test_eway_bill_default_roles_and_valid_transporter_recipient(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Eway Trans Co', 'owner@ewaytrans.test');
        $owner->update(['email' => 'owner@ewaytrans.test']);

        $master = TransporterMaster::create([
            'name' => 'Eway Carrier',
            'email' => 'carrier@ewaytrans.test',
            'pan_number' => 'ABCDE3333C',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);

        $lr = $this->createTestLr($company, null, $transporter->id, null, 'LR-EWAY-TR');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
        ]);
        $fb->eway_bill_number = 'EWB-TRANS';
        $fb->eway_bill_expiry = now()->addDay()->toDateString();

        $this->createTemplate('EWAY_EXPIRY_1D', [], true, false, true);

        $sentEmails = [];
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldReceive('sendTemplate')
            ->twice()
            ->andReturnUsing(function ($tmpl, $email) use (&$sentEmails) {
                $sentEmails[] = $email;
            });

        $service = new EwayBillNotificationService($mailSender);
        $service->notifyExpiry($fb, 1);

        $this->assertContains('owner@ewaytrans.test', $sentEmails);
        $this->assertContains('carrier@ewaytrans.test', $sentEmails);
    }

    public function test_advance_payment_skips_invalid_user_and_transporter_emails(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Adv Skip Co', 'owner@advskip.test');
        $owner->update(['email' => 'not-valid']);

        $master = TransporterMaster::create([
            'name' => 'Skip Fleet',
            'email' => 'bad-transporter',
            'pan_number' => 'ABCDE4444D',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);

        $advance = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 100.00,
            'payment_date' => now()->toDateString(),
        ]);

        $this->createTemplate('ADVANCE_PAYMENT_PAID', null, true, false, true);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');

        $service = new AdvancePaymentNotificationService($mailSender);
        $service->notifyAdvancePaid($advance);
    }

    public function test_company_lifecycle_subscription_renewed_empty_recipients_and_role_fallback(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Lifecycle Co', 'lifecycle@company.test');
        $payment = new SubscriptionPayment(['total_amount' => 1000.00]);
        $payment->tenure = '1 Year';

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $service = new CompanyLifecycleNotificationService($mailSender);

        $this->createTemplate('COMPANY_SUBSCRIPTION_RENEWED', ['moderator'], false, false, true);
        $mailSender->shouldNotReceive('sendTemplate');
        $service->notifySubscriptionRenewed($company, $payment);

        EmailTemplate::where('key', 'COMPANY_SUBSCRIPTION_RENEWED')->delete();
        $this->createTemplate('COMPANY_SUBSCRIPTION_RENEWED', null, false, false, true);

        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($tmpl, $email) => $email === 'lifecycle@company.test');

        $service->notifySubscriptionRenewed($company, $payment);
    }

    public function test_company_lifecycle_credit_low_skips_invalid_recipient_email(): void
    {
        [$company] = $this->createCompanyWithOwner('Credit Skip Co', 'owner@creditskip.test');

        $adminRole = UserRole::where('name', 'admin')->first();
        User::create([
            'name' => 'Bad Admin',
            'email' => 'invalid-admin',
            'password' => bcrypt('password'),
            'user_role_id' => $adminRole->id,
            'company_id' => $company->id,
        ]);

        $this->createTemplate('COMPANY_CREDIT_LOW', ['super_admin', 'admin'], false, false, true);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($tmpl, $email) => $email === 'owner@creditskip.test');

        $service = new CompanyLifecycleNotificationService($mailSender);
        $service->notifyCreditLow($company, 10, 2);
    }

    public function test_voucher_payment_skips_invalid_internal_and_transporter_emails(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Voucher Skip Co', 'not-email');
        $owner->update(['email' => 'not-email']);

        $master = TransporterMaster::create([
            'name' => 'Voucher Fleet',
            'email' => 'also-not-email',
            'pan_number' => 'ABCDE5555E',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
        ]);

        $lr = $this->createTestLr($company, null, $transporter->id, null, 'LR-VP-SKIP');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100.00,
            'tr_amount' => 1000.00,
            'balance_amount' => 1000.00,
        ]);

        $vp = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => 'VP-SKIP',
            'amount' => 500.00,
            'payment_mode' => 'bank',
            'payment_type' => 'advance',
            'payment_status' => 'paid',
            'date' => now()->toDateString(),
        ]);

        $this->createTemplate('VOUCHER_PAYMENT_PAID', ['super_admin'], true, false, true);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');

        $service = new VoucherPaymentNotificationService($mailSender);
        $service->notifyForCurrentStatus($vp);
    }

    public function test_invoice_notify_skips_invalid_customer_email(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Invoice Skip Co', 'invskip@company.test');

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Bad Email Customer',
            'email' => 'not-customer-email',
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-SKIP-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000.00,
            'net_amount' => 1000.00,
            'paid_amount' => 0.00,
            'remaining_amount' => 1000.00,
            'payment_status' => 'pending',
        ]);

        $this->createTemplate('INVOICE_PAYMENT_PENDING', ['super_admin'], false, true);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($tmpl, $email) => $email === 'invskip@company.test');

        $service = new InvoiceNotificationService($mailSender);
        $service->notifyForCurrentStatus($invoice);
    }

    public function test_invoice_notification_returns_when_company_missing_or_recipients_empty(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');
        $service = new InvoiceNotificationService($mailSender);

        [$company] = $this->createCompanyWithOwner('Missing Company Co', 'missing-co@company.test');
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => Consignee::create(['company_id' => $company->id, 'name' => 'Gap Customer'])->id,
            'invoice_number' => 'INV-GAP-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
        ]);
        $company->delete();

        $this->createTemplate('INVOICE_PAYMENT_PENDING', ['moderator'], false, false);
        $service->notifyForCurrentStatus($invoice->fresh());

        $this->createTemplate('INVOICE_DUE_REMINDER', ['moderator'], false, false);
        $service->notifyDueReminder($invoice->fresh(), 2);
    }

    public function test_invoice_notification_uses_default_roles_and_skips_invalid_internal_email(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Invoice Roles Co', 'valid-owner@company.test');

        User::create([
            'name' => 'Bad Email User',
            'email' => 'bad-internal-email',
            'password' => bcrypt('password'),
            'user_role_id' => UserRole::where('name', 'admin')->value('id'),
            'company_id' => $company->id,
        ]);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => Consignee::create(['company_id' => $company->id, 'name' => 'Roles Customer'])->id,
            'invoice_number' => 'INV-ROLES-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
        ]);

        $this->createTemplate('INVOICE_PAYMENT_PENDING', [], false, false);

        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldReceive('sendTemplate')
            ->once()
            ->withArgs(fn ($tmpl, $email) => $email === 'valid-owner@company.test');

        $service = new InvoiceNotificationService($mailSender);
        $service->notifyForCurrentStatus($invoice);
    }

    public function test_company_lifecycle_notifications_return_when_recipients_empty(): void
    {
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');
        $service = new CompanyLifecycleNotificationService($mailSender);
        [$company] = $this->createCompanyWithOwner('Lifecycle Gap Co', 'lifecycle@company.test');

        $this->createTemplate('COMPANY_PLAN_EXPIRING_SOON', ['moderator']);
        $service->notifyPlanExpiringSoon($company, 3);

        $this->createTemplate('COMPANY_PLAN_EXPIRED', ['moderator']);
        $service->notifyPlanExpired($company);

        $this->createTemplate('COMPANY_CREDIT_LOW', ['moderator']);
        $service->notifyCreditLow($company, 10, 2);
    }

    public function test_lorry_receipt_notification_recipient_resolution_edge_cases(): void
    {
        [$company] = $this->createCompanyWithOwner('LR Gap Co', 'lr-gap@company.test');
        $lrService = new LorryReceiptNotificationService(Mockery::mock(EmailTemplateMailSender::class));
        $template = $this->createTemplate('LR_STATUS_IN_PROGRESS', ['super_admin'], true, true);
        $lr = $this->createTestLr($company, null, null, null, 'LR-GAP-1');

        $resolveRecipients = new \ReflectionMethod(LorryReceiptNotificationService::class, 'resolveRecipients');
        $resolveRecipients->setAccessible(true);
        $this->assertTrue($resolveRecipients->invoke($lrService, $lr, 'LR_STATUS_UNKNOWN', $template)->isEmpty());

        $lrWithParties = $this->createTestLr($company, null, null, null, 'LR-GAP-2');
        $lrWithParties->setRelation('transporter', (object) [
            'name' => null,
            'contact_person_name' => 'Fallback Contact',
            'transporterMaster' => (object) [
                'email' => 'fallback-transporter@example.com',
                'name' => null,
            ],
        ]);
        $lrWithParties->setRelation('customer', (object) [
            'email' => 'customer-no-name@example.com',
            'name' => null,
        ]);
        $lrWithParties->setRelation('consignee', null);

        $recipients = $resolveRecipients->invoke(
            $lrService,
            $lrWithParties,
            'LR_STATUS_IN_PROGRESS',
            $template
        );

        $this->assertSame('Fallback Contact', $recipients->firstWhere('email', 'fallback-transporter@example.com')['name']);
        $this->assertSame('User', $recipients->firstWhere('email', 'customer-no-name@example.com')['name']);
    }

    public function test_voucher_and_advance_notifications_use_default_roles_and_name_fallbacks(): void
    {
        [$company] = $this->createCompanyWithOwner('Payments Gap Co', 'payments-gap@company.test');
        $master = TransporterMaster::create([
            'name' => 'Voucher Fallback Master',
            'email' => 'voucher-fallback@example.com',
            'pan_number' => 'VCHFB1234A',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'name' => null,
            'contact_person_name' => 'Voucher Contact Only',
        ]);
        $lr = $this->createTestLr($company, null, $transporter->id, null, 'LR-VCH-GAP');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'balance_amount' => 1000,
        ]);
        $vp = VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $fb->id,
            'voucher_number' => 'VP-GAP-1',
            'amount' => 500,
            'payment_mode' => 'bank',
            'payment_type' => 'advance',
            'payment_status' => 'paid',
            'date' => now()->toDateString(),
        ]);

        $this->createTemplate('VOUCHER_PAYMENT_PAID', [], true);
        $voucherService = new VoucherPaymentNotificationService(Mockery::mock(EmailTemplateMailSender::class));
        $resolveVoucherRecipients = new \ReflectionMethod(VoucherPaymentNotificationService::class, 'resolveRecipients');
        $resolveVoucherRecipients->setAccessible(true);
        $template = EmailTemplate::where('key', 'VOUCHER_PAYMENT_PAID')->firstOrFail();
        $vp->setRelation('freight_booking', (object) [
            'transporter' => (object) [
                'name' => null,
                'contact_person_name' => 'Voucher Contact Only',
                'transporterMaster' => (object) [
                    'email' => 'voucher-fallback@example.com',
                    'name' => null,
                ],
            ],
        ]);

        $transporterRecipient = $resolveVoucherRecipients->invoke($voucherService, $vp, $template, $company)
            ->first(fn (array $recipient) => $recipient['email'] === 'voucher-fallback@example.com');
        $this->assertSame('Voucher Contact Only', $transporterRecipient['name']);

        $advance = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 250,
            'payment_date' => now()->toDateString(),
            'remark' => 'Advance without date',
        ]);
        $advance->setAttribute('payment_date', null);
        $this->createTemplate('ADVANCE_PAYMENT_PAID', [], true);
        $advanceMailer = Mockery::mock(EmailTemplateMailSender::class);
        $advanceMailer->shouldReceive('sendTemplate')
            ->atLeast()
            ->once()
            ->withArgs(fn ($tmpl, $email, $vars) => $vars['paymentDate'] === '');
        (new AdvancePaymentNotificationService($advanceMailer))->notifyAdvancePaid($advance);
    }

    public function test_notification_services_cover_remaining_recipient_and_template_branches(): void
    {
        [$company, $owner] = $this->createCompanyWithOwner('Branch Tail Co', 'branch-tail@company.test');
        $mailSender = Mockery::mock(EmailTemplateMailSender::class);
        $mailSender->shouldNotReceive('sendTemplate');

        $lifecycle = new CompanyLifecycleNotificationService($mailSender);
        $lifecycle->notifyPlanExpiringSoon($company, 3);
        $lifecycle->notifyPlanExpired($company);

        $this->createTemplate('COMPANY_PLAN_EXPIRING_SOON', ['moderator']);
        $this->createTemplate('COMPANY_PLAN_EXPIRED', ['moderator']);
        $lifecycle->notifyPlanExpiringSoon($company, 3);
        $lifecycle->notifyPlanExpired($company);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => Consignee::create(['company_id' => $company->id, 'name' => 'Gap Invoice Customer'])->id,
            'invoice_number' => 'INV-TAIL-1',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'pending',
        ]);
        $invoiceService = new InvoiceNotificationService($mailSender);
        $this->createTemplate('INVOICE_PAYMENT_PENDING', ['moderator']);
        $invoiceService->notifyForCurrentStatus($invoice);
        $this->createTemplate('INVOICE_DUE_REMINDER', ['moderator']);
        $invoiceService->notifyDueReminder($invoice, 2);

        $master = TransporterMaster::create([
            'name' => 'Tail Fleet',
            'email' => 'bad-email',
            'pan_number' => 'TAIL1234A',
        ]);
        $transporter = Transporter::create([
            'company_id' => $company->id,
            'transporter_master_id' => $master->id,
            'contact_person_name' => 'Dispatch Only',
        ]);
        $lr = $this->createTestLr($company, null, $transporter->id, null, 'LR-TAIL-1');
        $fb = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'balance_amount' => 1000,
            'is_cancelled' => true,
        ]);
        $this->createTemplate('FR_STATUS_CANCELLED', ['moderator'], true);
        (new FreightBookingNotificationService($mailSender))->notifyCancelled($fb);

        $fb->eway_bill_number = 'EWB-TAIL';
        $fb->eway_bill_expiry = null;
        $this->createTemplate('EWAY_EXPIRY_1D', ['moderator'], true);
        (new EwayBillNotificationService($mailSender))->notifyExpiry($fb, 1);

        $adminRole = UserRole::where('name', 'admin')->first();
        User::create([
            'name' => 'Bad LR Email',
            'email' => 'bad-lr-email',
            'password' => bcrypt('password'),
            'user_role_id' => $adminRole->id,
            'company_id' => $company->id,
        ]);
        $this->createTemplate('LR_STATUS_IN_PROGRESS', ['admin'], true, false);
        (new LorryReceiptNotificationService($mailSender))->notifyStatusChange($lr, 'In-Progress');

        $advance = AdvancePayment::create([
            'company_id' => $company->id,
            'transporter_id' => $transporter->id,
            'advance_amount' => 100,
            'payment_date' => now()->toDateString(),
        ]);
        $advance->setRelation('transporter', (object) [
            'name' => null,
            'contact_person_name' => 'Advance Contact',
            'transporterMaster' => (object) [
                'email' => 'valid-tail@transporter.test',
                'name' => null,
            ],
        ]);
        $advanceService = new AdvancePaymentNotificationService(Mockery::mock(EmailTemplateMailSender::class));
        $resolveAdvanceRecipients = new \ReflectionMethod(AdvancePaymentNotificationService::class, 'resolveRecipients');
        $resolveAdvanceRecipients->setAccessible(true);
        $advanceTemplate = EmailTemplate::where('key', 'ADVANCE_PAYMENT_PAID')->first()
            ?? $this->createTemplate('ADVANCE_PAYMENT_PAID', [], true);
        $transporterRecipient = $resolveAdvanceRecipients->invoke(
            $advanceService,
            $advance,
            $advanceTemplate,
            $company
        )->first(fn (array $recipient) => $recipient['email'] === 'valid-tail@transporter.test');
        $this->assertSame('Advance Contact', $transporterRecipient['name']);

        $this->assertTrue(true);
    }
}
