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
        'generated_date',
        'customer_id',
        'total_amount',
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

    public function memoMaps()
    {
        return $this->hasMany(InvoiceMemoMap::class);
    }
}
