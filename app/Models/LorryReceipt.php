<?php

namespace App\Models;

use App\Contracts\Auditable;
use App\Models\Concerns\HasHybridAuditLog;
use App\Models\Traits\TracksUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LorryReceipt extends Model implements Auditable
{
    use HasHybridAuditLog, SoftDeletes, TracksUser;

    protected $fillable = [
        'truck_id',
        'transporter_id',
        'reference_by',
        'delivery_status',
        'consignor_id',
        'customer_id',
        'consignor_contact_number',
        'consignee_id',
        'consignee_contact_number',
        'source_city',
        'destination_city',
        'source_state',
        'destination_state',
        'weight',
        'unloading_weight',
        'no_of_units',
        'good_type',
        'goods_value',
        'fr_rate',
        'is_fixed_amount',
        'amount',
        'actual_amount',
        'gr_date',
        'memo_id',
        'memo_created_at',
        'lane',
        'damage_amount',
        'shortage_amount',
        'lr_number',
        'company_id',
        'transporter_type',
        'linked_lr_id',
        'invoice_number',
        'loading_station',
        'unloading_station',
        'gr_number',
        'deleted_by',
        'deleted_remark',
    ];

    protected $attributes = [
        'delivery_status' => 'Pending',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function truck()
    {
        return $this->belongsTo(Truck::class)->withTrashed();
    }

    public function syncStatusWithFreightBooking()
    {
        $freightBooking = FreightBooking::where('lr_id', $this->id)->first();

        if (! $freightBooking) {
            return false;
        }

        $deliveryStatus = 'Pending';
        if ($freightBooking->is_cancelled === '1') {
            $deliveryStatus = 'Cancelled';
        } elseif ($freightBooking->is_delivered === '1') {
            $deliveryStatus = 'Delivered';
        }

        $this->delivery_status = $deliveryStatus;
        $this->save();

        return true;
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class)->withTrashed();
    }

    public function consignor()
    {
        return $this->belongsTo(Consignee::class, 'consignor_id')->withTrashed();
    }

    public function consignee()
    {
        return $this->belongsTo(Consignee::class)->withTrashed();
    }

    public function freightBookings()
    {
        return $this->hasMany(FreightBooking::class, 'lr_id');
    }

    public function freightBooking(): BelongsTo
    {
        return $this->belongsTo(FreightBooking::class, 'freight_booking_id');
    }

    protected static function booted()
    {
        static::deleting(function (LorryReceipt $lorryReceipt): void {
            $lorryReceipt->freightBookings()->each(function (FreightBooking $freightBooking): void {
                $freightBooking->delete();
            });
        });
    }

    public function customer()
    {
        return $this->belongsTo(Consignee::class, 'customer_id')->withTrashed();
    }

    public function reference()
    {
        return $this->belongsTo(Transporter::class, 'reference_by')->withTrashed();
    }

    /**
     * @return list<string>
     */
    public function auditTrackedAttributes(): array
    {
        return [
            'lr_number',
            'delivery_status',
            'amount',
            'actual_amount',
            'memo_id',
            'weight',
            'unloading_weight',
            'consignee_id',
            'consignor_id',
            'transporter_id',
            'gr_date',
            'goods_value',
            'fr_rate',
        ];
    }

    public function auditSubjectLabel(): string
    {
        return (string) ($this->lr_number ?? 'LR #'.$this->id);
    }

    public function auditSubjectTypeName(): string
    {
        return 'Good Receipts (Bilty)';
    }
}
