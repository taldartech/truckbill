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
    ];

    public function lr()
    {
        return $this->belongsTo(LorryReceipt::class, 'lr_id');
    }

    public function linkedFreightBooking()
    {
        return $this->belongsTo(FreightBooking::class, 'linked_freight_booking_id');
    }
}
