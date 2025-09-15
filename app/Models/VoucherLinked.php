<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoucherLinked extends Model
{
  use SoftDeletes;
  protected $fillable = [
    'date',
    'voucher_ids',
    'pump_id',
    'company_id',
    'payment_type',
    'payment_mode',
    'payment_type_id',
    'payment_mode_id',
    'amount',
    'reference_transaction_id',
    'remark',
    'payment_status',
    'freight_booking_id',
    'voucher_number',
  ];


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function freight_booking()
    {
        return $this->belongsTo(FreightBooking::class);
    }

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }
}
