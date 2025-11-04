<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Customer extends Model
{
    use HasFactory, SoftDeletes;
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

    protected $dates = ['deleted_at'];
    
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
