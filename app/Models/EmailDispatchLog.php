<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDispatchLog extends Model
{
    protected $fillable = [
        'email_template_id',
        'company_id',
        'event_key',
        'recipient_email',
        'subject_snapshot',
        'body_snapshot',
        'status',
        'triggered_by_type',
        'triggered_by_id',
        'source',
        'error_message',
        'queued_at',
        'sent_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }
}
