<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'mobile',
        'email',
        'company_name',
        'business_type',
        'gst_number',
        'city',
        'status',
        'rejection_reason',
        'accepted_terms_at',
        'source',
    ];

    public const STATUS_LEAD_CREATED = 'LEAD_CREATED';
    public const STATUS_DEMO_SCHEDULED = 'DEMO_SCHEDULED';
    public const STATUS_DEMO_COMPLETED = 'DEMO_COMPLETED';
    public const STATUS_ACTIVATED = 'ACTIVATED';
    public const STATUS_REJECTED = 'REJECTED';

    protected function casts(): array
    {
        return [
            'accepted_terms_at' => 'datetime',
        ];
    }

    public function onboardingTokens(): HasMany
    {
        return $this->hasMany(OnboardingToken::class);
    }
}
