<?php

namespace App\Models;

use App\Models\Traits\TracksUser;
use Database\Factories\FreightBookingCustomAmountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreightBookingCustomAmount extends Model
{
    /** @use HasFactory<FreightBookingCustomAmountFactory> */
    use HasFactory, TracksUser;

    protected $fillable = [
        'freight_booking_id',
        'company_id',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function freightBooking(): BelongsTo
    {
        return $this->belongsTo(FreightBooking::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
