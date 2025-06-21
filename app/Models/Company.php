<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'gst_number',
        'pan_number',
        'gst_scheme',
        'gst_type',
        'address',
        'contact_person',
        'contact_number',
    ];
}
