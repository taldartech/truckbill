<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLogChange extends Model
{
    protected $fillable = [
        'audit_log_id',
        'attribute_name',
        'field_label',
        'old_value',
        'new_value',
        'sort_order',
    ];

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class);
    }
}
