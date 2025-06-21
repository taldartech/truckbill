<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'freight_booking_id',
        'truck_id',
        'transporter_id',
        'pan_verified',
        'status',
    ];

    public function freightBooking()
    {
        return $this->belongsTo(FreightBooking::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function payments()
    {
        return $this->hasMany(VoucherPayment::class);
    }
}
