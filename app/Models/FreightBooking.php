<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Models\Concerns\HasHybridAuditLog;
use App\Models\Traits\TracksUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class FreightBooking extends Model implements Auditable
{
    use HasFactory, HasHybridAuditLog, SoftDeletes, TracksUser;

    protected $fillable = [
        'lr_id',
        'transporter_id',
        'transporter_name',
        'transporter_tds_percentage',
        'truck_id',
        'tr_fr_rate',
        'is_fixed_amount',
        'tr_amount',
        'advance_amount',
        'balance_amount',
        'negative_adjusted_amount',
        'tds_amount',
        'shortage',
        'good_type',
        'detention',
        'deviation',
        'misc_expense',
        'damage_qty',
        'damage_rate',
        'damage_amount',
        'shortage_qty',
        'shortage_rate',
        'shortage_amount',
        'remark',
        'is_delivered',
        'is_cancelled',
        'linked_freight_booking_id',
        'pan_verified',
        'voucher_number',
        'company_id',
        'eway_bill_number',
        'eway_bill_expiry',
        'linked_lr_id',
        'voucher_printed',
        'voucher_last_printed_at',
    ];

    protected $casts = [
        'voucher_printed' => 'boolean',
        'voucher_last_printed_at' => 'datetime',
    ];

    public function lr()
    {
        return $this->belongsTo(LorryReceipt::class, 'lr_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function consignee()
    {
        return $this->belongsTo(Consignee::class);
    }

    public function linkedFreightBooking()
    {
        return $this->belongsTo(FreightBooking::class, 'linked_freight_booking_id');
    }

    public function sourceCity()
    {
        return $this->belongsTo(City::class, 'source_city_id');
    }

    public function destinationCity()
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class, 'transporter_id')->withTrashed();
    }

    public function voucherPayments(): HasMany
    {
        return $this->hasMany(VoucherPayment::class, 'freight_booking_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(VoucherPayment::class, 'freight_booking_id');
    }

    public function customAmounts(): HasMany
    {
        return $this->hasMany(FreightBookingCustomAmount::class);
    }

    public function customAmountsTotal(): float
    {
        if ($this->relationLoaded('customAmounts')) {
            return (float) $this->customAmounts->sum('amount');
        }

        return (float) $this->customAmounts()->sum('amount');
    }

    /**
     * Allocate the next hire voucher number. Call inside an open DB transaction.
     * Uses withTrashed() because the unique index still covers soft-deleted rows,
     * and locks the current max row to serialize concurrent creates.
     */
    public static function allocateNextVoucherNumber(): int
    {
        static::query()
            ->withTrashed()
            ->orderByDesc('voucher_number')
            ->lockForUpdate()
            ->first();

        return ((int) static::withTrashed()->max('voucher_number')) + 1;
    }

    public function effectivePayableAmount(): float
    {
        return (float) $this->tr_amount
            - ($this->tds_amount ?? 0)
            - ($this->damage_amount ?? 0)
            - ($this->shortage_amount ?? 0)
            + ($this->detention ?? 0)
            + ($this->deviation ?? 0)
            - ($this->misc_expense ?? 0)
            + $this->customAmountsTotal()
            + ($this->negative_adjusted_amount ?? 0);
    }

    public function balanceFromAdvance(?float $advanceAmount = null): float
    {
        $advance = $advanceAmount ?? (float) ($this->advance_amount ?? 0);

        return round(
            $this->effectivePayableAmount() - $advance,
            2
        );
    }

    public function isLinked(): bool
    {
        return ! empty($this->linked_lr_id);
    }

    public function scopeLinkedGroup($query, int $linkedLrId, int $companyId)
    {
        return $query->where('linked_lr_id', $linkedLrId)->where('company_id', $companyId);
    }

    /**
     * @return Collection<int, VoucherPayment>
     */
    public function voucherPaymentsUpToDate(string|Carbon $date): Collection
    {
        $end = $date instanceof Carbon ? $date->copy()->endOfDay() : Carbon::parse($date)->endOfDay();

        return $this->loadedVoucherPayments()->filter(
            fn (VoucherPayment $payment): bool => $this->paymentOccursOnOrBefore($payment, $end)
        );
    }

    public function balanceAsOnDate(string|Carbon $date): float
    {
        return $this->dispatchPaymentSummary($date instanceof Carbon ? $date->toDateString() : $date)['balance'];
    }

    /**
     * @return array{
     *     cash: float,
     *     bank: float,
     *     fuel: float,
     *     advance: float,
     *     total_paid: float,
     *     balance: float
     * }
     */
    public function dispatchPaymentSummary(?string $asOnDate = null): array
    {
        $payments = $asOnDate !== null
            ? $this->voucherPaymentsUpToDate($asOnDate)
            : $this->loadedVoucherPayments();

        $totalPaid = (float) $payments->sum('amount');

        return [
            'cash' => (float) $payments->where('payment_mode', 'cash')->sum('amount'),
            'bank' => (float) $payments->where('payment_mode', 'bank transfer')->sum('amount'),
            'fuel' => (float) $payments->where('payment_mode', 'fuel(paid to pump)')->sum('amount'),
            'advance' => (float) $payments->where('payment_mode', 'adjust in advance')->sum('amount'),
            'total_paid' => $totalPaid,
            'balance' => $asOnDate !== null
                ? $this->effectivePayableAmount() - $totalPaid
                : (float) ($this->balance_amount ?? 0),
        ];
    }

    /**
     * @return Collection<int, VoucherPayment>
     */
    protected function loadedVoucherPayments(): Collection
    {
        if ($this->relationLoaded('voucherPayments')) {
            return $this->voucherPayments;
        }

        if ($this->relationLoaded('payments')) {
            return $this->payments;
        }

        return $this->voucherPayments()->get();
    }

    protected function paymentOccursOnOrBefore(VoucherPayment $payment, Carbon $end): bool
    {
        $paymentDate = $payment->date !== null
            ? Carbon::parse($payment->date)
            : ($payment->created_at?->copy());

        if ($paymentDate === null) {
            return false;
        }

        return $paymentDate->lte($end);
    }

    public function voucherLinkeds(): HasMany
    {
        return $this->hasMany(VoucherLinked::class, 'freight_booking_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (FreightBooking $freightBooking): void {
            $freightBooking->customAmounts()->each(function (FreightBookingCustomAmount $customAmount): void {
                $customAmount->delete();
            });

            $freightBooking->voucherPayments()->each(function (VoucherPayment $payment): void {
                $payment->delete();
            });

            $freightBooking->voucherLinkeds()->each(function (VoucherLinked $linked): void {
                $linked->delete();
            });
        });
    }

    /**
     * Check if TDS amount can be changed for this freight booking
     *
     * TDS can be changed if:
     * 1. TDS is not submitted (submission date hasn't passed)
     * 2. OR complete payment is done to the transporter (balance_amount <= 0.01)
     *
     * TDS submission happens on the 3rd of next month (date from config, default: 3)
     */
    public function canChangeTdsAmount(): bool
    {
        // If payment is completed (balance_amount <= 0.01), TDS can not be changed
        if (($this->balance_amount ?? 0) <= 0.01) {
            return false;
        }

        // Check if TDS submission date has passed
        if (! $this->lr || ! $this->lr->gr_date) {
            // If no GR date, allow change (edge case)
            return true;
        }

        $grDate = Carbon::parse($this->lr->gr_date);
        $grMonth = $grDate->month;
        $grYear = $grDate->year;

        $submissionDay = config('payments.tds_submission_day', 3);

        // Calculate submission date: 3rd (or from config) of next month
        $submissionDate = Carbon::create($grYear, $grMonth, 1)
            ->addMonth()
            ->day($submissionDay)
            ->startOfDay();

        // If current date is before submission date, TDS can be changed
        return Carbon::now()->lt($submissionDate);
    }

    /**
     * @return list<string>
     */
    public function auditTrackedAttributes(): array
    {
        return [
            'tr_amount',
            'advance_amount',
            'balance_amount',
            'tds_amount',
            'is_delivered',
            'is_cancelled',
            'voucher_number',
            'remark',
            'eway_bill_number',
            'eway_bill_expiry',
            'tr_fr_rate',
            'shortage_amount',
            'damage_amount',
        ];
    }

    public function auditSubjectLabel(): string
    {
        $lr = $this->relationLoaded('lr') ? $this->lr : $this->lr()->first();
        $lrNo = $lr?->lr_number;

        return $lrNo ? "FB for LR {$lrNo}" : 'Good Hires (Lorry Hire) #'.$this->id;
    }

    public function auditSubjectTypeName(): string
    {
        return 'Good Hires (Lorry Hire)';
    }
}
