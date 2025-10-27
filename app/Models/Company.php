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
    'country_id',
    'state_id',
    'city_id',
    'pin_code',
    'template_jurisdictional_boundary',
    'template_header',
    'template_name',
    'template_address',
    'template_note',
  ];

  public function country()
  {
    return $this->belongsTo(Country::class);
  }

  public function state()
  {
    return $this->belongsTo(State::class);
  }

  public function city()
  {
    return $this->belongsTo(City::class);
  }
    public function branches()
{
    return $this->hasMany(Branch::class, 'company_id');
}
}
