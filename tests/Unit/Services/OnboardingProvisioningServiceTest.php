<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\OnboardingProvisioningLog;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\OnboardingLinkNotification;
use App\Notifications\OnboardingWelcomeNotification;
use App\Notifications\ResetPasswordNotification;
use App\Services\OnboardingProvisioningService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OnboardingProvisioningServiceTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OnboardingProvisioningService();
    }

    // -------------------------------------------------------------
    // CREATE USER TESTS
    // -------------------------------------------------------------

    public function test_create_user_provisions_super_admin_and_logs(): void
    {
        $payload = [
            'idempotency_key' => 'idem-key-100',
            'name' => 'Alice Admin',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'email_verified_at' => now(),
            'policy_accepted_at' => now(),
        ];

        $response = $this->service->createUser($payload);

        $this->assertSame('created', $response['status']);
        $this->assertSame('alice@example.com', $response['email']);
        $this->assertSame('super_admin', $response['role']);
        $this->assertNotNull($response['user_id']);

        $user = User::find($response['user_id']);
        $this->assertNotNull($user);
        $this->assertSame('Alice Admin', $user->name);
        $this->assertNotNull($user->referral_code);
        $this->assertSame(8, strlen($user->referral_code));
        $this->assertNull($user->company_id);
        $this->assertNull($user->branch_id);

        // Check log with redacted password
        $log = OnboardingProvisioningLog::where('idempotency_key', 'idem-key-100')->first();
        $this->assertNotNull($log);
        $this->assertSame('create_user', $log->action);
        $this->assertSame($user->id, $log->user_id);
        $this->assertArrayNotHasKey('password', $log->request_payload);
        $this->assertSame('Alice Admin', $log->request_payload['name']);
        $this->assertSame($response, $log->response_payload);
    }

    public function test_create_user_returns_already_provisioned_for_duplicate_idempotency_key(): void
    {
        $payload = [
            'idempotency_key' => 'idem-dup-key',
            'name' => 'Bob Super',
            'email' => 'bob@example.com',
            'password' => 'secret123',
        ];

        // First call
        $firstResp = $this->service->createUser($payload);
        $this->assertSame('created', $firstResp['status']);

        // Second call with same idempotency key
        $secondResp = $this->service->createUser($payload);
        $this->assertSame('already_provisioned', $secondResp['status']);
        $this->assertSame($firstResp['user_id'], $secondResp['user_id']);
    }

    public function test_create_user_returns_existing_log_when_status_is_not_created(): void
    {
        OnboardingProvisioningLog::create([
            'idempotency_key' => 'custom-log-key',
            'action' => 'custom_action',
            'user_id' => null,
            'request_payload' => ['email' => 'test@example.com'],
            'response_payload' => [
                'status' => 'pending_review',
                'reason' => 'manual_verification',
            ],
        ]);

        $payload = [
            'idempotency_key' => 'custom-log-key',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ];

        $resp = $this->service->createUser($payload);
        $this->assertSame('pending_review', $resp['status']);
    }

    public function test_create_user_throws_validation_exception_when_email_already_taken(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $payload = [
            'idempotency_key' => 'idem-email-dup',
            'name' => 'Duplicate Email User',
            'email' => 'existing@example.com',
            'password' => 'secret123',
        ];

        $this->expectException(ValidationException::class);
        $this->service->createUser($payload);
    }

    // -------------------------------------------------------------
    // UPDATE USER TESTS
    // -------------------------------------------------------------

    public function test_update_user_successfully_updates_super_admin(): void
    {
        $role = UserRole::firstOrCreate(['name' => 'super_admin']);
        $user = User::factory()->create([
            'email' => 'update_me@example.com',
            'user_role_id' => $role->id,
            'name' => 'Old Name',
        ]);

        $newVerifiedAt = now();
        $newPolicyAt = now();

        $status = $this->service->updateUser([
            'email' => 'update_me@example.com',
            'name' => 'New Name',
            'password' => 'new_password_123',
            'email_verified_at' => $newVerifiedAt,
            'policy_accepted_at' => $newPolicyAt,
        ]);

        $this->assertSame(true, $status['exists']);
        $this->assertSame('active', $status['status']);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertTrue(Hash::check('new_password_123', $user->password));
    }

    public function test_update_user_throws_exception_when_user_not_found(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->updateUser([
            'email' => 'non_existent@example.com',
            'name' => 'Test',
        ]);
    }

    public function test_update_user_throws_exception_when_user_is_not_super_admin(): void
    {
        $role = UserRole::firstOrCreate(['name' => 'admin']);
        User::factory()->create([
            'email' => 'regular_admin@example.com',
            'user_role_id' => $role->id,
        ]);

        $this->expectException(ValidationException::class);
        $this->service->updateUser([
            'email' => 'regular_admin@example.com',
            'name' => 'Test',
        ]);
    }

    // -------------------------------------------------------------
    // STATUS TESTS (statusFor, statusByEmail, resolveStatus)
    // -------------------------------------------------------------

    public function test_status_for_null_user_returns_not_exists(): void
    {
        $status = $this->service->statusFor(null);

        $this->assertSame([
            'exists' => false,
            'email' => null,
        ], $status);
    }

    public function test_status_by_email_for_non_existent_user(): void
    {
        $status = $this->service->statusByEmail('notfound@example.com');

        $this->assertSame([
            'exists' => false,
            'email' => 'notfound@example.com',
        ], $status);
    }

    public function test_resolve_status_conditions(): void
    {
        $superAdminRole = UserRole::firstOrCreate(['name' => 'super_admin']);
        $otherRole = UserRole::firstOrCreate(['name' => 'moderator']);

        // 1. Role is not super_admin -> inactive
        $modUser = User::factory()->create([
            'user_role_id' => $otherRole->id,
            'email' => 'mod@example.com',
        ]);
        $modStatus = $this->service->statusByEmail('mod@example.com');
        $this->assertSame('inactive', $modStatus['status']);

        // 2. Super admin is blocked -> blocked
        $blockedUser = User::factory()->create([
            'user_role_id' => $superAdminRole->id,
            'email' => 'blocked@example.com',
            'is_blocked' => true,
        ]);
        $blockedStatus = $this->service->statusByEmail('blocked@example.com');
        $this->assertSame('blocked', $blockedStatus['status']);

        // 3. Email verified is null -> pending_email_verification
        $unverifiedUser = User::factory()->create([
            'user_role_id' => $superAdminRole->id,
            'email' => 'unverified@example.com',
            'is_blocked' => false,
            'email_verified_at' => null,
            'policy_accepted_at' => now(),
        ]);
        $unverifiedStatus = $this->service->statusByEmail('unverified@example.com');
        $this->assertSame('pending_email_verification', $unverifiedStatus['status']);

        // 4. Policy accepted is null -> pending_policy_acceptance
        $noPolicyUser = User::factory()->create([
            'user_role_id' => $superAdminRole->id,
            'email' => 'nopolicy@example.com',
            'is_blocked' => false,
            'email_verified_at' => now(),
            'policy_accepted_at' => null,
        ]);
        $noPolicyStatus = $this->service->statusByEmail('nopolicy@example.com');
        $this->assertSame('pending_policy_acceptance', $noPolicyStatus['status']);

        // 5. All valid -> active & company count
        $activeUser = User::factory()->create([
            'user_role_id' => $superAdminRole->id,
            'email' => 'active@example.com',
            'is_blocked' => false,
            'email_verified_at' => now(),
            'policy_accepted_at' => now(),
        ]);

        Company::create([
            'name' => 'Active Co 1',
            'user_id' => $activeUser->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
        ]);
        Company::create([
            'name' => 'Active Co 2',
            'user_id' => $activeUser->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
        ]);

        $activeStatus = $this->service->statusByEmail('active@example.com');
        $this->assertSame('active', $activeStatus['status']);
        $this->assertSame(2, $activeStatus['company_count']);
        $this->assertTrue($activeStatus['email_verified']);
        $this->assertTrue($activeStatus['policy_accepted']);
    }

    // -------------------------------------------------------------
    // MASTER PANEL SUMMARY SERVICE TESTS
    // -------------------------------------------------------------

    public function test_master_panel_summary_resolve_filters(): void
    {
        $summaryService = new \App\Services\MasterPanelSummaryService();

        // 1. Default dates when empty input
        $filters = $summaryService->resolveFilters([]);
        $this->assertSame(now()->startOfMonth()->toDateString(), $filters['from_date']);
        $this->assertSame(now()->endOfMonth()->toDateString(), $filters['to_date']);
        $this->assertNull($filters['company_id']);
        $this->assertNull($filters['super_admin_user_id']);

        // 2. Swaps dates when from_date > to_date
        $swapped = $summaryService->resolveFilters([
            'from_date' => '2026-05-20',
            'to_date' => '2026-05-10',
            'company_id' => '12',
            'super_admin_user_id' => '34',
        ]);
        $this->assertSame('2026-05-10', $swapped['from_date']);
        $this->assertSame('2026-05-20', $swapped['to_date']);
        $this->assertSame(12, $swapped['company_id']);
        $this->assertSame(34, $swapped['super_admin_user_id']);
    }

    public function test_master_panel_summary_scoped_company_ids(): void
    {
        $summaryService = new \App\Services\MasterPanelSummaryService();

        $superRole = UserRole::firstOrCreate(['name' => 'super_admin'], ['name' => 'super_admin']);
        $adminRole = UserRole::firstOrCreate(['name' => 'admin'], ['name' => 'admin']);

        $superUser = User::factory()->create(['user_role_id' => $superRole->id]);
        $otherSuper = User::factory()->create(['user_role_id' => $superRole->id]);
        $viewerAdmin = User::factory()->create(['user_role_id' => $adminRole->id]);

        $c1 = Company::create(['name' => 'Co 1', 'user_id' => $superUser->id]);
        $c2 = Company::create(['name' => 'Co 2', 'user_id' => $otherSuper->id]);

        // When viewer is super_admin, restricted to own companies
        $viewerSuperIds = $summaryService->scopedCompanyIds($superUser, ['company_id' => null, 'super_admin_user_id' => null]);
        $this->assertTrue($viewerSuperIds->contains($c1->id));
        $this->assertFalse($viewerSuperIds->contains($c2->id));

        // When viewer is master admin, but filters by super_admin_user_id
        $filteredSuperIds = $summaryService->scopedCompanyIds($viewerAdmin, ['company_id' => null, 'super_admin_user_id' => $otherSuper->id]);
        $this->assertFalse($filteredSuperIds->contains($c1->id));
        $this->assertTrue($filteredSuperIds->contains($c2->id));

        // When filters by company_id
        $filteredCoIds = $summaryService->scopedCompanyIds($viewerAdmin, ['company_id' => $c1->id, 'super_admin_user_id' => null]);
        $this->assertSame([$c1->id], $filteredCoIds->all());
    }

    public function test_master_panel_summary_build_kpis_and_breakdowns(): void
    {
        $summaryService = new \App\Services\MasterPanelSummaryService();
        $start = now()->copy()->startOfMonth();
        $end = now()->copy()->endOfMonth();

        // Empty collection
        $emptyKpis = $summaryService->buildKpis(collect(), $start, $end);
        $this->assertSame(0, $emptyKpis['total_companies']);
        $this->assertSame(0, $emptyKpis['revenue']);
        $this->assertTrue($summaryService->buildCompanyBreakdown(collect(), $start, $end)->isEmpty());

        // With data
        $superRole = UserRole::firstOrCreate(['name' => 'super_admin'], ['name' => 'super_admin']);
        $user = User::factory()->create(['user_role_id' => $superRole->id]);
        $company = Company::create(['name' => 'Summary Freight Co', 'user_id' => $user->id]);

        $customer = \App\Models\Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer A',
        ]);

        $lr = \App\Models\LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => 'LR-SUM-001',
            'gr_date' => now()->toDateString(),
            'source_city' => 'City A',
            'source_state' => 'State A',
            'destination_city' => 'City B',
            'destination_state' => 'State B',
            'lane' => 'A-B',
            'good_type' => 'Goods',
            'weight' => 10.0,
            'no_of_units' => 1,
            'fr_rate' => 100,
            'amount' => 1000,
            'delivery_status' => 'Delivered',
            'invoice_generated' => 0,
        ]);

        $invoice = \App\Models\Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_type' => 'Forward',
            'invoice_number' => 'INV-SUM-001',
            'generated_date' => now()->toDateString(),
            'total_amount' => 1200.00,
            'net_amount' => 1200.00,
            'payment_status' => 'Pending',
        ]);

        \App\Models\InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 200.00,
            'mode' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $booking = \App\Models\FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lr->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 800,
            'advance_amount' => 100,
            'balance_amount' => 700,
            'is_delivered' => false,
            'is_cancelled' => false,
        ]);

        \App\Models\VoucherPayment::create([
            'company_id' => $company->id,
            'freight_booking_id' => $booking->id,
            'voucher_number' => 'VCH-001',
            'payment_type' => 'advance',
            'payment_mode' => 'cash',
            'date' => now()->toDateString(),
            'amount' => 500,
            'payment_status' => 'paid',
        ]);

        $kpis = $summaryService->buildKpis(collect([$company->id]), $start, $end);
        $this->assertSame(1, $kpis['total_companies']);
        $this->assertEquals(1200.00, $kpis['revenue']);
        $this->assertEquals(1200.00, $kpis['total_invoiced']);
        $this->assertEquals(200.00, $kpis['invoice_paid_in_range']);
        $this->assertEquals(1000.00, $kpis['outstanding_amount']);
        $this->assertSame(1, $kpis['total_lrs']);
        $this->assertSame(1, $kpis['delivered_lrs']);
        $this->assertSame(1, $kpis['lrs_pending_invoice']);
        $this->assertSame(1, $kpis['freight_bookings']);
        $this->assertSame(1, $kpis['active_trips']);
        $this->assertEquals(500.00, $kpis['voucher_total']);
        $this->assertEquals(500.00, $kpis['voucher_paid']);

        // Company breakdown
        $breakdown = $summaryService->buildCompanyBreakdown(collect([$company->id]), $start, $end);
        $this->assertCount(1, $breakdown);
        $this->assertSame($company->name, $breakdown->first()['name']);
        $this->assertSame($user->name, $breakdown->first()['owner_name']);

        // Super Admin breakdown
        $superBreakdown = $summaryService->buildSuperAdminBreakdown(collect([$company->id]), $start, $end, $user->id);
        $this->assertCount(1, $superBreakdown);
        $this->assertSame($user->name, $superBreakdown->first()['name']);
        $this->assertSame(1, $superBreakdown->first()['company_count']);

        // Super Admin breakdown with scoped companies and null onlySuperAdminId (covers line 209)
        $superBreakdownNoId = $summaryService->buildSuperAdminBreakdown(collect([$company->id]), $start, $end);
        $this->assertCount(1, $superBreakdownNoId);
        $this->assertSame($user->name, $superBreakdownNoId->first()['name']);

        // Super Admin breakdown with empty scoped companies and null onlySuperAdminId
        $superBreakdownEmpty = $summaryService->buildSuperAdminBreakdown(collect(), $start, $end);
        $this->assertCount(1, $superBreakdownEmpty);
    }

    // -------------------------------------------------------------
    // NOTIFICATIONS TESTS
    // -------------------------------------------------------------

    public function test_onboarding_link_notification_delivery_and_mail_content(): void
    {
        $notification = new OnboardingLinkNotification('John Transporter', 'https://truckbill.in/onboard?token=secret123');
        $user = new User(['name' => 'John Transporter', 'email' => 'john@example.com']);

        $this->assertSame(['mail'], $notification->via($user));
        $this->assertSame([], $notification->toArray($user));

        $mail = $notification->toMail($user);
        $this->assertSame('Your TruckBill Onboarding Link', $mail->subject);
        $this->assertSame('Hi John Transporter,', $mail->greeting);
        $this->assertSame('Complete Onboarding', $mail->actionText);
        $this->assertSame('https://truckbill.in/onboard?token=secret123', $mail->actionUrl);

        $intro = implode(' ', $mail->introLines);
        $outro = implode(' ', $mail->outroLines);
        $this->assertStringContainsString('attend the demo of TruckBill', $intro);
        $this->assertStringContainsString('Create your login credentials', $outro);
        $this->assertStringContainsString('Team TruckBill', (string) $mail->salutation);
    }

    public function test_onboarding_welcome_notification_delivery_and_mail_content(): void
    {
        $notification = new OnboardingWelcomeNotification('Alice Logistic');
        $user = new User(['name' => 'Alice Logistic', 'email' => 'alice@example.com']);

        $this->assertSame(['mail'], $notification->via($user));
        $this->assertSame([], $notification->toArray($user));

        $mail = $notification->toMail($user);
        $this->assertSame('Welcome to TruckBill', $mail->subject);
        $this->assertSame('Hi Alice Logistic,', $mail->greeting);
        $this->assertSame('Log In to TruckBill', $mail->actionText);
        $this->assertSame(config('app.url'), $mail->actionUrl);

        $intro = implode(' ', $mail->introLines);
        $outro = implode(' ', $mail->outroLines);
        $this->assertStringContainsString('Welcome to TruckBill!', $intro);
        $this->assertStringContainsString('billing and LR management to GST and TDS compliance', $outro);
        $this->assertStringContainsString('Team TruckBill', (string) $mail->salutation);
    }

    public function test_reset_password_notification_mail_content_and_callback(): void
    {
        $token = 'test-reset-token-999';
        $notification = new ResetPasswordNotification($token);
        $user = new User(['name' => 'Bob User', 'email' => 'bob@example.com']);

        // 1. Default toMail representation
        $mail = $notification->toMail($user);
        $this->assertSame('Reset Password Notification', $mail->subject);
        $this->assertSame('emails.auth.reset_password', $mail->view);
        $this->assertSame($token, $mail->viewData['token']);
        $this->assertSame('bob@example.com', $mail->viewData['email']);
        $this->assertSame(config('auth.passwords.'.config('auth.defaults.passwords').'.expire'), $mail->viewData['count']);

        // 2. Custom toMail callback
        ResetPasswordNotification::toMailUsing(function ($notifiable, $t) {
            return (new MailMessage)
                ->subject('Custom Reset Subject')
                ->line('Token is '.$t);
        });

        $customMail = $notification->toMail($user);
        $this->assertSame('Custom Reset Subject', $customMail->subject);
        $this->assertContains('Token is '.$token, $customMail->introLines);

        // Reset callback to null
        ResetPasswordNotification::toMailUsing(null);
    }
}

