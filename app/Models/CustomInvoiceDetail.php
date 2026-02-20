<?php

namespace App\Models;

use App\Models\Traits\TracksUser;
use Illuminate\Database\Eloquent\Model;

class CustomInvoiceDetail extends Model
{
    use TracksUser;

    protected $fillable = [
        'invoice_id',
        'lr_id',
        'portal_rate',
        'diff',
        'damage_qty',
        'damage_rate',
        'shortage_qty',
        'amount',
        'remark',
        'sort_order',
    ];

    public function invoice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function lorryReceipt(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LorryReceipt::class, 'lr_id')->withTrashed();
    }
}
