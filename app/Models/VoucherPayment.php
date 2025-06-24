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

    public function getPaymentStatusBadge()
    {
        switch ($this->payment_status) {
            case 'pending':
                return '<span class="badge bg-warning">Pending</span>';
            case 'in-progress':
                return '<span class="badge bg-info">In Progress</span>';
            case 'paid':
                return '<span class="badge bg-success">Paid</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }
}
