<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Truck extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transporter_id',
        'truck_number',
        'truck_type',
        'driver_id',
        'driver_name',
        'registration_date',
    ];

    protected $dates = ['deleted_at'];

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
