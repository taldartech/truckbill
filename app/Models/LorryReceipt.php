<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Truck;
use App\Models\Transporter;

class LorryReceipt extends Model
{
    protected $fillable = [
        'truck_id',
        'transporter_id',
        'eway_bill_number',
        'delivery_status',
        'consignor_id',
        'consignee_id',
        'consignee_contact_number',
        'source_city',
        'destination_city',
        'source_state',
        'destination_state',
        'weight',
        'no_of_units',
        'good_type',
        'fr_rate',
        'amount',
        'linked_lr_id',
        'gr_date',
        'gr_number',
        'memo_id',
    ];

    public function truck() { return $this->belongsTo(Truck::class); }
    public function transporter() { return $this->belongsTo(Transporter::class); }
    // Add consignor/consignee relations if needed later
}
