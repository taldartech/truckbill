<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transporter extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'gst_number',
        'gst_type',
        'pan_number',
        'is_owner',
        'declaration_given',
        'total_trucks',
        'advance_amount',
        'registered_truck_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    //
}
