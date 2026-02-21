<?php
namespace App\Models;

use App\Models\Traits\TracksUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, TracksUser;

    protected $fillable = [
        'manual_invoice_number',
        'company_id',
        'invoice_type',
        'custom_invoice',
        'generated_date',
        'customer_id',
        'total_amount',
        'base_amount',
        'payment_status',
        'memo_ids',
        'net_amount',
        'sgst',
        'cgst',
        'igst',
        'lr_ids',
    ];
    protected $casts = [
        'memo_ids' => 'array',
        'lr_ids' => 'array',
    ];

    public function consignee()
{
    return $this->belongsTo(Consignee::class, 'customer_id')->withTrashed();
}

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function customInvoiceDetails(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomInvoiceDetail::class)->orderBy('sort_order');
    }

    /**
     * Get total paid amount
     */
    public function getPaidAmountAttribute()
    {
        // Use loaded relationship if available, otherwise query
        if ($this->relationLoaded('payments')) {
            return $this->payments->sum('amount');
        }
        return $this->payments()->sum('amount');
    }

    /**
     * Get remaining balance (net_amount is amount to collect)
     */
    public function getRemainingAmountAttribute()
    {
        $amountToCollect = $this->net_amount ?? $this->total_amount;

        return max(0, $amountToCollect - $this->paid_amount);
    }
}
