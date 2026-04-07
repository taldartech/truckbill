<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'company_id',
        'subject',
        'body_html',
        'allowed_variables',
        'allowed_roles',
        'send_to_transporter',
        'send_to_customer',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allowed_variables' => 'array',
            'allowed_roles' => 'array',
            'send_to_transporter' => 'boolean',
            'send_to_customer' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
