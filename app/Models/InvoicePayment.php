<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Models\Concerns\HasHybridAuditLog;
use App\Models\Traits\TracksUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model implements Auditable
{
    use HasFactory, HasHybridAuditLog, TracksUser;

    protected $fillable = [
        'invoice_id',
        'mode',
        'amount',
        'remark',
        'payment_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return list<string>
     */
    public function auditTrackedAttributes(): array
    {
        return [
            'amount',
            'mode',
            'remark',
            'payment_date',
        ];
    }

    public function auditSubjectLabel(): string
    {
        $invoiceNo = $this->relationLoaded('invoice')
            ? $this->invoice?->invoice_number
            : null;
        if ($invoiceNo === null && $this->invoice_id) {
            $invoiceNo = Invoice::query()->whereKey($this->invoice_id)->value('invoice_number');
        }

        $suffix = $invoiceNo ? " (Invoice {$invoiceNo})" : '';

        return 'Payment #'.$this->id.$suffix;
    }

    public function auditSubjectTypeName(): string
    {
        return 'Invoice payment';
    }

    public function resolveAuditCompanyId(): ?int
    {
        if ($this->invoice_id) {
            return Invoice::query()->whereKey($this->invoice_id)->value('company_id');
        }

        return null;
    }
}
