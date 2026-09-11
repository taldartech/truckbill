<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'code',
        'primary_type',
        'is_builtin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_builtin' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AccountGroup::class, 'parent_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }
}
