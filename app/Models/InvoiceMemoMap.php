<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceMemoMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'memo_id',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function memo()
    {
        return $this->belongsTo(Memo::class);
    }
}
