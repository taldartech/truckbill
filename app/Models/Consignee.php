<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consignee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'city_id',
        'state_id',
        'country_id',
        'contact_number',
    ];

    protected $casts = [
        'city_name' => 'string',
        'state_name' => 'string',
        'country_name' => 'string',
    ];

    protected $table = 'consignees';

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
}
