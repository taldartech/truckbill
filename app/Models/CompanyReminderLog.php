<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyReminderLog extends Model
{
    protected $fillable = [
        'company_id',
        'reminder_key',
        'sent_on',
    ];

    protected $casts = [
        'sent_on' => 'date',
    ];
}
