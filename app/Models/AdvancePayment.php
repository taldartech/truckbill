<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvancePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transporter_id',
        'advance_amount',
        'payment_date',
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }
}
