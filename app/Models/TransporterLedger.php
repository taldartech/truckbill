<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransporterLedger extends Model
{
    protected $table = 'transporter_ledgers';
    
    protected $fillable = [
        'transporter_id',
        'amount',
        'transaction_type',
        'remark'
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2);
    }
}
