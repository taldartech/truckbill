<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VoucherLinked extends Model
{
  use SoftDeletes;
  protected $fillable = [
    'date',
    'voucher_ids',
    'pump_id',
    'company_id',
    'payment_type',
    'payment_mode',
    'payment_type_id',
    'payment_mode_id',
    'amount',
    'reference_transaction_id',
    'remark',
    'payment_status',
    'freight_booking_id',
    'voucher_number',
  ];

  public function company()
  {
    return $this->belongsTo(Company::class);
  }

  public function freight_booking()
  {
    return $this->belongsTo(FreightBooking::class);
  }

  public function pump()
  {
    return $this->belongsTo(Pump::class);
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
}
