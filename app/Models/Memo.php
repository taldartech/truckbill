<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;

class Memo extends Model
{
    use HasFactory;

    protected $fillable = [
        'memo_number'
    ];

    /**
     * Get all invoices associated with this memo
     */
    
}
