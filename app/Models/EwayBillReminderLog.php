<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EwayBillReminderLog extends Model
{
    protected $fillable = [
        'freight_booking_id',
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
