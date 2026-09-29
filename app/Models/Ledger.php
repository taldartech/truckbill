<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ledger extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_group_id',
        'name',
        'code',
        'opening_balance',
        'opening_balance_type',
        'opening_balance_date',
        'opening_balance_reference',
        'current_balance',
        'entity_type',
        'entity_id',
        'gstin',
        'pan_number',
        'tds_section',
        'tds_rate',
        'is_builtin',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'opening_balance_date' => 'date',
            'tds_rate' => 'decimal:2',
            'is_builtin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function voucherItems(): HasMany
    {
        return $this->hasMany(AccountingVoucherItem::class);
    }

    /**
     * Calculate running balance up to an optional date.
     */
    public function getCalculatedBalance(?string $toDate = null): array
    {
        $query = $this->voucherItems()
            ->whereHas('accountingVoucher', function ($vQuery) use ($toDate): void {
                $vQuery->where('is_posted', true);
                if ($toDate !== null) {
                    $vQuery->whereDate('voucher_date', '<=', $toDate);
                }
            });

        $debitSum = (clone $query)->where('type', 'debit')->sum('amount');
        $creditSum = (clone $query)->where('type', 'credit')->sum('amount');

        $openingDebit = $this->opening_balance_type === 'debit' ? (float) $this->opening_balance : 0.0;
        $openingCredit = $this->opening_balance_type === 'credit' ? (float) $this->opening_balance : 0.0;

        $totalDebit = $openingDebit + (float) $debitSum;
        $totalCredit = $openingCredit + (float) $creditSum;

        if ($totalDebit >= $totalCredit) {
            return [
                'amount' => round($totalDebit - $totalCredit, 2),
                'type' => 'debit',
                'formatted' => '₹'.number_format($totalDebit - $totalCredit, 2).' Dr',
            ];
        }

        return [
            'amount' => round($totalCredit - $totalDebit, 2),
            'type' => 'credit',
            'formatted' => '₹'.number_format($totalCredit - $totalDebit, 2).' Cr',
        ];
    }

    /**
     * Get human-readable group head display with full hierarchy and primary type.
     */
    public function getGroupHeadDisplay(): string
    {
        if (! $this->accountGroup) {
            return 'General Ledger';
        }

        $type = ucfirst($this->accountGroup->primary_type ?? 'Ledger');

        return $this->accountGroup->getHierarchyPath().' ('.$type.')';
    }
}
