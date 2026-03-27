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

    public function test_audit_log_index_is_accessible_to_admin(): void
    {
        $this->seedRoles();
        $user = $this->makeAdminUser();

        $this->actingAs($user);

        $response = $this->get(route('audit-logs.index'));

        $response->assertOk();
        $response->assertSee('Audit log', false);
    }
}
