<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'manual_invoice_number',
        'company_id',
        'invoice_type',
        'generated_date',
        'customer_id',
        'total_amount',
        'payment_status',
        'memo_ids',
    ];
    protected $casts = [
        'memo_ids' => 'array',
      
    ];

    public function consignee()
{
    return $this->belongsTo(Consignee::class, 'customer_id');
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
