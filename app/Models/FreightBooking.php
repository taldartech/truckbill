<?php
namespace App\Models;

use App\Contracts\Auditable;
use App\Models\Concerns\HasHybridAuditLog;
use App\Models\Traits\TracksUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightBooking extends Model implements Auditable
{
    use HasFactory, HasHybridAuditLog, TracksUser;

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

    public function voucherPayments()
    {
        return $this->hasMany(VoucherPayment::class, 'freight_booking_id');
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\VoucherPayment::class, 'freight_booking_id');
    }

    /**
     * Check if TDS amount can be changed for this freight booking
     *
     * TDS can be changed if:
     * 1. TDS is not submitted (submission date hasn't passed)
     * 2. OR complete payment is done to the transporter (balance_amount <= 0.01)
     *
     * TDS submission happens on the 3rd of next month (date from config, default: 3)
     *
     * @return bool
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

        $grDate = \Carbon\Carbon::parse($this->lr->gr_date);
        $grMonth = $grDate->month;
        $grYear = $grDate->year;

        $submissionDay = config('payments.tds_submission_day', 3);

        // Calculate submission date: 3rd (or from config) of next month
        $submissionDate = \Carbon\Carbon::create($grYear, $grMonth, 1)
            ->addMonth()
            ->day($submissionDay)
            ->startOfDay();

        // If current date is before submission date, TDS can be changed
        return \Carbon\Carbon::now()->lt($submissionDate);
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

        return $lrNo ? "FB for LR {$lrNo}" : 'Freight booking #'.$this->id;
    }

    public function auditSubjectTypeName(): string
    {
        return 'Freight booking';
    }
}
