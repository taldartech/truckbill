<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Truck extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transporter_id',
        'company_id',
        'truck_number',
        'truck_type',
        'driver_id',
        'driver_name',
        'registration_date',
    ];

    protected $dates = ['deleted_at'];

    protected static function booted(): void
    {
        static::saving(function (Truck $truck): void {
            if ($truck->transporter_id) {
                $truck->company_id = Transporter::where('id', $truck->transporter_id)->value('company_id');
            }
        });
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
