<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodType extends Model
{
      use SoftDeletes;
    protected $fillable = [
        'user_id',
        'name',
        'deleted_by',
        'deleted_remark',
    ];
}
