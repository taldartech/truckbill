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
        'voucher_number',
        'formatted_voucher_number',
    ];

    public function freightBooking()
    {
        return $this->belongsTo(FreightBooking::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function consignee()
    {
        return $this->belongsTo(Consignee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function payments()
    {
        return $this->hasMany(VoucherPayment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            // Get the highest existing voucher number
            $lastVoucher = self::orderBy('voucher_number', 'desc')->first();
            $nextNumber = $lastVoucher ? $lastVoucher->voucher_number + 1 : 1;
            
            // Store the raw number
            $voucher->voucher_number = $nextNumber;
            
            // Format and store the formatted version
            $voucher->formatted_voucher_number = 'V-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    // Accessor to get formatted voucher number
    public function getVoucherNumberAttribute($value)
    {
        return $this->formatted_voucher_number ?? 'V-' . str_pad($value, 6, '0', STR_PAD_LEFT);
    }
}
