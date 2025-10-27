<?php
namespace App\Models;

use App\Models\Traits\TracksUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightBooking extends Model
{
    use HasFactory, TracksUser;

    protected $fillable = [
        'lr_id',
        'transporter_id',
        'transporter_name',
        'transporter_tds_percentage',
        'truck_id',
        'tr_fr_rate',
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
}
