<?php

namespace Tests\Feature;

use App\Contracts\Auditable;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    protected function makeAdminUser(): User
    {
        $company = Company::create([
            'name' => 'Audit Test Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    public function test_invoice_update_writes_audit_log_row(): void
    {
        $this->seedRoles();
        $user = $this->makeAdminUser();

        $customer = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Buyer',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-AUDIT-1',
            'company_id' => $user->company_id,
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'total_amount' => 1000,
            'payment_status' => 'pending',
        ]);

        $this->actingAs($user);

        $invoice->update(['payment_status' => 'partial']);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => 'updated',
            'subject_id' => $invoice->id,
        ]);

        $log = AuditLog::query()->where('subject_id', $invoice->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('Payment status', $log->summary);

        $this->assertDatabaseHas('audit_log_changes', [
            'audit_log_id' => $log->id,
            'attribute_name' => 'payment_status',
        ]);
        $this->assertSame(1, $log->fieldChanges()->count());
    }

    public function test_audit_log_index_requires_super_admin_and_company_context(): void
    {
        $this->seedRoles();

        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Audit Super Co',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('audit-logs.index'));
        $response->assertOk();
        $response->assertSee('Audit Super Co', false);

        $response = $this->withSession(['super_admin_company_id' => $company->id])
            ->get(route('audit-logs.index'));
        $response->assertOk();
        $response->assertSee('Audit log', false);
        $response->assertSee('Audit Super Co', false);
    }

    public function test_audit_log_multi_company_defaults_to_all_companies(): void
    {
        $this->seedRoles();

        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        Company::create([
            'name' => 'Co A',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        Company::create([
            'name' => 'Co B',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('audit-logs.index'));
        $response->assertOk();
        $response->assertSee('All your companies', false);

        $coA = Company::query()->where('name', 'Co A')->firstOrFail();
        $response = $this->get(route('audit-logs.index', ['company_id' => $coA->id]));
        $response->assertOk();
        $response->assertSee('Co A');
    }

    public function test_audit_log_entries_are_scoped_to_selected_company(): void
    {
        $this->seedRoles();

        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $coA = Company::create([
            'name' => 'Co A',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $coB = Company::create([
            'name' => 'Co B',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $userA = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $coA->id,
        ]);

        $userB = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $coB->id,
        ]);

        AuditLog::query()->create([
            'company_id' => $coA->id,
            'user_id' => $userA->id,
            'action' => 'updated',
            'subject_type' => Invoice::class,
            'subject_id' => 1,
            'subject_label' => 'INV-A',
            'subject_type_name' => 'Invoice',
            'summary' => 'Shared term log - Co A',
            'occurred_at' => now(),
        ]);

        AuditLog::query()->create([
            'company_id' => $coB->id,
            'user_id' => $userB->id,
            'action' => 'updated',
            'subject_type' => Invoice::class,
            'subject_id' => 2,
            'subject_label' => 'INV-B',
            'subject_type_name' => 'Invoice',
            'summary' => 'Shared term log - Co B',
            'occurred_at' => now(),
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('audit-logs.index', [
            'company_id' => $coA->id,
            'q' => 'Shared term',
        ]));

        $response->assertOk();
        $response->assertSee('Co A');
        $response->assertSee('Shared term log - Co A');
        $response->assertDontSee('Shared term log - Co B', false);
    }

    public function test_audit_log_without_company_filter_searches_all_owned_companies(): void
    {
        $this->seedRoles();

        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $coA = Company::create([
            'name' => 'Co A',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $coB = Company::create([
            'name' => 'Co B',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $userA = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $coA->id,
        ]);

        $userB = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $coB->id,
        ]);

        AuditLog::query()->create([
            'company_id' => $coA->id,
            'user_id' => $userA->id,
            'action' => 'updated',
            'subject_type' => Invoice::class,
            'subject_id' => 1,
            'subject_label' => 'INV-A',
            'subject_type_name' => 'Invoice',
            'summary' => 'Shared term log - Co A',
            'occurred_at' => now(),
        ]);

        AuditLog::query()->create([
            'company_id' => $coB->id,
            'user_id' => $userB->id,
            'action' => 'updated',
            'subject_type' => Invoice::class,
            'subject_id' => 2,
            'subject_label' => 'INV-B',
            'subject_type_name' => 'Invoice',
            'summary' => 'Shared term log - Co B',
            'occurred_at' => now(),
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('audit-logs.index', [
            'q' => 'Shared term',
        ]));

        $response->assertOk();
        $response->assertSee('Shared term log - Co A', false);
        $response->assertSee('Shared term log - Co B', false);
    }

    public function test_audit_log_rejects_session_company_not_owned_by_super_admin(): void
    {
        $this->seedRoles();

        $owner = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $otherSuperAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $foreignCompany = Company::create([
            'name' => 'Other Owner Co',
            'user_id' => $otherSuperAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $this->actingAs($owner);

        $response = $this->withSession(['super_admin_company_id' => $foreignCompany->id])
            ->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('No companies found', false);
        $response->assertSessionMissing('super_admin_company_id');
    }

    public function test_audit_log_lr_number_filter_uses_exact_match_not_partial(): void
    {
        $this->seedRoles();

        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Audit LR Co',
            'user_id' => $superAdmin->id,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
        ]);

        $admin = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer',
        ]);

        $lr369 = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => '369',
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Ahmedabad',
            'source_state' => 'Gujarat',
            'destination_city' => 'Mumbai',
            'destination_state' => 'Maharashtra',
            'lane' => 'Lane 1',
            'good_type' => 'Cement',
            'weight' => 10,
            'no_of_units' => 1,
            'fr_rate' => 1000,
            'amount' => 10000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);

        $lr3699 = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $customer->id,
            'consignee_id' => $customer->id,
            'lr_number' => '3699',
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Ahmedabad',
            'source_state' => 'Gujarat',
            'destination_city' => 'Mumbai',
            'destination_state' => 'Maharashtra',
            'lane' => 'Lane 1',
            'good_type' => 'Cement',
            'weight' => 10,
            'no_of_units' => 1,
            'fr_rate' => 1000,
            'amount' => 10000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);

        $fb369 = FreightBooking::create([
            'lr_id' => $lr369->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'advance_amount' => 0,
            'balance_amount' => 10000,
            'company_id' => $company->id,
            'voucher_number' => 369,
            'pan_verified' => true,
        ]);

        $fb3699 = FreightBooking::create([
            'lr_id' => $lr3699->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'advance_amount' => 0,
            'balance_amount' => 10000,
            'company_id' => $company->id,
            'voucher_number' => 3699,
            'pan_verified' => true,
        ]);

        AuditLog::query()->create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'action' => 'updated',
            'subject_type' => LorryReceipt::class,
            'subject_id' => $lr369->id,
            'subject_label' => '369',
            'subject_type_name' => 'Good Receipts (Bilty)',
            'summary' => 'LR 369 updated',
            'occurred_at' => now(),
        ]);

        AuditLog::query()->create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'action' => 'updated',
            'subject_type' => VoucherPayment::class,
            'subject_id' => 99999,
            'subject_label' => '3699',
            'subject_type_name' => 'Voucher payment',
            'summary' => 'Voucher payment 3699 updated',
            'occurred_at' => now(),
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('audit-logs.index', [
            'company_id' => $company->id,
            'lr_number' => '369',
        ]));

        $response->assertOk();
        $response->assertSee('LR 369 updated', false);
        $response->assertDontSee('Voucher payment 3699 updated', false);
    }

    public function test_audit_log_index_is_not_accessible_to_company_admin(): void
    {
        $this->seedRoles();
        $user = $this->makeAdminUser();

        $this->actingAs($user);

        $response = $this->get(route('audit-logs.index'));

        $response->assertForbidden();
    }

    // ==========================================
    // Auditable Contract Tests
    // ==========================================

    public function test_auditable_models_implement_interface(): void
    {
        $this->assertInstanceOf(Auditable::class, new Invoice());
        $this->assertInstanceOf(Auditable::class, new LorryReceipt());
        $this->assertInstanceOf(Auditable::class, new FreightBooking());
        $this->assertInstanceOf(Auditable::class, new VoucherPayment());
        $this->assertInstanceOf(Auditable::class, new InvoicePayment());
    }

    public function test_invoice_auditable_methods(): void
    {
        $invoice = new Invoice([
            'invoice_number' => 'INV-2026-99',
            'company_id' => 42,
        ]);

        $this->assertSame('INV-2026-99', $invoice->auditSubjectLabel());
        $this->assertSame('Invoice', $invoice->auditSubjectTypeName());
        $this->assertSame(42, $invoice->resolveAuditCompanyId());
        $this->assertIsArray($invoice->auditTrackedAttributes());
        $this->assertContains('invoice_number', $invoice->auditTrackedAttributes());
        $this->assertContains('total_amount', $invoice->auditTrackedAttributes());

        $fallbackInvoice = new Invoice();
        $fallbackInvoice->id = 7;
        $this->assertSame('#7', $fallbackInvoice->auditSubjectLabel());
    }

    public function test_lorry_receipt_auditable_methods(): void
    {
        $lr = new LorryReceipt([
            'lr_number' => 'LR-999',
            'company_id' => 15,
        ]);

        $this->assertSame('LR-999', $lr->auditSubjectLabel());
        $this->assertSame('Good Receipts (Bilty)', $lr->auditSubjectTypeName());
        $this->assertSame(15, $lr->resolveAuditCompanyId());
        $this->assertIsArray($lr->auditTrackedAttributes());
        $this->assertContains('lr_number', $lr->auditTrackedAttributes());
        $this->assertContains('amount', $lr->auditTrackedAttributes());
    }

    public function test_freight_booking_auditable_methods(): void
    {
        $booking = new FreightBooking([
            'company_id' => 77,
        ]);
        $booking->id = 55;

        $this->assertSame('Good Hires (Lorry Hire) #55', $booking->auditSubjectLabel());
        $this->assertSame('Good Hires (Lorry Hire)', $booking->auditSubjectTypeName());
        $this->assertSame(77, $booking->resolveAuditCompanyId());
        $this->assertIsArray($booking->auditTrackedAttributes());
        $this->assertContains('tr_fr_rate', $booking->auditTrackedAttributes());

        $lr = new LorryReceipt(['lr_number' => 'LR-777']);
        $booking->setRelation('lr', $lr);
        $this->assertSame('FB for LR LR-777', $booking->auditSubjectLabel());
    }

    public function test_voucher_payment_auditable_methods(): void
    {
        $voucher = new VoucherPayment([
            'voucher_number' => 'VP-789',
            'company_id' => 88,
        ]);

        $this->assertSame('VP-789', $voucher->auditSubjectLabel());
        $this->assertSame('Voucher payment', $voucher->auditSubjectTypeName());
        $this->assertSame(88, $voucher->resolveAuditCompanyId());
        $this->assertIsArray($voucher->auditTrackedAttributes());
        $this->assertContains('voucher_number', $voucher->auditTrackedAttributes());
        $this->assertContains('amount', $voucher->auditTrackedAttributes());
    }

    public function test_invoice_payment_auditable_methods(): void
    {
        $this->seedRoles();
        $user = $this->makeAdminUser();

        $customer = Consignee::create([
            'company_id' => $user->company_id,
            'name' => 'Buyer Pay',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-PAY-1',
            'company_id' => $user->company_id,
            'customer_id' => $customer->id,
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'net_amount' => 1000,
            'total_amount' => 1000,
        ]);

        $payment = new InvoicePayment([
            'invoice_id' => $invoice->id,
            'amount' => 500,
        ]);
        $payment->id = 12;

        $this->assertSame('Payment #12 (Invoice INV-PAY-1)', $payment->auditSubjectLabel());
        $this->assertSame('Invoice payment', $payment->auditSubjectTypeName());
        $this->assertSame($user->company_id, $payment->resolveAuditCompanyId());
        $this->assertIsArray($payment->auditTrackedAttributes());
        $this->assertContains('amount', $payment->auditTrackedAttributes());

        // Without invoice relation/invoice_id
        $orphanPayment = new InvoicePayment(['amount' => 100]);
        $orphanPayment->id = 99;
        $this->assertSame('Payment #99', $orphanPayment->auditSubjectLabel());
        $this->assertNull($orphanPayment->resolveAuditCompanyId());
    }

    public function test_has_hybrid_audit_log_resolve_company_id_handles_missing_attribute(): void
    {
        $invoice = new Invoice();
        $this->assertNull($invoice->resolveAuditCompanyId());
    }

    public function test_all_expected_models_implement_auditable_contract(): void
    {
        $classes = [
            Invoice::class,
            LorryReceipt::class,
            FreightBooking::class,
            VoucherPayment::class,
            InvoicePayment::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(
                is_subclass_of($class, Auditable::class),
                "Expected {$class} to implement App\\Contracts\\Auditable"
            );
        }
    }

    public function test_auditable_interface_contract_methods_and_custom_implementation(): void
    {
        $this->assertTrue(interface_exists(Auditable::class));

        $reflection = new \ReflectionClass(Auditable::class);
        $this->assertTrue($reflection->isInterface());

        $expectedMethods = [
            'auditTrackedAttributes',
            'auditSubjectLabel',
            'auditSubjectTypeName',
            'resolveAuditCompanyId',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue($reflection->hasMethod($methodName), "Missing method {$methodName} on Auditable");
            $method = $reflection->getMethod($methodName);
            $this->assertTrue($method->isPublic());
        }

        // Test anonymous class fulfilling the Auditable contract
        $customAuditable = new class implements Auditable {
            public function auditTrackedAttributes(): array
            {
                return ['custom_key'];
            }

            public function auditSubjectLabel(): string
            {
                return 'Custom #123';
            }

            public function auditSubjectTypeName(): string
            {
                return 'CustomEntity';
            }

            public function resolveAuditCompanyId(): ?int
            {
                return 99;
            }
        };

        $this->assertInstanceOf(Auditable::class, $customAuditable);
        $this->assertSame(['custom_key'], $customAuditable->auditTrackedAttributes());
        $this->assertSame('Custom #123', $customAuditable->auditSubjectLabel());
        $this->assertSame('CustomEntity', $customAuditable->auditSubjectTypeName());
        $this->assertSame(99, $customAuditable->resolveAuditCompanyId());
    }

    public function test_audit_log_controller_remaining_branches(): void
    {
        $this->seedRoles();

        // 1. Super admin owning NO companies
        $superNoCo = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);
        $response1 = $this->actingAs($superNoCo)
            ->withSession(['super_admin_company_id' => 999999])
            ->get(route('audit-logs.index'));
        $response1->assertOk();
        $this->assertFalse(session()->has('super_admin_company_id'));

        // 2. Super admin owning 2 companies, empty company_id in request, and invalid session company
        $co1 = Company::create(['name' => 'Owner Co 1', 'user_id' => $superNoCo->id, 'gst_slab' => 5]);
        $co2 = Company::create(['name' => 'Owner Co 2', 'user_id' => $superNoCo->id, 'gst_slab' => 5]);

        // company_id present but empty string: ?company_id=
        $response2 = $this->actingAs($superNoCo)
            ->get(route('audit-logs.index', ['company_id' => '']));
        $response2->assertOk();

        // session company_id not in ownedIds
        $response3 = $this->actingAs($superNoCo)
            ->withSession(['super_admin_company_id' => 888888])
            ->get(route('audit-logs.index'));
        $response3->assertOk();

        // 3. User filter for a foreign company user (line 107 false branch)
        $foreignCompany = Company::create(['name' => 'Foreign Co', 'user_id' => null, 'gst_slab' => 5]);
        $foreignUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $foreignCompany->id,
        ]);
        $response4 = $this->actingAs($superNoCo)
            ->get(route('audit-logs.index', ['user_id' => $foreignUser->id]));
        $response4->assertOk();

        // 4. lr_number where no related records exist: hasRelatedIds is false (line 180)
        $response5 = $this->actingAs($superNoCo)
            ->get(route('audit-logs.index', ['lr_number' => 'LR-COMPLETELY-UNKNOWN']));
        $response5->assertOk();

        // 5. AuditLog with subject_label = 'FB for LR ...' to populate freightBookingIdsFromAuditLogs
        $lrNum = 'LR-AUDIT-ORPHAN-01';
        AuditLog::create([
            'company_id' => $co1->id,
            'user_id' => $superNoCo->id,
            'action' => 'created',
            'subject_type' => FreightBooking::class,
            'subject_type_name' => 'Good Hires (Lorry Hire)',
            'subject_id' => 12345,
            'subject_label' => 'FB for LR '.$lrNum,
            'occurred_at' => now()->subDays(10),
            'summary' => 'Created freight booking orphan',
        ]);

        // And create audit logs in past periods to hit trend percentage ($prev7Days > 0)
        AuditLog::create([
            'company_id' => $co1->id,
            'user_id' => $superNoCo->id,
            'action' => 'updated',
            'subject_type' => Invoice::class,
            'subject_type_name' => 'Invoice',
            'subject_id' => 54321,
            'subject_label' => 'INV-STAT-01',
            'occurred_at' => now()->subDays(2),
            'summary' => 'Updated invoice recently',
        ]);

        // Search query q
        $response6 = $this->actingAs($superNoCo)
            ->get(route('audit-logs.index', [
                'lr_number' => $lrNum,
                'q' => 'orphan',
            ]));
        $response6->assertOk();

        // 6. Valid session company_id in ownedIds (line 57)
        $response7 = $this->actingAs($superNoCo)
            ->withSession(['super_admin_company_id' => $co1->id])
            ->get(route('audit-logs.index'));
        $response7->assertOk();

        // 7. Super admin with 0 companies and active filter (lines 89-90 & 269)
        $userZeroCo = User::factory()->create(['user_role_id' => 2, 'company_id' => null]);
        $response8 = $this->actingAs($userZeroCo)
            ->get(route('audit-logs.index', ['q' => 'search-no-companies']));
        $response8->assertOk();

        // 8. User filter for a same-company user (line 107)
        $sameUser = User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $co1->id,
        ]);
        $response9 = $this->actingAs($superNoCo)
            ->get(route('audit-logs.index', ['user_id' => $sameUser->id]));
        $response9->assertOk();

        // 9. LR with Invoice and InvoicePayment (lines 147-150, 212-216)
        $cust = Consignee::create([
            'company_id' => $co1->id,
            'name' => 'Audit Test Consignee',
        ]);
        $lrInv = LorryReceipt::create([
            'company_id' => $co1->id,
            'customer_id' => $cust->id,
            'consignor_id' => $cust->id,
            'consignee_id' => $cust->id,
            'lr_number' => 'LR-WITH-INV-PAY',
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Pending',
            'source_city' => 'Ahmedabad',
            'source_state' => 'Gujarat',
            'destination_city' => 'Mumbai',
            'destination_state' => 'Maharashtra',
            'lane' => 'Lane 1',
            'good_type' => 'Cement',
            'weight' => 10,
            'no_of_units' => 1,
            'fr_rate' => 1000,
            'amount' => 10000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'agency',
        ]);
        $inv = Invoice::create([
            'company_id' => $co1->id,
            'customer_id' => $cust->id,
            'invoice_number' => 'INV-LR-001',
            'invoice_type' => 'Forward',
            'generated_date' => now()->toDateString(),
            'lr_ids' => [$lrInv->id],
            'total_amount' => 1000,
            'net_amount' => 1000,
            'payment_status' => 'partial',
        ]);
        InvoicePayment::create([
            'company_id' => $co1->id,
            'invoice_id' => $inv->id,
            'amount' => 500,
            'payment_date' => now()->toDateString(),
            'mode' => 'cash',
        ]);
        $response10 = $this->actingAs($superNoCo)
            ->get(route('audit-logs.index', ['lr_number' => 'LR-WITH-INV-PAY']));
        $response10->assertOk();

        // 10. Filters: subject_type, date_from, date_to (lines 99, 113, 117)
        $response11 = $this->actingAs($superNoCo)
            ->get(route('audit-logs.index', [
                'subject_type' => Invoice::class,
                'date_from' => now()->subDays(5)->toDateString(),
                'date_to' => now()->toDateString(),
            ]));
        $response11->assertOk();
    }
}


