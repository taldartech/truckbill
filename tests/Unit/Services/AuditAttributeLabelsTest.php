<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\AuditLogChange;
use App\Models\Company;
use App\Models\FreightBooking;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LorryReceipt;
use App\Models\User;
use App\Models\VoucherPayment;
use App\Services\AuditAttributeLabels;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuditAttributeLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_label_for_known_models_and_attributes(): void
    {
        $invoice = new Invoice();
        $this->assertSame('Invoice number', AuditAttributeLabels::labelFor($invoice, 'invoice_number'));
        $this->assertSame('Total amount', AuditAttributeLabels::labelFor($invoice, 'total_amount'));

        $payment = new InvoicePayment();
        $this->assertSame('Amount', AuditAttributeLabels::labelFor($payment, 'amount'));
        $this->assertSame('Payment mode', AuditAttributeLabels::labelFor($payment, 'mode'));

        $lr = new LorryReceipt();
        $this->assertSame('LR number', AuditAttributeLabels::labelFor($lr, 'lr_number'));
        $this->assertSame('Delivery status', AuditAttributeLabels::labelFor($lr, 'delivery_status'));

        $booking = new FreightBooking();
        $this->assertSame('Transporter amount', AuditAttributeLabels::labelFor($booking, 'tr_amount'));
        $this->assertSame('Advance amount', AuditAttributeLabels::labelFor($booking, 'advance_amount'));

        $voucher = new VoucherPayment();
        $this->assertSame('Voucher number', AuditAttributeLabels::labelFor($voucher, 'voucher_number'));
        $this->assertSame('Payment status', AuditAttributeLabels::labelFor($voucher, 'payment_status'));
    }

    public function test_label_for_unmapped_attribute_and_default_model(): void
    {
        // Unmapped attribute on mapped model
        $invoice = new Invoice();
        $this->assertSame('Custom field', AuditAttributeLabels::labelFor($invoice, 'custom_field'));

        // Default unmapped model (hits default => [])
        $user = new User();
        $this->assertSame('Email verified at', AuditAttributeLabels::labelFor($user, 'email_verified_at'));
    }

    public function test_audit_logger_skips_when_unauthenticated(): void
    {
        Auth::logout();
        $logger = new AuditLogger();
        $invoice = new Invoice(['invoice_number' => 'INV-001', 'company_id' => 1]);

        $logger->logCreated($invoice);
        $logger->logUpdated($invoice);
        $logger->logDeleted($invoice);

        $this->assertSame(0, AuditLog::count());
    }

    private function createTestInvoice(Company $company, array $attrs = []): Invoice
    {
        $customer = \App\Models\Consignee::create([
            'company_id' => $company->id,
            'name' => 'Test Customer',
        ]);

        return Invoice::create(array_merge([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'invoice_type' => 'Forward',
            'invoice_number' => 'INV-AUDIT-' . uniqid(),
            'generated_date' => now()->toDateString(),
            'total_amount' => 5000.00,
            'payment_status' => 'Pending',
        ], $attrs));
    }

    public function test_audit_logger_creates_log_and_changes_for_created_and_deleted(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $company = Company::create([
            'name' => 'Audit Transport Co',
            'user_id' => $user->id,
        ]);

        $invoice = $this->createTestInvoice($company, [
            'invoice_number' => 'INV-AUDIT-101',
            'total_amount' => 5000.00,
        ]);

        $logger = new AuditLogger();
        $logger->logCreated($invoice);

        $createdLog = AuditLog::where('action', 'created')->first();
        $this->assertNotNull($createdLog);
        $this->assertSame($company->id, $createdLog->company_id);
        $this->assertSame($user->id, $createdLog->user_id);
        $this->assertSame('Invoice INV-AUDIT-101 was created.', $createdLog->summary);
        $this->assertGreaterThan(0, AuditLogChange::where('audit_log_id', $createdLog->id)->count());

        $logger->logDeleted($invoice);
        $deletedLog = AuditLog::where('action', 'deleted')->first();
        $this->assertNotNull($deletedLog);
        $this->assertSame('Invoice INV-AUDIT-101 was deleted.', $deletedLog->summary);
    }

    public function test_audit_logger_updates_with_single_and_multiple_and_equivalent_changes(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $company = Company::create([
            'name' => 'Audit Transport Co 2',
            'user_id' => $user->id,
        ]);

        $invoice = $this->createTestInvoice($company, [
            'invoice_number' => 'INV-AUDIT-202',
            'total_amount' => 5000.00,
            'payment_status' => 'Pending',
        ]);

        $logger = new AuditLogger();

        // 1. Untracked change only -> skips
        $invoice->syncChanges();
        $invoice->setRawAttributes(array_merge($invoice->getAttributes(), ['updated_at' => now()->addMinute()]));
        $invoice->syncChanges();
        $logger->logUpdated($invoice);
        $this->assertSame(0, AuditLog::where('action', 'updated')->count());

        // 2. Equivalent numeric values (e.g. 5000.00 vs 5000.000) -> skips
        $invoice->setRawAttributes(array_merge($invoice->getAttributes(), ['total_amount' => '5000.000000']));
        $invoice->syncChanges();
        $logger->logUpdated($invoice);
        $this->assertSame(0, AuditLog::where('action', 'updated')->count());

        // 3. Single tracked change -> single summary format
        $invoice->syncOriginal();
        $invoice->total_amount = 7500.00;
        $invoice->syncChanges();
        $logger->logUpdated($invoice);

        $singleLog = AuditLog::where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($singleLog);
        $this->assertStringContainsString('Invoice INV-AUDIT-202: Total amount changed from', $singleLog->summary);

        // 4. Multiple tracked changes -> multiple summary format
        $invoice->syncOriginal();
        $invoice->total_amount = 9000.00;
        $invoice->payment_status = 'Paid';
        $invoice->syncChanges();
        $logger->logUpdated($invoice);

        $multiLog = AuditLog::where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($multiLog);
        $this->assertStringContainsString('Invoice INV-AUDIT-202 was updated (2 fields).', $multiLog->summary);
    }

    public function test_audit_logger_format_value_edge_cases(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $company = Company::create([
            'name' => 'Audit Transport Co 3',
            'user_id' => $user->id,
        ]);

        $invoice = $this->createTestInvoice($company, [
            'invoice_number' => 'INV-AUDIT-303',
            'generated_date' => '2026-05-15',
            'lr_ids' => [1, 2, 3],
        ]);

        $invoice->syncOriginal();
        $invoice->generated_date = '2026-06-20';
        $invoice->lr_ids = [1, 2, 3, 4];
        $invoice->syncChanges();

        $logger = new AuditLogger();
        $logger->logUpdated($invoice);

        $log = AuditLog::where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($log);

        $changes = AuditLogChange::where('audit_log_id', $log->id)->get()->keyBy('attribute_name');
        $this->assertTrue($changes->has('generated_date'));
        $this->assertTrue($changes->has('lr_ids'));
        $this->assertSame('[1,2,3,4]', $changes['lr_ids']->new_value);
    }
}

