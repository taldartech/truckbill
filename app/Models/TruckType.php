<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TruckType extends Model
{
     use SoftDeletes;
    protected $fillable = ['name'];

    public function trucks()
    {
        return $this->hasMany(Truck::class, 'truck_type', 'name');
    }
}
