<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReminderLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'company_id',
        'reminder_key',
        'sent_on',
    ];

    protected function casts(): array
    {
        return [
            'sent_on' => 'date',
        ];
    }
}
