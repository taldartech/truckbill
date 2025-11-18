<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
  use SoftDeletes;
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
    'user_id',
    'is_blocked',
    'company_type',
    'company_logo',
    'gst_slab',
    'plan_end_date',
    'lr_credits',
    'truck_credits',
  ];

  protected $dates = ['deleted_at'];

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
public function user()
{
    return $this->belongsTo(User::class);
}
}
