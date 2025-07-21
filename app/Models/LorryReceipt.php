<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Truck;
use App\Models\Transporter;
use App\Models\Customer;
use App\Models\Consignee;
use App\Models\Company;

class LorryReceipt extends Model
{
    protected $fillable = [
        'truck_id',
        'transporter_id',
        'delivery_status',
        'consignor_id',
        'customer_id',
        'consignor_contact_number',
        'consignee_id',
        'source_city',
        'destination_city',
        'source_state',
        'destination_state',
        'weight',
        'no_of_units',
        'good_type',
        'fr_rate',
        'amount',
        'gr_date',
        'memo_id',
        'lane',
        'damage_amount',
        'shortage_amount',
        'lr_number',
        'company_id',
    ];

    protected $attributes = [
        'delivery_status' => 'Pending',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function syncStatusWithFreightBooking()
    {
        $freightBooking = \App\Models\FreightBooking::where('lr_id', $this->id)->first();
        
        if (!$freightBooking) {
            return false;
        }

        $deliveryStatus = 'Pending';
        if ($freightBooking->is_cancelled === '1') {
            $deliveryStatus = 'Cancelled';
        } elseif ($freightBooking->is_delivered === '1') {
            $deliveryStatus = 'Delivered';
        }

        $this->delivery_status = $deliveryStatus;
        $this->save();
        return true;
    }

    public function transporter() { return $this->belongsTo(Transporter::class); }
    public function consignor() { return $this->belongsTo(Consignee::class, 'consignor_id'); }
    public function consignee() { return $this->belongsTo(Consignee::class); }
    

    public function freightBooking()
    {
        return $this->belongsTo(FreightBooking::class, 'freight_booking_id');
    }
    public function customer() { return $this->belongsTo(Consignee::class, 'customer_id'); }
}
