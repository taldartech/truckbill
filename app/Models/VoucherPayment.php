<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'payment_type',
        'payment_mode',
        'amount',
        'transaction_id',
        'remark',
        'payment_status',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
