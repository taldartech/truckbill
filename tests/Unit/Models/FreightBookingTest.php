<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Consignee;
use App\Models\FreightBooking;
use App\Models\LorryReceipt;
use App\Models\Transporter;
use App\Models\Truck;
use App\Models\UserRole;
use App\Models\VoucherPayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreightBookingTest extends TestCase
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
    }

    protected function makeFreightBookingWithLr(?string $grDate = null, float $balanceAmount = 500): FreightBooking
    {
        $company = Company::create([
            'name' => 'Test Co',
            'user_id' => null,
            'gst_slab' => config('payments.gst_slabs')[0],
            'is_blocked' => false,
        ]);

        $transporter = Transporter::factory()->forCompany($company->id)->create([
            'tds_percentage' => 2,
        ]);

        $truck = Truck::create([
            'transporter_id' => $transporter->id,
            'truck_number' => 'GJ01AB1234',
            'truck_type' => 'Flatbed',
        ]);

        $lr = LorryReceipt::create([
            'company_id' => $company->id,
            'customer_id' => Consignee::create(['company_id' => $company->id, 'name' => 'C', 'address' => 'A'])->id,
            'consignor_id' => Consignee::create(['company_id' => $company->id, 'name' => 'S', 'address' => 'A'])->id,
            'consignee_id' => Consignee::create(['company_id' => $company->id, 'name' => 'E', 'address' => 'A'])->id,
            'truck_id' => $truck->id,
            'transporter_id' => $transporter->id,
            'reference_by' => $transporter->id,
            'lr_number' => 'LR-U-'.uniqid(),
            'gr_number' => 'GR-U-'.uniqid(),
            'invoice_number' => 'INV-U-'.uniqid(),
            'gr_date' => $grDate ?? now()->toDateString(),
            'source_city' => 'A',
            'source_state' => 'B',
            'destination_city' => 'C',
            'destination_state' => 'D',
            'lane' => 'L1',
            'good_type' => 'Steel',
            'weight' => 10,
            'fr_rate' => 100,
            'amount' => 1000,
            'damage_amount' => 0,
            'shortage_amount' => 0,
            'delivery_status' => 'Pending',
        ]);

        return FreightBooking::create([
            'lr_id' => $lr->id,
            'transporter_id' => $transporter->id,
            'tr_fr_rate' => 100,
            'tr_amount' => 1000,
            'advance_amount' => 500,
            'balance_amount' => $balanceAmount,
            'tds_amount' => 10,
            'company_id' => $company->id,
            'voucher_number' => 'VB-U-'.uniqid(),
            'transporter_tds_percentage' => 2,
        ]);
    }

    public function test_can_change_tds_returns_false_when_payment_complete(): void
    {
        $fb = $this->makeFreightBookingWithLr(grDate: '2025-01-15', balanceAmount: 0);

        $this->assertFalse($fb->canChangeTdsAmount());
    }

    public function test_can_change_tds_returns_false_when_balance_near_zero(): void
    {
        $fb = $this->makeFreightBookingWithLr(grDate: '2025-01-15', balanceAmount: 0.01);

        $this->assertFalse($fb->canChangeTdsAmount());
    }

    public function test_can_change_tds_returns_true_before_submission_date(): void
    {
        Carbon::setTestNow('2025-02-01 12:00:00'); // Before Feb 3

        $fb = $this->makeFreightBookingWithLr(grDate: '2025-01-15', balanceAmount: 500);

        $this->assertTrue($fb->canChangeTdsAmount());

        Carbon::setTestNow();
    }

    public function test_can_change_tds_returns_false_after_submission_date(): void
    {
        Carbon::setTestNow('2025-02-05 12:00:00'); // After Feb 3 (submission day)

        $fb = $this->makeFreightBookingWithLr(grDate: '2025-01-15', balanceAmount: 500);

        $this->assertFalse($fb->canChangeTdsAmount());

        Carbon::setTestNow();
    }

    public function test_can_change_tds_returns_true_on_submission_day(): void
    {
        Carbon::setTestNow('2025-02-02 23:59:59'); // Day before submission (Feb 3)

        $fb = $this->makeFreightBookingWithLr(grDate: '2025-01-15', balanceAmount: 500);

        $this->assertTrue($fb->canChangeTdsAmount());

        Carbon::setTestNow();
    }

    public function test_balance_as_on_date_excludes_payments_after_cutoff(): void
    {
        $fb = $this->makeFreightBookingWithLr(grDate: '2025-01-15', balanceAmount: 200);

        VoucherPayment::create([
            'freight_booking_id' => $fb->id,
            'company_id' => $fb->company_id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 300,
            'payment_status' => 'paid',
            'date' => '2025-01-20',
        ]);

        VoucherPayment::create([
            'freight_booking_id' => $fb->id,
            'company_id' => $fb->company_id,
            'payment_type' => 'cash',
            'payment_mode' => 'cash',
            'amount' => 200,
            'payment_status' => 'paid',
            'date' => '2025-02-10',
        ]);

        $fb->load('voucherPayments');

        $this->assertSame(690.0, $fb->balanceAsOnDate('2025-01-31'));
        $this->assertSame(490.0, $fb->balanceAsOnDate('2025-02-28'));

        $summary = $fb->dispatchPaymentSummary('2025-01-31');
        $this->assertSame(300.0, $summary['cash']);
        $this->assertSame(300.0, $summary['total_paid']);
        $this->assertSame(690.0, $summary['balance']);

        $laterSummary = $fb->dispatchPaymentSummary('2025-02-28');
        $this->assertSame(500.0, $laterSummary['cash']);
        $this->assertSame(500.0, $laterSummary['total_paid']);
        $this->assertSame(490.0, $laterSummary['balance']);
    }
}
