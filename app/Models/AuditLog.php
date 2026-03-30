<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'subject_type_name',
        'summary',
        'occurred_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Per-field old/new values (normalized table, not JSON).
     */
    public function fieldChanges(): HasMany
    {
        return $this->hasMany(AuditLogChange::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * User-facing module label (aligned with nav; overrides legacy stored subject_type_name).
     */
    public function subjectTypeDisplayName(): string
    {
        return match ($this->subject_type) {
            LorryReceipt::class => 'Good Receipts (Bilty)',
            FreightBooking::class => 'Good Hires (Lorry Hire)',
            default => $this->subject_type_name,
        };
    }

    /**
     * Summary text with legacy labels rewritten for display.
     */
    public function displaySummary(): string
    {
        $s = $this->summary;
        $s = str_replace('Freight booking', 'Good Hires (Lorry Hire)', $s);
        $s = str_replace('Lorry receipt', 'Good Receipts (Bilty)', $s);

        return $s;
    }
}
