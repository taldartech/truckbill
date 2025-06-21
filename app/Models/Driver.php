<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'license_number',
        'address',
    ];
    // Add hasMany trucks if needed later

    //
}
