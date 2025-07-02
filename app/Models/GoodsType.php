<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsType extends Model
{
    protected $table = 'good_types';
    
    protected $fillable = [
        'name',
    ];
}
