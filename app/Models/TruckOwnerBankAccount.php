<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TruckOwnerBankAccount extends Model
{
    protected $fillable = [
        'transporter_id',
        'bank_name',
        'account_number',
        'ifsc_code',
        'beneficiary_name',
        'bulk_payment_id'
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }
}

