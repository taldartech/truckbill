<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\Invoice;
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
}
