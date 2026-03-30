<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserRole;
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

    public function test_audit_log_multi_company_requires_selection(): void
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
        $response->assertSee('Select a company');

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
        $response->assertSee('Enter a company first', false);
        $response->assertSessionMissing('super_admin_company_id');
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
