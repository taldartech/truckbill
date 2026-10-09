<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwayBill extends Model
{
    protected $fillable = [
        'freight_booking_id',
        'eway_bill_number',
        'eway_bill_expiry',
    ];

    protected $casts = [
        'eway_bill_expiry' => 'date',
    ];

    public function freightBooking(): BelongsTo
    {
        return $this->belongsTo(FreightBooking::class);
    }
}
