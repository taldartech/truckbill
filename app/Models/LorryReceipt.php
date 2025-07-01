<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Truck;
use App\Models\Transporter;
use App\Models\Customer;
use App\Models\Consignee;

class LorryReceipt extends Model
{
    protected $fillable = [
        'truck_id',
        // 'transporter_id',
        // 'eway_bill_number',
        'delivery_status',
        'consignor_id',
        'customer_id',
        'consignor_contact_number',
        'consignee_id',
        // 'consignee_contact_number',
        'source_city',
        'destination_city',
        'source_state',
        'destination_state',
        'weight',
        'no_of_units',
        'good_type',
        'fr_rate',
        'amount',
        // 'linked_lr_id',
        'gr_date',
        'gr_number',
        'memo_id',
        'lane',
        'damage_qty',
        'damage_rate',
        'shortage_qty',
        'shortage_rate',
        'service_tax',
        'swachh_bharat_cess',
        'lr_number',
    ];

    public function truck() { return $this->belongsTo(Truck::class); }
    public function transporter() { return $this->belongsTo(Transporter::class); }
    public function consignor() { return $this->belongsTo(Consignee::class, 'consignor_id'); }
    public function consignee() { return $this->belongsTo(Consignee::class); }
    public function customer() { return $this->belongsTo(Customer::class, 'customer_id'); }
}
