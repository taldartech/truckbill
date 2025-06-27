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
        'city',
        'state',
        'country',
        'contact_number',
    ];

    protected $table = 'consignees';

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
