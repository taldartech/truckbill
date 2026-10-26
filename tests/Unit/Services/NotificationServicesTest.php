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
}
