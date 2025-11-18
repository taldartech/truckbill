<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentLedger extends Model
{
    protected $fillable = [
        'company_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'payment_type',
        'truck_count',
        'lr_credits',
        'addon_truck_count',
        'addon_lr_credits',
        'tenure',
        'truck_cost',
        'lr_credits_cost',
        'annual_maintenance_cost',
        'subtotal',
        'promo_code_id',
        'promo_discount',
        'gst_amount',
        'total_amount',
        'status',
        'razorpay_response',
    ];

    protected $casts = [
        'truck_cost' => 'decimal:2',
        'lr_credits_cost' => 'decimal:2',
        'annual_maintenance_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'promo_discount' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'razorpay_response' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function upcomingPlan(): HasOne
    {
        return $this->hasOne(UpcomingPlan::class, 'payment_ledger_id');
    }
}
