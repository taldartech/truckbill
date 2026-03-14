<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TruckType extends Model
{
    protected $fillable = ['name'];

    public function trucks()
    {
        return $this->hasMany(Truck::class, 'truck_type', 'name');
    }
}
