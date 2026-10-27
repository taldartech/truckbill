<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Models\Concerns\HasHybridAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountingVoucher extends Model implements Auditable
{
    use HasFactory, HasHybridAuditLog, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'voucher_type',
        'voucher_number',
        'voucher_date',
        'reference_number',
        'reference_date',
        'narration',
        'source_type',
        'source_id',
        'total_amount',
        'is_posted',
        'user_id',
        'challan_bsr_code',
        'challan_serial_no',
        'challan_tender_date',
        'challan_head',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'voucher_date' => 'date',
            'reference_date' => 'date',
            'challan_tender_date' => 'date',
            'total_amount' => 'decimal:2',
            'is_posted' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccountingVoucherItem::class);
    }

    public function reversalVouchers(): HasMany
    {
        return $this->hasMany(AccountingVoucher::class, 'source_id')->where('source_type', self::class);
    }

    public function originalVoucher(): BelongsTo
    {
        return $this->belongsTo(AccountingVoucher::class, 'source_id');
    }

    public function isReversed(): bool
    {
        return $this->reversalVouchers()->exists();
    }

    /**
     * Assert if the voucher debits equal credits within 0.01 tolerance.
     */
    public function isBalanced(): bool
    {
        $debits = (float) $this->items()->where('type', 'debit')->sum('amount');
        $credits = (float) $this->items()->where('type', 'credit')->sum('amount');

        return abs($debits - $credits) < 0.01;
    }

    /**
     * @return list<string>
     */
    public function auditTrackedAttributes(): array
    {
        return [
            'voucher_type',
            'voucher_number',
            'voucher_date',
            'reference_number',
            'narration',
            'total_amount',
            'is_posted',
        ];
    }

    public function auditSubjectLabel(): string
    {
        return (string) ($this->voucher_number ?? 'Voucher #'.$this->id);
    }

    public function auditSubjectTypeName(): string
    {
        return 'Accounting Voucher';
    }
}
