<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'pan_active',
        'address',
        'city_id',
        'state_id',
        'country_id',
        'mobile_number',
        'short_name',
        'beneficiary_id',
    ];

    protected $casts = [
        'pan_active' => 'boolean',
        ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }



    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    //
   // In Truck.php
public function driver()
{
    return $this->belongsTo(\App\Models\Driver::class);
}
}
