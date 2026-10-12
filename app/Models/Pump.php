<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pump extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'pump_name',
        'contact_name',
        'contact_number',
        'remark',
        'deleted_by',
        'deleted_remark',
    ];

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->pump_name ?? '');
    }
}

