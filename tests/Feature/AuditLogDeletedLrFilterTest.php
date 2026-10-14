<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogDeletedLrFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
    }

    public function test_lr_number_filter_includes_deleted_lr_and_related_audit_entries(): void
    {
        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Deleted LR Audit Co',
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

        $this->actingAs($admin);

        $lr = LorryReceipt::create([
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

        $freightBooking = FreightBooking::create([
            'lr_id' => $lr->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'advance_amount' => 0,
            'balance_amount' => 10000,
            'company_id' => $company->id,
            'voucher_number' => 369,
            'pan_verified' => true,
        ]);

        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'Cash']);

        $voucherPayment = VoucherPayment::create([
            'freight_booking_id' => $freightBooking->id,
            'company_id' => $company->id,
            'voucher_number' => 'VP-369',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'amount' => 2000,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        $lr->delete();

        $this->assertSoftDeleted('lorry_receipts', ['id' => $lr->id]);
        $this->assertSoftDeleted('freight_bookings', ['id' => $freightBooking->id]);
        $this->assertSoftDeleted('voucher_payments', ['id' => $voucherPayment->id]);

        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => LorryReceipt::class,
            'subject_id' => $lr->id,
            'action' => 'deleted',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => FreightBooking::class,
            'subject_id' => $freightBooking->id,
            'action' => 'deleted',
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('audit-logs.index', [
            'company_id' => $company->id,
            'lr_number' => '369',
        ]));

        $response->assertOk();
        $response->assertSee('Good Receipts (Bilty) 369 was deleted.', false);
        $response->assertSee('Good Hires (Lorry Hire) FB for LR 369 was deleted.', false);
    }

    public function test_lr_number_filter_includes_soft_deleted_voucher_payment_audit_entries(): void
    {
        $superAdmin = User::factory()->create([
            'user_role_id' => 2,
            'company_id' => null,
        ]);

        $company = Company::create([
            'name' => 'Deleted VP Audit Co',
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

        $this->actingAs($admin);

        $lr = LorryReceipt::create([
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

        $freightBooking = FreightBooking::create([
            'lr_id' => $lr->id,
            'tr_fr_rate' => 1000,
            'tr_amount' => 10000,
            'advance_amount' => 0,
            'balance_amount' => 10000,
            'company_id' => $company->id,
            'voucher_number' => 369,
            'pan_verified' => true,
        ]);

        $paymentType = PaymentType::firstOrCreate(['name' => 'Cash']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'Cash']);

        $voucherPayment = VoucherPayment::create([
            'freight_booking_id' => $freightBooking->id,
            'company_id' => $company->id,
            'voucher_number' => 'VP-369',
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'amount' => 2000,
            'payment_status' => 'pending',
            'date' => now()->toDateString(),
        ]);

        $voucherPayment->delete();

        $this->assertSoftDeleted('voucher_payments', ['id' => $voucherPayment->id]);

        $this->assertDatabaseHas('audit_logs', [
            'subject_type' => VoucherPayment::class,
            'subject_id' => $voucherPayment->id,
            'action' => 'deleted',
        ]);

        $this->actingAs($superAdmin);

        $response = $this->get(route('audit-logs.index', [
            'company_id' => $company->id,
            'lr_number' => '369',
        ]));

        $response->assertOk();
        $response->assertSee('Voucher payment VP-369 was deleted.', false);
    }
}
