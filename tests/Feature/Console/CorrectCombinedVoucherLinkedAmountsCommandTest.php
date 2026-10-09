<?php

namespace Tests\Feature\Console;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\VoucherLinked;
use App\Models\VoucherPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectCombinedVoucherLinkedAmountsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('The command freight-bookings:correct-combined-voucher-amounts was removed in commit 4bf7d7f2 as obsolete.');
    }

    public function test_command_redistributes_voucher_payments_and_recalculates_freight_bookings(): void
    {
        [, $fbOne, $fbTwo] = $this->seedLinkedCombinedPaymentMismatch();

        $this->artisan('freight-bookings:correct-combined-voucher-amounts', [
            '--apply' => true,
            '--reset-negative-adjusted' => true,
        ])
            ->expectsConfirmation('This will update voucher_payments and freight_bookings. Continue?', 'yes')
            ->assertSuccessful()
            ->expectsOutputToContain('VoucherLinked #1');

        $fbOne->refresh();
        $fbTwo->refresh();

        $payments = VoucherPayment::query()->where('linked_voucher_id', 1)->whereNull('deleted_at')->get();

        $this->assertCount(2, $payments);
        $this->assertSame(9000.0, (float) $payments->firstWhere('freight_booking_id', $fbOne->id)?->amount);
        $this->assertSame(2250.0, (float) $payments->firstWhere('freight_booking_id', $fbTwo->id)?->amount);
        $this->assertSame(11250.0, (float) $payments->sum('amount'));

        $this->assertSame(9000.0, (float) $fbOne->advance_amount);
        $this->assertSame(0.0, (float) $fbOne->balance_amount);
        $this->assertSame(0.0, (float) $fbOne->negative_adjusted_amount);

        $this->assertSame(2250.0, (float) $fbTwo->advance_amount);
        $this->assertSame(0.0, (float) $fbTwo->balance_amount);
        $this->assertSame(0.0, (float) $fbTwo->negative_adjusted_amount);
    }

    public function test_command_dry_run_does_not_persist_changes(): void
    {
        [, $fbOne, $fbTwo] = $this->seedLinkedCombinedPaymentMismatch();

        $this->artisan('freight-bookings:correct-combined-voucher-amounts')
            ->assertSuccessful()
            ->expectsOutputToContain('DRY RUN complete');

        $fbOne->refresh();
        $fbTwo->refresh();

        $this->assertSame(2000.0, (float) VoucherPayment::query()->whereNull('deleted_at')->sum('amount'));
        $this->assertSame(2000.0, (float) $fbOne->advance_amount);
        $this->assertCount(1, VoucherPayment::query()->whereNull('deleted_at')->get());
    }

    /**
     * @return array{0: null, 1: FreightBooking, 2: FreightBooking}
     */
    private function seedLinkedCombinedPaymentMismatch(): array
    {
        $company = Company::create([
            'name' => 'Test Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'lr_credits' => 5,
            'is_blocked' => false,
            'plan_end_date' => now()->addYear(),
        ]);

        $customer = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Customer',
            'address' => 'Addr',
        ]);
        $consignor = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignor',
            'address' => 'Addr',
        ]);
        $consignee = Consignee::create([
            'company_id' => $company->id,
            'name' => 'Consignee',
            'address' => 'Addr',
        ]);

        $transporter = Transporter::factory()
            ->forCompany($company->id)
            ->create([
                'mobile_number' => '9999999999',
                'declaration_given' => false,
                'tds_percentage' => 0,
                'total_trucks' => 0,
                'advance_amount' => 0,
            ]);

        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        $linkedLrId = 9;

        $lrOne = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => '170',
            'gr_number' => '170',
            'invoice_number' => 'INV-170',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Delivered',
            'source_city' => 'Wajwana',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Dungarpur',
            'destination_state' => 'Rajasthan',
            'unloading_station' => 'DOOKA',
            'lane' => 'Road',
            'good_type' => 'cement',
            'weight' => 20,
            'no_of_units' => 400,
            'fr_rate' => 473,
            'amount' => 9460,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'owner',
            'linked_lr_id' => $linkedLrId,
        ]);

        $lrTwo = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'consignor_id' => $consignor->id,
            'consignee_id' => $consignee->id,
            'lr_number' => '171',
            'gr_number' => '171',
            'invoice_number' => 'INV-171',
            'memo_id' => null,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'gr_date' => now()->toDateString(),
            'delivery_status' => 'Delivered',
            'source_city' => 'Wajwana',
            'source_state' => 'Rajasthan',
            'destination_city' => 'Dungarpur',
            'destination_state' => 'Rajasthan',
            'unloading_station' => 'DOOKA',
            'lane' => 'Road',
            'good_type' => 'cement',
            'weight' => 5,
            'no_of_units' => 100,
            'fr_rate' => 473,
            'amount' => 2365,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'transporter_type' => 'owner',
            'linked_lr_id' => $linkedLrId,
        ]);

        $fbOne = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lrOne->id,
            'transporter_id' => $transporter->id,
            'transporter_name' => $transporter->name,
            'transporter_tds_percentage' => 0,
            'tr_fr_rate' => 450,
            'is_fixed_amount' => 0,
            'tr_amount' => 9000,
            'advance_amount' => 2000,
            'balance_amount' => 0,
            'negative_adjusted_amount' => -7000,
            'tds_amount' => 0,
            'good_type' => 'cement',
            'is_delivered' => 1,
            'is_cancelled' => 0,
            'linked_lr_id' => $linkedLrId,
            'voucher_number' => 2883,
            'pan_verified' => 1,
        ]);

        $fbTwo = FreightBooking::create([
            'company_id' => $company->id,
            'lr_id' => $lrTwo->id,
            'transporter_id' => $transporter->id,
            'transporter_name' => $transporter->name,
            'transporter_tds_percentage' => 0,
            'tr_fr_rate' => 450,
            'is_fixed_amount' => 0,
            'tr_amount' => 2250,
            'advance_amount' => 0,
            'balance_amount' => 0,
            'negative_adjusted_amount' => -2250,
            'tds_amount' => 0,
            'good_type' => 'cement',
            'is_delivered' => 1,
            'is_cancelled' => 0,
            'linked_lr_id' => $linkedLrId,
            'voucher_number' => 2882,
            'pan_verified' => 1,
        ]);

        $lrOne->update(['freight_booking_id' => $fbOne->id]);
        $lrTwo->update(['freight_booking_id' => $fbTwo->id]);

        $linkedVoucher = VoucherLinked::create([
            'date' => now()->toDateString(),
            'voucher_ids' => json_encode([]),
            'company_id' => $company->id,
            'payment_type' => 'advance',
            'payment_mode' => 'cash',
            'amount' => 11250,
            'remark' => 'Combined Payment: 11250. [LR IDs: 170, 171]',
            'payment_status' => 'in-progress',
            'freight_booking_id' => $fbOne->id,
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $fbOne->id,
            'voucher_number' => (string) $fbOne->voucher_number,
            'payment_type' => 'advance',
            'payment_mode' => 'cash',
            'amount' => 2000,
            'remark' => $linkedVoucher->remark,
            'payment_status' => 'in-progress',
            'company_id' => $company->id,
            'date' => now()->toDateString(),
            'linked_voucher_id' => $linkedVoucher->id,
        ]);

        return [null, $fbOne, $fbTwo];
    }
}
