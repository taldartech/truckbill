<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreightBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'lr_id',
        'transporter_id',
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
    ];

    public function lr()
    {
        return $this->belongsTo(LorryReceipt::class, 'lr_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function consignee()
    {
        return $this->belongsTo(Consignee::class);
    }

    public function linkedFreightBooking()
    {
        return $this->belongsTo(FreightBooking::class, 'linked_freight_booking_id');
    }
}
