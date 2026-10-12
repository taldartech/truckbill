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
        'company_id',
        'bank_ledger_id',
        'remark',
    ];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function bankLedger()
    {
        return $this->belongsTo(Ledger::class, 'bank_ledger_id');
    }
}
