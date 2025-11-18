<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodType extends Model
{
    protected $fillable = [
        'user_id',
        'name'
    ];
}
