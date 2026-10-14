<?php

namespace Tests\Feature\LR;

use App\Models\Company;
use App\Models\CompanySubscription;
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
            'lr_credits' => 5,
            'plan_end_date' => now()->addYear(),
        ]);

        CompanySubscription::create([
            'company_id' => $company->id,
            'status' => 'active',
            'starts_on' => now()->subMonth(),
            'ends_on' => now()->addYear(),
            'included_branches' => 2,
            'users_per_branch' => 3,
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
            ->delete("/lorry_receipts/{$lr->id}", ['delete_remark' => 'Entered by mistake'])
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

    public function test_cannot_delete_lorry_receipt_when_status_is_not_pending(): void
    {
        $admin = $this->makeAdmin();
        $lr = $this->createLorryReceipt($admin, ['delivery_status' => 'In-Progress']);

        $this->actingAs($admin)
            ->from('/lorry_receipts')
            ->delete("/lorry_receipts/{$lr->id}", ['delete_remark' => 'Entered by mistake'])
            ->assertRedirect('/lorry_receipts')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('lorry_receipts', [
            'id' => $lr->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleting_one_linked_lorry_receipt_deletes_entire_group(): void
    {
        $admin = $this->makeAdmin();
        $linkedId = 42;

        $lr1 = $this->createLorryReceipt($admin, [
            'lr_number' => 'LR-LINK-DEL-001',
            'gr_number' => 'GR-LINK-DEL-001',
            'invoice_number' => 'INV-LINK-DEL-001',
            'linked_lr_id' => $linkedId,
            'delivery_status' => 'Pending',
        ]);
        $lr2 = $this->createLorryReceipt($admin, [
            'lr_number' => 'LR-LINK-DEL-002',
            'gr_number' => 'GR-LINK-DEL-002',
            'invoice_number' => 'INV-LINK-DEL-002',
            'linked_lr_id' => $linkedId,
            'delivery_status' => 'Pending',
        ]);

        $this->actingAs($admin)
            ->delete("/lorry_receipts/{$lr1->id}", ['delete_remark' => 'Entered by mistake'])
            ->assertRedirect(route('lorry_receipts.index'));

        $this->assertSoftDeleted('lorry_receipts', ['id' => $lr1->id]);
        $this->assertSoftDeleted('lorry_receipts', ['id' => $lr2->id]);
    }

    public function test_cannot_delete_linked_group_when_any_member_is_not_pending(): void
    {
        $admin = $this->makeAdmin();
        $linkedId = 43;

        $lr1 = $this->createLorryReceipt($admin, [
            'lr_number' => 'LR-LINK-BLOCK-001',
            'gr_number' => 'GR-LINK-BLOCK-001',
            'invoice_number' => 'INV-LINK-BLOCK-001',
            'linked_lr_id' => $linkedId,
            'delivery_status' => 'Pending',
        ]);
        $lr2 = $this->createLorryReceipt($admin, [
            'lr_number' => 'LR-LINK-BLOCK-002',
            'gr_number' => 'GR-LINK-BLOCK-002',
            'invoice_number' => 'INV-LINK-BLOCK-002',
            'linked_lr_id' => $linkedId,
            'delivery_status' => 'Delivered',
        ]);

        $this->actingAs($admin)
            ->from('/lorry_receipts')
            ->delete("/lorry_receipts/{$lr1->id}", ['delete_remark' => 'Entered by mistake'])
            ->assertRedirect('/lorry_receipts')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('lorry_receipts', ['id' => $lr2->id, 'deleted_at' => null]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createLorryReceipt(User $admin, array $overrides = []): LorryReceipt
    {
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
            'mobile_number' => fake()->unique()->numerify('##########'),
        ]);
        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => fake()->unique()->bothify('GJ##??####'),
            'truck_type' => 'Flatbed',
        ]);

        return LorryReceipt::create(array_merge([
            'company_id' => $admin->company_id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => 'LR-'.fake()->unique()->numerify('######'),
            'gr_number' => 'GR-'.fake()->unique()->numerify('######'),
            'invoice_number' => 'INV-'.fake()->unique()->numerify('######'),
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
        ], $overrides));
    }

    public function test_mpdf_document_uses_writable_storage_temp_dir(): void
    {
        $mpdf = MpdfDocument::make();

        $this->assertStringStartsWith(storage_path('app/mpdf'), $mpdf->tempDir);
        $this->assertDirectoryIsWritable($mpdf->tempDir);
    }
}
