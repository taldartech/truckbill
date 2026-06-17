<?php

namespace Tests\Feature\LR;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\User;
use App\Models\UserRole;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use App\Support\MpdfDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LorryReceiptDeleteCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    protected function seedRoles(): void
    {
        UserRole::create(['id' => 1, 'name' => 'master_admin']);
        UserRole::create(['id' => 2, 'name' => 'super_admin']);
        UserRole::create(['id' => 3, 'name' => 'admin']);
        UserRole::create(['id' => 4, 'name' => 'moderator']);
        UserRole::create(['id' => 5, 'name' => 'accountant']);
    }

    protected function makeAdmin(): User
    {
        $company = Company::create([
            'name' => 'Main Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        return User::factory()->create([
            'user_role_id' => 3,
            'company_id' => $company->id,
        ]);
    }

    public function test_deleting_lorry_receipt_soft_deletes_freight_booking_and_voucher_linked(): void
    {
        $admin = $this->makeAdmin();
        $paymentType = PaymentType::firstOrCreate(['name' => 'final']);
        $paymentMode = PaymentMode::firstOrCreate(['name' => 'cash']);

        $customer = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Customer A',
            'address' => 'Addr',
        ]);
        $consignor = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Consignor A',
            'address' => 'Addr',
        ]);
        $consignee = Consignee::create([
            'company_id' => $admin->company_id,
            'name' => 'Consignee A',
            'address' => 'Addr',
        ]);
        $transporter = Transporter::factory()->forCompany($admin->company_id)->create([
            'mobile_number' => '9999999999',
        ]);
        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-CASCADE-001',
            'gr_number' => 'GR-CASCADE-001',
            'invoice_number' => 'INV-CASCADE-001',
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'gr_date' => now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'Lane',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'delivery_status' => 'Pending',
        ]);

        $booking = FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 0,
            'balance_amount' => 1000,
            'tds_amount' => 0,
            'company_id' => $admin->company_id,
            'voucher_number' => 7001,
        ]);

        $payment = VoucherPayment::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => 'final',
            'payment_mode' => 'cash',
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'amount' => 500,
            'date' => now()->toDateString(),
            'payment_status' => 'paid',
            'voucher_number' => 'VP-7001',
        ]);

        $linked = VoucherLinked::create([
            'freight_booking_id' => $booking->id,
            'company_id' => $admin->company_id,
            'payment_type' => 'final',
            'payment_mode' => 'cash',
            'payment_type_id' => $paymentType->id,
            'payment_mode_id' => $paymentMode->id,
            'amount' => 500,
            'date' => now()->toDateString(),
            'payment_status' => 'paid',
            'voucher_ids' => json_encode([$payment->id]),
        ]);

        $payment->update(['linked_voucher_id' => $linked->id]);

        $this->actingAs($admin)
            ->delete("/lorry_receipts/{$lr->id}")
            ->assertRedirect(route('lorry_receipts.index'));

        $this->assertSoftDeleted('lorry_receipts', ['id' => $lr->id]);
        $this->assertSoftDeleted('freight_bookings', ['id' => $booking->id]);
        $this->assertSoftDeleted('voucher_payments', ['id' => $payment->id]);
        $this->assertSoftDeleted('voucher_linkeds', ['id' => $linked->id]);

        $this->assertDatabaseHas('freight_bookings', [
            'id' => $booking->id,
            'lr_id' => $lr->id,
        ]);
        $this->assertDatabaseHas('voucher_linkeds', [
            'id' => $linked->id,
            'freight_booking_id' => $booking->id,
        ]);
    }

    public function test_mpdf_document_uses_writable_storage_temp_dir(): void
    {
        $mpdf = MpdfDocument::make();

        $this->assertStringStartsWith(storage_path('app/mpdf'), $mpdf->tempDir);
        $this->assertDirectoryIsWritable($mpdf->tempDir);
    }
}
