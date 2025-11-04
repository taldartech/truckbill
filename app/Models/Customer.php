<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'name',
        'gst_number',
        'pan_number',
        'address',
        'contact_person',
        'contact_number',
        'country_id',
        'state_id',
        'city_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }


    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
