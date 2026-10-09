<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCentre extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category',
        'name',
        'code',
        'entity_type',
        'entity_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function voucherItems(): HasMany
    {
        return $this->hasMany(AccountingVoucherItem::class);
    }
}
