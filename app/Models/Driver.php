<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'mobile',
        'license_number',
        'address',
        'company_id',
    ];
    
    protected $dates = ['deleted_at'];
    
    // Add hasMany trucks if needed later

    //
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
