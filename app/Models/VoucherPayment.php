<?php
namespace App\Models;

use App\Contracts\Auditable;
use App\Models\Concerns\HasHybridAuditLog;
use App\Models\Traits\TracksUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Transporter;

class VoucherPayment extends Model implements Auditable
{
    use HasFactory, HasHybridAuditLog, SoftDeletes, TracksUser;

    protected $fillable = [
        'freight_booking_id',
        'voucher_number',
        'payment_type',
        'payment_mode',
        'payment_type_id',
        'payment_mode_id',
        'amount',
        'remark',
        'payment_status',
        'company_id',
        'pump_id',
        'bank_ledger_id',
        'reference_transaction_id',
        'date',
        'linked_voucher_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (VoucherPayment $payment): void {
            if (empty($payment->voucher_number)) {
                $payment->voucher_number = 'VP-' . str_pad((string) ((int) (static::withTrashed()->max('id') ?? 0) + 1), 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function bankLedger()
    {
        return $this->belongsTo(Ledger::class, 'bank_ledger_id');
    }

    public function freight_booking()
    {
        return $this->belongsTo(FreightBooking::class);
    }

    public function pump()
    {
        return $this->belongsTo(Pump::class);
    }

    public function voucher_linked()
    {
        return $this->belongsTo(VoucherLinked::class);
    }

    /**
     * Valid payment statuses
     */
    public static $validStatuses = ['pending', 'in progress', 'paid'];

    public function getPaymentStatusBadge()
    {
        switch ($this->payment_status) {
            case 'pending':
                return '<span class="badge bg-warning">Pending</span>';
            case 'in-progress':
                return '<span class="badge bg-info">In Progress</span>';
            case 'paid':
                return '<span class="badge bg-success">Paid</span>';
            default:
                return '<span class="badge bg-secondary">Unknown</span>';
        }
    }

    /**
     * Get the transporter associated with this voucher payment through freight booking
     */
    public function transporter()
    {
        return $this->belongsTo(Transporter::class, 'transporter_id');
    }

    /**
     * Get the transporter badge based on is_owner status
     */
    public function getTransporterBadge()
    {
        if (!$this->freight_booking || !$this->freight_booking->transporter) {
            return '<span class="text-muted">-</span>';
        }

        $transporter = $this->freight_booking->transporter;
        $name = $transporter->name;

        if ($transporter->is_owner) {
            return '<span class="badge bg-success"><i class="bi bi-truck"></i> ' . e($name) . '</span>';
        } else {
            return '<span class="badge bg-primary"><i class="bi bi-building"></i> ' . e($name) . '</span>';
        }
    }

    /**
     * @return list<string>
     */
    public function auditTrackedAttributes(): array
    {
        return [
            'amount',
            'payment_status',
            'payment_mode',
            'payment_type',
            'remark',
            'date',
            'voucher_number',
        ];
    }

    public function auditSubjectLabel(): string
    {
        return (string) ($this->voucher_number ?? 'Voucher payment #'.$this->id);
    }

    public function auditSubjectTypeName(): string
    {
        return 'Voucher payment';
    }
}
