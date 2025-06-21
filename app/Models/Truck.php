<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $fillable = [
        'transporter_id',
        'truck_number',
        'truck_type',
        'driver_name',
        'registration_date',
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    //
}
